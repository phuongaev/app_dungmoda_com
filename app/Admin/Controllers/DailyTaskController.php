<?php
// app/Admin/Controllers/DailyTaskController.php

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\TaskCategory;
use Encore\Admin\Controllers\AdminController;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

use Encore\Admin\Facades\Admin;

class DailyTaskController extends Controller
{
    use HasResourceActions;

    protected $title = 'Công việc hàng ngày';

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
        $grid = new Grid(new DailyTask());

        $grid->column('id', __('ID'))->sortable();
        $grid->model()->orderBy('id', 'desc');
        
        $grid->column('title', __('Tiêu đề'))
            ->display(function ($title) {
                $icon = $this->category->icon ?? 'fa-tasks';
                return "<i class='fa {$icon}'></i> {$title}";
            })->width(250);
            
        $grid->column('category.name', __('Danh mục'))
            ->display(function ($name) {
                if (!$name) return '-';
                $color = $this->category->color ?? '#007bff';
                return "<span class='label' style='background-color: {$color}'>{$name}</span>";
            });
            
        $grid->column('priority', __('Ưu tiên'))
            ->display(function ($priority) {
                $colors = [
                    'low' => 'success',
                    'medium' => 'info', 
                    'high' => 'warning',
                    'urgent' => 'danger'
                ];
                $labels = [
                    'low' => 'Thấp',
                    'medium' => 'Trung bình',
                    'high' => 'Cao', 
                    'urgent' => 'Khẩn cấp'
                ];
                $color = $colors[$priority] ?? 'warning';
                $label = $labels[$priority] ?? 'Trung bình';
                return "<span class='label label-{$color}'>{$label}</span>";
            });
            
        $grid->column('frequency', __('Tần suất'))
            ->display(function ($frequency) {
                // Sử dụng accessor mới từ model
                return $this->frequency_label;
            });
            
        $grid->column('suggested_time', __('Thời gian gợi ý'))
            ->display(function ($time) {
                return $time ? date('H:i', strtotime($time)) : '-';
            });
            
        $grid->column('estimated_minutes', __('Thời gian ước tính'))->suffix(' phút');
        
        $grid->column('assigned_users', __('Người được giao'))
            ->display(function ($users) {
                if (empty($users)) return '<span class="text-muted">Tất cả</span>';
                return '<span class="label label-info">' . count($users) . ' người</span>';
            });
            
        $grid->column('is_required', __('Bắt buộc'))
            ->display(function ($required) {
                return $required ? '<span class="label label-danger">Bắt buộc</span>' : '<span class="label label-default">Tùy chọn</span>';
            });
            
        $grid->column('is_active', __('Trạng thái'))
            ->display(function ($active) {
                return $active ? '<span class="label label-success">Hoạt động</span>' : '<span class="label label-danger">Tạm dừng</span>';
            });

        $grid->filter(function($filter){
            $filter->like('title', 'Tiêu đề');
            $filter->equal('category_id', 'Danh mục')->select(TaskCategory::pluck('name', 'id'));
            $filter->equal('priority', 'Ưu tiên')->select([
                'low' => 'Thấp',
                'medium' => 'Trung bình',
                'high' => 'Cao',
                'urgent' => 'Khẩn cấp'
            ]);
            // Cập nhật filter cho frequency - tạm thời đơn giản hóa
            $filter->where(function ($query) {
                $query->where('frequency', 'like', '%' . $this->input . '%');
            }, 'Tần suất');
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
        $show = new Show(DailyTask::findOrFail($id));

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
        $form = new Form(new DailyTask());

        $form->text('title', __('Tiêu đề công việc'))->required();
        
        $form->textarea('description', __('Mô tả chi tiết'));
        
        $form->select('category_id', __('Danh mục'))
            ->options(TaskCategory::where('is_active', 1)->pluck('name', 'id'));
            
        $form->select('priority', __('Độ ưu tiên'))
            ->options([
                'low' => 'Thấp',
                'medium' => 'Trung bình',
                'high' => 'Cao',
                'urgent' => 'Khẩn cấp'
            ])->default('medium');
            
        $form->time('suggested_time', __('Thời gian gợi ý thực hiện'));
        
        $form->number('estimated_minutes', __('Thời gian ước tính (phút)'));
        
        // Cập nhật field frequency thành multipleSelect
        $form->multipleSelect('frequency', __('Tần suất thực hiện'))
            ->options([
                'daily' => 'Hàng ngày',
                'weekdays' => 'Ngày làm việc (T2-T6)',
                'weekends' => 'Cuối tuần (T7-CN)',
                'monday' => 'Thứ 2',
                'tuesday' => 'Thứ 3', 
                'wednesday' => 'Thứ 4',
                'thursday' => 'Thứ 5',
                'friday' => 'Thứ 6',
                'saturday' => 'Thứ 7',
                'sunday' => 'Chủ nhật'
            ])
            ->default(['daily'])
            ->help('Chọn một hoặc nhiều tần suất. Ví dụ: Chọn "Thứ 2" và "Thứ 5" cho công việc làm 2 ngày/tuần.');
            
        $form->date('start_date', __('Ngày bắt đầu'));
        $form->date('end_date', __('Ngày kết thúc'));
        
        $roles = cache()->remember('admin_roles', 3600, function() {
            return \Encore\Admin\Auth\Database\Role::pluck('name', 'slug')->toArray();
        });
        $form->checkbox('assigned_roles', __('Vai trò được giao'))
            ->options($roles);
            
        $form->multipleSelect('assigned_users', __('Người cụ thể được giao'))
            ->options(\Encore\Admin\Auth\Database\Administrator::pluck('name', 'id'));
            
        $form->switch('is_required', __('Bắt buộc hoàn thành'))->default(1);
        $form->switch('is_active', __('Trạng thái hoạt động'))->default(1);
        $form->number('sort_order', __('Thứ tự sắp xếp'))->default(0);

        $form->hidden('created_by')->default(Admin::user()->id);

        return $form;
    }
}