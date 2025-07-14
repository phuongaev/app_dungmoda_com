<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;

use App\Models\Attendance;
use App\Models\Package;

use App\Admin\Widgets\DailyTasksWidget;
use App\Admin\Widgets\OnlineEmployeesWidget;
use App\Admin\Widgets\CashFlowWidget;
use App\Admin\Widgets\ShiftCalendarDashboardWidget;

use App\Admin\Widgets\UpcomingRequestsWidget;
use App\Admin\Widgets\EmployeeInfoWidget;
use App\Admin\Widgets\EmployeeRequestsWidget;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        // Lấy thông tin user và role của họ
        $user = Admin::user();
        $userRoles = $user ? $user->roles->pluck('slug')->toArray() : [];

        // Check if user is admin
        $isAdmin = in_array('administrator', $userRoles);

        return $content
            ->title('Tổng quan')
            ->description('Bảng điều khiển...')
            // ->row(Dashboard::title())

            // Row 1: Alert kiện hàng (full width)
            ->row(function (Row $row) use ($isAdmin, $user) {
                // Cột 1
                $row->column(6, function (Column $column) use ($isAdmin, $user) {
                    // Chấm công
                    $column->append($this->attendanceWidget());

                    // Danh sách nhân viên đang làm việc
                    $column->append(new OnlineEmployeesWidget());

                    // Kiện hàng cần xử lý
                    if ($isAdmin) {
                        // $column->append($this->packagesWidget());
                        $column->append(new CashFlowWidget());
                    }

                    if (!$isAdmin) {
                        // Hiển thị thông tin cá nhân cho nhân viên
                        $column->append(new EmployeeInfoWidget($user));
                        $column->append(new EmployeeRequestsWidget($user));
                    }


                });
                
                // Cột 2
                $row->column(6, function (Column $column) {
                    // Đơn hàng cần xử lý
                    $column->append(new DailyTasksWidget());

                    // Danh sách nhân viên trực ca tối
                    $column->append(new ShiftCalendarDashboardWidget());
                });             
            })
 



            // Row 2: Widget đơn xin nghỉ và hoán đổi ca (chỉ admin mới thấy)
            ->row(function (Row $row) use ($isAdmin) {
                if ($isAdmin) {
                    $row->column(6, function (Column $column) {
                        $column->append(new UpcomingRequestsWidget());
                    });
                }
            })




            // Row 3: Calendar hoặc thông tin khác
            ->row(function (Row $row) use ($isAdmin, $user) {
                
            });

   
   
    }


    // Chấm công
    protected function attendanceWidget()
    {
        $userId = Admin::user()->id;
        $today = today();
        
        // Lấy tất cả sessions hôm nay
        $allSessions = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->orderBy('check_in_time', 'desc')
            ->get();
            
        // Session hiện tại (chưa checkout)
        $currentSession = $allSessions->whereNull('check_out_time')->first();
        
        // Tính tổng thời gian làm việc
        $totalMinutes = $allSessions->where('status', 'checked_out')->sum(function($session) {
            return ($session->work_hours * 60) + $session->work_minutes;
        });
        
        $totalHours = intval($totalMinutes / 60);
        $totalMins = $totalMinutes % 60;
        $totalWorkTime = sprintf('%02d:%02d', $totalHours, $totalMins);
        
        $canCheckIn = !$currentSession;
        $canCheckOut = !!$currentSession;
        
        return view('admin.widgets.attendance', compact(
            'currentSession', 
            'allSessions', 
            'totalWorkTime', 
            'canCheckIn', 
            'canCheckOut'
        ));
    }



    /**
     * Row thông tin hệ thống
     */
    private function systemRow()
    {
        return function (Row $row) {
            $row->column(4, function (Column $column) {
                $column->append(Dashboard::environment());
            });

            $row->column(4, function (Column $column) {
                $column->append(Dashboard::extensions());
            });

            $row->column(4, function (Column $column) {
                $column->append(Dashboard::dependencies());
            });
        };
    }


    /**
     * Row kiện hàng cần xử lý
     */
    private function packagesWidget()
    {
        $data = $this->getPackagesData();
        return view('admin.dashboard.pending-packages', $data);
    }


    /**
     * Lấy dữ liệu kiện hàng
     */
    private function getPackagesData()
    {
        $pendingPackages = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'pending' => $pendingPackages->where('package_status', 'pending')->count(),
            'delivered_vn' => $pendingPackages->where('package_status', 'delivered_vn')->count(),
            'total' => $pendingPackages->count()
        ];

        $urgentPackages = $pendingPackages->filter(function($package) {
            return $package->created_at->lt(now()->subHours(24));
        });

        return compact('pendingPackages', 'stats', 'urgentPackages');
    }

}
