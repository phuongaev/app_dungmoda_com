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
                
                // Thêm icon phân biệt loại task
                $typeIcon = $this->task_type === 'one_time' ? 'fa-clock-o' : 'fa-repeat';
                $typeColor = $this->task_type === 'one_time' ? 'text-orange' : 'text-blue';
                
                return "<i class='fa {$icon}'></i> <i class='fa {$typeIcon} {$typeColor}' title='{$this->task_type_label}'></i> {$title}";
            })->width(280);
            
        $grid->column('category.name', __('Danh mục'))
            ->display(function ($name) {
                if (!$name) return '-';
                $color = $this->category->color ?? '#007bff';
                return "<span class='label' style='background-color: {$color}'>{$name}</span>";
            });
            
        $grid->column('task_type', __('Loại'))
            ->display(function ($taskType) {
                $colors = [
                    'recurring' => 'info',
                    'one_time' => 'warning'
                ];
                $color = $colors[$taskType] ?? 'info';
                return "<span class='label label-{$color}'>{$this->task_type_label}</span>";
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
            
        // Hiển thị khoảng thời gian
        $grid->column('time_range', __('Khoảng thời gian'))
            ->display(function () {
                $start = $this->start_date ? $this->start_date->format('d/m/Y') : '';
                $end = $this->end_date ? $this->end_date->format('d/m/Y') : '';
                
                if ($this->task_type === 'one_time') {
                    // One-time task: start → deadline
                    if ($start && $end) {
                        return "{$start} → {$end}";
                    } elseif ($end) {
                        return "Deadline: {$end}";
                    } else {
                        return '-';
                    }
                } else {
                    // Recurring task: start → end (hoặc vô hạn)
                    if ($start && $end) {
                        return "{$start} → {$end}";
                    } elseif ($start) {
                        return "Từ: {$start}";
                    } else {
                        return 'Không giới hạn';
                    }
                }
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

        // Hiển thị trạng thái hoàn thành cho one-time tasks
        $grid->column('completion_status', __('Trạng thái hoàn thành'))
            ->display(function () {
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
                    $completedTime = $completion->completed_at_time ? $completion->completed_at_time->format('d/m H:i') : '';
                    $reviewBadge = $completion->review_status_badge;
                    
                    $html .= "<div style='margin-bottom: 5px;'>";
                    $html .= "<strong>{$userName}</strong> ({$completedTime})<br>";
                    $html .= $reviewBadge;
                    $html .= "</div>";
                }

                return $html;
            })->width(200);

        // Cột Review Actions - đơn giản không cần ghi chú
        $grid->column('review_actions', __('Review Actions'))
            ->display(function () {
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
                    
                    if ($completion->review_status == 0) {
                        // Chưa review → nút "Cần review"
                        $html .= '<a href="' . admin_url("daily-tasks/toggle-review/{$completion->id}/1") . '" 
                                   class="btn btn-xs btn-warning" 
                                   onclick="return confirm(\'Đánh dấu cần kiểm tra lại?\')">
                                   <i class="fa fa-exclamation-triangle"></i> Cần review
                                 </a>';
                    } else {
                        // Đang cần review → nút "OK"
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
            $filter->equal('task_type', 'Loại công việc')->select([
                'recurring' => 'Lặp lại',
                'one_time' => 'Một lần'
            ]);
            $filter->equal('priority', 'Ưu tiên')->select([
                'low' => 'Thấp',
                'medium' => 'Trung bình',
                'high' => 'Cao',
                'urgent' => 'Khẩn cấp'
            ]);
            $filter->where(function ($query) {
                $query->where('frequency', 'like', '%' . $this->input . '%');
            }, 'Tần suất');
            $filter->equal('is_active', 'Trạng thái')->select([1 => 'Hoạt động', 0 => 'Tạm dừng']);
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            // Loại bỏ tất cả logic phức tạp, chỉ giữ disable view
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
        
        // Radio chọn loại task
        $form->radio('task_type', __('Loại công việc'))
            ->options([
                'recurring' => 'Lặp lại theo tần suất',
                'one_time' => 'Thực hiện một lần trong khoảng thời gian'
            ])
            ->default('recurring')
            ->help('• Lặp lại: Task sẽ lặp lại theo tần suất trong khoảng thời gian<br>• Một lần: Task chỉ cần hoàn thành 1 lần trong khoảng thời gian');
            
        // Frequency field  
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
            ->help('Chọn tần suất cho công việc lặp lại. Bỏ qua nếu chọn "Thực hiện một lần"');
            
        // Date fields - dùng chung cho cả 2 loại
        $form->date('start_date', __('Ngày bắt đầu'))
            ->help('Ngày bắt đầu có thể thực hiện công việc');
        $form->date('end_date', __('Ngày kết thúc / Deadline'))
            ->help('• Lặp lại: Ngày kết thúc lặp lại (để trống = vô hạn)<br>• Một lần: Deadline phải hoàn thành');
        
        
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

    /**
     * Toggle review status - cập nhật để set status = in_process khi admin yêu cầu review
     */
    public function toggleReview($completionId, $status)
    {
        try {
            $completion = \App\Models\UserTaskCompletion::findOrFail($completionId);
            
            if ($status == 1) {
                // Admin yêu cầu review: set review_status = 1, status = in_process
                $completion->update([
                    'review_status' => 1,
                    'status' => 'in_process'
                ]);
                $message = 'Đã yêu cầu nhân viên kiểm tra lại công việc!';
            } else {
                // Admin hoàn thành review: set review_status = 0, status = completed
                $completion->update([
                    'review_status' => 0,
                    'status' => 'completed'
                ]);
                $message = 'Đã xác nhận công việc hoàn thành!';
            }

            admin_toastr($message, 'success');
            
        } catch (\Exception $e) {
            \Log::error("Toggle review error: " . $e->getMessage());
            admin_toastr('Có lỗi xảy ra: ' . $e->getMessage(), 'error');
        }
        
        return redirect()->back();
    }
}