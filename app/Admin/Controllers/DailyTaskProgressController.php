<?php
// app/Admin/Controllers/DailyTaskProgressController.php

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Auth\Database\Administrator;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;

class DailyTaskProgressController extends Controller
{
    protected $title = 'Tiến độ hoàn thành công việc nhân viên';

    /**
     * Dashboard tiến độ tổng quan
     */
    public function index(Content $content)
    {
        $today = Carbon::today();
        $data = $this->getTodayProgressData($today);
        
        return $content
            ->header('Tiến độ hoàn thành công việc')
            ->description('Báo cáo chi tiết tiến độ làm việc của nhân viên')
            ->view('admin.daily-task-progress.index', $data);
    }

    /**
     * Chi tiết tiến độ theo ngày
     */
    public function daily(Request $request)
    {
        $targetDate = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        $user = Admin::user();
        $userRoles = $user->roles->pluck('slug')->toArray();
        
        // Lấy tasks của user cho ngày đã chọn (bao gồm cả recurring và one-time)
        $userTasks = DailyTask::with(['completions' => function($query) use ($user, $targetDate) {
            return $query->where('user_id', $user->id)->where('completion_date', $targetDate);
        }, 'category'])
        ->where('is_active', 1)
        ->orderBy('priority', 'desc')
        ->orderBy('suggested_time')
        ->get()
        ->filter(function($task) use ($user, $userRoles, $targetDate) {
            // Sử dụng method mới cho ngày cụ thể - hỗ trợ cả recurring và one-time
            return $task->isActiveOnDate($targetDate) && $task->isAssignedToUser($user->id, $userRoles);
        });
        
        $totalTasks = $userTasks->count();
        $completedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
        })->count();
        $skippedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'skipped';
        })->count();
        $failedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'failed';
        })->count();
        $pendingTasks = $totalTasks - $completedTasks - $skippedTasks - $failedTasks;
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        
        // Group by category và task type
        $tasksByCategory = $userTasks->groupBy(function($task) {
            $categoryName = $task->category ? $task->category->name : 'Không phân loại';
            $taskTypeLabel = $task->task_type === 'one_time' ? ' (Một lần)' : '';
            return $categoryName . $taskTypeLabel;
        });
        
        // Priority stats
        $priorityStats = [];
        foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
            $priorityTasks = $userTasks->where('priority', $priority);
            $priorityCompleted = $priorityTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            $priorityStats[$priority] = [
                'total' => $priorityTasks->count(),
                'completed' => $priorityCompleted,
                'rate' => $priorityTasks->count() > 0 ? round(($priorityCompleted / $priorityTasks->count()) * 100) : 0
            ];
        }
        
        // Task type stats
        $recurringTasks = $userTasks->where('task_type', 'recurring')->count();
        $oneTimeTasks = $userTasks->where('task_type', 'one_time')->count();
        $overdueTasks = $userTasks->filter(function($task) {
            return $task->isOverdue();
        })->count();
        
        return view('admin.daily-task-progress.daily', [
            'targetDate' => $targetDate,
            'userTasks' => $userTasks,
            'tasksByCategory' => $tasksByCategory,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'skippedTasks' => $skippedTasks,
            'failedTasks' => $failedTasks,
            'pendingTasks' => $pendingTasks,
            'completionRate' => $completionRate,
            'priorityStats' => $priorityStats,
            'recurringTasks' => $recurringTasks,
            'oneTimeTasks' => $oneTimeTasks,
            'overdueTasks' => $overdueTasks
        ]);
    }

    /**
     * Chi tiết tiến độ của một nhân viên
     */
    public function userDetail($userId, Content $content)
    {
        $user = Administrator::findOrFail($userId);
        $date = request('date', Carbon::today()->format('Y-m-d'));
        $targetDate = Carbon::parse($date);
        $data = $this->getUserDetailData($user, $targetDate);
        
        return $content
            ->header("Chi tiết tiến độ: {$user->name}")
            ->description("Thống kê chi tiết công việc ngày {$targetDate->format('d/m/Y')}")
            ->view('admin.daily-task-progress.user_detail', $data);
    }

    /**
     * Lấy dữ liệu tiến độ hôm nay
     */
    protected function getTodayProgressData($today)
    {
        // Lấy tất cả users
        $users = Administrator::where('is_active', 1)->with(['roles'])->orderBy('name')->get();
        
        $totalUsers = $users->count();
        $totalCompletedUsers = 0;
        $totalTasksAssigned = 0;
        $totalTasksCompleted = 0;
        $userProgressData = collect();
        
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            
            // Lấy tasks được assign cho user này
            $userTasks = DailyTask::with(['completions' => function($query) use ($user, $today) {
                $query->where('user_id', $user->id)->where('completion_date', $today);
            }, 'category'])
            ->where('is_active', 1)
            ->get()
            ->filter(function($task) use ($user, $userRoles) {
                return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
            });
            
            $userTaskCount = $userTasks->count();
            $userCompletedCount = $userTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            $completionRate = $userTaskCount > 0 ? round(($userCompletedCount / $userTaskCount) * 100) : 0;
            
            // Thống kê urgent tasks
            $urgentTasks = $userTasks->where('priority', 'urgent');
            $urgentTotal = $urgentTasks->count();
            $urgentCompleted = $urgentTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            // Last activity
            $lastActivity = $this->getLastActivity($user->id, $today);
            
            $totalTasksAssigned += $userTaskCount;
            $totalTasksCompleted += $userCompletedCount;
            
            // User hoàn thành 100%
            if ($userTaskCount > 0 && $userCompletedCount == $userTaskCount) {
                $totalCompletedUsers++;
            }
            
            $userProgressData->push([
                'user' => $user,
                'total_tasks' => $userTaskCount,
                'completed_tasks' => $userCompletedCount,
                'pending_tasks' => $userTaskCount - $userCompletedCount,
                'completion_rate' => $completionRate,
                'urgent_total' => $urgentTotal,
                'urgent_completed' => $urgentCompleted,
                'last_activity' => $lastActivity,
                'roles' => $user->roles->pluck('name')->implode(', ')
            ]);
        }
        
        $overallCompletionRate = $totalTasksAssigned > 0 ? round(($totalTasksCompleted / $totalTasksAssigned) * 100) : 0;
        $userCompletionRate = $totalUsers > 0 ? round(($totalCompletedUsers / $totalUsers) * 100) : 0;
        
        // Lấy recent completions
        $recentCompletions = UserTaskCompletion::with(['user', 'dailyTask.category'])
            ->whereDate('completion_date', $today)
            ->orderBy('completed_at_time', 'desc')
            ->take(10)
            ->get();
        
        return [
            'today' => $today,
            'totalUsers' => $totalUsers,
            'totalCompletedUsers' => $totalCompletedUsers,
            'totalTasksAssigned' => $totalTasksAssigned,
            'totalTasksCompleted' => $totalTasksCompleted,
            'overallCompletionRate' => $overallCompletionRate,
            'userCompletionRate' => $userCompletionRate,
            'userProgressData' => $userProgressData->sortBy('completion_rate'),
            'recentCompletions' => $recentCompletions
        ];
    }

    /**
     * Lấy dữ liệu tiến độ theo ngày
     */
    protected function getDailyProgressData($targetDate)
    {
        $completions = UserTaskCompletion::with(['user', 'dailyTask.category'])
            ->whereDate('completion_date', $targetDate)
            ->orderBy('completed_at_time', 'desc')
            ->get();
            
        $users = $completions->pluck('user')->unique('id');
        $totalCompletions = $completions->count();
        $completedCount = $completions->where('status', 'completed')->count();
        $skippedCount = $completions->where('status', 'skipped')->count();
        $failedCount = $completions->where('status', 'failed')->count();
        
        return [
            'targetDate' => $targetDate,
            'completions' => $completions,
            'users' => $users,
            'totalCompletions' => $totalCompletions,
            'completedCount' => $completedCount,
            'skippedCount' => $skippedCount,
            'failedCount' => $failedCount
        ];
    }

    /**
     * Lấy dữ liệu chi tiết user - cập nhật để hỗ trợ one-time tasks
     */
    protected function getUserDetailData($user, $targetDate)
    {
        $userRoles = $user->roles->pluck('slug')->toArray();
        
        // Lấy tất cả tasks được assign cho user trong ngày (bao gồm cả recurring và one-time)
        $userTasks = DailyTask::with(['completions' => function($query) use ($user, $targetDate) {
            $query->where('user_id', $user->id)->where('completion_date', $targetDate);
        }, 'category'])
        ->where('is_active', 1)
        ->orderBy('priority', 'desc')
        ->orderBy('suggested_time')
        ->get()
        ->filter(function($task) use ($user, $userRoles, $targetDate) {
            // Sử dụng method mới để check cả recurring và one-time tasks
            return $task->isActiveOnDate($targetDate) && $task->isAssignedToUser($user->id, $userRoles);
        });
        
        $totalTasks = $userTasks->count();
        $completedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
        })->count();
        $skippedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'skipped';
        })->count();
        $failedTasks = $userTasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'failed';
        })->count();
        $pendingTasks = $totalTasks - $completedTasks - $skippedTasks - $failedTasks;
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        
        // Group by category với phân biệt task type
        $tasksByCategory = $userTasks->groupBy(function($task) {
            $categoryName = $task->category ? $task->category->name : 'Không phân loại';
            return $categoryName;
        });
        
        // Priority stats
        $priorityStats = [];
        foreach (['urgent', 'high', 'medium', 'low'] as $priority) {
            $priorityTasks = $userTasks->where('priority', $priority);
            $priorityCompleted = $priorityTasks->filter(function($task) {
                return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
            })->count();
            
            $priorityStats[$priority] = [
                'total' => $priorityTasks->count(),
                'completed' => $priorityCompleted,
                'rate' => $priorityTasks->count() > 0 ? round(($priorityCompleted / $priorityTasks->count()) * 100) : 0
            ];
        }
        
        return [
            'userTasks' => $userTasks,
            'tasksByCategory' => $tasksByCategory,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'skippedTasks' => $skippedTasks,
            'failedTasks' => $failedTasks,
            'pendingTasks' => $pendingTasks,
            'completionRate' => $completionRate,
            'priorityStats' => $priorityStats
        ];
    }

    /**
     * Lấy hoạt động cuối cùng của user
     */
    protected function getLastActivity($userId, $date)
    {
        $lastCompletion = UserTaskCompletion::where('user_id', $userId)
            ->where('completion_date', $date)
            ->orderBy('completed_at_time', 'desc')
            ->first();
            
        if (!$lastCompletion || !$lastCompletion->completed_at_time) {
            return null;
        }
        
        return Carbon::parse($lastCompletion->completed_at_time)->format('H:i');
    }
}