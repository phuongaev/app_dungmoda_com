<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

use App\Models\FanPage;
use App\Models\OmniProfile;
use App\Models\ZaloTask;
use App\Models\BaseStatus;

class CustomerController extends Controller
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
            ->header(trans('Quản lý khách hàng'))
            ->description(trans('Thông tin khách hàng cơ bản'))
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
        $grid = new Grid(new Customer);
        $grid->model()->orderBy('created_at', 'desc');

        // Eager load các quan hệ cho filter theo tên
        $grid->model()->with(['fanpage', 'profile', 'status']);

        $grid->id('ID');
        $grid->phone('SĐT');
        $grid->name('Họ tên');

        $grid->address('Địa chỉ')->style('max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;')->width(480);
        // $grid->city('Tỉnh');
        // $grid->district('Huyện');
        // $grid->zipcode('Zipcode');

        $grid->psid('Psid');
        // $grid->page_id('page_id');
        $grid->column('fanpage.page_name', 'Tên Fanpage');

        // $grid->profile_id('profile_id');
        $grid->column('profile.profile_name', 'Tên Profile');

        // $grid->zalo_task_id('zalo_task_id');
        $grid->column('zaloTask.zalo_task_name', 'Nhiệm vụ Zalo');
        $grid->zalo_name('Tên Zalo')->filter('like');
        // $grid->thread_id('Thread Id');

        $grid->priority_id('Ưu tiên');

        // $grid->status_id('status_id');
        $grid->column('status.status_name', 'Trạng thái Zalo');

        // $grid->created_at(trans('admin.created_at'));
        // $grid->updated_at(trans('admin.updated_at'));
        $grid->column('created_at', __('Tạo lúc'))
            ->display(function ($created_at) {
                return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
            })->filter('range', 'datetime')->width(120);
        $grid->column('updated_at', __('Cập nhật'))
            ->display(function ($updated_at) {
                return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
            })->width(120);


        // FILTER ĐA TRƯỜNG
        $grid->filter(function($filter) {
            // Filter theo phone, name, city, psid (trực tiếp từ bảng customers)
            $filter->like('phone', 'Số điện thoại');
            $filter->like('name', 'Tên khách');
            $filter->like('city', 'Thành phố');
            $filter->like('psid', 'PSID');

            // Filter theo tên các bảng liên kết
            // page_name từ quan hệ fanpage
            $filter->equal('page_id', 'Tên Fanpage')->select(
                FanPage::all()->pluck('page_name', 'page_id')
            );

            // profile_name từ quan hệ profile
            $filter->equal('profile_id', 'Tên Profile')->select(
                OmniProfile::all()->pluck('profile_name', 'profile_id')
            );

            // status_name từ quan hệ status
            $filter->equal('status_id', 'Trạng thái')->select(
                BaseStatus::all()->pluck('status_name', 'status_id')
            );
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
        $show = new Show(Customer::findOrFail($id));

        $show->id('ID');
        $show->phone('phone');
        $show->name('name');
        $show->address('address');
        $show->city('city');
        $show->district('district');
        $show->zipcode('zipcode');
        $show->psid('psid');
        $show->page_id('page_id');
        $show->profile_id('profile_id');
        $show->zalo_name('zalo_name');
        $show->thread_id('thread_id');
        $show->zalo_task_id('zalo_task_id');
        $show->priority_id('priority_id');
        $show->status_id('status_id');
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
        $form = new Form(new Customer);

        $form->display('ID');
        $form->text('phone', 'phone');
        $form->text('name', 'name');
        $form->text('address', 'address');
        $form->text('city', 'city');
        $form->text('district', 'district');
        $form->text('zipcode', 'zipcode');
        $form->text('psid', 'psid');

        // $form->text('page_id', 'page_id');
        $form->select('page_id', 'Fanpage')
            ->options(FanPage::all()->pluck('page_name', 'page_id'));

        // $form->text('profile_id', 'profile_id');
        $form->select('profile_id', 'Profile')
            ->options(OmniProfile::all()->pluck('profile_name', 'profile_id'));

        $form->text('zalo_name', 'zalo_name');
        $form->text('thread_id', 'thread_id');
        $form->text('zalo_task_id', 'zalo_task_id');
        $form->text('priority_id', 'priority_id');

        $form->text('status_id', 'status_id');
        $form->select('status_id', 'Trạng thái')
            ->options(BaseStatus::all()->pluck('status_name', 'status_id'));

        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
