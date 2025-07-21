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

    public function index(Content $content)
    {
        return $content
            ->header('Quản lý công việc hàng ngày')
            ->description('Quản lý tasks và review')
            ->body($this->grid());
    }

    public function show($id, Content $content)
    {
        return $content
            ->header('Chi tiết công việc')
            ->description('')
            ->body($this->detail($id));
    }

    public function edit($id, Content $content)
    {
        return $content
            ->header('Sửa công việc')
            ->description('')
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->header('Tạo công việc mới')
            ->description('')
            ->body($this->form());
    }

    protected function grid()
    {
        $grid = new Grid(new DailyTask());
        $grid->model()->orderBy('id', 'desc');
        
        $grid->column('id', 'ID')->sortable();
        
        $grid->column('title', 'Tiêu đề')->display(function ($title) {
            $icon = $this->category->icon ?? 'fa-tasks';
            $typeIcon = $this->task_type === 'one_time' ? 'fa-clock-o' : 'fa-repeat';
            $typeColor = $this->task_type === 'one_time' ? 'text-orange' : 'text-blue';
            return "<i class='fa {$icon}'></i> <i class='fa {$typeIcon} {$typeColor}'></i> {$title}";
        })->width(280);
            
        $grid->column('category.name', 'Danh mục')->display(function ($name) {
            if (!$name) return '-';
            $color = $this->category->color ?? '#007bff';
            return "<span class='label' style='background-color: {$color}'>{$name}</span>";
        });
            
        $grid->column('priority', 'Ưu tiên')->display(function ($priority) {
            $colors = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
            $labels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
            $color = $colors[$priority] ?? 'info';
            $label = $labels[$priority] ?? 'Trung bình';
            return "<span class='label label-{$color}'>{$label}</span>";
        });
            
        $grid->column('task_type', 'Loại')->display(function ($taskType) {
            $colors = ['recurring' => 'info', 'one_time' => 'warning'];
            $labels = ['recurring' => 'Lặp lại', 'one_time' => 'Một lần'];
            $color = $colors[$taskType] ?? 'info';
            $label = $labels[$taskType] ?? 'Lặp lại';
            return "<span class='label label-{$color}'>{$label}</span>";
        });

        $grid->column('date_range', 'Thời gian')->display(function () {
            $start = $this->start_date ? $this->start_date->format('d/m/Y') : '';
            $end = $this->end_date ? $this->end_date->format('d/m/Y') : '';
            
            if ($this->task_type === 'one_time') {
                return $start && $end ? "{$start} → {$end}" : ($end ? "Deadline: {$end}" : '-');
            } else {
                return $start && $end ? "{$start} → {$end}" : ($start ? "Từ: {$start}" : 'Không giới hạn');
            }
        });
            
        $grid->column('suggested_time', 'Thời gian gợi ý')->display(function ($time) {
            return $time ? date('H:i', strtotime($time)) : '-';
        });
            
        $grid->column('assigned_users', 'Người được giao')->display(function ($users) {
            return empty($users) ? '<span class="text-muted">Tất cả</span>' : 
                   '<span class="label label-info">' . count($users) . ' người</span>';
        });
            
        $grid->column('is_active', 'Trạng thái')->display(function ($active) {
            return $active ? '<span class="label label-success">Hoạt động</span>' : 
                            '<span class="label label-danger">Tạm dừng</span>';
        });

        // Completion status for one-time tasks only
        $grid->column('completion_status', 'Trạng thái hoàn thành')->display(function () {
            if ($this->task_type !== 'one_time') {
                return '<span class="text-muted">N/A</span>';
            }

            $completions = \App\Models\UserTaskCompletion::where('daily_task_id', $this->id)
                ->where('status', 'completed')
                ->with(['user'])
                ->get();

            if ($completions->isEmpty()) {
                return '<span class="label label-default">Chưa hoàn thành</span>';
            }

            $html = '';
            foreach ($completions as $completion) {
                $userName = $completion->user->name;
                $completedTime = $completion->completed_at_time ? 
                    $completion->completed_at_time->format('d/m H:i') : '';
                $reviewBadge = $completion->review_status ? 
                    '<span class="label label-warning">Cần review</span>' : 
                    '<span class="label label-success">OK</span>';
                
                $html .= "<div style='margin-bottom: 5px;'>";
                $html .= "<strong>{$userName}</strong> ({$completedTime})<br>";
                $html .= $reviewBadge;
                $html .= "</div>";
            }
            return $html;
        })->width(200);

        // Review actions for one-time tasks
        $grid->column('review_actions', 'Review Actions')->display(function () {
            if ($this->task_type !== 'one_time') {
                return '<span class="text-muted">N/A</span>';
            }

            $completions = \App\Models\UserTaskCompletion::where('daily_task_id', $this->id)
                ->where('status', 'completed')
                ->get();

            if ($completions->isEmpty()) {
                return '<span class="text-muted">Chưa hoàn thành</span>';
            }

            $html = '';
            foreach ($completions as $completion) {
                $userName = $completion->user->name;
                
                $html .= '<div style="margin-bottom: 10px;">';
                $html .= '<strong>' . $userName . '</strong><br>';
                
                if (!$completion->review_status) {
                    $html .= '<a href="' . admin_url("daily-tasks/toggle-review/{$completion->id}/1") . '" 
                               class="btn btn-xs btn-warning" 
                               onclick="return confirm(\'Đánh dấu cần review?\')">
                               <i class="fa fa-exclamation-triangle"></i> Cần review
                             </a>';
                } else {
                    $html .= '<span class="label label-warning">Đã yêu cầu review</span><br>';
                    $html .= '<a href="' . admin_url("daily-tasks/toggle-review/{$completion->id}/0") . '" 
                               class="btn btn-xs btn-success" style="margin-top: 5px;">
                               <i class="fa fa-check"></i> Đánh dấu OK
                             </a>';
                }
                $html .= '</div>';
            }
            return $html;
        })->width(150);

        $grid->filter(function($filter){
            $filter->like('title', 'Tiêu đề');
            $filter->equal('category_id', 'Danh mục')->select(TaskCategory::pluck('name', 'id'));
            $filter->equal('task_type', 'Loại')->select(['recurring' => 'Lặp lại', 'one_time' => 'Một lần']);
            $filter->equal('priority', 'Ưu tiên')->select([
                'low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'
            ]);
            $filter->equal('is_active', 'Trạng thái')->select([1 => 'Hoạt động', 0 => 'Tạm dừng']);
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(DailyTask::findOrFail($id));
        $show->id('ID');
        $show->title('Tiêu đề');
        $show->description('Mô tả');
        $show->created_at('Tạo lúc');
        $show->updated_at('Cập nhật lúc');
        return $show;
    }

    protected function form()
    {
        $form = new Form(new DailyTask());

        $form->text('title', 'Tiêu đề')->required();
        $form->textarea('description', 'Mô tả');
        $form->select('category_id', 'Danh mục')->options(TaskCategory::pluck('name', 'id'));
        $form->select('priority', 'Ưu tiên')->options([
            'low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'
        ])->default('medium');
        
        $form->time('suggested_time', 'Thời gian gợi ý');
        $form->number('estimated_minutes', 'Thời gian ước tính (phút)');
        
        $form->radio('task_type', 'Loại công việc')->options([
            'recurring' => 'Lặp lại theo lịch trình',
            'one_time' => 'Thực hiện một lần'
        ])->default('recurring');

        $form->checkbox('frequency', 'Tần suất (chỉ cho lặp lại)')->options([
            'daily' => 'Hàng ngày', 'weekdays' => 'Ngày làm việc', 'weekends' => 'Cuối tuần',
            'monday' => 'Thứ 2', 'tuesday' => 'Thứ 3', 'wednesday' => 'Thứ 4',
            'thursday' => 'Thứ 5', 'friday' => 'Thứ 6', 'saturday' => 'Thứ 7', 'sunday' => 'Chủ nhật'
        ])->when('recurring', function (Form $form) {
            $form->checkbox('frequency')->required();
        });

        $form->date('start_date', 'Ngày bắt đầu');
        $form->date('end_date', 'Ngày kết thúc / Deadline');
        
        $roles = cache()->remember('admin_roles', 3600, function() {
            return \Encore\Admin\Auth\Database\Role::pluck('name', 'slug')->toArray();
        });
        $form->checkbox('assigned_roles', 'Vai trò được giao')->options($roles);
        $form->multipleSelect('assigned_users', 'Người cụ thể được giao')
            ->options(\Encore\Admin\Auth\Database\Administrator::pluck('name', 'id'));
            
        $form->switch('is_required', 'Bắt buộc hoàn thành')->default(1);
        $form->switch('is_active', 'Trạng thái hoạt động')->default(1);
        $form->number('sort_order', 'Thứ tự sắp xếp')->default(0);
        $form->hidden('created_by')->default(Admin::user()->id);

        return $form;
    }

    public function toggleReview($completionId, $status)
    {
        try {
            $completion = \App\Models\UserTaskCompletion::findOrFail($completionId);
            
            if ($status == 1) {
                $completion->update(['review_status' => 1, 'status' => 'in_process']);
                $message = 'Đã yêu cầu nhân viên kiểm tra lại!';
            } else {
                $completion->update(['review_status' => 0, 'status' => 'completed']);
                $message = 'Đã xác nhận hoàn thành!';
            }

            admin_toastr($message, 'success');
        } catch (\Exception $e) {
            admin_toastr('Có lỗi xảy ra: ' . $e->getMessage(), 'error');
        }
        
        return redirect()->back();
    }
}