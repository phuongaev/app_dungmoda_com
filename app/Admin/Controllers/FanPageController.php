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
            ->header('Quản lý Fan Pages')
            ->description('Danh sách các trang fanpage')
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
            ->header('Chi tiết Fan Page')
            ->description('Thông tin chi tiết fanpage')
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
            ->header('Chỉnh sửa Fan Page')
            ->description('Cập nhật thông tin fanpage')
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
            ->header('Tạo Fan Page mới')
            ->description('Thêm fanpage mới vào hệ thống')
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

        // Eager load dataset để hiển thị tên
        $grid->model()->with(['dataset']);

        $grid->page_id('ID Fanpage')->sortable();
        $grid->page_name('Tên Fanpage')->sortable();
        $grid->page_short_name('Tên rút gọn')->sortable();
        
        // Hiển thị tên dataset thay vì ID
        $grid->column('dataset.dataset_name', 'Tên Dataset');
        
        $grid->instagram_id('Instagram ID');
        
        // Hiển thị pancake_token rút gọn với nút copy
        $grid->pancake_token('Pancake Token')
            ->display(function ($token) {
                if (empty($token)) {
                    return '<span class="text-muted">Chưa có token</span>';
                }
                
                // Hiển thị 20 ký tự đầu + ... + 10 ký tự cuối
                return strlen($token) > 30 
                    ? substr($token, 0, 20) . '...' . substr($token, -10)
                    : $token;
            })
            ->copyable()
            ->width(250);

        // Hiển thị botcake_token rút gọn với nút copy
        $grid->botcake_token('Botcake Token')
            ->display(function ($token) {
                if (empty($token)) {
                    return '<span class="text-muted">Chưa có token</span>';
                }
                
                // Hiển thị 20 ký tự đầu + ... + 10 ký tự cuối
                return strlen($token) > 30 
                    ? substr($token, 0, 20) . '...' . substr($token, -10)
                    : $token;
            })
            ->copyable()
            ->width(250);

        $grid->created_at('Ngày tạo')->display(function ($created_at) {
            return date('d/m/Y H:i', strtotime($created_at));
        })->sortable();

        $grid->updated_at('Cập nhật cuối')->display(function ($updated_at) {
            return date('d/m/Y H:i', strtotime($updated_at));
        })->sortable();

        // Thêm filter
        $grid->filter(function($filter) {
            $filter->like('page_name', 'Tên Fanpage');
            $filter->like('page_short_name', 'Tên rút gọn');
            $filter->equal('dataset_id', 'Dataset')->select(
                Dataset::all()->pluck('dataset_name', 'dataset_id')
            );
            $filter->like('instagram_id', 'Instagram ID');
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
        $show = new Show(FanPage::findOrFail($id));

        $show->page_id('ID Fanpage');
        $show->page_name('Tên Fanpage');
        $show->page_short_name('Tên rút gọn');
        $show->dataset_id('Dataset ID');
        $show->column('dataset.dataset_name', 'Tên Dataset');
        $show->instagram_id('Instagram ID');
        $show->pancake_token('Pancake Token')->as(function ($token) {
            return $token ?: 'Chưa có token';
        });
        $show->botcake_token('Botcake Token')->as(function ($token) {
            return $token ?: 'Chưa có token';
        });
        $show->created_at('Ngày tạo');
        $show->updated_at('Cập nhật cuối');

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

        $form->text('page_id', 'ID Fanpage')
            ->help('ID duy nhất của fanpage, tối đa 30 số');

        $form->text('page_name', 'Tên Fanpage')
            ->help('Tên đầy đủ của fanpage');

        $form->text('page_short_name', 'Tên rút gọn')
            ->help('Tên rút gọn để hiển thị');

        $form->select('dataset_id', 'Dataset')
            ->options(Dataset::all()->pluck('dataset_name', 'dataset_id'))
            ->help('Chọn dataset liên kết với fanpage này');

        $form->text('instagram_id', 'Instagram ID')
            ->help('ID Instagram liên kết (tùy chọn)');

        $form->textarea('pancake_token', 'Pancake Token')
            ->rows(3)
            ->help('Token để kết nối với Pancake API (tùy chọn)');

        $form->textarea('botcake_token', 'Botcake Token')
            ->rows(3)
            ->help('Token để kết nối với Botcake API (tùy chọn)');

        return $form;
    }
}