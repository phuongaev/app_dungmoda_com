<?php

namespace App\Admin\Controllers;

use App\Models\Dataset;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class DatasetController extends Controller
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
        $grid = new Grid(new Dataset);

        $grid->id('ID');
        $grid->dataset_id('Dataset Id');
        $grid->dataset_name('Dataset Name');
        $grid->dataset_token('Dataset Token')->style('max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;');
        $grid->business_id('Business Id');
        $grid->created_at(trans('admin.created_at'));
        $grid->updated_at(trans('admin.updated_at'));

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
        $show = new Show(Dataset::findOrFail($id));

        $show->id('ID');
        $show->dataset_id('Dataset Id');
        $show->dataset_name('Dataset Name');
        $show->dataset_token('Dataset Token');
        $show->business_id('Business Id');
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
        $form = new Form(new Dataset);

        // $form->display('ID');
        $form->text('dataset_id', 'Dataset Id');
        $form->text('dataset_name', 'Dataset Name');
        $form->text('dataset_token', 'Dataset Token');
        $form->text('business_id', 'Business Id');
        // $form->display(trans('admin.created_at'));
        // $form->displDatasetay(trans('admin.updated_at'));

        return $form;
    }
}
