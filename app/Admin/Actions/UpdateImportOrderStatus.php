<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\ImportOrder;

class UpdateImportOrderStatus extends BatchAction
{
    public $name = 'Cập nhật trạng thái';

    public function handle(Collection $collection, Request $request)
    {
        $status = $request->get('import_status');
        $validStatuses = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'in_transit' => 'Đang vận chuyển',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];

        if (!array_key_exists($status, $validStatuses)) {
            return $this->response()->error('Trạng thái không hợp lệ.')->refresh();
        }

        foreach ($collection as $model) {
            $model->import_status = $status;
            $model->save();
        }

        $statusLabel = $validStatuses[$status];
        return $this->response()->success("Đã cập nhật {$collection->count()} phiếu nhập thành '{$statusLabel}'!")->refresh();
    }

    public function form()
    {
        $this->select('import_status', 'Trạng thái mới')->options([
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'in_transit' => 'Đang vận chuyển',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ])->required();
    }
}