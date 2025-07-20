<?php
// app/Admin/Widgets/TaskProgressDashboardWidget.php

namespace App\Admin\Widgets;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Widgets\Widget;
use Carbon\Carbon;

class TaskProgressDashboardWidget extends Widget
{
    protected $view = 'admin.widgets.task_progress_dashboard';

    public function render()
    {
        $today = Carbon::today();
        
        // Thống kê tổng quan
        $totalUsers = Administrator::count();
        $totalActiveTasks = DailyTask::where('is_active', 1)->count();
        $totalRecurringTasks = DailyTask::where('is_active', 1)->where('task_type', 'recurring')->count();
        $totalOneTimeTasks = DailyTask::where('is_active', 1)->where('task_type', 'one_time')->count();
        
        // Thống kê hoàn thành hôm nay
        $todayCompletions = UserTaskCompletion::whereDate('completion_date', $today)
            ->where('status', 'completed')
            ->count();
            
        $todayAssignedTasks = $this->getTotalAssignedTasksToday();
        $todayCompletionRate = $todayAssignedTasks > 0 ? 
            round(($todayCompletions / $todayAssignedTasks) * 100) : 0;
        
        // Top performers hôm nay
        $topPerformers = $this->getTopPerformersToday();
        
        // Users chưa hoàn thành
        $incompleteUsers = $this->getIncompleteUsers();
        
        // Thống kê theo độ ưu tiên
        $priorityStats = $this->getPriorityStats();
        
        // Xu hướng 7 ngày qua
        $weeklyTrend = $this->getWeeklyTrend();
        
        // Thống kê overdue one-time tasks
        $overdueTasks = $this->getOverdueTasks();
        
        // Thống kê review status
        $reviewStats = $this->getReviewStats();
        
        return view($this->view, [
            'totalUsers' => $totalUsers,
            'totalActiveTasks' => $totalActiveTasks,
            'totalRecurringTasks' => $totalRecurringTasks,
            'totalOneTimeTasks' => $totalOneTimeTasks,
            'todayCompletions' => $todayCompletions,
            'todayAssignedTasks' => $todayAssignedTasks,
            'todayCompletionRate' => $todayCompletionRate,
            'topPerformers' => $topPerformers,
            'incompleteUsers' => $incompleteUsers,
            'priorityStats' => $priorityStats,
            'weeklyTrend' => $weeklyTrend,
            'overdueTasks' => $overdueTasks,
            'reviewStats' => $reviewStats,
            'today' => $today
        ])->render();
    }

    /**
     * Tính tổng số task được assign hôm nay
     */
    protected function getTotalAssignedTasksToday()
    {
        $today = Carbon::today();
        return $this->getTotalAssignedTasksForDate($today);
    }

    /**
     * Tính tổng task được assign cho một ngày cụ thể
     */
    protected function getTotalAssignedTasksForDate($date)
    {
        $totalTasks = 0;
        $users = Administrator::with(['roles'])->get();
        
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            $userTasks = DailyTask::where('is_active', 1)
                ->get()
                ->filter(function($task) use ($user, $userRoles, $date) {
                    // Sử dụng method mới từ model - hỗ trợ cả recurring và one-time
                    return $task->isActiveOnDate($date) && $task->isAssignedToUser($user->id, $userRoles);
                });
                
            $totalTasks += $userTasks->count();
        }
        
        return $totalTasks;
    }

    /**
     * Lấy top performers hôm nay
     */
    protected function getTopPerformersToday()
    {
        $today = Carbon::today();
        $performers = collect();
        
        $users = Administrator::with(['roles'])->get();
        
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            $userTasks = DailyTask::with(['completions' => function($query) use ($user, $today) {
                $query->where('user_id', $user->id)->where('completion_date', $today);
            }])
            ->where('is_active', 1)
            ->get()
            ->filter(function($task) use ($user, $userRoles, $today) {
                return $task->isActiveOnDate($today) && $task->isAssignedToUser($user->id, $userRoles);
            });
            
            $totalTasks = $userTasks->count();
            $completedTasks = $userTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            if ($totalTasks > 0) {
                $completionRate = round(($completedTasks / $totalTasks) * 100);
                
                $performers->push([
                    'name' => $user->name,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $completionRate
                ]);
            }
        }
        
        return $performers->sortByDesc('completion_rate')->take(5);
    }

    /**
     * Lấy users chưa hoàn thành task
     */
    protected function getIncompleteUsers()
    {
        $today = Carbon::today();
        $incompleteUsers = collect();
        
        $users = Administrator::with(['roles'])->get();
        
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            $userTasks = DailyTask::with(['completions' => function($query) use ($user, $today) {
                $query->where('user_id', $user->id)->where('completion_date', $today);
            }])
            ->where('is_active', 1)
            ->get()
            ->filter(function($task) use ($user, $userRoles, $today) {
                return $task->isActiveOnDate($today) && $task->isAssignedToUser($user->id, $userRoles);
            });
            
            $totalTasks = $userTasks->count();
            $completedTasks = $userTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            $pendingTasks = $totalTasks - $completedTasks;
            
            if ($pendingTasks > 0) {
                $incompleteUsers->push([
                    'name' => $user->name,
                    'pending_tasks' => $pendingTasks,
                    'total_tasks' => $totalTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
                ]);
            }
        }
        
        return $incompleteUsers->sortByDesc('pending_tasks')->take(10);
    }

    /**
     * Thống kê theo độ ưu tiên
     */
    protected function getPriorityStats()
    {
        $today = Carbon::today();
        $stats = [];
        
        $priorities = ['urgent', 'high', 'medium', 'low'];
        
        foreach ($priorities as $priority) {
            $totalTasks = DailyTask::where('is_active', 1)
                ->where('priority', $priority)
                ->get()
                ->filter(function($task) use ($today) {
                    return $task->isActiveOnDate($today);
                })
                ->count();
                
            $completedTasks = UserTaskCompletion::whereDate('completion_date', $today)
                ->where('status', 'completed')
                ->whereHas('task', function($query) use ($priority) {
                    $query->where('priority', $priority);
                })
                ->count();
                
            $stats[$priority] = [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Xu hướng hoàn thành 7 ngày qua
     */
    protected function getWeeklyTrend()
    {
        $trend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            $dayCompletions = UserTaskCompletion::whereDate('completion_date', $date)
                ->where('status', 'completed')
                ->count();
                
            $dayAssignedTasks = $this->getTotalAssignedTasksForDate($date);
            
            $dayRate = $dayAssignedTasks > 0 ? round(($dayCompletions / $dayAssignedTasks) * 100) : 0;
            
            $trend[] = [
                'date' => $date->format('d/m'),
                'completion_rate' => $dayRate,
                'completed' => $dayCompletions,
                'total' => $dayAssignedTasks
            ];
        }
        
        return $trend;
    }

    /**
     * Lấy danh sách one-time tasks quá hạn
     */
    protected function getOverdueTasks()
    {
        $today = Carbon::today();
        
        return DailyTask::where('is_active', 1)
            ->where('task_type', 'one_time')
            ->where('end_date', '<', $today)
            ->with(['category'])
            ->get()
            ->filter(function($task) {
                // Chỉ lấy những task chưa được hoàn thành
                $completed = UserTaskCompletion::where('daily_task_id', $task->id)
                    ->where('status', 'completed')
                    ->exists();
                return !$completed;
            });
    }

    /**
     * Thống kê review status cho one-time tasks  
     */
    protected function getReviewStats()
    {
        $needReviews = UserTaskCompletion::where('review_status', 1)
            ->where('status', 'completed')
            ->whereHas('dailyTask', function($q) {
                $q->where('task_type', 'one_time');
            })
            ->count();

        $totalCompletedOneTime = UserTaskCompletion::where('status', 'completed')
            ->whereHas('dailyTask', function($q) {
                $q->where('task_type', 'one_time');
            })
            ->count();

        return [
            'need_review' => $needReviews,
            'total_completed' => $totalCompletedOneTime,
            'ok_tasks' => $totalCompletedOneTime - $needReviews
        ];
    }
}