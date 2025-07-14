<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class ApproveSwapAction extends RowAction
{
    public $name = 'Duyệt';

    public function handle(Model $model)
    {
        $notes = request('admin_notes', '');
        
        try {
            $controller = new \App\Admin\Controllers\ShiftSwapRequestController();
            $response = $controller->approve(request(), $model->id);
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
        $this->textarea('admin_notes', 'Ghi chú duyệt')->rows(3)->placeholder('Nhập ghi chú khi duyệt (không bắt buộc)...');
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-success"><i class="fa fa-check"></i> Duyệt</a>';
    }
}