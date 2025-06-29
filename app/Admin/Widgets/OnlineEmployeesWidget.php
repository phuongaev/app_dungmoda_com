<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Widget;
use App\Models\Attendance;
use Encore\Admin\Auth\Database\Administrator;

class OnlineEmployeesWidget extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin.widgets.online-employees';

    /**
     * @var array
     */
    protected $data = [];

    public function __construct($title = 'Nhân viên đang Online')
    {
        $this->title = $title;
        $this->loadData();
    }

    protected function loadData()
    {
        $today = today();
        
        // Lấy nhân viên đã check-in nhưng chưa check-out hôm nay
        $onlineEmployees = Attendance::with('user')
            ->where('work_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->orderBy('check_in_time', 'desc')
            ->get();

        $this->data = [
            'online_employees' => $onlineEmployees,
            'total_online' => $onlineEmployees->count(),
            'total_employees' => Administrator::where('id', '>', 1)->count() // Bỏ qua admin
        ];
    }

    /**
     * @return string
     */
    public function render()
    {
        return view($this->view, $this->data)->render();
    }
}