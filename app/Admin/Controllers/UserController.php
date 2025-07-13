<?php

namespace App\Admin\Controllers;

use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Hash;

class UserController extends AdminController
{
    protected $title = 'Nhân viên';

    protected function grid()
    {
        $userModel = config('admin.database.users_model');
        $grid = new Grid(new $userModel());

        $grid->column('id', 'ID')->sortable();
        $grid->column('avatar', 'Avatar')->image('', 60, 60);
        $grid->column('username', trans('admin.username'));
        $grid->column('name', trans('admin.name'));

        // THÊM CỘT TRẠNG THÁI VÀO ĐÂY
        $grid->column('is_active', 'Hoạt động')->switch([
            'on'  => ['value' => 1, 'text' => 'Bật', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Tắt', 'color' => 'danger'],
        ]);

        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();
        $grid->column('created_at', trans('admin.created_at'));

        $grid->actions(function ($actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
        });

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    protected function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));

        // THÊM TRƯỜNG BẬT/TẮT VÀO ĐÂY
        $form->switch('is_active', 'Hoạt động')->default(1);

        // THÊM TRƯỜNG THREAD_ID VÀO ĐÂY
        $form->text('thread_id', 'Thread ID')->placeholder('Nhập Thread ID');

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        return $form;
    }
}