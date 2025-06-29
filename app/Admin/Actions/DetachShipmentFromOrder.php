<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DetachShipmentFromOrder extends RowAction
{
    public $name = 'Xóa liên kết';

    public function handle(Model $model, Request $request)
    {
        $importOrderId = $request->route('import_order');
        $model->importOrders()->detach($importOrderId);

        return $this->response()->success('Xóa liên kết mã vận đơn với phiếu nhập thành công!')->refresh();
    }

    public function dialog()
    {
        $this->confirm('Có chắc chắn xóa liên kết mã vận đơn với mã phiếu nhập không?');
    }
}