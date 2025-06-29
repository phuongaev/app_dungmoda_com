<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Shipment;

class UpdateShipmentStatus extends BatchAction
{
    public $name = 'Cập nhật trạng thái';

    /**
     * Xử lý logic cập nhật
     */
    public function handle(Collection $collection, Request $request)
    {
        // Lấy trạng thái từ form popup
        $status = $request->get('shipment_status');

        // Lấy danh sách trạng thái hợp lệ từ form
        $validStatuses = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã gửi',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy'
        ];

        // Kiểm tra xem trạng thái có hợp lệ không
        if (!$status || !array_key_exists($status, $validStatuses)) {
            return $this->response()->error('Trạng thái không hợp lệ.')->refresh();
        }

        // Vòng lặp qua các Vận đơn đã chọn và cập nhật
        foreach ($collection as $model) {
            $model->shipment_status = $status;
            $model->save();
        }

        $count = $collection->count();
        $statusLabel = $validStatuses[$status];

        // Trả về thông báo thành công
        return $this->response()->success("Đã cập nhật {$count} vận đơn thành '{$statusLabel}'!")->refresh();
    }

    /**
     * Định nghĩa form popup
     */
    public function form()
    {
        // Sử dụng danh sách trạng thái anh cung cấp
        $this->select('shipment_status', 'Chọn trạng thái mới')->options([
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã gửi',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy'
        ])->required();
    }
}