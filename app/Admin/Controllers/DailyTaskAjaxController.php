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
    public function toggleCompletion(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $taskId = $request->input('task_id');
        $isCompleted = filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN);
        $today = Carbon::today()->format('Y-m-d');

        try {
            $task = DailyTask::findOrFail($taskId);
            
            $attributes = [
                'daily_task_id' => $taskId,
                'user_id' => $user->id,
                'completion_date' => $today
            ];

            if ($isCompleted) {
                $completion = UserTaskCompletion::firstOrCreate($attributes);
                $completionTime = now();
                
                $completion->update([
                    'status' => 'completed',
                    'completed_at_time' => $completionTime,
                    'review_status' => 0
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Đã hoàn thành: {$task->title}",
                    'completion_time' => $completionTime->format('H:i'),
                    'task_id' => $taskId
                ]);
            } else {
                $completion = UserTaskCompletion::where($attributes)->first();
                
                if ($completion) {
                    $completion->update([
                        'status' => 'in_process',
                        'completed_at_time' => null
                    ]);
                    $message = "Chuyển trạng thái đang thực hiện: {$task->title}";
                } else {
                    UserTaskCompletion::create(array_merge($attributes, [
                        'status' => 'in_process',
                        'completed_at_time' => null,
                        'review_status' => 0
                    ]));
                    $message = "Bắt đầu thực hiện: {$task->title}";
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'task_id' => $taskId
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addNote(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $taskId = $request->input('task_id');
        $notes = $request->input('notes', '');
        $today = Carbon::today()->format('Y-m-d');

        try {
            $task = DailyTask::findOrFail($taskId);
            
            $completion = UserTaskCompletion::firstOrCreate([
                'daily_task_id' => $taskId,
                'user_id' => $user->id,
                'completion_date' => $today
            ], [
                'status' => 'in_process',
                'review_status' => 0
            ]);

            $completion->update(['notes' => $notes]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ghi chú!',
                'task_id' => $taskId,
                'notes' => $notes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats(Request $request)
    {
        $user = Admin::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $userRoles = $user->roles->pluck('slug')->toArray();

        try {
            $tasks = DailyTask::with(['completions' => function($query) use ($user, $date) {
                    $query->where('user_id', $user->id)->where('completion_date', $date);
                }])
                ->where('is_active', 1)
                ->get()
                ->filter(function($task) use ($user, $userRoles) {
                    return $task->isAssignedToUser($user->id, $userRoles);
                });

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->filter(function($task) {
                $completion = $task->completions->first();
                return $completion && 
                       $completion->status === 'completed' &&
                       !$completion->review_status;
            })->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                    'date' => $date
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}