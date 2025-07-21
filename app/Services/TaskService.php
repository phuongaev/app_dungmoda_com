<?php
// app/Services/TaskService.php

namespace App\Services;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use App\Repositories\TaskRepository;
use Encore\Admin\Auth\Database\Administrator;
use Carbon\Carbon;

class TaskService
{
    protected $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Lấy tasks được assigned cho user trong ngày cụ thể
     */
    public function getUserTasksForDate($user, $date, $includeCompletions = true)
    {
        $userRoles = $user->roles->pluck('slug')->toArray();
        
        return $this->taskRepository->getActiveTasksForUserAndDate($user->id, $userRoles, $date, $includeCompletions);
    }

    /**
     * Tính toán completion statistics
     */
    public function calculateCompletionStats($tasks)
    {
        $total = $tasks->count();
        $completed = 0;
        $skipped = 0;
        $failed = 0;
        $inProcess = 0;

        foreach ($tasks as $task) {
            $completion = $task->completions->first();
            if ($completion) {
                switch ($completion->status) {
                    case 'completed':
                        $completed++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                    case 'failed':
                        $failed++;
                        break;
                    case 'in_process':
                        $inProcess++;
                        break;
                }
            }
        }

        $pending = $total - $completed - $skipped - $failed - $inProcess;
        $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'skipped' => $skipped,
            'failed' => $failed,
            'in_process' => $inProcess,
            'pending' => $pending,
            'completion_rate' => $completionRate
        ];
    }

    /**
     * Group tasks by category với thông tin task type
     */
    public function groupTasksByCategory($tasks)
    {
        return $tasks->groupBy(function($task) {
            $categoryName = $task->category ? $task->category->name : 'Không có danh mục';
            $taskType = $task->task_type === 'one_time' ? ' (One-time)' : ' (Recurring)';
            return $categoryName . $taskType;
        });
    }

    /**
     * Toggle completion status cho task
     */
    public function toggleTaskCompletion($taskId, $userId, $isCompleted, $date = null)
    {
        $date = $date ?: Carbon::today()->format('Y-m-d');
        $task = DailyTask::findOrFail($taskId);
        
        $attributes = [
            'daily_task_id' => $taskId,
            'user_id' => $userId,
            'completion_date' => $date
        ];

        if ($isCompleted) {
            $completion = UserTaskCompletion::firstOrCreate($attributes);
            $completionTime = now();
            
            $completion->update([
                'status' => 'completed',
                'completed_at_time' => $completionTime,
                'review_status' => 0
            ]);

            return [
                'success' => true,
                'message' => "Đã hoàn thành: {$task->title}",
                'completion_time' => $completionTime->format('H:i'),
                'task_id' => $taskId
            ];
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
            
            return [
                'success' => true,
                'message' => $message,
                'task_id' => $taskId
            ];
        }
    }

    /**
     * Cập nhật ghi chú cho task
     */
    public function updateTaskNote($taskId, $userId, $notes, $date = null)
    {
        $date = $date ?: Carbon::today()->format('Y-m-d');
        $task = DailyTask::findOrFail($taskId);
        
        $completion = UserTaskCompletion::firstOrCreate([
            'daily_task_id' => $taskId,
            'user_id' => $userId,
            'completion_date' => $date
        ], [
            'status' => 'in_process',
            'review_status' => 0
        ]);

        $completion->update(['notes' => $notes]);

        return [
            'success' => true,
            'message' => 'Đã cập nhật ghi chú!',
            'task_id' => $taskId,
            'notes' => $notes
        ];
    }

    /**
     * Lấy thống kê tổng quan cho ngày cụ thể
     */
    public function getDailyOverviewStats($date)
    {
        // Lấy tất cả users active
        $users = \Encore\Admin\Auth\Database\Administrator::where('is_active', 1)
            ->with(['roles'])
            ->orderBy('name')
            ->get();

        $totalUsers = $users->count();
        $totalCompletedUsers = 0;
        $totalTasksAssigned = 0;
        $totalTasksCompleted = 0;
        $userProgressData = collect();
        $completeUsers = collect();
        $incompleteUsers = collect();

        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            // Lấy tasks được assign cho user
            $userTasks = $this->getUserTasksForDate($user, $date, true);
            
            $userTaskCount = $userTasks->count();
            $userCompletedCount = $userTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && 
                       $task->completions->first()->status === 'completed';
            })->count();
            
            $completionRate = $userTaskCount > 0 ? 
                round(($userCompletedCount / $userTaskCount) * 100) : 100;
            
            // Thống kê urgent tasks
            $urgentTasks = $userTasks->where('priority', 'urgent');
            $urgentTotal = $urgentTasks->count();
            $urgentCompleted = $urgentTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && 
                       $task->completions->first()->status === 'completed';
            })->count();
            
            // Lấy hoạt động cuối cùng
            $lastCompletion = UserTaskCompletion::where('user_id', $user->id)
                ->whereDate('completion_date', $date)
                ->orderBy('updated_at', 'desc')
                ->first();
            $lastActivity = $lastCompletion ? $lastCompletion->updated_at->format('H:i') : null;
            
            $totalTasksAssigned += $userTaskCount;
            $totalTasksCompleted += $userCompletedCount;
            
            if ($completionRate >= 100) {
                $totalCompletedUsers++;
                $completeUsers->push([
                    'id' => $user->id,
                    'name' => $user->name,
                    'completion_rate' => $completionRate,
                    'completed_tasks' => $userCompletedCount,
                    'total_tasks' => $userTaskCount
                ]);
            } else {
                $incompleteUsers->push([
                    'id' => $user->id,
                    'name' => $user->name,
                    'completion_rate' => $completionRate,
                    'completed_tasks' => $userCompletedCount,
                    'total_tasks' => $userTaskCount,
                    'pending_tasks' => $userTaskCount - $userCompletedCount
                ]);
            }
            
            $userProgressData->push([
                'user' => $user,
                'completion_rate' => $completionRate,
                'completed_tasks' => $userCompletedCount,
                'total_tasks' => $userTaskCount,
                'pending_tasks' => $userTaskCount - $userCompletedCount,
                'urgent_total' => $urgentTotal,
                'urgent_completed' => $urgentCompleted,
                'last_activity' => $lastActivity,
                'roles' => $user->roles->pluck('name')->implode(', ')
            ]);
        }

        $userCompletionRate = $totalUsers > 0 ? 
            round(($totalCompletedUsers / $totalUsers) * 100) : 0;
        $overallCompletionRate = $totalTasksAssigned > 0 ? 
            round(($totalTasksCompleted / $totalTasksAssigned) * 100) : 0;

        // Lấy completions của ngày
        $completions = $this->taskRepository->getCompletionsForDate($date);
        
        // Lấy hoạt động gần đây
        $recentCompletions = UserTaskCompletion::with(['user', 'dailyTask.category'])
            ->whereDate('completion_date', $date)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();
        
        return [
            'today' => $date,
            'target_date' => $date,
            'totalUsers' => $totalUsers,
            'totalCompletedUsers' => $totalCompletedUsers,
            'userCompletionRate' => $userCompletionRate,
            'totalTasksAssigned' => $totalTasksAssigned,
            'totalTasksCompleted' => $totalTasksCompleted,
            'overallCompletionRate' => $overallCompletionRate,
            'userProgressData' => $userProgressData,
            'completeUsers' => $completeUsers,
            'incompleteUsers' => $incompleteUsers,
            'completions' => $completions,
            'users' => $completions->pluck('user')->unique('id'),
            'total_completions' => $completions->count(),
            'completed_count' => $completions->where('status', 'completed')->count(),
            'skipped_count' => $completions->where('status', 'skipped')->count(),
            'failed_count' => $completions->where('status', 'failed')->count(),
            'recentCompletions' => $recentCompletions
        ];
    }

    /**
     * Lấy data cho trang daily progress của user
     */
    public function getDailyTasksData($user, $date)
    {
        // Lấy tasks của user
        $userTasks = $this->getUserTasksForDate($user, $date);
        
        // Tính toán stats
        $stats = $this->calculateCompletionStats($userTasks);
        
        // Group by category
        $tasksByCategory = $this->groupTasksByCategory($userTasks);
        
        // Lấy completions của ngày để thống kê tổng thể
        $allCompletions = $this->taskRepository->getCompletionsForDate($date);
        
        return array_merge($stats, [
            'target_date' => $date,
            'targetDate' => $date, // Alias cho compatibility
            'user_tasks' => $userTasks,
            'userTasks' => $userTasks, // Alias
            'tasks_by_category' => $tasksByCategory,
            'tasksByCategory' => $tasksByCategory, // Alias
            'total' => $stats['total'],
            'completed' => $stats['completed'],
            'completion_rate' => $stats['completion_rate'],
            'completionRate' => $stats['completion_rate'], // Alias
            'totalCompletions' => $allCompletions->count(),
            'completedTasks' => $stats['completed'],
            'totalTasks' => $stats['total'],
            'pendingTasks' => $stats['pending'],
            'skippedTasks' => $stats['skipped'],
            'failedTasks' => $stats['failed'],
            'tasks' => $userTasks // Alias cho template compatibility
        ]);
    }

    /**
     * Lấy data chi tiết cho user detail page
     */
    public function getUserDetailData($user, $date)
    {
        // Lấy daily data
        $dailyData = $this->getDailyTasksData($user, $date);
        
        // Lấy lịch sử 7 ngày gần đây
        $weekHistory = $this->getUserWeekHistory($user, $date);
        
        // Lấy thống kê nâng cao
        $advancedStats = $this->getAdvancedUserStats($user, $date);
        
        return array_merge($dailyData, [
            'user' => $user,
            'targetDate' => $date,
            'target_date' => $date,
            'week_history' => $weekHistory,
            'weekHistory' => $weekHistory,
            'advanced_stats' => $advancedStats
        ]);
    }

    /**
     * Lấy lịch sử 7 ngày của user
     */
    private function getUserWeekHistory($user, $targetDate)
    {
        $history = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::parse($targetDate)->subDays($i);
            $dayTasks = $this->getUserTasksForDate($user, $date);
            $dayStats = $this->calculateCompletionStats($dayTasks);
            
            $history[] = [
                'date' => $date,
                'day_name' => $date->format('D'),
                'stats' => $dayStats,
                'total_tasks' => $dayStats['total'],
                'completed_tasks' => $dayStats['completed'],
                'completion_rate' => $dayStats['completion_rate']
            ];
        }
        
        return collect($history);
    }

    /**
     * Lấy thống kê nâng cao cho user
     */
    private function getAdvancedUserStats($user, $date)
    {
        $userTasks = $this->getUserTasksForDate($user, $date);
        
        // Thống kê theo priority
        $priorityStats = [
            'urgent' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];
        
        $priorityCompleted = [
            'urgent' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];
        
        foreach ($userTasks as $task) {
            $priority = $task->priority ?? 'medium';
            $priorityStats[$priority]++;
            
            $completion = $task->completions->first();
            if ($completion && $completion->status === 'completed') {
                $priorityCompleted[$priority]++;
            }
        }
        
        // Tasks cần review
        $needsReview = $userTasks->filter(function($task) {
            $completion = $task->completions->first();
            return $completion && $completion->review_status == 1;
        })->count();
        
        return [
            'priority_stats' => $priorityStats,
            'priority_completed' => $priorityCompleted,
            'needs_review' => $needsReview,
            'overdue_tasks' => $userTasks->filter(function($task) {
                return $task->task_type === 'one_time' && $task->isOverdue();
            })->count()
        ];
    }

    /**
     * Toggle review status
     */
    public function toggleReviewStatus($completionId, $status)
    {
        $completion = UserTaskCompletion::findOrFail($completionId);
        
        if ($status == 1) {
            $completion->markForReview();
            $message = 'Đã đánh dấu cần review lại';
        } else {
            $completion->completeReview();
            $message = 'Đã xác nhận hoàn thành';
        }

        return [
            'success' => true,
            'message' => $message,
            'completion' => $completion
        ];
    }
}