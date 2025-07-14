<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class CancelLeaveAction extends RowAction
{
    public $name = 'Hủy';

    public function handle(Model $model)
    {
        $notes = request('admin_notes', '');
        
        try {
            $controller = new \App\Admin\Controllers\LeaveRequestController();
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

    public function form()
    {
        $this->textarea('admin_notes', 'Lý do hủy')->rows(3)->placeholder('Nhập lý do hủy đơn đã duyệt...')->required();
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-warning"><i class="fa fa-ban"></i> Hủy</a>';
    }
}