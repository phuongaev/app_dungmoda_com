<?php
// app/Admin/Controllers/DailyTaskProgressController.php (Optimized - Clean)

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use App\Services\TaskService;
use App\Repositories\TaskRepository;
use Encore\Admin\Auth\Database\Administrator;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyTaskProgressController extends Controller
{
    protected $title = 'Tiến độ hoàn thành công việc nhân viên';
    protected $taskService;
    protected $taskRepository;

    public function __construct(TaskService $taskService, TaskRepository $taskRepository)
    {
        $this->taskService = $taskService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Dashboard tiến độ tổng quan
     */
    public function index(Content $content)
    {
        $today = Carbon::today();
        $data = $this->taskService->getDailyOverviewStats($today);
        
        return $content
            ->header('Tiến độ hoàn thành công việc')
            ->description('Báo cáo chi tiết tiến độ làm việc của nhân viên')
            ->view('admin.daily-task-progress.index', $data);
    }

    /**
     * Chi tiết tiến độ theo ngày
     */
    public function daily(Request $request, Content $content)
    {
        $targetDate = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        $user = Admin::user();
        
        // Lấy data từ TaskService
        $data = $this->taskService->getDailyTasksData($user, $targetDate);
        
        return $content
            ->header('Tiến độ công việc cá nhân')
            ->description('Theo dõi công việc ngày ' . $targetDate->format('d/m/Y'))
            ->view('admin.daily-task-progress.daily', $data);
    }

    /**
     * Báo cáo tiến độ theo tuần
     */
    public function weekly(Request $request, Content $content)
    {
        $startWeek = $request->get('week') ? Carbon::parse($request->get('week')) : Carbon::now()->startOfWeek();
        $endWeek = $startWeek->copy()->endOfWeek();
        
        $weeklyData = $this->buildWeeklyProgressData($startWeek, $endWeek);
        
        return $content
            ->header('Báo cáo tiến độ theo tuần')
            ->description('Thống kê tiến độ từ ' . $startWeek->format('d/m/Y') . ' đến ' . $endWeek->format('d/m/Y'))
            ->view('admin.daily-task-progress.weekly', $weeklyData);
    }

    /**
     * Chi tiết tiến độ của một user cụ thể
     */
    public function userDetail(Request $request, $userId, Content $content)
    {
        $user = Administrator::findOrFail($userId);
        $targetDate = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        
        $data = $this->taskService->getUserDetailData($user, $targetDate);
        
        return $content
            ->header("Chi tiết tiến độ: {$user->name}")
            ->description("Thống kê chi tiết công việc ngày {$targetDate->format('d/m/Y')}")
            ->view('admin.daily-task-progress.user_detail', $data);
    }

    /**
     * API endpoint để lấy stats nhanh cho AJAX
     */
    public function getQuickStats(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $user = Admin::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        try {
            $userTasks = $this->taskService->getUserTasksForDate($user, Carbon::parse($date));
            $stats = $this->taskService->calculateCompletionStats($userTasks);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách tasks cần review
     */
    public function getTasksNeedingReview()
    {
        $tasksNeedingReview = $this->taskRepository->getTasksNeedingReview();
        
        return response()->json([
            'success' => true,
            'data' => $tasksNeedingReview->map(function($completion) {
                return [
                    'id' => $completion->id,
                    'task_title' => $completion->dailyTask->title,
                    'user_name' => $completion->user->name,
                    'completion_date' => $completion->completion_date->format('d/m/Y'),
                    'notes' => $completion->notes,
                    'review_url' => admin_url("daily-tasks/toggle-review/{$completion->id}/0")
                ];
            })
        ]);
    }

    /**
     * Lấy overdue one-time tasks
     */
    public function getOverdueTasks()
    {
        $overdueTasks = $this->taskRepository->getOverdueOneTimeTasks();
        
        return response()->json([
            'success' => true,
            'data' => $overdueTasks->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'category' => $task->category ? $task->category->name : 'N/A',
                    'end_date' => $task->end_date->format('d/m/Y'),
                    'days_overdue' => $task->end_date->diffInDays(Carbon::today()),
                    'edit_url' => admin_url("daily-tasks/{$task->id}/edit")
                ];
            })
        ]);
    }

    /**
     * Xây dựng dữ liệu tiến độ theo tuần
     */
    private function buildWeeklyProgressData($startWeek, $endWeek)
    {
        $dailyStats = [];
        $current = $startWeek->copy();
        
        while ($current->lte($endWeek)) {
            $dailyStats[$current->format('Y-m-d')] = $this->taskService->getDailyOverviewStats($current);
            $current->addDay();
        }
        
        // Tính toán summary stats
        $totalUsers = Administrator::count();
        $weeklyCompletions = collect($dailyStats)->sum('completed_count');
        $weeklyTotalTasks = collect($dailyStats)->sum('total_completions');
        $weeklyCompletionRate = $weeklyTotalTasks > 0 ? 
            round(($weeklyCompletions / $weeklyTotalTasks) * 100) : 0;
        
        return [
            'start_week' => $startWeek,
            'end_week' => $endWeek,
            'daily_stats' => $dailyStats,
            'total_users' => $totalUsers,
            'weekly_completions' => $weeklyCompletions,
            'weekly_total_tasks' => $weeklyTotalTasks,
            'weekly_completion_rate' => $weeklyCompletionRate,
            'completion_rate_by_category' => $this->buildWeeklyCompletionRateByCategory($startWeek, $endWeek)
        ];
    }

    /**
     * Xây dựng completion rate theo category trong tuần
     */
    private function buildWeeklyCompletionRateByCategory($startDate, $endDate)
    {
        return $this->taskRepository->getCompletionRateByCategory($startDate)
            ->merge($this->taskRepository->getCompletionRateByCategory($endDate))
            ->groupBy('category_name')
            ->map(function($group) {
                $totalTasks = $group->sum('total_tasks');
                $completedTasks = $group->sum('completed_tasks');
                $rate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
                
                return [
                    'category_name' => $group->first()->category_name,
                    'category_color' => $group->first()->category_color,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $rate
                ];
            });
    }
}