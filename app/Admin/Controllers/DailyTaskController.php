<?php
// app/Admin/Controllers/DailyTaskController.php (Optimized)

namespace App\Admin\Controllers;

use App\Models\DailyTask;
use App\Models\TaskCategory;
use App\Models\UserTaskCompletion;
use App\Services\TaskService;
use App\Repositories\TaskRepository;
use Encore\Admin\Controllers\AdminController;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class DailyTaskController extends Controller
{
    use HasResourceActions;

    protected $title = 'Công việc hàng ngày';
    protected $taskService;
    protected $taskRepository;

    public function __construct(TaskService $taskService, TaskRepository $taskRepository)
    {
        $this->taskService = $taskService;
        $this->taskRepository = $taskRepository;
    }

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
        $grid->model()->with(['category', 'creator'])->orderBy('id', 'desc');
        
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
            $label = $labels[$priority] ?? $priority;
            return "<span class='label label-{$color}'>{$label}</span>";
        });

        $grid->column('task_type', 'Loại')->display(function ($type) {
            $color = $type === 'one_time' ? 'warning' : 'info';
            $label = $type === 'one_time' ? 'One Time' : 'Lặp lại';
            return "<span class='label label-{$color}'>{$label}</span>";
        });

        $grid->column('frequency_label', 'Tần suất')->display(function () {
            return $this->frequency_label;
        });

        $grid->column('suggested_time', 'Giờ đề xuất')->display(function ($time) {
            if (!$time) return '-';
            // Kiểm tra nếu là string thì parse thành datetime trước
            if (is_string($time)) {
                return date('H:i', strtotime($time));
            }
            // Nếu là datetime object thì dùng format
            return $time->format('H:i');
        });
            
        $grid->column('assigned_users', 'Người được giao')->display(function ($users) {
            return empty($users) ? '<span class="text-muted">Tất cả</span>' : 
                   '<span class="label label-info">' . count($users) . ' người</span>';
        });
            
        $grid->column('is_active', 'Trạng thái')->display(function ($active) {
            return $active ? '<span class="label label-success">Hoạt động</span>' : 
                            '<span class="label label-danger">Tạm dừng</span>';
        });

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
                    $html .= '<a href="' . admin_url("daily-tasks/toggle-review/{$completion->id}/1") . '"';
                    $html .= ' class="btn btn-xs btn-warning">Cần review</a>';
                } else {
                    $html .= '<a href="' . admin_url("daily-tasks/toggle-review/{$completion->id}/0") . '"';
                    $html .= ' class="btn btn-xs btn-success">Xác nhận OK</a>';
                }
                $html .= '</div>';
            }
            return $html;
        })->width(150);

        $this->configureGridActions($grid);
        $this->configureGridFilters($grid);

        return $grid;
    }

    private function configureGridActions($grid)
    {
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });
    }

    private function configureGridFilters($grid)
    {
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('title', 'Tiêu đề');
            $filter->equal('category_id', 'Danh mục')->select(TaskCategory::pluck('name', 'id'));
            $filter->equal('priority', 'Độ ưu tiên')->select($this->getPriorityOptions());
            $filter->equal('task_type', 'Loại task')->select(['recurring' => 'Lặp lại', 'one_time' => 'One Time']);
            $filter->equal('is_active', 'Trạng thái')->select([1 => 'Hoạt động', 0 => 'Tạm dừng']);
        });
    }

    protected function detail($id)
    {
        $show = new Show(DailyTask::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title', 'Tiêu đề');
        $show->field('description', 'Mô tả');
        $show->field('category.name', 'Danh mục');
        $show->field('priority', 'Ưu tiên')->using($this->getPriorityLabels());
        $show->field('task_type', 'Loại task')->using(['recurring' => 'Lặp lại', 'one_time' => 'One Time']);
        $show->field('frequency_label', 'Tần suất');
        $show->field('suggested_time', 'Giờ đề xuất');
        $show->field('estimated_minutes', 'Thời gian ước tính (phút)');
        $show->field('start_date', 'Ngày bắt đầu');
        $show->field('end_date', 'Ngày kết thúc');
        $show->field('is_required', 'Bắt buộc')->using([0 => 'Không', 1 => 'Có']);
        $show->field('is_active', 'Hoạt động')->using([0 => 'Không', 1 => 'Có']);
        $show->field('created_at', 'Ngày tạo');
        $show->field('updated_at', 'Ngày cập nhật');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new DailyTask());

        $form->text('title', 'Tiêu đề')->required();
        $form->textarea('description', 'Mô tả');
        
        $form->select('category_id', 'Danh mục')
            ->options(TaskCategory::where('is_active', 1)->pluck('name', 'id'))
            ->required();
            
        $form->select('priority', 'Độ ưu tiên')
            ->options($this->getPriorityOptions())
            ->default('medium')
            ->required();

        $form->select('task_type', 'Loại task')
            ->options(['recurring' => 'Lặp lại', 'one_time' => 'One Time'])
            ->default('recurring')
            ->required();

        // FIXED: Chỉ 1 field frequency duy nhất, không dùng when()
        $form->multipleSelect('frequency', 'Tần suất')
            ->options($this->getFrequencyOptions())
            ->default(['daily']);

        $form->time('suggested_time', 'Giờ đề xuất');
        $form->number('estimated_minutes', 'Thời gian ước tính (phút)')->min(1);
        
        $form->date('start_date', 'Ngày bắt đầu');
        $form->date('end_date', 'Ngày kết thúc');
        
        $form->multipleSelect('assigned_roles', 'Vai trò được giao')
            ->options($this->getRoleOptions());
            
        $form->multipleSelect('assigned_users', 'Người dùng cụ thể')
            ->options($this->getUserOptions());

        $form->switch('is_required', 'Bắt buộc')->default(1);
        $form->switch('is_active', 'Hoạt động')->default(1);
        $form->number('sort_order', 'Thứ tự sắp xếp')->default(0);

        $form->hidden('created_by')->default(Admin::user()->id);

        // FIXED: Thêm JavaScript để handle show/hide frequency field
        Admin::script('
            $(document).ready(function() {
                function toggleFrequencyField() {
                    var taskType = $(\'select[name="task_type"]\').val();
                    var frequencyGroup = $(\'select[name="frequency[]"]\').closest(\'.form-group\');
                    var frequencyLabel = frequencyGroup.find(\'label\');
                    
                    if (taskType === \'one_time\') {
                        frequencyGroup.hide();
                        // Set giá trị one_time cho hidden input
                        $(\'select[name="frequency[]"]\').val([\'one_time\']).trigger(\'change\');
                    } else {
                        frequencyGroup.show();
                        // Reset về daily nếu chưa có giá trị
                        var currentVal = $(\'select[name="frequency[]"]\').val();
                        if (!currentVal || currentVal.length === 0 || currentVal.includes(\'one_time\')) {
                            $(\'select[name="frequency[]"]\').val([\'daily\']).trigger(\'change\');
                        }
                    }
                }
                
                // Chạy lần đầu khi load
                toggleFrequencyField();
                
                // Chạy khi thay đổi task_type
                $(\'select[name="task_type"]\').on(\'change\', function() {
                    toggleFrequencyField();
                });
            });
        ');

        return $form;
    }

    // Review actions
    public function toggleReview($completionId, $status)
    {
        try {
            $result = $this->taskService->toggleReviewStatus($completionId, $status);
            admin_toastr($result['message'], 'success');
        } catch (\Exception $e) {
            admin_toastr('Có lỗi xảy ra: ' . $e->getMessage(), 'error');
        }

        return back();
    }

    public function updateCompletionNote(Request $request, $completionId)
    {
        try {
            $completion = UserTaskCompletion::findOrFail($completionId);
            $completion->update(['notes' => $request->input('notes', '')]);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ghi chú thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper methods for options
    private function getPriorityOptions()
    {
        return ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    }

    private function getPriorityLabels()
    {
        return ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    }

    private function getFrequencyOptions()
    {
        return [
            'daily' => 'Hàng ngày',
            'weekdays' => 'Ngày làm việc',
            'weekends' => 'Cuối tuần',
            'monday' => 'Thứ 2',
            'tuesday' => 'Thứ 3',
            'wednesday' => 'Thứ 4',
            'thursday' => 'Thứ 5',
            'friday' => 'Thứ 6',
            'saturday' => 'Thứ 7',
            'sunday' => 'Chủ nhật'
        ];
    }

    private function getRoleOptions()
    {
        return \Encore\Admin\Auth\Database\Role::pluck('name', 'slug')->toArray();
    }

    private function getUserOptions()
    {
        return \Encore\Admin\Auth\Database\Administrator::pluck('name', 'id')->toArray();
    }
}