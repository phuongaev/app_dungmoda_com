<?php

namespace App\Admin\Controllers;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ProductController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('Quản lý sản phẩm'))
            ->description(trans('Thông tin cơ bản của sản phẩm'))
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
        $grid = new Grid(new Product);
        // Sắp xếp theo id giảm dần (DESC)
        $grid->model()->orderBy('id', 'desc');


        // FILTER ĐA TRƯỜNG
        $grid->filter(function($filter) {
            $filter->like('product_code', 'Mã sản phẩm');
            $filter->like('variations_code', 'Mã mẫu mã');
            $filter->like('variations_name', 'Màu sắc');
        });
        // Tạo nhanh data
        $grid->quickCreate(function ($form) {
            $form->text('product_code', 'Mã sản phẩm');
            $form->text('variations_code', 'Mã mẫu mã');
            $form->text('variations_name', 'Thuộc tính');
            $form->text('price', 'Giá bán');
            $form->text('import_price', 'Giá nhập');
        });


        $grid->id('ID');
        $grid->product_code('Mã sản phẩm')->copyable()->filter('like');
        $grid->variations_code('Mã mẫu mã')->editable()->copyable()->filter('like');
        $grid->variations_name('Thuộc tính')->editable()->copyable()->filter('like');
        // $grid->product_img('product_img');
        $grid->price('Giá bán');
        $grid->sale_price('Giá sale')->editable();

        // $grid->import_price('import_price');
        if (Admin::user()->isAdministrator()) {
            $grid->import_price('Im_Price')->editable();
        }


        // $grid->created_at(trans('admin.created_at'));
        // $grid->updated_at(trans('admin.updated_at'));
        $grid->column('created_at', __('Tạo lúc'))
            ->display(function ($created_at) {
                return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
            })->filter('range', 'datetime')->width(150);
        $grid->column('updated_at', __('Cập nhật'))
            ->display(function ($updated_at) {
                return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
            })->width(150);

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
        $show = new Show(Product::findOrFail($id));

        $show->id('ID');
        $show->product_code('product_code');
        $show->variations_code('variations_code');
        $show->variations_name('variations_name');
        $show->product_img('product_img');
        $show->price('price');
        $show->sale_price('sale_price');
        // $show->import_price('import_price');
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
        $form = new Form(new Product);

        $form->display('ID');
        $form->text('product_code', 'product_code');
        $form->text('variations_code', 'variations_code');
        $form->text('variations_name', 'variations_name');
        $form->text('product_img', 'product_img');
        $form->text('price', 'price');
        $form->text('sale_price', 'sale_price');
        $form->text('import_price', 'import_price');
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
