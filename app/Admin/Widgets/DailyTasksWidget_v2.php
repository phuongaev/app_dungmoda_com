<?php
// app/Admin/Widgets/DailyTasksWidget.php

namespace App\Admin\Widgets;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Widgets\Widget;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;

class DailyTasksWidget extends Widget
{
    protected $view = 'admin.widgets.daily_tasks_improved';

    // No script method needed - handled in view with external files

    /**
     * Implement abstract method render() required by Laravel-admin Widget
     */
    public function render()
    {
        $user = Admin::user();
        if (!$user) {
            return view($this->view, [
                'tasks' => collect(),
                'totalTasks' => 0,
                'completedTasks' => 0,
                'completionRate' => 0,
                'today' => Carbon::today()
            ])->render();
        }

        $userId = $user->id;
        $today = Carbon::today();
        $userRoles = $user->roles->pluck('slug')->toArray();

        // Lấy tất cả tasks active cho hôm nay
        $tasks = DailyTask::with([
            'category',
            'completions' => function($query) use ($userId, $today) {
                $query->where('user_id', $userId)
                      ->where('completion_date', $today);
            }
        ])
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->orderBy('suggested_time')
        ->get()
        ->filter(function($task) use ($userId, $userRoles) {
            return $this->isTaskAssignedToUser($task, $userId, $userRoles);
        });

        // Tính toán thống kê
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(function($task) {
            $completion = $task->completions->first();
            return $completion && $completion->status === 'completed';
        })->count();
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return view($this->view, [
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'today' => $today
        ])->render();
    }

    /**
     * Legacy content method for compatibility
     */
    public function content()
    {
        return $this->render();
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