<?php

namespace App\Admin\Controllers;

use App\Models\PosOrderWorkflowHistory;
use App\Models\PosOrder;
use App\Models\BaseStatus;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkflowHistoryController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     */
    public function index(Content $content)
    {
        return $content
            ->header('Lịch sử Workflow')
            ->description('Quản lý lịch sử chạy workflow cho đơn hàng')
            ->row($this->statisticsBoxes())
            ->body($this->grid());
    }

    /**
     * Show interface.
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Chi tiết lịch sử Workflow')
            ->description('Thông tin chi tiết')
            ->body($this->detail($id));
    }

    /**
     * Statistics page
     */
    public function statistics(Content $content)
    {
        return $content
            ->header('Thống kê Workflow')
            ->description('Báo cáo và phân tích workflow')
            ->row($this->statisticsBoxes())
            ->row($this->detailedStatistics());
    }

    /**
     * View workflow histories by order
     */
    public function byOrder($orderId, Content $content)
    {
        $order = PosOrder::where('order_id', $orderId)->first();
        
        if (!$order) {
            return $content
                ->header('Không tìm thấy đơn hàng')
                ->body('<div class="alert alert-warning">Đơn hàng không tồn tại.</div>');
        }

        return $content
            ->header("Lịch sử Workflow - Đơn hàng: {$orderId}")
            ->description('Chi tiết các lần chạy workflow')
            ->body($this->gridByOrder($order));
    }

    /**
     * Make a grid builder.
     */
    protected function grid()
    {
        $grid = new Grid(new PosOrderWorkflowHistory());

        $grid->model()->orderBy('executed_at', 'desc');
        
        // Eager load relationships
        $grid->model()->with(['posOrder', 'workflowStatus']);

        $grid->column('id', 'ID')->sortable();
        
        $grid->column('posOrder.order_id', 'Mã đơn hàng')
            ->display(function ($orderId) {
                return "<a href='/admin/pos-orders/{$this->pos_order_id}' target='_blank'>{$orderId}</a>";
            })
            ->width(120);

        $grid->column('posOrder.customer_name', 'Khách hàng')
            ->display(function ($customerName) {
                return strlen($customerName) > 20 ? substr($customerName, 0, 20) . '...' : $customerName;
            })
            ->width(140);

        $grid->column('workflowStatus.status_name', 'Workflow Status')
            ->display(function ($statusName) {
                return "<span class='label label-primary'>{$statusName}</span>";
            })
            ->width(150);

        $grid->column('workflow_id', 'Workflow ID')
            ->display(function ($workflowId) {
                return $workflowId ? "<span class='label label-info'>{$workflowId}</span>" : '<span class="text-muted">N/A</span>';
            })
            ->width(100);

        $grid->column('executed_at', 'Thời gian chạy')
            ->display(function ($executedAt) {
                return Carbon::parse($executedAt)->format('d/m/Y H:i:s');
            })
            ->sortable()
            ->width(140);

        $grid->column('created_at', 'Ngày tạo')
            ->display(function ($createdAt) {
                return Carbon::parse($createdAt)->format('d/m/Y H:i');
            })
            ->sortable()
            ->width(120);
            
        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('posOrder.order_id', 'Mã đơn hàng');
            $filter->like('posOrder.customer_name', 'Tên khách hàng');
            
            $filter->equal('workflow_id', 'Workflow ID');
            $filter->equal('workflow_status_id', 'Workflow Status')->select(
                BaseStatus::pluck('status_name', 'status_id')->toArray()
            );
            
            $filter->between('executed_at', 'Thời gian chạy')->datetime();
            
            $filter->scope('today', 'Hôm nay')->where(function ($query) {
                $query->whereDate('executed_at', Carbon::today());
            });
            
            $filter->scope('last_7_days', '7 ngày qua')->where(function ($query) {
                $query->where('executed_at', '>=', Carbon::now()->subDays(7));
            });
            
            $filter->scope('last_30_days', '30 ngày qua')->where(function ($query) {
                $query->where('executed_at', '>=', Carbon::now()->subDays(30));
            });
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
        });

        // Bulk actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        $grid->paginate(50);
        $grid->disableCreateButton();
        $grid->disableExport();

        return $grid;
    }

    /**
     * Grid for specific order
     */
    protected function gridByOrder($order)
    {
        $grid = new Grid(new PosOrderWorkflowHistory());
        
        $grid->model()->where('pos_order_id', $order->id)
            ->orderBy('executed_at', 'desc')
            ->with('workflowStatus');

        $grid->column('id', 'ID');
        
        $grid->column('workflowStatus.status_name', 'Workflow Status')
            ->display(function ($statusName) {
                return "<span class='label label-primary'>{$statusName}</span>";
            });

        $grid->column('workflow_id', 'Workflow ID')
            ->display(function ($workflowId) {
                return $workflowId ? "<span class='label label-info'>{$workflowId}</span>" : '<span class="text-muted">N/A</span>';
            });

        $grid->column('executed_at', 'Thời gian chạy')
            ->display(function ($executedAt) {
                return Carbon::parse($executedAt)->format('d/m/Y H:i:s');
            })
            ->sortable();

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->paginate(20);

        return $grid;
    }

    /**
     * Make a show builder.
     */
    protected function detail($id)
    {
        $show = new Show(PosOrderWorkflowHistory::findOrFail($id));

        $show->field('id', 'ID');
        
        $show->divider();
        
        $show->field('posOrder.order_id', 'Mã đơn hàng');
        $show->field('posOrder.customer_name', 'Tên khách hàng');
        $show->field('posOrder.customer_phone', 'Số điện thoại');
        
        $show->divider();
        
        $show->field('workflowStatus.status_name', 'Workflow Status');
        $show->field('workflow_status_id', 'Workflow Status ID');
        $show->field('workflow_id', 'Workflow ID');
        
        $show->divider();
        
        $show->field('executed_at', 'Thời gian chạy');
        $show->field('created_at', 'Ngày tạo');
        $show->field('updated_at', 'Ngày cập nhật');

        return $show;
    }

    /**
     * Statistics boxes
     */
    protected function statisticsBoxes()
    {
        $today = Carbon::today();
        $last7Days = Carbon::now()->subDays(7);
        $last30Days = Carbon::now()->subDays(30);

        // Thống kê hôm nay
        $todayCount = PosOrderWorkflowHistory::whereDate('executed_at', $today)->count();
        
        // Thống kê 7 ngày qua
        $last7DaysCount = PosOrderWorkflowHistory::where('executed_at', '>=', $last7Days)->count();
        
        // Thống kê 30 ngày qua
        $last30DaysCount = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)->count();
        
        // Tổng số lịch sử
        $totalCount = PosOrderWorkflowHistory::count();

        return [
            new InfoBox('Hôm nay', 'calendar', 'aqua', $todayCount, '/admin/workflow-histories?scope=today'),
            new InfoBox('7 ngày qua', 'clock-o', 'green', $last7DaysCount, '/admin/workflow-histories?scope=last_7_days'),
            new InfoBox('30 ngày qua', 'calendar-check-o', 'yellow', $last30DaysCount, '/admin/workflow-histories?scope=last_30_days'),
            new InfoBox('Tổng cộng', 'database', 'red', $totalCount, '/admin/workflow-histories')
        ];
    }

    /**
     * Detailed statistics
     */
    protected function detailedStatistics()
    {
        $last30Days = Carbon::now()->subDays(30);

        // Top 10 workflow được chạy nhiều nhất trong 30 ngày
        $topWorkflows = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)
            ->whereNotNull('workflow_id')
            ->selectRaw('workflow_id, COUNT(*) as count')
            ->groupBy('workflow_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Top 10 workflow status trong 30 ngày
        $topStatuses = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)
            ->join('base_statuses', 'pos_order_workflow_histories.workflow_status_id', '=', 'base_statuses.status_id')
            ->selectRaw('base_statuses.status_name, COUNT(*) as count')
            ->groupBy('base_statuses.status_id', 'base_statuses.status_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Thống kê theo ngày trong 7 ngày qua
        $dailyStats = PosOrderWorkflowHistory::where('executed_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(executed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $content = '';

        // Top Workflows
        if ($topWorkflows->isNotEmpty()) {
            $workflowHeaders = ['Workflow ID', 'Số lần chạy'];
            $workflowRows = $topWorkflows->map(function($item) {
                return [$item->workflow_id, $item->count];
            })->toArray();
            
            $workflowTable = new \Encore\Admin\Widgets\Table($workflowHeaders, $workflowRows);
            $content .= new Box('Top Workflows (30 ngày qua)', $workflowTable->render());
        }

        // Top Statuses
        if ($topStatuses->isNotEmpty()) {
            $statusHeaders = ['Workflow Status', 'Số lần chạy'];
            $statusRows = $topStatuses->map(function($item) {
                return [$item->status_name, $item->count];
            })->toArray();
            
            $statusTable = new \Encore\Admin\Widgets\Table($statusHeaders, $statusRows);
            $content .= new Box('Top Workflow Statuses (30 ngày qua)', $statusTable->render());
        }

        // Daily Stats
        if ($dailyStats->isNotEmpty()) {
            $dailyHeaders = ['Ngày', 'Số lần chạy'];
            $dailyRows = $dailyStats->map(function($item) {
                return [Carbon::parse($item->date)->format('d/m/Y'), $item->count];
            })->toArray();
            
            $dailyTable = new \Encore\Admin\Widgets\Table($dailyHeaders, $dailyRows);
            $content .= new Box('Thống kê theo ngày (7 ngày qua)', $dailyTable->render());
        }

        return $content;
    }

    /**
     * Bulk delete (if needed)
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->get('ids');
        
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Không có dữ liệu để xóa'
            ]);
        }

        try {
            PosOrderWorkflowHistory::whereIn('id', $ids)->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Đã xóa ' . count($ids) . ' bản ghi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage()
            ]);
        }
    }
}