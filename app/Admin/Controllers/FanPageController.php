<?php

namespace App\Admin\Controllers;

use App\Models\FanPage;
use App\Models\Dataset;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class FanPageController extends Controller
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
        $grid = new Grid(new FanPage);

        $grid->id('ID');
        $grid->page_id('Id Fanpage');
        $grid->page_name('Tên Fanpage');
        $grid->page_short_name('Tên rút gọn');
        $grid->dataset_id('Dataset Id');
        $grid->column('dataset.dataset_name', 'Tên Dataset'); // Hiển thị tên thay cho ID
        $grid->instagram_id('Instagram Id');
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
        $show = new Show(FanPage::findOrFail($id));

        $show->id('ID');
        $show->page_id('page_id');
        $show->page_name('page_name');
        $show->page_short_name('page_short_name');
        $show->dataset_id('dataset_id');
        $show->instagram_id('instagram_id');
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
        $form = new Form(new FanPage);

        // $form->display('ID');
        $form->text('page_id', 'Id Trang');
        $form->text('page_name', 'Tên Trang');
        $form->text('page_short_name', 'Tên rút gọn');

        // $form->text('dataset_id', 'dataset_id');
        $form->select('dataset_id', 'Dataset')
         ->options(Dataset::all()->pluck('dataset_name', 'dataset_id'));

        $form->text('instagram_id', 'instagram_id');
        // $form->display(trans('admin.created_at'));
        // $form->display(trans('admin.updated_at'));

        return $form;
    }
}
