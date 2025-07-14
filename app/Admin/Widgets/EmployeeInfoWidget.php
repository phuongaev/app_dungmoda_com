<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Widget;
use App\Models\Attendance;
use App\Models\EveningShift;
use Carbon\Carbon;

class EmployeeInfoWidget extends Widget
{
    protected $view = 'admin.widgets.employee-info';
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the data for the widget
     */
    public function data()
    {
        $today = Carbon::today();
        
        // Lấy attendance hôm nay
        $todayAttendance = Attendance::where('user_id', $this->user->id)
            ->where('work_date', $today)
            ->first();

        // Lấy ca trực tuần này
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        
        $thisWeekShifts = EveningShift::where('admin_user_id', $this->user->id)
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->orderBy('shift_date')
            ->get();

        // Lấy ca trực tháng này
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        
        $thisMonthShifts = EveningShift::where('admin_user_id', $this->user->id)
            ->whereBetween('shift_date', [$monthStart, $monthEnd])
            ->count();

        // Lấy attendance tháng này
        $thisMonthAttendance = Attendance::where('user_id', $this->user->id)
            ->whereYear('work_date', $today->year)
            ->whereMonth('work_date', $today->month)
            ->where('status', 'checked_out')
            ->count();

        return [
            'user' => $this->user,
            'today_attendance' => $todayAttendance,
            'week_shifts' => $thisWeekShifts,
            'month_shifts_count' => $thisMonthShifts,
            'month_attendance_count' => $thisMonthAttendance,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'today' => $today
        ];
    }

    /**
     * Render the widget
     */
    public function render()
    {
        return view($this->view, $this->data())->render();
    }
}