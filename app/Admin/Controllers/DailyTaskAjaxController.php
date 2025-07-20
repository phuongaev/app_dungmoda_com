<?php
// app/Admin/Controllers/DailyTaskAjaxController.php

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;

class DailyTaskAjaxController extends AdminController
{
    /**
     * Toggle task completion status
     */
    public function toggleCompletion(Request $request)
    {
        try {
            $user = Admin::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Không tìm thấy người dùng đăng nhập.'
                ], 401);
            }

            $taskId = $request->input('task_id');
            $isCompleted = filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN);
            $notes = $request->input('notes', '');
            $today = Carbon::today();

            // Validate task exists
            $task = DailyTask::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy công việc này.'
                ], 404);
            }

            $attributes = [
                'daily_task_id' => $taskId,
                'user_id' => $user->id,
                'completion_date' => $today
            ];

            if ($isCompleted) {
                // Nếu là 'hoàn thành', tìm hoặc tạo mới và cập nhật
                $completion = UserTaskCompletion::firstOrCreate($attributes);
                
                $completionTime = now();
                $completion->update([
                    'status' => 'completed',
                    'completed_at_time' => $completionTime,
                    'notes' => $notes
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Đã hoàn thành: {$task->title}",
                    'completion_time' => $completionTime->format('H:i'),
                    'task_id' => $taskId
                ]);
            } else {
                // Nếu bỏ check, xóa completion
                UserTaskCompletion::where($attributes)->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => "Đã bỏ đánh dấu: {$task->title}",
                    'task_id' => $taskId
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Daily Task Toggle Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add or update task notes
     */
    public function addNote(Request $request)
    {
        try {
            $user = Admin::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Không tìm thấy người dùng đăng nhập.'
                ], 401);
            }

            $taskId = $request->input('task_id');
            $notes = $request->input('notes', '');
            $today = Carbon::today();

            // Validate task exists
            $task = DailyTask::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy công việc này.'
                ], 404);
            }

            $completion = UserTaskCompletion::firstOrCreate(
                [
                    'daily_task_id' => $taskId,
                    'user_id' => $user->id,
                    'completion_date' => $today
                ],
                [
                    'status' => 'skipped', // Trạng thái mặc định khi chỉ thêm ghi chú
                    'review_status' => 0    // Mặc định không cần review
                ]
            );

            $completion->update(['notes' => $notes]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ghi chú!',
                'task_id' => $taskId,
                'notes' => $notes
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily Task Note Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task completion statistics
     */
    public function getStats(Request $request)
    {
        try {
            $user = Admin::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Không tìm thấy người dùng đăng nhập.'
                ], 401);
            }

            $userId = $user->id;
            $date = $request->input('date', Carbon::today());
            $userRoles = $user->roles->pluck('slug')->toArray();

            // Get tasks for the date
            $tasks = DailyTask::with(['completions' => function($query) use ($userId, $date) {
                $query->where('user_id', $userId)->where('completion_date', $date);
            }])
            ->where('is_active', 1)
            ->get()
            ->filter(function($task) use ($userId, $userRoles) {
                return $this->isTaskAssignedToUser($task, $userId, $userRoles);
            });

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->filter(function($task) {
                return $task->completions->isNotEmpty() && 
                       $task->completions->first()->status === 'completed';
            })->count();

            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $completionRate,
                    'date' => $date
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily Task Stats Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra xem task có được assign cho user không
     */
    private function isTaskAssignedToUser($task, $userId, $userRoles)
    {
        // Nếu không có assigned_users và assigned_roles thì assign cho tất cả
        if (empty($task->assigned_users) && empty($task->assigned_roles)) {
            return true;
        }

        // Kiểm tra assigned_users
        if (!empty($task->assigned_users) && in_array($userId, $task->assigned_users)) {
            return true;
        }

        // Kiểm tra assigned_roles
        if (!empty($task->assigned_roles)) {
            foreach ($userRoles as $role) {
                if (in_array($role, $task->assigned_roles)) {
                    return true;
                }
            }
        }

        return false;
    }
}