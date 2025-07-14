<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class EmployeeCancelLeaveAction extends RowAction
{
    public $name = 'Hủy đơn';

    public function handle(Model $model)
    {
        try {
            $controller = new \App\Admin\Controllers\EmployeeLeaveRequestController();
            $response = $controller->cancel(request(), $model->id);
            $result = $response->getData();
            
            if ($result->status) {
                return $this->response()->success($result->message)->refresh();
            } else {
                return $this->response()->error($result->message);
            }
        } catch (\Exception $e) {
            return $this->response()->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function dialog()
    {
        $this->confirm('Bạn có chắc chắn muốn hủy đơn xin nghỉ này?');
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-warning"><i class="fa fa-ban"></i> Hủy đơn</a>';
    }
}