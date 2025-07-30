<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use App\Models\PosOrderWorkflowHistory;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class N8nWebhookController extends Controller
{
    /**
     * Nhận workflow history từ n8n
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Tạo workflow history từ n8n
     * POST /api/n8n/workflow-history
     * 
     * Body:
     * {
     *   "pos_order_id": 123,
     *   "workflow_id": "workflow_abc123",
     *   "workflow_status_id": 5,
     *   "executed_at": "2025-07-30T10:30:00Z" // optional
     * }
     */
    public function createWorkflowHistory(Request $request): JsonResponse
    {
        try {
            // Log request để debug
            Log::info('N8n Webhook received', [
                'data' => $request->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Validate request data
            $validated = $request->validate([
                'pos_order_id' => 'required|integer|exists:pos_orders,id',
                'workflow_id' => 'required|string|max:55',
                'workflow_status_id' => 'required|integer',
                'executed_at' => 'sometimes|date', // Optional, default to now
            ]);

            // Kiểm tra pos_order tồn tại
            $posOrder = PosOrder::find($validated['pos_order_id']);
            if (!$posOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại',
                    'error_code' => 'ORDER_NOT_FOUND'
                ], 404);
            }

            // Kiểm tra workflow tồn tại và active (tùy chọn)
            $workflow = Workflow::where('workflow_id', $validated['workflow_id'])->first();
            if ($workflow && $workflow->workflow_status !== 'active') {
                Log::warning('Workflow không active', [
                    'workflow_id' => $validated['workflow_id'],
                    'status' => $workflow->workflow_status
                ]);
            }

            // Tạo workflow history
            $workflowHistory = PosOrderWorkflowHistory::create([
                'pos_order_id' => $validated['pos_order_id'],
                'workflow_id' => $validated['workflow_id'],
                'workflow_status_id' => $validated['workflow_status_id'],
                'executed_at' => $validated['executed_at'] ?? now(),
            ]);

            // Log success
            Log::info('Workflow history created successfully', [
                'id' => $workflowHistory->id,
                'pos_order_id' => $validated['pos_order_id'],
                'workflow_id' => $validated['workflow_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo lịch sử workflow thành công',
                'data' => [
                    'id' => $workflowHistory->id,
                    'pos_order_id' => $workflowHistory->pos_order_id,
                    'workflow_id' => $workflowHistory->workflow_id,
                    'workflow_status_id' => $workflowHistory->workflow_status_id,
                    'executed_at' => $workflowHistory->executed_at->toISOString(),
                    'created_at' => $workflowHistory->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('N8n webhook validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 422);

        } catch (\Exception $e) {
            Log::error('N8n webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo lịch sử workflow',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Batch tạo nhiều workflow history (nếu cần)
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Tạo nhiều workflow history một lúc (batch)
     * POST /api/n8n/workflow-history/batch
     * 
     * Body:
     * {
     *   "histories": [
     *     {
     *       "pos_order_id": 123,
     *       "workflow_id": "workflow_abc123", 
     *       "workflow_status_id": 5,
     *       "executed_at": "2025-07-30T10:30:00Z"
     *     },
     *     {
     *       "pos_order_id": 124,
     *       "workflow_id": "workflow_def456",
     *       "workflow_status_id": 3
     *     }
     *   ]
     * }
     */
    public function batchCreateWorkflowHistory(Request $request): JsonResponse
    {
        try {
            // Validate batch request
            $validated = $request->validate([
                'histories' => 'required|array|max:100', // Limit 100 records per batch
                'histories.*.pos_order_id' => 'required|integer|exists:pos_orders,id',
                'histories.*.workflow_id' => 'required|string|max:55',
                'histories.*.workflow_status_id' => 'required|integer',
                'histories.*.executed_at' => 'sometimes|date',
            ]);

            $results = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($validated['histories'] as $index => $historyData) {
                try {
                    $workflowHistory = PosOrderWorkflowHistory::create([
                        'pos_order_id' => $historyData['pos_order_id'],
                        'workflow_id' => $historyData['workflow_id'],
                        'workflow_status_id' => $historyData['workflow_status_id'],
                        'executed_at' => $historyData['executed_at'] ?? now(),
                    ]);

                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'id' => $workflowHistory->id
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'data' => $historyData
                    ];
                }
            }

            if (empty($errors)) {
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo batch workflow history thành công',
                    'data' => [
                        'total' => count($results),
                        'created' => count($results),
                        'failed' => 0,
                        'results' => $results
                    ]
                ], 201);
            } else {
                DB::rollback();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi trong batch workflow history',
                    'data' => [
                        'total' => count($validated['histories']),
                        'created' => count($results),
                        'failed' => count($errors),
                        'results' => $results,
                        'errors' => $errors
                    ]
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('N8n batch webhook error', [
                'message' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo batch workflow history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái đơn hàng
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Kiểm tra trạng thái đơn hàng
     * GET /api/n8n/order-status
     * 
     * Query params: ?pos_order_id=123
     */
    public function checkOrderStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pos_order_id' => 'required|integer|exists:pos_orders,id',
            ]);

            $order = PosOrder::with('workflowHistories')
                            ->find($validated['pos_order_id']);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->order_id,
                    'pos_order_id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                    'status_name' => $order->status_name,
                    'workflow_histories_count' => $order->workflowHistories->count(),
                    'last_workflow_run' => $order->workflowHistories->first() ? $order->workflowHistories->first()->executed_at->toISOString() : null
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check cho n8n
     *
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'N8n webhook endpoint is working',
            'timestamp' => now()->toISOString(),
            'server_time' => now()->toDateTimeString()
        ], 200);
    }
}