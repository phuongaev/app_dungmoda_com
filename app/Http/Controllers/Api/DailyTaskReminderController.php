<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Auth\Database\Administrator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DailyTaskReminderController extends Controller
{
    /**
     * Get daily task reminders for all employees (except administrators and CEOs)
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();
            
            // Lấy danh sách employees (loại trừ administrator và ceo)
            $employees = Administrator::with(['roles'])
                ->whereHas('roles', function($query) {
                    // $query->whereNotIn('slug', ['administrator', 'ceo']);
                    $query->whereNotIn('slug', ['administrator']);
                })
                ->where('is_active', 1)
                ->get();

            $employeeData = [];
            $totalTasks = 0;
            $totalCompletedTasks = 0;

            foreach ($employees as $employee) {
                $userRoles = $employee->roles->pluck('slug')->toArray();
                
                // Lấy tất cả tasks active cho hôm nay của employee này
                $allTasks = DailyTask::with([
                    'category',
                    'completions' => function($query) use ($employee, $today) {
                        $query->where('user_id', $employee->id)
                              ->where('completion_date', $today);
                    }
                ])
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('suggested_time')
                ->get()
                ->filter(function($task) use ($employee, $userRoles) {
                    return $task->isActiveToday() && $task->isAssignedToUser($employee->id, $userRoles);
                });

                // Phân loại tasks đã hoàn thành và chưa hoàn thành
                $completedTasks = [];
                $pendingTasks = [];

                foreach ($allTasks as $task) {
                    $completion = $task->completions->first();
                    
                    if ($completion && $completion->status === 'completed') {
                        $completedTasks[] = [
                            'id' => $task->id,
                            'title' => $task->title,
                            'category' => $task->category ? $task->category->name : null,
                            'priority' => $task->priority,
                            'completed_at' => $completion->completed_at_time ? $completion->completed_at_time->format('H:i:s') : null,
                            'notes' => $completion->notes
                        ];
                    } else {
                        $pendingTasks[] = [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'category' => $task->category ? $task->category->name : null,
                            'priority' => $task->priority,
                            'suggested_time' => $task->suggested_time ? $task->suggested_time->format('H:i') : null,
                            'estimated_minutes' => $task->estimated_minutes,
                            'is_required' => $task->is_required
                        ];
                    }
                }

                $taskCount = $allTasks->count();
                $completedCount = count($completedTasks);
                $completionRate = $taskCount > 0 ? round(($completedCount / $taskCount) * 100, 1) : 0;

                // Chỉ thêm vào response nếu employee có ít nhất 1 task
                if ($taskCount > 0) {
                    $employeeData[] = [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'username' => $employee->username,
                        'email' => $employee->email,
                        'thread_id' => $employee->thread_id,
                        'completed_tasks' => $completedTasks,
                        'pending_tasks' => $pendingTasks,
                        'completion_rate' => $completionRate,
                        'total_tasks' => $taskCount,
                        'completed_count' => $completedCount,
                        'pending_count' => count($pendingTasks)
                    ];

                    $totalTasks += $taskCount;
                    $totalCompletedTasks += $completedCount;
                }
            }

            $overallCompletionRate = $totalTasks > 0 ? round(($totalCompletedTasks / $totalTasks) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'date' => $today->format('Y-m-d'),
                'employees' => $employeeData,
                'overall_stats' => [
                    'total_employees' => count($employeeData),
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $totalCompletedTasks,
                    'pending_tasks' => $totalTasks - $totalCompletedTasks,
                    'overall_completion_rate' => $overallCompletionRate
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily Task Reminders API Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy dữ liệu công việc hàng ngày.',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}