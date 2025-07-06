<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Models\SyncJob;
use App\Services\PosApiService;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use HasResourceActions;

    protected $posApiService;

    public function __construct(PosApiService $posApiService)
    {
        $this->posApiService = $posApiService;
    }

    /**
     * Index interface.
     */
    public function index(Content $content)
    {
        return $content
            ->header('Quản lý đơn hàng')
            ->description('Danh sách đơn hàng từ POS')
            ->row($this->getSyncStatusBoxes())
            ->body($this->grid());
    }

    /**
     * Show interface.
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Chi tiết đơn hàng')
            ->description('Thông tin chi tiết')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Chỉnh sửa đơn hàng')
            ->description('Cập nhật thông tin')
            ->body($this->form()->edit($id));
    }

    /**
     * Sync orders from API
     */
    public function syncOrders(Request $request)
    {
        try {
            $startPage = $request->get('start_page', 1);
            $maxPages = $request->get('max_pages', null);
            
            $result = $this->posApiService->syncOrders($startPage, 50, $maxPages);
            
            admin_toastr('Sync thành công! Đã đồng bộ ' . $result['synced_records'] . ' đơn hàng.', 'success');
            
        } catch (\Exception $e) {
            admin_toastr('Lỗi sync: ' . $e->getMessage(), 'error');
        }

        return redirect()->back();
    }

    /**
     * Resume sync job
     */
    public function resumeSync()
    {
        try {
            $result = $this->posApiService->resumeSync();
            admin_toastr('Resume sync thành công!', 'success');
        } catch (\Exception $e) {
            admin_toastr('Lỗi resume sync: ' . $e->getMessage(), 'error');
        }

        return redirect()->back();
    }

    /**
     * Pause running sync job
     */
    public function pauseSync()
    {
        try {
            $job = $this->posApiService->pauseRunningJob();
            if ($job) {
                admin_toastr('Đã dừng sync job thành công!', 'success');
            } else {
                admin_toastr('Không có sync job nào đang chạy.', 'warning');
            }
        } catch (\Exception $e) {
            admin_toastr('Lỗi khi dừng sync: ' . $e->getMessage(), 'error');
        }

        return redirect()->back();
    }

    /**
     * Get sync status boxes for dashboard
     */
    protected function getSyncStatusBoxes()
    {
        $status = $this->posApiService->getSyncStatus();
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        
        $boxes = collect([
            new InfoBox('Tổng đơn hàng', 'shopping-cart', 'blue', $totalOrders, '/admin/orders'),
            new InfoBox('Đơn hôm nay', 'calendar', 'green', $todayOrders, '/admin/orders'),
        ]);

        // Sync status box
        switch ($status['status']) {
            case 'running':
                $boxes->push(new InfoBox(
                    'Đang sync (' . $status['progress_percentage'] . '%)',
                    'refresh',
                    'yellow',
                    $status['synced_records'] . '/' . $status['total_records'],
                    '/admin/orders'
                ));
                break;
            case 'completed':
                $boxes->push(new InfoBox(
                    'Sync hoàn thành',
                    'check',
                    'green',
                    $status['synced_records'] . ' records',
                    '/admin/orders'
                ));
                break;
            case 'failed':
                $boxes->push(new InfoBox(
                    'Sync lỗi',
                    'exclamation-triangle',
                    'red',
                    'Xem log',
                    '/admin/orders'
                ));
                break;
            case 'paused':
                $boxes->push(new InfoBox(
                    'Sync tạm dừng',
                    'pause',
                    'orange',
                    'Page ' . $status['current_page'],
                    '/admin/orders'
                ));
                break;
            default:
                $boxes->push(new InfoBox(
                    'Chưa sync',
                    'database',
                    'gray',
                    'Bắt đầu sync',
                    '/admin/orders'
                ));
        }

        return $boxes->map(function ($box) {
            return $box->render();
        })->implode('');
    }

    /**
     * Make a grid builder.
     */
    protected function grid()
    {
        $grid = new Grid(new Order);

        // Sắp xếp mặc định
        $grid->model()->orderBy('inserted_at', 'desc');

        // Thêm các nút action
        $this->addGridActions($grid);

        // Filters - Tối ưu hiệu suất tìm kiếm
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // Tìm kiếm số điện thoại - tối ưu với index
            $filter->where(function ($query) {
                $phone = $this->input;
                if ($phone) {
                    // Exact search nếu là số thuần
                    if (preg_match('/^\d+$/', $phone)) {
                        $query->where('bill_phone_number', $phone);
                    } else {
                        // Partial search với FULLTEXT
                        $query->whereRaw('MATCH(bill_phone_number) AGAINST(? IN BOOLEAN MODE)', ["+{$phone}*"]);
                    }
                }
            }, 'Số điện thoại');
            
            // Tìm kiếm order_id - tối ưu với index
            $filter->where(function ($query) {
                $orderId = $this->input;
                if ($orderId) {
                    // Exact search nếu full order_id
                    if (strlen($orderId) > 8) {
                        $query->where('order_id', $orderId);
                    } else {
                        // Partial search với FULLTEXT
                        $query->whereRaw('MATCH(order_id) AGAINST(? IN BOOLEAN MODE)', ["+{$orderId}*"]);
                    }
                }
            }, 'Mã đơn hàng');
            
            // Tìm kiếm tên khách hàng - sử dụng FULLTEXT
            $filter->where(function ($query) {
                $name = $this->input;
                if ($name) {
                    $query->whereRaw('MATCH(bill_full_name) AGAINST(? IN BOOLEAN MODE)', ["+{$name}*"]);
                }
            }, 'Tên khách hàng');
            
            // Filter status - sử dụng index
            $filter->equal('status', 'Trạng thái')->select([
                0   => 'Mới',
                1   => 'Đã xác nhận',
                2   => 'Đã gửi hàng',
                3   => 'Đã nhận',
                4   => 'Đang hoàn',
                5   => 'Đã hoàn',
                6   => 'Hủy',
                8   => 'Đang đóng hàng',
                9   => 'Chờ chuyển hàng',
                11  => 'Chờ hàng',
                12  => 'Chờ in',
                13  => 'Đã in',
                16  => 'Đã thu tiền',
                15  => 'Hoàn một phần',
                20  => 'Đã đặt hàng',
            ]);
            
            // Filter page_id - có composite index
            $filter->equal('page_id', 'Page ID');
            $filter->like('page_name', 'Fanpage');
            
            // Filter system_id
            $filter->equal('system_id', 'System ID');
            
            // Filter pos_global_id
            $filter->equal('pos_global_id', 'POS Global ID');
            
            // Filter theo khoảng COD
            $filter->between('cod', 'Khoảng COD');
            
            // Filter ngày tạo - sử dụng index
            $filter->between('inserted_at', 'Ngày tạo')->datetime();
        });

        // Columns
        $grid->column('order_id', 'Mã đơn hàng')->sortable();
        // $grid->column('system_id', 'System ID')->sortable();
        // $grid->column('pos_global_id', 'POS Global')->limit(15)->sortable();
        $grid->column('bill_phone_number', 'Số điện thoại')->sortable();
        $grid->column('bill_full_name', 'Tên khách hàng')->limit(20);
        
        $grid->column('status_text', 'Trạng thái')->display(function () {
            $colors = [
                0 => 'default',
                1 => 'primary',
                2 => 'warning', 
                3 => 'success',
                4 => 'danger',
                5 => 'danger',
                6 => 'default',
                16 => 'success'
            ];
            
            $color = $colors[$this->status] ?? 'default';
            return "<span class='label label-{$color}'>{$this->status_text}</span>";
        });

        $grid->column('page_name', 'Fanpage')->limit(15);
        $grid->column('cod', 'COD')->display(function ($cod) {
            return number_format($cod, 0, ',', '.') . ' đ';
        })->sortable();
        
        $grid->column('total_quantity', 'SL')->sortable();
        $grid->column('formatted_inserted_at', 'Ngày tạo')->sortable('inserted_at');

        // Disable create button
        $grid->disableCreateButton();
        $grid->disableExport();

        return $grid;
    }

    /**
     * Add action buttons to grid
     */
    protected function addGridActions($grid)
    {
        $grid->tools(function ($tools) {
            $status = $this->posApiService->getSyncStatus();
            
            // Sync buttons based on status
            switch ($status['status']) {
                case 'running':
                    $tools->append('
                        <div class="btn-group">
                            <a href="' . admin_url('orders/pause-sync') . '" class="btn btn-warning btn-sm">
                                <i class="fa fa-pause"></i> Dừng Sync
                            </a>
                        </div>
                    ');
                    break;
                    
                case 'paused':
                    $tools->append('
                        <div class="btn-group">
                            <a href="' . admin_url('orders/resume-sync') . '" class="btn btn-success btn-sm">
                                <i class="fa fa-play"></i> Tiếp tục Sync
                            </a>
                            <a href="' . admin_url('orders/sync') . '" class="btn btn-primary btn-sm">
                                <i class="fa fa-refresh"></i> Sync từ đầu
                            </a>
                        </div>
                    ');
                    break;
                    
                default:
                    $tools->append('
                        <div class="btn-group">
                            <a href="' . admin_url('orders/sync') . '" class="btn btn-primary btn-sm">
                                <i class="fa fa-refresh"></i> Bắt đầu Sync
                            </a>
                        </div>
                    ');
            }

            // Status info
            if ($status['status'] === 'running') {
                $tools->append('
                    <div class="pull-right" style="margin-right: 10px; margin-top: 5px;">
                        <small class="text-muted">
                            Tiến trình: ' . $status['progress_percentage'] . '% 
                            (' . $status['synced_records'] . '/' . $status['total_records'] . ')
                        </small>
                    </div>
                ');
            }
        });
    }

    /**
     * Make a show builder.
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->field('order_id', 'Mã đơn hàng');
        $show->field('system_id', 'System ID');
        $show->field('pos_global_id', 'POS Global ID');
        $show->field('status_text', 'Trạng thái');
        $show->field('bill_full_name', 'Tên khách hàng');
        $show->field('bill_phone_number', 'Số điện thoại');
        $show->field('page_name', 'Fanpage');
        $show->field('order_sources_name', 'Nguồn đơn hàng');
        
        $show->field('cod', 'COD')->as(function ($cod) {
            return number_format($cod, 0, ',', '.') . ' đ';
        });
        
        $show->field('shipping_fee', 'Phí ship')->as(function ($fee) {
            return number_format($fee, 0, ',', '.') . ' đ';
        });
        
        $show->field('total_quantity', 'Tổng số lượng');
        $show->field('items_length', 'Số loại sản phẩm');
        
        $show->field('order_link', 'Link đơn hàng')->link();
        $show->field('link_confirm_order', 'Link xác nhận')->link();
        
        $show->field('last_editor', 'Người sửa cuối')->json();
        $show->field('time_send_partner', 'Thời gian gửi đối tác');
        $show->field('formatted_inserted_at', 'Ngày tạo API');
        $show->field('formatted_api_updated_at', 'Ngày cập nhật API');
        $show->field('created_at', 'Ngày tạo hệ thống');
        $show->field('updated_at', 'Ngày cập nhật hệ thống');

        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableDelete();
            });

        return $show;
    }

    /**
     * Make a form builder.
     */
    protected function form()
    {
        $form = new Form(new Order);

        $form->display('order_id', 'Mã đơn hàng');
        $form->display('system_id', 'System ID');
        $form->display('pos_global_id', 'POS Global ID');
        $form->text('bill_full_name', 'Tên khách hàng');
        $form->text('bill_phone_number', 'Số điện thoại');
        
        $form->select('status', 'Trạng thái')->options([
            0   => 'Mới',
            1   => 'Đã xác nhận',
            2   => 'Đã gửi hàng',
            3   => 'Đã nhận',
            4   => 'Đang hoàn',
            5   => 'Đã hoàn',
            6   => 'Hủy',
            8   => 'Đang đóng hàng',
            9   => 'Chờ chuyển hàng',
            11  => 'Chờ hàng',
            12  => 'Chờ in',
            13  => 'Đã in',
            16  => 'Đã thu tiền',
            15  => 'Hoàn một phần',
            20  => 'Đã đặt hàng',
        ]);

        $form->currency('cod', 'COD')->symbol('VND');
        $form->currency('shipping_fee', 'Phí ship')->symbol('VND');
        $form->number('total_quantity', 'Tổng số lượng');

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });

        return $form;
    }
}