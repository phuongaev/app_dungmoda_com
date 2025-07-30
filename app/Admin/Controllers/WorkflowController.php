<?php

namespace App\Admin\Controllers;

use App\Models\Workflow;
use App\Models\PosOrderWorkflowHistory;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkflowController extends Controller
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
            ->header('Quản lý Workflow')
            ->description('Danh sách các workflow n8n')
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
            ->header('Chi tiết Workflow')
            ->description('Thông tin chi tiết và thống kê workflow')
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
            ->header('Chỉnh sửa Workflow')
            ->description('Cập nhật thông tin workflow')
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
            ->header('Tạo Workflow mới')
            ->description('Thêm workflow mới vào hệ thống')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Workflow());

        $grid->model()->orderBy('created_at', 'desc');

        // Columns
        $grid->column('id', 'ID')->sortable();
        $grid->column('workflow_id', 'Workflow ID')->sortable();
        $grid->column('workflow_name', 'Tên Workflow')->sortable();
        $grid->column('workflow_status', 'Trạng thái')->display(function ($status) {
            $color = $status === 'active' ? 'success' : 'danger';
            $text = $status === 'active' ? 'Hoạt động' : 'Không hoạt động';
            return "<span class='label label-{$color}'>{$text}</span>";
        })->sortable();

        // Thêm cột thống kê
        $grid->column('total_runs', 'Lượt chạy')->display(function () {
            return $this->workflowHistories()->count();
        });

        $grid->column('last_run', 'Lần chạy cuối')->display(function () {
            $lastRun = $this->workflowHistories()->latest()->first();
            return $lastRun ? $lastRun->executed_at->format('d/m/Y H:i') : 'Chưa chạy';
        });

        $grid->column('created_at', 'Ngày tạo')->display(function ($date) {
            return Carbon::parse($date)->format('d/m/Y H:i');
        })->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->like('workflow_name', 'Tên Workflow');
            $filter->like('workflow_id', 'Workflow ID');
            $filter->equal('workflow_status', 'Trạng thái')->select(Workflow::getStatusOptions());
            $filter->between('created_at', 'Ngày tạo')->datetime();
        });

        // Actions
        $grid->actions(function ($actions) {
            // Có thể thêm action xem thống kê chi tiết
            $actions->add(new \Encore\Admin\Grid\Actions\Show());
        });

        // Tools
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete(); // Không cho phép xóa hàng loạt
            });
        });

        $grid->paginate(20);

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Content
     */
    protected function detail($id)
    {
        $workflow = Workflow::with('workflowHistories.posOrder')->findOrFail($id);

        $show = new Show($workflow);

        // Thông tin cơ bản
        $show->field('id', 'ID');
        $show->field('workflow_id', 'Workflow ID');
        $show->field('workflow_name', 'Tên Workflow');
        $show->field('workflow_status', 'Trạng thái')->as(function ($status) {
            return $status === 'active' ? 'Hoạt động' : 'Không hoạt động';
        });

        $show->divider();

        // Thống kê
        $show->field('statistics', 'Thống kê')->as(function () use ($workflow) {
            return $this->renderWorkflowStatistics($workflow);
        })->escape(false);

        $show->divider();

        // Lịch sử chạy gần đây
        $show->field('recent_histories', 'Lịch sử chạy gần đây')->as(function () use ($workflow) {
            return $this->renderRecentHistories($workflow);
        })->escape(false);

        $show->divider();

        $show->field('created_at', 'Ngày tạo');
        $show->field('updated_at', 'Ngày cập nhật');

        return $show;
    }

    /**
     * Render workflow statistics
     */
    protected function renderWorkflowStatistics($workflow)
    {
        $totalRuns = $workflow->workflowHistories()->count();
        $uniqueOrders = $workflow->workflowHistories()->distinct('pos_order_id')->count();
        $lastRun = $workflow->workflowHistories()->latest()->first();
        $firstRun = $workflow->workflowHistories()->oldest()->first();

        // Thống kê theo tháng gần đây
        $monthlyStats = $workflow->workflowHistories()
            ->selectRaw('DATE(executed_at) as date, COUNT(*) as count')
            ->where('executed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $statsHtml = "
        <div class='row'>
            <div class='col-md-3'>
                <div class='info-box'>
                    <span class='info-box-icon bg-blue'><i class='fa fa-play'></i></span>
                    <div class='info-box-content'>
                        <span class='info-box-text'>Tổng lượt chạy</span>
                        <span class='info-box-number'>{$totalRuns}</span>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='info-box'>
                    <span class='info-box-icon bg-green'><i class='fa fa-shopping-cart'></i></span>
                    <div class='info-box-content'>
                        <span class='info-box-text'>Đơn hàng duy nhất</span>
                        <span class='info-box-number'>{$uniqueOrders}</span>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='info-box'>
                    <span class='info-box-icon bg-yellow'><i class='fa fa-clock-o'></i></span>
                    <div class='info-box-content'>
                        <span class='info-box-text'>Lần chạy cuối</span>
                        <span class='info-box-number'>" . ($lastRun ? $lastRun->executed_at->format('d/m H:i') : 'N/A') . "</span>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='info-box'>
                    <span class='info-box-icon bg-red'><i class='fa fa-calendar'></i></span>
                    <div class='info-box-content'>
                        <span class='info-box-text'>Lần chạy đầu</span>
                        <span class='info-box-number'>" . ($firstRun ? $firstRun->executed_at->format('d/m H:i') : 'N/A') . "</span>
                    </div>
                </div>
            </div>
        </div>";

        return $statsHtml;
    }

    /**
     * Render recent workflow histories
     */
    protected function renderRecentHistories($workflow)
    {
        $histories = $workflow->workflowHistories()
                             ->with(['posOrder', 'workflowStatus'])
                             ->orderBy('executed_at', 'desc')
                             ->limit(20)
                             ->get();

        if ($histories->isEmpty()) {
            return '<div class="alert alert-info">Chưa có lịch sử chạy nào.</div>';
        }

        $tableData = [];
        foreach ($histories as $history) {
            $tableData[] = [
                'order_id' => $history->posOrder ? $history->posOrder->order_id : 'N/A',
                'customer_name' => $history->posOrder ? $history->posOrder->customer_name : 'N/A',
                'workflow_status' => $history->workflowStatus ? $history->workflowStatus->name : 'N/A',
                'executed_at' => $history->executed_at ? $history->executed_at->format('d/m/Y H:i:s') : 'N/A'
            ];
        }

        $headers = ['Mã đơn hàng', 'Khách hàng', 'Trạng thái', 'Thời gian chạy'];
        $table = new Table($headers, $tableData);

        return new Box('20 lần chạy gần đây', $table->render());
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Workflow());

        $form->text('workflow_id', 'Workflow ID')
             ->rules('required|string|max:55')
             ->help('ID workflow từ n8n (tối đa 55 ký tự)');

        $form->text('workflow_name', 'Tên Workflow')
             ->rules('required|string|max:255')
             ->help('Tên mô tả cho workflow');

        $form->select('workflow_status', 'Trạng thái')
             ->options(Workflow::getStatusOptions())
             ->default('active')
             ->rules('required');

        // Custom validation
        $form->saving(function (Form $form) {
            // Kiểm tra workflow_id unique khi tạo mới
            if (!$form->model()->id) {
                $exists = Workflow::where('workflow_id', $form->workflow_id)->exists();
                if ($exists) {
                    throw new \Exception('Workflow ID đã tồn tại trong hệ thống');
                }
            }
        });

        return $form;
    }
}