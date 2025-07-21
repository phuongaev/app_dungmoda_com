<?php
// app/Repositories/TaskRepository.php

namespace App\Repositories;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskRepository
{
    /**
     * Lấy active tasks cho user và ngày cụ thể với tối ưu query
     */
    public function getActiveTasksForUserAndDate($userId, $userRoles, $date, $includeCompletions = true)
    {
        $query = DailyTask::with(['category'])
            ->where('is_active', 1)
            ->orderBy('priority', 'desc')
            ->orderBy('suggested_time');

        if ($includeCompletions) {
            $query->with(['completions' => function($q) use ($userId, $date) {
                $q->where('user_id', $userId)->where('completion_date', $date);
            }]);
        }

        // Lấy tất cả tasks và filter theo business logic
        $allTasks = $query->get();
        
        return $allTasks->filter(function($task) use ($userId, $userRoles, $date) {
            return $task->isActiveOnDate($date) && $task->isAssignedToUser($userId, $userRoles);
        });
    }

    /**
     * Lấy completions cho ngày cụ thể với eager loading
     */
    public function getCompletionsForDate($date)
    {
        return UserTaskCompletion::with(['dailyTask.category', 'user'])
            ->whereDate('completion_date', $date)
            ->get();
    }

    /**
     * Lấy tasks với completion status cho grid display
     */
    public function getTasksWithCompletionStatus()
    {
        return DailyTask::with(['category', 'creator'])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Lấy one-time tasks với completion details
     */
    public function getOneTimeTasksWithCompletions()
    {
        return DailyTask::with(['completions.user', 'category'])
            ->where('task_type', 'one_time')
            ->where('is_active', 1)
            ->get();
    }

    /**
     * Tìm task completion hoặc tạo mới
     */
    public function findOrCreateCompletion($taskId, $userId, $date)
    {
        return UserTaskCompletion::firstOrCreate([
            'daily_task_id' => $taskId,
            'user_id' => $userId,
            'completion_date' => $date
        ], [
            'status' => 'in_process',
            'review_status' => 0
        ]);
    }

    /**
     * Lấy completion stats cho user trong khoảng thời gian
     */
    public function getUserCompletionStats($userId, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: $startDate;
        
        return UserTaskCompletion::where('user_id', $userId)
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->selectRaw('
                status,
                COUNT(*) as count,
                completion_date
            ')
            ->groupBy('status', 'completion_date')
            ->get();
    }

    /**
     * Lấy tasks cần review
     */
    public function getTasksNeedingReview()
    {
        return UserTaskCompletion::with(['dailyTask', 'user'])
            ->where('review_status', 1)
            ->where('status', 'in_process')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy overdue one-time tasks
     */
    public function getOverdueOneTimeTasks($date = null)
    {
        $date = $date ?: Carbon::today();
        
        return DailyTask::where('task_type', 'one_time')
            ->where('is_active', 1)
            ->where('end_date', '<', $date)
            ->whereDoesntHave('completions', function($q) {
                $q->where('status', 'completed');
            })
            ->with(['category'])
            ->get();
    }

    /**
     * Lấy completion rate theo category
     */
    public function getCompletionRateByCategory($date)
    {
        return UserTaskCompletion::join('daily_tasks', 'user_task_completions.daily_task_id', '=', 'daily_tasks.id')
            ->join('task_categories', 'daily_tasks.category_id', '=', 'task_categories.id')
            ->whereDate('user_task_completions.completion_date', $date)
            ->selectRaw('
                task_categories.name as category_name,
                task_categories.color as category_color,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN user_task_completions.status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
                ROUND((SUM(CASE WHEN user_task_completions.status = "completed" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate
            ')
            ->groupBy('task_categories.id', 'task_categories.name', 'task_categories.color')
            ->get();
    }

    /**
     * Tìm tasks theo search criteria
     */
    public function searchTasks($search = null, $categoryId = null, $taskType = null, $status = null)
    {
        $query = DailyTask::with(['category', 'creator']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($taskType) {
            $query->where('task_type', $taskType);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        return $query->orderBy('priority', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}