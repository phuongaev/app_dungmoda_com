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
     * Make a grid builder - Fixed encoding issues
     */
    protected function grid()
    {
        $grid = new Grid(new PosOrderWorkflowHistory());

        $grid->model()->orderBy('executed_at', 'desc');
        
        // Eager load relationships with proper encoding - Added workflow relationship
        $grid->model()->with(['posOrder', 'workflow']);

        $grid->column('id', 'ID')->sortable()->width(100);
        
        // Fixed customer name encoding issue
        $grid->column('posOrder.order_id', 'Mã đơn hàng')
            ->display(function ($orderId) {
                if (!$orderId) return '<span class="text-muted">N/A</span>';
                $safeOrderId = htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8');
                return "<a href='/admin/pos-orders/{$this->pos_order_id}' target='_blank'>{$safeOrderId}</a>";
            })
            ->width(120);

        // Fixed customer name with proper encoding
        $grid->column('posOrder.customer_name', 'Khách hàng')
            ->display(function ($customerName) {
                if (!$customerName) return '<span class="text-muted">N/A</span>';
                
                // Ensure proper UTF-8 encoding
                $safeName = mb_convert_encoding($customerName, 'UTF-8', 'UTF-8');
                $safeName = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');
                
                // Truncate if too long
                if (mb_strlen($safeName, 'UTF-8') > 20) {
                    $safeName = mb_substr($safeName, 0, 20, 'UTF-8') . '...';
                }
                
                return $safeName;
            })
            ->width(140);

        $grid->column('workflow_status_id', 'Workflow Status ID')
            ->display(function ($statusId) {
                if (!$statusId) return '<span class="text-muted">N/A</span>';
                return "<span class='label label-primary'>{$statusId}</span>";
            })
            ->width(120);

        $grid->column('workflow_id', 'Workflow ID')
            ->display(function ($workflowId) {
                if (!$workflowId) return '<span class="text-muted">N/A</span>';
                $safeWorkflowId = htmlspecialchars($workflowId, ENT_QUOTES, 'UTF-8');
                return "<span class='label label-info'>{$safeWorkflowId}</span>";
            })
            ->width(120);

        // NEW: Thêm cột Workflow Name
        $grid->column('workflow.workflow_name', 'Workflow Name')
            ->display(function ($workflowName) {
                if (!$workflowName) {
                    // Fallback: nếu không có workflow name, hiển thị workflow_id
                    $workflowId = $this->workflow_id ?? 'N/A';
                    return "<span class='text-muted'>{$workflowId}</span>";
                }
                
                // Ensure proper UTF-8 encoding
                $safeName = mb_convert_encoding($workflowName, 'UTF-8', 'UTF-8');
                $safeName = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');
                
                // Truncate if too long
                if (mb_strlen($safeName, 'UTF-8') > 50) {
                    $safeName = mb_substr($safeName, 0, 50, 'UTF-8') . '...';
                }
                
                return "<span class='text-primary'>{$safeName}</span>";
            })
            ->width(280);

        $grid->column('executed_at', 'Thời gian chạy')
            ->display(function ($executedAt) {
                if (!$executedAt) return '<span class="text-muted">N/A</span>';
                try {
                    return Carbon::parse($executedAt)->format('d/m/Y H:i:s');
                } catch (\Exception $e) {
                    return '<span class="text-muted">Invalid date</span>';
                }
            })
            ->sortable()
            ->width(170);

        $grid->column('created_at', 'Ngày tạo')
            ->display(function ($createdAt) {
                if (!$createdAt) return '<span class="text-muted">N/A</span>';
                try {
                    return Carbon::parse($createdAt)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    return '<span class="text-muted">Invalid date</span>';
                }
            })
            ->sortable()
            ->width(170);
            
        // Filters with proper encoding
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('posOrder.order_id', 'Mã đơn hàng');
            $filter->like('posOrder.customer_name', 'Tên khách hàng');
            $filter->equal('workflow_id', 'Workflow ID');
            // NEW: Thêm filter theo Workflow Name
            $filter->like('workflow.workflow_name', 'Workflow Name');
            $filter->equal('workflow_status_id', 'Workflow Status ID');
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
            // $actions->disableView();
        });

        // Bulk actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        // Grid settings - Fixed footer issues
        $grid->paginate(20);
        $grid->disableCreateButton();
        $grid->disableExport();
        
        // Fix grid footer encoding
        $grid->footer(function ($query) {
            $total = $query->count();
            return "<div class='text-center'><small>Tổng cộng: <strong>{$total}</strong> bản ghi</small></div>";
        });

        return $grid;
    }

    /**
     * Grid for specific order - Fixed encoding
     */
    protected function gridByOrder($order)
    {
        $grid = new Grid(new PosOrderWorkflowHistory());
        
        $grid->model()->where('pos_order_id', $order->id)
            ->orderBy('executed_at', 'desc')
            ->with(['workflow']); // Added workflow relationship

        $grid->column('id', 'ID');
        
        $grid->column('workflow_status_id', 'Workflow Status ID')
            ->display(function ($statusId) {
                if (!$statusId) return '<span class="text-muted">N/A</span>';
                return "<span class='label label-primary'>{$statusId}</span>";
            });

        $grid->column('workflow_id', 'Workflow ID')
            ->display(function ($workflowId) {
                if (!$workflowId) return '<span class="text-muted">N/A</span>';
                $safeWorkflowId = htmlspecialchars($workflowId, ENT_QUOTES, 'UTF-8');
                return "<span class='label label-info'>{$safeWorkflowId}</span>";
            });

        // NEW: Thêm Workflow Name cho grid by order
        $grid->column('workflow.workflow_name', 'Workflow Name')
            ->display(function ($workflowName) {
                if (!$workflowName) {
                    $workflowId = $this->workflow_id ?? 'N/A';
                    return "<span class='text-muted'>{$workflowId}</span>";
                }
                
                $safeName = mb_convert_encoding($workflowName, 'UTF-8', 'UTF-8');
                $safeName = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');
                
                if (mb_strlen($safeName, 'UTF-8') > 30) {
                    $safeName = mb_substr($safeName, 0, 30, 'UTF-8') . '...';
                }
                
                return "<span class='text-primary'>{$safeName}</span>";
            });

        $grid->column('executed_at', 'Thời gian chạy')
            ->display(function ($executedAt) {
                if (!$executedAt) return '<span class="text-muted">N/A</span>';
                try {
                    return Carbon::parse($executedAt)->format('d/m/Y H:i:s');
                } catch (\Exception $e) {
                    return '<span class="text-muted">Invalid date</span>';
                }
            })
            ->sortable();

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->paginate(20);

        return $grid;
    }

    /**
     * Make a show builder - Fixed encoding
     */
    protected function detail($id)
    {
        $show = new Show(PosOrderWorkflowHistory::with(['posOrder', 'workflow'])->findOrFail($id));

        $show->field('id', 'ID');
        
        $show->divider();
        
        $show->field('posOrder.order_id', 'Mã đơn hàng');
        $show->field('posOrder.customer_name', 'Tên khách hàng')->as(function ($name) {
            return $name ? mb_convert_encoding($name, 'UTF-8', 'UTF-8') : 'N/A';
        });
        $show->field('posOrder.customer_phone', 'Số điện thoại');
        
        $show->divider();
        
        $show->field('workflow_status_id', 'Workflow Status ID');
        $show->field('workflow_id', 'Workflow ID');
        // NEW: Thêm Workflow Name vào detail view
        $show->field('workflow.workflow_name', 'Workflow Name')->as(function ($name) {
            return $name ? mb_convert_encoding($name, 'UTF-8', 'UTF-8') : 'N/A';
        });
        
        $show->divider();
        
        $show->field('executed_at', 'Thời gian chạy');
        $show->field('created_at', 'Ngày tạo');

        return $show;
    }

    /**
     * Statistics boxes - Fixed encoding issues
     */
    protected function statisticsBoxes()
    {
        try {
            $today = Carbon::today();
            $last7Days = Carbon::now()->subDays(7);
            $last30Days = Carbon::now()->subDays(30);

            // Safe statistics calculation
            $todayCount = PosOrderWorkflowHistory::whereDate('executed_at', $today)->count();
            $last7DaysCount = PosOrderWorkflowHistory::where('executed_at', '>=', $last7Days)->count();
            $last30DaysCount = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)->count();
            $totalCount = PosOrderWorkflowHistory::count();

            $statsHtml = "
            <div class='row'>
                <div class='col-md-3'>
                    <div class='info-box'>
                        <span class='info-box-icon bg-aqua'><i class='fa fa-calendar'></i></span>
                        <div class='info-box-content'>
                            <span class='info-box-text'>Hôm nay</span>
                            <span class='info-box-number'>" . number_format($todayCount) . "</span>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='info-box'>
                        <span class='info-box-icon bg-green'><i class='fa fa-clock-o'></i></span>
                        <div class='info-box-content'>
                            <span class='info-box-text'>7 ngày qua</span>
                            <span class='info-box-number'>" . number_format($last7DaysCount) . "</span>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='info-box'>
                        <span class='info-box-icon bg-yellow'><i class='fa fa-calendar-check-o'></i></span>
                        <div class='info-box-content'>
                            <span class='info-box-text'>30 ngày qua</span>
                            <span class='info-box-number'>" . number_format($last30DaysCount) . "</span>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='info-box'>
                        <span class='info-box-icon bg-red'><i class='fa fa-database'></i></span>
                        <div class='info-box-content'>
                            <span class='info-box-text'>Tổng cộng</span>
                            <span class='info-box-number'>" . number_format($totalCount) . "</span>
                        </div>
                    </div>
                </div>
            </div>";

            return new Box('Thống kê tổng quan', $statsHtml);

        } catch (\Exception $e) {
            return new Box('Thống kê tổng quan', '<div class="alert alert-warning">Không thể tải thống kê</div>');
        }
    }

    /**
     * Detailed statistics - Fixed encoding
     */
    protected function detailedStatistics()
    {
        try {
            $last30Days = Carbon::now()->subDays(30);

            // Top 10 workflow (safe query)
            $topWorkflows = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)
                ->whereNotNull('workflow_id')
                ->selectRaw('workflow_id, COUNT(*) as count')
                ->groupBy('workflow_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // Top 10 workflow status (safe query)
            $topStatuses = PosOrderWorkflowHistory::where('executed_at', '>=', $last30Days)
                ->selectRaw('workflow_status_id, COUNT(*) as count')
                ->groupBy('workflow_status_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // Daily stats (safe query)
            $dailyStats = PosOrderWorkflowHistory::where('executed_at', '>=', Carbon::now()->subDays(7))
                ->selectRaw('DATE(executed_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            $content = '';

            // Top Workflows table
            if ($topWorkflows->isNotEmpty()) {
                $workflowTable = '<table class="table table-striped">
                    <thead><tr><th>Workflow ID</th><th>Số lần chạy</th></tr></thead>
                    <tbody>';
                
                foreach ($topWorkflows as $item) {
                    $safeWorkflowId = htmlspecialchars($item->workflow_id, ENT_QUOTES, 'UTF-8');
                    $workflowTable .= "<tr><td>{$safeWorkflowId}</td><td>" . number_format($item->count) . "</td></tr>";
                }
                
                $workflowTable .= '</tbody></table>';
                $content .= new Box('Top Workflows (30 ngày qua)', $workflowTable);
            }

            // Top Statuses table
            if ($topStatuses->isNotEmpty()) {
                $statusTable = '<table class="table table-striped">
                    <thead><tr><th>Workflow Status ID</th><th>Số lần chạy</th></tr></thead>
                    <tbody>';
                
                foreach ($topStatuses as $item) {
                    $statusTable .= "<tr><td>{$item->workflow_status_id}</td><td>" . number_format($item->count) . "</td></tr>";
                }
                
                $statusTable .= '</tbody></table>';
                $content .= new Box('Top Workflow Status (30 ngày qua)', $statusTable);
            }

            // Daily Stats table
            if ($dailyStats->isNotEmpty()) {
                $dailyTable = '<table class="table table-striped">
                    <thead><tr><th>Ngày</th><th>Số lần chạy</th></tr></thead>
                    <tbody>';
                
                foreach ($dailyStats as $item) {
                    $date = Carbon::parse($item->date)->format('d/m/Y');
                    $dailyTable .= "<tr><td>{$date}</td><td>" . number_format($item->count) . "</td></tr>";
                }
                
                $dailyTable .= '</tbody></table>';
                $content .= new Box('Thống kê theo ngày (7 ngày qua)', $dailyTable);
            }

            return $content;

        } catch (\Exception $e) {
            return new Box('Thống kê chi tiết', '<div class="alert alert-warning">Không thể tải thống kê chi tiết</div>');
        }
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