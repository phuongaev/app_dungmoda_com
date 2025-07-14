<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class RejectLeaveAction extends RowAction
{
    public $name = 'Từ chối';

    public function handle(Model $model)
    {
        $notes = request('admin_notes');
        
        $response = app('App\Admin\Controllers\LeaveRequestController')->reject(request(), $model->id);
        $result = $response->getData();
        
        if ($result->status) {
            return $this->response()->success($result->message)->refresh();
        } else {
            return $this->response()->error($result->message);
        }
    }

    public function form()
    {
        $this->textarea('admin_notes', 'Lý do từ chối')->rows(3)->placeholder('Nhập lý do từ chối...');
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-danger"><i class="fa fa-times"></i> Từ chối</a>';
    }
}