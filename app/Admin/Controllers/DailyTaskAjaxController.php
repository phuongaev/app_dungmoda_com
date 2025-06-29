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
                return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng đăng nhập.'], 401);
            }

            $taskId = $request->input('task_id');
            $isCompleted = filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN);
            $notes = $request->input('notes', '');
            $today = Carbon::today();

            $task = DailyTask::findOrFail($taskId);

            $attributes = [
                'daily_task_id' => $taskId,
                'user_id' => $user->id,
                'completion_date' => $today
            ];

            if ($isCompleted) {
                // Nếu là 'hoàn thành', tìm hoặc tạo mới và cập nhật
                $completion = UserTaskCompletion::firstOrCreate($attributes);
                
                $completion->update([
                    'status' => 'completed',
                    'completed_at_time' => now(),
                    'notes' => $notes
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Đã hoàn thành: {$task->title}",
                    'completion_time' => now()->format('H:i')
                ]);
            } else {
                UserTaskCompletion::where($attributes)->delete();
                return response()->json([
                    'success' => true,
                    'message' => "Đã bỏ đánh dấu: {$task->title}"
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
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
                return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng đăng nhập.'], 401);
            }

            $taskId = $request->input('task_id');
            $notes = $request->input('notes', '');
            $today = Carbon::today();

            $task = DailyTask::findOrFail($taskId);

            $completion = UserTaskCompletion::firstOrCreate(
                [
                    'daily_task_id' => $taskId,
                    'user_id' => $user->id,
                    'completion_date' => $today
                ],
                [
                    'status' => 'skipped' 
                ]
            );

            $completion->update(['notes' => $notes]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ghi chú!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task completion statistics
     */
    public function getStats(Request $request)
    {
        $userId = Admin::user()->id;
        $date = $request->input('date', Carbon::today());

        // Get user roles
        $userRoles = Admin::user()->roles->pluck('slug')->toArray();

        // Get tasks for the date
        $tasks = DailyTask::with(['completions' => function($query) use ($userId, $date) {
            $query->where('user_id', $userId)->where('completion_date', $date);
        }])
        ->where('is_active', 1)
        ->get()
        ->filter(function($task) use ($userId, $userRoles) {
            return $task->isAssignedToUser($userId, $userRoles);
        });

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
        })->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return response()->json([
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $completionRate,
            'date' => $date
        ]);
    }

    /**
     * Get weekly completion report
     */
    public function getWeeklyReport(Request $request)
    {
        $userId = Admin::user()->id;
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        $weeklyData = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dailyTasks = DailyTask::with(['completions' => function($query) use ($userId, $date) {
                $query->where('user_id', $userId)->where('completion_date', $date->toDateString());
            }])
            ->where('is_active', 1)
            ->get()
            ->filter(function($task) use ($userId, $date) {
                $userRoles = Admin::user()->roles->pluck('slug')->toArray();
                return $task->isActiveToday() && $task->isAssignedToUser($userId, $userRoles);
            });

            $totalTasks = $dailyTasks->count();
            $completedTasks = $dailyTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();

            $weeklyData[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'day_name_vi' => $this->getDayNameVi($date->format('l')),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];
        }

        return response()->json($weeklyData);
    }

    private function getDayNameVi($dayName)
    {
        $days = [
            'Monday' => 'Thứ 2',
            'Tuesday' => 'Thứ 3',
            'Wednesday' => 'Thứ 4',
            'Thursday' => 'Thứ 5',
            'Friday' => 'Thứ 6',
            'Saturday' => 'Thứ 7',
            'Sunday' => 'Chủ nhật'
        ];

        return $days[$dayName] ?? $dayName;
    }
}