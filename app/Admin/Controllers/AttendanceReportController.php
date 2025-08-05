<?php

namespace App\Admin\Controllers;

use App\Models\Attendance;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Role;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function __construct()
    {
        // Middleware để chỉ admin mới được truy cập
        $this->middleware(function ($request, $next) {
            if (!Admin::user()->isRole('administrator')) {
                admin_error('Không có quyền', 'Bạn không có quyền truy cập báo cáo này!');
                return back();
            }
            return $next($request);
        });
    }

    /**
     * Trang báo cáo chính
     */
    public function index(Content $content, Request $request)
    {
        // Lấy tham số filter từ request
        $filters = $this->getFilters($request);
        
        // Lấy dữ liệu báo cáo
        $reportData = $this->getReportData($filters);
        
        return $content
            ->title('Báo cáo chấm công')
            ->description('Thống kê và phân tích dữ liệu chấm công')
            ->body(view('admin.attendance-report.index', compact('reportData', 'filters')));
    }

    /**
     * Lấy các tham số filter
     */
    private function getFilters(Request $request)
    {
        $now = Carbon::now();
        
        return [
            'month' => $request->get('month', $now->format('Y-m')),
            'start_date' => $request->get('start_date', $now->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', $now->endOfMonth()->format('Y-m-d')),
            'user_id' => $request->get('user_id'),
            'role_id' => $request->get('role_id'),
            'filter_type' => $request->get('filter_type', 'month') // month hoặc custom
        ];
    }

    /**
     * Lấy dữ liệu báo cáo
     */
    private function getReportData($filters)
    {
        // Xác định khoảng thời gian với validation
        try {
            if ($filters['filter_type'] === 'month' && !empty($filters['month'])) {
                $startDate = Carbon::parse($filters['month'] . '-01')->startOfMonth();
                $endDate = Carbon::parse($filters['month'] . '-01')->endOfMonth();
            } else {
                $startDate = !empty($filters['start_date']) 
                    ? Carbon::parse($filters['start_date'])->startOfDay()
                    : Carbon::now()->startOfMonth();
                $endDate = !empty($filters['end_date'])
                    ? Carbon::parse($filters['end_date'])->endOfDay()
                    : Carbon::now()->endOfMonth();
            }
        } catch (\Exception $e) {
            // Fallback to current month if date parsing fails
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // Query cơ bản với validation
        $query = Attendance::with(['user.roles'])
            ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('user_id')
            ->whereNotNull('work_date');

        // Filter theo user với validation
        if (!empty($filters['user_id']) && is_numeric($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Lấy dữ liệu attendance
        $attendances = $query->get();

        // Filter theo role nếu có với validation
        if (!empty($filters['role_id']) && is_numeric($filters['role_id'])) {
            $attendances = $attendances->filter(function ($attendance) use ($filters) {
                try {
                    return $attendance->user && 
                           $attendance->user->roles && 
                           $attendance->user->roles->contains('id', $filters['role_id']);
                } catch (\Exception $e) {
                    return false;
                }
            });
        }

        // Tính toán số liệu cho từng nhân viên
        $employeeStats = $this->calculateEmployeeStats($attendances, $startDate, $endDate);
        
        // Tính top 3 nhân viên
        $topEmployees = $this->getTopEmployees($employeeStats);
        
        // Tổng quan
        $overview = $this->getOverview($attendances, $startDate, $endDate);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'days_count' => $startDate->diffInDays($endDate) + 1
            ],
            'overview' => $overview,
            'employee_stats' => $employeeStats,
            'top_employees' => $topEmployees,
            'users' => Administrator::where('id', '!=', 1)->get(), // Exclude super admin
            'roles' => Role::all()
        ];
    }

    /**
     * Tính toán số liệu cho từng nhân viên
     */
    private function calculateEmployeeStats($attendances, $startDate, $endDate)
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        $stats = [];
        
        // Lọc bỏ các attendance không có user_id hợp lệ
        $validAttendances = $attendances->filter(function($attendance) {
            return $attendance->user_id && $attendance->user;
        });
        
        // Group theo user_id
        $attendancesByUser = $validAttendances->groupBy('user_id');
        
        foreach ($attendancesByUser as $userId => $userAttendances) {
            // Kiểm tra user_id hợp lệ
            if (!$userId || $userAttendances->isEmpty()) {
                continue;
            }
            
            $user = $userAttendances->first()->user;
            if (!$user) {
                continue;
            }
            
            // Tính tổng giờ làm việc và làm tròn xuống
            $totalMinutes = $userAttendances->sum(function ($attendance) {
                $hours = $attendance->work_hours ?? 0;
                $minutes = $attendance->work_minutes ?? 0;
                return ($hours * 60) + $minutes;
            });
            
            // Luôn làm tròn xuống (floor)
            $totalHoursDecimal = $totalMinutes / 60;
            $displayHours = floor($totalHoursDecimal); // Luôn làm tròn xuống
            $remainingMinutes = $totalMinutes % 60;
            
            // Format hiển thị: luôn hiển thị chỉ số giờ đã làm tròn xuống
            $workTimeDisplay = $displayHours . 'h';
            
            // Giữ nguyên cho mục đích sắp xếp
            $totalHours = floor($totalMinutes / 60);
            $originalMinutes = $totalMinutes % 60;
            
            // Đếm số ngày làm việc thực tế (distinct work_date có check-in)
            $workDates = $userAttendances
                ->filter(function($attendance) {
                    return $attendance->check_in_time !== null && $attendance->work_date;
                })
                ->pluck('work_date')
                ->filter() // Loại bỏ null values
                ->unique();
                
            $workDays = $workDates->count();
            
            // Đếm số ngày làm việc đầy đủ (distinct work_date có ít nhất 1 session checked_out)
            $completeDates = $userAttendances
                ->filter(function($attendance) {
                    return $attendance->status === 'checked_out' && $attendance->work_date;
                })
                ->pluck('work_date')
                ->filter() // Loại bỏ null values
                ->unique();
                
            $completeDays = $completeDates->count();
            
            // Tổng số lần chấm công (để thống kê thêm)
            $totalCheckIns = $userAttendances->filter(function($attendance) {
                return $attendance->check_in_time !== null;
            })->count();
            
            // Tỷ lệ chấm công
            $attendanceRate = $totalDays > 0 ? round(($workDays / $totalDays) * 100, 1) : 0;
            
            // Giờ làm trung bình mỗi ngày (làm tròn xuống)
            $avgHoursPerDay = $workDays > 0 ? floor(($totalMinutes / 60) / $workDays * 10) / 10 : 0; // Làm tròn xuống 1 chữ số
            
            // Lấy roles một cách an toàn
            $roles = 'N/A';
            try {
                if ($user->roles && $user->roles->count() > 0) {
                    $roles = $user->roles->pluck('name')->filter()->join(', ');
                }
            } catch (\Exception $e) {
                $roles = 'N/A';
            }
            
            $stats[] = [
                'user' => $user,
                'roles' => $roles ?: 'N/A',
                'total_hours' => $displayHours, // Giờ đã làm tròn xuống
                'total_minutes' => 0, // Không hiển thị phút
                'total_work_time' => $workTimeDisplay, // Hiển thị đã làm tròn xuống
                'total_work_time_original' => sprintf('%02d:%02d', $totalHours, $originalMinutes), // Giữ nguyên cho sort
                'work_days' => $workDays, // Số ngày làm việc thực tế
                'complete_days' => $completeDays, // Số ngày hoàn thành
                'total_check_ins' => $totalCheckIns, // Tổng lần chấm công
                'absent_days' => max(0, $totalDays - $workDays),
                'attendance_rate' => $attendanceRate,
                'avg_hours_per_day' => $avgHoursPerDay,
                'total_minutes_for_sort' => $totalMinutes
            ];
        }
        
        // Sắp xếp theo tổng giờ làm việc (giảm dần)
        usort($stats, function ($a, $b) {
            return $b['total_minutes_for_sort'] <=> $a['total_minutes_for_sort'];
        });
        
        return $stats;
    }

    /**
     * Lấy top 3 nhân viên làm việc nhiều nhất
     */
    private function getTopEmployees($employeeStats)
    {
        return array_slice($employeeStats, 0, 3);
    }

    /**
     * Tính tổng quan
     */
    private function getOverview($attendances, $startDate, $endDate)
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalEmployees = Administrator::where('id', '!=', 1)->count();
        
        // Lọc attendances hợp lệ
        $validAttendances = $attendances->filter(function($attendance) {
            return $attendance->user_id && $attendance->work_date;
        });
        
        // Tổng giờ làm việc của tất cả nhân viên (làm tròn xuống)
        $totalMinutes = $validAttendances->sum(function ($attendance) {
            $hours = $attendance->work_hours ?? 0;
            $minutes = $attendance->work_minutes ?? 0;
            return ($hours * 60) + $minutes;
        });
        
        // Luôn làm tròn xuống cho overview
        $totalHoursDecimal = $totalMinutes / 60;
        $overviewHours = floor($totalHoursDecimal); // Làm tròn xuống
        
        // Format hiển thị: chỉ hiển thị số giờ đã làm tròn xuống
        $overviewWorkTime = $overviewHours . 'h';
        
        // Tổng lượt chấm công
        $totalCheckIns = $validAttendances->filter(function($attendance) {
            return $attendance->check_in_time !== null;
        })->count();
        
        // Tổng ngày làm việc thực tế (distinct work_date + user_id)
        $uniqueWorkDays = $validAttendances
            ->filter(function($attendance) {
                return $attendance->check_in_time !== null;
            })
            ->map(function($attendance) {
                return $attendance->user_id . '_' . $attendance->work_date;
            })
            ->unique()
            ->count();
        
        // Tổng ngày làm việc hoàn thành (distinct work_date + user_id có ít nhất 1 session checked_out)
        $uniqueCompleteDays = $validAttendances
            ->filter(function($attendance) {
                return $attendance->status === 'checked_out';
            })
            ->map(function($attendance) {
                return $attendance->user_id . '_' . $attendance->work_date;
            })
            ->unique()
            ->count();
        
        // Giờ làm trung bình mỗi lần chấm công (làm tròn xuống)
        $avgHoursPerCheckIn = $totalCheckIns > 0 ? floor(($totalMinutes / 60) / $totalCheckIns * 10) / 10 : 0;
        
        return [
            'total_employees' => $totalEmployees,
            'period_days' => $totalDays,
            'total_work_time' => $overviewWorkTime, // Hiển thị đã làm tròn
            'total_check_ins' => $totalCheckIns,
            'total_work_days' => $uniqueWorkDays, // Tổng ngày làm việc thực tế
            'total_complete_days' => $uniqueCompleteDays,
            'avg_hours_per_check_in' => $avgHoursPerCheckIn
        ];
    }
}