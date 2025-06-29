<?php

namespace App\Admin\Extensions;

use Encore\Admin\Widgets\Widget;
use App\Models\Package;

class PendingPackagesWidget extends Widget
{
    protected $view = 'admin.widgets.pending-packages';

    public function script()
    {
        return <<<SCRIPT
        // Auto refresh mỗi 5 phút
        setTimeout(function() {
            location.reload();
        }, 300000);
        SCRIPT;
    }

    public function content()
    {
        // Lấy các kiện hàng cần xử lý
        $pendingPackages = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Thống kê
        $stats = [
            'pending' => $pendingPackages->where('package_status', 'pending')->count(),
            'delivered_vn' => $pendingPackages->where('package_status', 'delivered_vn')->count(),
            'total' => $pendingPackages->count()
        ];

        return view($this->view, [
            'packages' => $pendingPackages,
            'stats' => $stats
        ]);
    }
}