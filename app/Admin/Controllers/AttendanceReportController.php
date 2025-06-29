<?php

namespace App\Admin\Controllers;

use App\Models\Attendance;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Auth\Database\Administrator;

class AttendanceReportController extends AdminController
{
    protected $title = 'Báo cáo chấm công';

    protected function grid()
    {
        $grid = new Grid(new Administrator());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Nhân viên'));
        
        // Tổng giờ làm tháng này
        $grid->column('total_hours_this_month', __('Tổng giờ tháng này'))
            ->display(function () {
                $total = Attendance::where('user_id', $this->id)
                    ->thisMonth()
                    ->sum(\DB::raw('work_hours * 60 + work_minutes'));
                    
                $hours = intval($total / 60);
                $minutes = $total % 60;
                
                return sprintf('%02d:%02d', $hours, $minutes);
            });

        // Số ngày đi làm tháng này
        $grid->column('work_days_this_month', __('Số ngày làm'))
            ->display(function () {
                return Attendance::where('user_id', $this->id)
                    ->thisMonth()
                    ->where('status', 'checked_out')
                    ->count();
            });

        $grid->disableCreateButton();
        $grid->disableActions();

        return $grid;
    }
}