<?php

namespace App\Admin\Controllers;

use App\Models\TaskCategory;
use Encore\Admin\Controllers\AdminController;
use App\Http\Controllers\Controller;

use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class TaskCategoryController extends Controller
{
    use HasResourceActions;

    protected $title = 'Danh mục công việc';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TaskCategory());

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('name', __('Tên danh mục'))->editable();
        
        $grid->column('color', __('Màu sắc'))
            ->display(function ($color) {
                return "<span style='display:inline-block;width:20px;height:20px;background-color:{$color};border-radius:3px;'></span> {$color}";
            });
            
        $grid->column('icon', __('Icon'))
            ->display(function ($icon) {
                return $icon ? "<i class='fa {$icon}'></i> {$icon}" : '-';
            });
            
        $grid->column('sort_order', __('Thứ tự'))->editable()->sortable();
        
        $grid->column('is_active', __('Trạng thái'))
            ->display(function ($active) {
                return $active ? '<span class="label label-success">Hoạt động</span>' : '<span class="label label-danger">Tạm dừng</span>';
            });
            
        $grid->column('created_at', __('Ngày tạo'))->display(function ($date) {
            return date('d/m/Y H:i', strtotime($date));
        });

        $grid->filter(function($filter){
            $filter->like('name', 'Tên danh mục');
            $filter->equal('is_active', 'Trạng thái')->select([1 => 'Hoạt động', 0 => 'Tạm dừng']);
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
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
        $show = new Show(TaskCategory::findOrFail($id));

        $show->id('ID');
        $show->order_id('order_id');
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TaskCategory());

        $form->text('name', __('Tên danh mục'))->required();
        
        $form->color('color', __('Màu sắc'))->default('#007bff');
        
        $form->text('icon', __('Icon (Font Awesome)'))
            ->placeholder('VD: fa-tasks, fa-clipboard-list')
            ->help('Nhập class icon Font Awesome (không cần prefix fa)');
            
        $form->number('sort_order', __('Thứ tự sắp xếp'))->default(0);
        
        $form->switch('is_active', __('Trạng thái'))->default(1);

        return $form;
    }
}
