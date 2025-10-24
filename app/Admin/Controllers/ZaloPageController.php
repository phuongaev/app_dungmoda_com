<?php

namespace App\Admin\Controllers;

use App\Models\ZaloPage;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ZaloPageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Zalo Pages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ZaloPage());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('phone_number', __('Số điện thoại'));
        $grid->column('zalo_name', __('Tên Zalo'));
        $grid->column('global_id', __('Global ID'));
        $grid->column('sdob', __('Ngày sinh'))->display(function ($sdob) {
            return $sdob ? date('d/m/Y', strtotime($sdob)) : '';
        });
        $grid->column('pc_user_name', __('PC Username'));
        $grid->column('created_at', __('Ngày tạo'))->display(function ($created_at) {
            return date('d/m/Y H:i', strtotime($created_at));
        });
        $grid->column('updated_at', __('Cập nhật'))->display(function ($updated_at) {
            return date('d/m/Y H:i', strtotime($updated_at));
        });

        $grid->filter(function($filter){
            $filter->like('phone_number', 'Số điện thoại');
            $filter->like('zalo_name', 'Tên Zalo');
            $filter->like('pc_user_name', 'PC Username');
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ZaloPage::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('phone_number', __('Số điện thoại'));
        $show->field('global_id', __('Global ID'));
        $show->field('sdob', __('Ngày sinh'))->as(function ($sdob) {
            return $sdob ? date('d/m/Y', strtotime($sdob)) : '';
        });
        $show->field('zalo_name', __('Tên Zalo'));
        $show->field('avatar_url', __('Avatar URL'))->link();
        $show->field('pc_page_id', __('PC Page ID'));
        $show->field('pc_user_name', __('PC Username'));
        $show->field('created_at', __('Ngày tạo'));
        $show->field('updated_at', __('Cập nhật'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ZaloPage());

        $form->text('phone_number', __('Số điện thoại'))
            ->rules('required|unique:zalo_pages,phone_number,{{id}}')
            ->help('Ví dụ: +84869725059');
        
        $form->text('global_id', __('Global ID'))
            ->help('Ví dụ: 2I3DJEKRLTPQ36F5OQJQ0FQI7NRQTI00');
        
        $form->text('sdob', __('Ngày sinh'))
            // ->format('DD/MM/YYYY')
            ->help('Ví dụ: 20/11/1992');
        
        $form->text('zalo_name', __('Tên Zalo'))
            ->help('Ví dụ: Hồng Dungmoda');
        
        $form->url('avatar_url', __('Avatar URL'))
            ->help('Link ảnh đại diện');
        
        $form->text('pc_page_id', __('PC Page ID'))
            ->help('Ví dụ: 828938612518031660');
        
        $form->text('pc_user_name', __('PC Username'))
            ->help('Ví dụ: pzl_84869725059');

        return $form;
    }
}