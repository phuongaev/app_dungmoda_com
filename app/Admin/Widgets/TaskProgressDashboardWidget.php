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
        
        return view($this->view, [
            'totalUsers' => $totalUsers,
            'totalActiveTasks' => $totalActiveTasks,
            'todayCompletions' => $todayCompletions,
            'todayAssignedTasks' => $todayAssignedTasks,
            'todayCompletionRate' => $todayCompletionRate,
            'topPerformers' => $topPerformers,
            'incompleteUsers' => $incompleteUsers,
            'priorityStats' => $priorityStats,
            'weeklyTrend' => $weeklyTrend,
            'today' => $today
        ])->render();
    }

    /**
     * Tính tổng số task được assign hôm nay
     */
    protected function getTotalAssignedTasksToday()
    {
        $today = Carbon::today();
        $totalTasks = 0;
        
        $users = Administrator::with(['roles'])->get();
        
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            $userTasks = DailyTask::where('is_active', 1)
                ->get()
                ->filter(function($task) use ($user, $userRoles, $today) {
                    return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
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
                return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
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
                return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
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
        
        return $incompleteUsers->sortByDesc('pending_tasks')->take(5);
    }

    /**
     * Thống kê theo độ ưu tiên
     */
    protected function getPriorityStats()
    {
        $today = Carbon::today();
        $priorities = ['urgent', 'high', 'medium', 'low'];
        $stats = [];
        
        foreach ($priorities as $priority) {
            $totalTasks = 0;
            $completedTasks = 0;
            
            $users = Administrator::with(['roles'])->get();
            
            foreach ($users as $user) {
                $userRoles = $user->roles->pluck('slug')->toArray();
                
                $userPriorityTasks = DailyTask::with(['completions' => function($query) use ($user, $today) {
                    $query->where('user_id', $user->id)->where('completion_date', $today);
                }])
                ->where('is_active', 1)
                ->where('priority', $priority)
                ->get()
                ->filter(function($task) use ($user, $userRoles, $today) {
                    return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
                });
                
                $taskCount = $userPriorityTasks->count();
                $completedCount = $userPriorityTasks->filter(function($task) {
                    return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
                })->count();
                
                $totalTasks += $taskCount;
                $completedTasks += $completedCount;
            }
            
            $stats[$priority] = [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Xu hướng 7 ngày qua
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
                    // Sử dụng method mới từ model
                    return $task->isActiveOnDate($date) && $task->isAssignedToUser($user->id, $userRoles);
                });
                
            $totalTasks += $userTasks->count();
        }
        
        return $totalTasks;
    }


    
}