<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use App\Models\Package;

class UpdatePackageStatus extends BatchAction
{
    public $name = 'Cập nhật trạng thái'; // Tên hiển thị trong menu dropdown

    /**
     * Phương thức này để xử lý logic khi người dùng bấm submit.
     */
    public function handle(Collection $collection, Request $request)
    {
        $status = $request->get('package_status');

        if (!$status) {
            return $this->response()->error('Vui lòng chọn trạng thái!')->refresh();
        }

        $validStatuses = [
            'pending' => 'Chờ xử lý',
            'in_transit' => 'Đang vận chuyển',
            'delivered_vn' => 'Nhập kho VN',
            'delivered' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy'
        ];

        if (!array_key_exists($status, $validStatuses)) {
            return $this->response()->error('Trạng thái không hợp lệ!')->refresh();
        }

        // $collection chứa tất cả các model đã được chọn
        foreach ($collection as $model) {
            $model->package_status = $status;
            $model->save();
        }
        
        $count = $collection->count();
        $statusLabel = $validStatuses[$status];

        return $this->response()->success("Đã cập nhật {$count} kiện hàng thành '{$statusLabel}'!")->refresh();
    }

    /**
     * Phương thức này để định nghĩa các trường trong form popup.
     */
    public function form()
    {
        $this->select('package_status', 'Chọn trạng thái')->options([
            'pending' => 'Chờ xử lý',
            'in_transit' => 'Đang vận chuyển',
            'delivered_vn' => 'Nhập kho VN',
            'delivered' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy'
        ])->required();
    }
}