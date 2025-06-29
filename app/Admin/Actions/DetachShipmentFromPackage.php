<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DetachShipmentFromPackage extends RowAction
{
    public $name = 'Xóa liên kết';

    public function handle(Model $model, Request $request)
    {
        $packageId = $request->route('package');
        $model->packages()->detach($packageId);

        return $this->response()->success('Xóa liên kết mã vận đơn với mã kiện hàng thành công!')->refresh();
    }

    public function dialog()
    {
        $this->confirm('Có chắc chắn xóa liên kết mã vận đơn với mã kiện hàng không?');
    }
}