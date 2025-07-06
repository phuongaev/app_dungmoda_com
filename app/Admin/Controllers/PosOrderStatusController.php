<?php

namespace App\Admin\Controllers;

use App\Models\PosOrderStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PosOrderStatusController extends AdminController
{
    protected $title = 'Quản lý trạng thái đơn hàng';

    protected function grid()
    {
        $grid = new Grid(new PosOrderStatus());

        $grid->model()->orderBy('sort_order');

        $grid->column('status_code', 'Mã trạng thái')
            ->sortable()
            ->width(100);

        $grid->column('status_name', 'Tên trạng thái')
            ->editable()
            ->width(150);

        $grid->column('status_color', 'Màu hiển thị')
            ->display(function ($color) {
                return "<span class='label label-{$color}'>{$color}</span>";
            })
            ->width(120);

        $grid->column('description', 'Mô tả')
            ->limit(50)
            ->editable('textarea')
            ->width(200);

        $grid->column('sort_order', 'Thứ tự')
            ->editable()
            ->sortable()
            ->width(80);

        $grid->column('is_active', 'Hoạt động')
            ->display(function ($active) {
                return $active ? 
                    '<span class="label label-success">Có</span>' : 
                    '<span class="label label-danger">Không</span>';
            })
            ->width(80);

        $grid->column('orders_count', 'Số đơn hàng')
            ->display(function () {
                return $this->orders()->count();
            })
            ->width(100);

        $grid->column('created_at', 'Ngày tạo')
            ->display(function ($createdAt) {
                return date('d/m/Y H:i', strtotime($createdAt));
            })
            ->width(130);

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->like('status_name', 'Tên trạng thái');
            $filter->equal('status_color', 'Màu')->select([
                'default' => 'Default',
                'primary' => 'Primary',
                'success' => 'Success',
                'info' => 'Info',
                'warning' => 'Warning',
                'danger' => 'Danger'
            ]);
            $filter->equal('is_active', 'Hoạt động')->select([
                1 => 'Có',
                0 => 'Không'
            ]);
        });

        // Tools
        $grid->tools(function ($tools) {
            $tools->append('<a href="' . route('admin.pos-order-statuses.refresh-cache') . '" class="btn btn-sm btn-info">
                <i class="fa fa-refresh"></i> Refresh Cache
            </a>');
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(PosOrderStatus::findOrFail($id));

        $show->field('status_code', 'Mã trạng thái');
        $show->field('status_name', 'Tên trạng thái');
        $show->field('status_color', 'Màu hiển thị')->as(function ($color) {
            return "<span class='label label-{$color}'>{$color}</span>";
        });
        $show->field('description', 'Mô tả');
        $show->field('sort_order', 'Thứ tự sắp xếp');
        $show->field('is_active', 'Hoạt động')->using([1 => 'Có', 0 => 'Không']);
        
        $show->divider();
        
        $show->field('created_at', 'Ngày tạo');
        $show->field('updated_at', 'Ngày cập nhật');

        // Hiển thị số lượng đơn hàng theo trạng thái này
        $show->field('orders_count', 'Số đơn hàng')->as(function () {
            return $this->orders()->count();
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new PosOrderStatus());

        $form->number('status_code', 'Mã trạng thái')
            ->required()
            ->min(0)
            ->help('Mã trạng thái phải là số dương và duy nhất');

        $form->text('status_name', 'Tên trạng thái')
            ->required()
            ->rules('required|max:100');

        $form->select('status_color', 'Màu hiển thị')
            ->options([
                'default' => 'Default (Xám)',
                'primary' => 'Primary (Xanh dương)',
                'success' => 'Success (Xanh lá)',
                'info' => 'Info (Xanh nhạt)',
                'warning' => 'Warning (Vàng)',
                'danger' => 'Danger (Đỏ)'
            ])
            ->default('default')
            ->required();

        $form->textarea('description', 'Mô tả')
            ->rows(3)
            ->help('Mô tả chi tiết về trạng thái này');

        $form->number('sort_order', 'Thứ tự sắp xếp')
            ->default(0)
            ->min(0)
            ->help('Thứ tự hiển thị trong danh sách (số nhỏ hơn sẽ hiển thị trước)');

        $form->switch('is_active', 'Hoạt động')
            ->default(1)
            ->help('Bật/tắt trạng thái này trong hệ thống');

        // Validation rules
        $form->saving(function (Form $form) {
            // Kiểm tra status_code unique
            if ($form->isCreating()) {
                $exists = PosOrderStatus::where('status_code', $form->status_code)->exists();
                if ($exists) {
                    $error = new \Encore\Admin\Validators\MessageBag();
                    $error->add('status_code', 'Mã trạng thái đã tồn tại!');
                    return back()->withInput()->withErrors($error);
                }
            }
        });

        return $form;
    }

    // Method để refresh cache
    public function refreshCache()
    {
        try {
            PosOrderStatus::refreshCache();
            admin_toastr('Cache đã được refresh thành công!', 'success');
        } catch (\Exception $e) {
            admin_toastr('Lỗi khi refresh cache: ' . $e->getMessage(), 'error');
        }

        return redirect()->back();
    }
}