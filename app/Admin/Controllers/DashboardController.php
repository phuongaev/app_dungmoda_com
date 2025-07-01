<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

use App\Models\Package;

class DashboardController extends Controller
{
    /**
     * Dashboard tổng quan kiện hàng
     */
    public function packages(Content $content)
    {
        $data = $this->getDetailedPackagesData();
        
        return $content
            ->title('Thống kê Kiện hàng')
            ->description('Thống kê tổng quan kiện hàng')
            ->row(function (Row $row) use ($data) {
                $row->column(12, function (Column $column) use ($data) {
                    $column->append(view('admin.dashboard.packages-full', $data));
                });
            });
    }

    /**
     * Dashboard báo cáo
     */
    public function reports(Content $content)
    {
        return $content
            ->title('Dashboard Báo cáo')
            ->description('Thống kê và báo cáo')
            ->row(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->append(view('admin.dashboard.monthly-stats'));
                });
                $row->column(6, function (Column $column) {
                    $column->append(view('admin.dashboard.yearly-stats'));
                });
            });
    }

    /**
     * Dashboard doanh thu
     */
    public function sales(Content $content)
    {
        return $content
            ->title('Dashboard Doanh thu')
            ->description('Thống kê doanh thu và bán hàng')
            ->row(function (Row $row) {
                $row->column(8, function (Column $column) {
                    $column->append(view('admin.dashboard.sales-chart'));
                });
                $row->column(4, function (Column $column) {
                    $column->append(view('admin.dashboard.sales-summary'));
                });
            });
    }

    /**
     * Lấy dữ liệu chi tiết kiện hàng
     */
    private function getDetailedPackagesData()
    {
        // Lấy TẤT CẢ kiện hàng để thống kê
        $allPackages = Package::all();
        
        // Lấy kiện hàng cần xử lý (với shipments)
        $query = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->with('shipments'); // Eager load shipments
        
        // Thêm filter theo trạng thái
        if (request('status')) {
            $query->where('package_status', request('status'));
        }
        
        // Thêm search mã kiện
        if (request('search')) {
            $query->where('package_code', 'like', '%' . request('search') . '%');
        }

        $pendingPackages = $query->orderBy('created_at', 'desc')->paginate(20);

        // Thống kê tổng theo TẤT CẢ trạng thái
        $stats = [
            'pending' => $allPackages->where('package_status', 'pending')->count(),
            'delivered_vn' => $allPackages->where('package_status', 'delivered_vn')->count(),
            'in_transit' => $allPackages->where('package_status', 'in_transit')->count(),
            'delivered' => $allPackages->where('package_status', 'delivered')->count(),
            'cancelled' => $allPackages->where('package_status', 'cancelled')->count(),
        ];

        // Tính toán các số liệu quan trọng
        $stats['total_need_action'] = $stats['pending'] + $stats['delivered_vn']; // Cần xử lý
        $stats['total_all'] = array_sum($stats); // Tổng tất cả
        $stats['completion_rate'] = $stats['total_all'] > 0 ? round(($stats['delivered'] / $stats['total_all']) * 100, 1) : 0;

        // Thống kê theo đối tác (chỉ tổng số kiện)
        // $partnerTotals = Package::selectRaw('shipping_partner, count(*) as total')
        //     ->groupBy('shipping_partner')
        //     ->pluck('total', 'shipping_partner')
        //     ->toArray();
        $partnerTotals = Package::selectRaw('shipping_partner, SUM(weight) as total')
                    ->groupBy('shipping_partner')
                    ->pluck('total', 'shipping_partner')
                    ->toArray();

        // Thống kê theo tuần (7 ngày gần đây) - tất cả trạng thái
        $weeklyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $weeklyStats[$date] = Package::whereDate('created_at', $date)->count();
        }

        // Kiện hàng quá hạn (pending hoặc delivered_vn > 24h)
        $urgentPackages = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        // Kiện hàng rất khẩn cấp (> 48h)
        $criticalPackages = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->where('created_at', '<', now()->subHours(48))
            ->count();

        // Top 5 kiện hàng cần xử lý gấp nhất
        $topUrgentPackages = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();

        return compact(
            'pendingPackages', 
            'stats', 
            'partnerTotals', 
            'urgentPackages', 
            'criticalPackages',
            'topUrgentPackages'
        );
    }





}