<?php
// app/Admin/Controllers/DailyTaskAjaxController.php (Optimized)

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use App\Services\TaskService;
use App\Repositories\TaskRepository;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;

class DailyTaskAjaxController extends AdminController
{
    protected $taskService;
    protected $taskRepository;

    public function __construct(TaskService $taskService, TaskRepository $taskRepository)
    {
        $this->taskService = $taskService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Toggle completion status của task
     */
    public function toggleCompletion(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $taskId = $request->input('task_id');
        $isCompleted = filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN);
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $result = $this->taskService->toggleTaskCompletion($taskId, $user->id, $isCompleted, $date);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm/cập nhật ghi chú cho task
     */
    public function addNote(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $taskId = $request->input('task_id');
        $notes = $request->input('notes', '');
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $result = $this->taskService->updateTaskNote($taskId, $user->id, $notes, $date);
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê nhanh cho ngày cụ thể
     */
    public function getStats(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $tasks = $this->taskService->getUserTasksForDate($user, Carbon::parse($date));
            $stats = $this->taskService->calculateCompletionStats($tasks);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks' => $stats['total'],
                    'completed_tasks' => $stats['completed'],
                    'completion_rate' => $stats['completion_rate'],
                    'pending_tasks' => $stats['pending'],
                    'in_process_tasks' => $stats['in_process']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách tasks của user cho ngày cụ thể
     */
    public function getTasks(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $categoryId = $request->input('category_id');

        try {
            $tasks = $this->taskService->getUserTasksForDate($user, Carbon::parse($date));

            // Filter by category if specified
            if ($categoryId) {
                $tasks = $tasks->where('category_id', $categoryId);
            }

            $formattedTasks = $tasks->map(function($task) {
                $completion = $task->completions->first();
                
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'category' => $task->category ? [
                        'id' => $task->category->id,
                        'name' => $task->category->name,
                        'color' => $task->category->color
                    ] : null,
                    'priority' => $task->priority,
                    'task_type' => $task->task_type,
                    'suggested_time' => $task->suggested_time ? $task->suggested_time->format('H:i') : null,
                    'estimated_minutes' => $task->estimated_minutes,
                    'completion' => $completion ? [
                        'status' => $completion->status,
                        'completed_at_time' => $completion->completed_at_time ? 
                            $completion->completed_at_time->format('H:i') : null,
                        'notes' => $completion->notes,
                        'review_status' => $completion->review_status
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTasks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch update multiple tasks
     */
    public function batchUpdate(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $updates = $request->input('updates', []); // Array of [task_id, status, notes]
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $results = [];

        try {
            foreach ($updates as $update) {
                $taskId = $update['task_id'];
                $status = $update['status'];
                $notes = $update['notes'] ?? '';

                // Update completion status
                if (in_array($status, ['completed', 'skipped', 'failed', 'in_process'])) {
                    $completion = $this->taskRepository->findOrCreateCompletion($taskId, $user->id, $date);
                    
                    $updateData = ['status' => $status];
                    if ($status === 'completed') {
                        $updateData['completed_at_time'] = now();
                        $updateData['review_status'] = 0;
                    } else {
                        $updateData['completed_at_time'] = null;
                    }
                    
                    if ($notes) {
                        $updateData['notes'] = $notes;
                    }
                    
                    $completion->update($updateData);
                    
                    $results[] = [
                        'task_id' => $taskId,
                        'success' => true,
                        'status' => $status
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ' . count($results) . ' công việc',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy completion history của user
     */
    public function getCompletionHistory(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $startDate = $request->input('start_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        try {
            $history = $this->taskRepository->getUserCompletionStats(
                $user->id, 
                $startDate, 
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick action để đánh dấu tất cả tasks của ngày là completed
     */
    public function markAllCompleted(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $tasks = $this->taskService->getUserTasksForDate($user, Carbon::parse($date), false);
            $updatedCount = 0;

            foreach ($tasks as $task) {
                $this->taskService->toggleTaskCompletion($task->id, $user->id, true, $date);
                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Đã đánh dấu hoàn thành {$updatedCount} công việc",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset tất cả tasks của ngày về trạng thái pending
     */
    public function resetAllTasks(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $deletedCount = UserTaskCompletion::where('user_id', $user->id)
                ->where('completion_date', $date)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Đã reset {$deletedCount} công việc",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}