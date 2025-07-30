<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PosOrderApiController extends Controller
{
    /**
     * Lấy đơn hàng từ ngày date về trước và chưa chạy workflow
     */
    public function getOrdersBeforeDateNotRunWorkflow(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'date' => 'required|date',
                'workflow_id' => 'required|string|max:55',
                'status' => 'required|string',
                'limit' => 'sometimes|integer|min:1|max:1000',
                'offset' => 'sometimes|integer|min:0',
                'with_details' => 'sometimes|boolean',
                'require_fb_id' => 'sometimes|boolean', // Filter có customer_fb_id hay không
            ]);

            // Parse date
            $targetDate = Carbon::parse($validated['date'])->endOfDay();
            $workflowId = $validated['workflow_id'];
            $statusFilter = $validated['status'];
            $requireFbId = $validated['require_fb_id'] ?? true; // Mặc định true

            // Build query
            $query = PosOrder::query();

            // Filter theo inserted_at <= date
            $query->where('inserted_at', '<=', $targetDate);

            // Filter theo status (nếu không phải 'all')
            if ($statusFilter !== 'all') {
                if (is_numeric($statusFilter)) {
                    $query->where('status', $statusFilter);
                } elseif (str_contains($statusFilter, ',')) {
                    $statusArray = array_map('trim', explode(',', $statusFilter));
                    $query->whereIn('status', $statusArray);
                }
            }

            // Filter customer_fb_id not null/not empty
            if ($requireFbId) {
                $query->whereNotNull('customer_fb_id')
                      ->where('customer_fb_id', '!=', '')
                      ->where('customer_fb_id', '!=', '0');
            }

            // Filter chưa từng chạy workflow_id - Simple version
            $query->whereDoesntHave('workflowHistories', function ($q) use ($workflowId) {
                $q->where('workflow_id', $workflowId);
            });

            // Select columns
            $selectColumns = [
                'id', 'order_id', 'customer_name', 'customer_phone', 'customer_fb_id',
                'cod', 'status', 'status_name', 'order_sources_name',
                'total_quantity', 'inserted_at', 'created_at'
            ];

            $query->select($selectColumns);

            // Pagination
            $limit = $validated['limit'] ?? 100;
            $offset = $validated['offset'] ?? 0;

            // Get total count
            $totalCount = $query->count();

            // Get orders
            $orders = $query->orderBy('inserted_at', 'desc')
                           ->limit($limit)
                           ->offset($offset)
                           ->get();

            // Format response
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_fb_id' => $order->customer_fb_id,
                    'cod' => $order->cod,
                    'cod_formatted' => number_format($order->cod ?? 0, 0, ',', '.') . ' VND',
                    'status' => $order->status,
                    'status_name' => $order->status_name,
                    'order_sources_name' => $order->order_sources_name,
                    'total_quantity' => $order->total_quantity,
                    'inserted_at' => $order->inserted_at ? $order->inserted_at->toISOString() : null,
                    'inserted_at_formatted' => $order->inserted_at ? $order->inserted_at->format('d/m/Y H:i:s') : null,
                    'created_at' => $order->created_at ? $order->created_at->toISOString() : null,
                ];
            });

            // Response
            $response = [
                'success' => true,
                'data' => [
                    'orders' => $formattedOrders,
                    'summary' => [
                        'total_orders' => $totalCount,
                        'returned_orders' => $orders->count(),
                        'total_cod' => $orders->sum('cod'),
                        'total_cod_formatted' => number_format($orders->sum('cod'), 0, ',', '.') . ' VND',
                        'date_filter' => $targetDate->format('Y-m-d'),
                        'workflow_id' => $workflowId,
                        'status_filter' => $statusFilter,
                        'require_fb_id' => $requireFbId
                    ],
                    'pagination' => [
                        'total' => $totalCount,
                        'limit' => $limit,
                        'offset' => $offset,
                        'current_page' => floor($offset / $limit) + 1,
                        'total_pages' => ceil($totalCount / $limit),
                        'has_more' => ($offset + $limit) < $totalCount
                    ]
                ],
                'message' => "Tìm thấy {$totalCount} đơn hàng chưa chạy workflow {$workflowId}" . ($requireFbId ? " (có Facebook ID)" : "")
            ];

            return response()->json($response, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy đơn hàng',
                'error' => $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Filter orders - Basic version  
     */
    public function filterOrders(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'statuses' => 'sometimes|array',
                'statuses.*' => 'integer',
                'limit' => 'sometimes|integer|min:1|max:1000',
                'offset' => 'sometimes|integer|min:0'
            ]);

            $query = PosOrder::query();

            if (!empty($validated['statuses'])) {
                $query->whereIn('status', $validated['statuses']);
            }

            $limit = $validated['limit'] ?? 50;
            $offset = $validated['offset'] ?? 0;
            $totalCount = $query->count();

            $orders = $query->orderBy('inserted_at', 'desc')
                           ->limit($limit)
                           ->offset($offset)
                           ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'pagination' => [
                        'total' => $totalCount,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $totalCount
                    ]
                ],
                'message' => 'Lọc đơn hàng thành công'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}