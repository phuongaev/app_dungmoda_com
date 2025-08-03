<?php

namespace App\Admin\Controllers;

use App\Models\PosOrder;
use App\Models\PosOrderStatus;

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


class PosOrderController extends Controller
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
        // return $content
        //     ->header(trans('Quản lý đơn hàng'))
        //     ->description(trans('Thông tin cơ bản các đơn hàng'))
        //     ->body($this->grid());

        $grid = $this->grid();
        
        // Tạo HTML cho modal
        $modalHtml = view('admin.pos_orders.delivery_contacts_modal')->render();
        
        // Tạo script trực tiếp thay vì dùng file blade
        $scriptHtml = $this->getDeliveryContactsScript();
        
        return $content
            ->header(trans('Quản lý đơn hàng'))
            ->description(trans('Thông tin cơ bản các đơn hàng'))
            ->body($grid)
            ->body($modalHtml)   // Thêm modal
            ->body($scriptHtml); // Thêm scripts
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
        $grid = new Grid(new PosOrder());

        $grid->model()->orderBy('inserted_at', 'desc');

        if (request('quick_search')) {
            $searchTerm = request('quick_search');
            $grid->model()->where(function ($query) use ($searchTerm) {
                $query->where('order_id', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                      ->orWhere('shipment_id', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'like', "%{$searchTerm}%");
            });
        }

        // Tối ưu query với select specific columns và relationship
        $grid->model()->select([
            'id', 'order_id', 'shipment_id', 'customer_name', 'customer_phone', 
            'cod', 'status', 'sub_status', 'dataset_status', 'status_name', 'order_sources_name', 
            'total_quantity', 'created_at', 'pos_updated_at', 'inserted_at', 'order_link'
        ])->withStatusInfo();

        // Filters - tối ưu với index
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // Tìm kiếm chính - sử dụng composite index
            $filter->where(function ($query) {
                $query->where('customer_phone', 'like', "%{$this->input}%")
                      ->orWhere('order_id', 'like', "%{$this->input}%");
            }, 'Tìm kiếm SĐT/Mã đơn hàng', 'search');

            // $filter->like('order_id', 'Mã đơn hàng');
            // $filter->like('customer_phone', 'Số điện thoại');
            $filter->like('shipment_id', 'Mã vận đơn');

            // Filter theo trạng thái - có index
            $filter->equal('status', 'Trạng thái')->select(PosOrderStatus::getSelectOptions());
            
            // Filter theo dataset_status (Dataset) - giá trị cụ thể
            $filter->equal('dataset_status', 'Dataset')->select(PosOrderStatus::getSelectOptions());
            
            // Filter có Dataset - scope filter
            $filter->scope('has_dataset', 'Có Dataset')->where(function ($query) {
                $query->whereNotNull('dataset_status');
            });
            
            $filter->between('cod', 'Giá trị COD')->integer();
            
            // Filter theo nguồn
            // $filter->equal('order_sources', 'Nguồn đơn hàng')->select([
            //     -1 => 'Facebook',
            //     -7 => 'Webcake',
            //     2 => 'Zalo',
            //     3 => 'Phone'
            // ]);

            // Filter theo page_id
            $filter->equal('page_id', 'Page ID');

            // Filter theo thời gian - sử dụng index status_created
            // $filter->between('created_at', 'Thời gian tạo')->datetime();

            // Filter theo thời gian - sử dụng index inserted_at
            $filter->between('inserted_at', 'Thời gian tạo')->datetime();

            // Quick filters
            $filter->scope('today', 'Hôm nay')->where(function ($query) {
                $query->whereDate('inserted_at', today());
            });
            
            $filter->scope('this_week', 'Tuần này')->where(function ($query) {
                $query->whereBetween('inserted_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            });

        });

        // Columns
        $grid->column('order_id', 'Mã đơn hàng')
            ->copyable()
            ->width(170)->filter('like');

        $grid->column('customer_name', 'Khách hàng')
            ->limit(20)
            ->width(170);

        $grid->column('customer_phone', 'SĐT')
            ->display(function ($phone) {
                return $this->formatted_phone ?? $phone;
            })
            ->copyable()
            ->width(120)->filter('like');

        $grid->column('cod', 'COD')
            ->display(function ($cod) {
                return '<span class="label label-success">' . number_format($cod, 0, ',', '.') . ' VND</span>';
            })
            ->sortable()
            ->width(120);

        $grid->column('total_quantity', 'SL')
            ->sortable()
            ->width(60);

        $grid->column('status_name', 'Trạng thái')
            ->display(function ($statusName) {
                $color = $this->status_color ?? 'default';
                return "<span class='label label-{$color}'>{$statusName}</span>";
            })
            ->width(120);

        $grid->column('shipment_id', 'Mã vận đơn')->display(function ($shipmentId) {
            $html = '';
            
            // Hiển thị shipment_id hoặc N/A nếu null
            if (!empty($shipmentId)) {
                $html .= '<span>' . $shipmentId . '</span>';
                
                // Chỉ hiển thị button khi có shipment_id
                $html .= ' <button type="button" class="btn btn-xs btn-default btn-delivery-contacts" 
                            data-shipment-id="' . $shipmentId . '"
                            title="Xem số điện thoại nhân viên giao hàng">
                            <i class="fa fa-phone text-primary"></i>
                          </button>';
            } else {
                $html .= '<span class="text-muted">---</span>';
            }
            
            return $html;
        })->width(150)->copyable()->filter('like');

        $grid->column('order_sources_name', 'Nguồn')
            ->label([
                'Facebook' => 'primary',
                'Webcake' => 'success',
                'Website' => 'success',
                'Zalo' => 'info',
                'Phone' => 'warning'
            ])
            ->width(90);

        // Page ID
        // $grid->column('page_id', 'Page Id')
        //     ->copyable()
        //     ->width(170);

        // Nút mở xem link đơn hàng trên POS
        $grid->column('order_link', 'Link')
            ->display(function ($orderLink) {
                if (!$orderLink) {
                    return '<i class="fa fa-link text-muted" title="Không có link"></i>';
                }
                
                return '<a href="' . $orderLink . '" target="_blank" class="text-default" title="Mở đơn hàng">
                    <i class="fa fa-external-link"></i>
                </a>';
            })
            ->width(55);

        // Cột Dataset - hiển thị thông tin dataset_status
        $grid->column('dataset_status', 'Dataset')
            ->display(function ($datasetStatus) {
                if (!$datasetStatus || !$this->datasetStatusInfo) {
                    return '';
                }
                
                $color = $this->dataset_status_color ?? 'default';
                $name = $this->dataset_status_name;
                
                return "<span class='label label-{$color}'>{$name}</span>";
            })
            ->width(120);

        $grid->column('inserted_at', 'Ngày tạo')
            ->display(function ($createdAt) {
                return date('d/m/Y H:i', strtotime($createdAt));
            })
            ->sortable()
            ->width(150);

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        // Bulk actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            // $batch->add('Xuất Excel', new ExportOrdersAction());
        });

        // Tools
        $grid->tools(function ($tools) {
            // Thêm search box vào đầu tools
            $tools->append(view('admin.pos_orders.search_box'));
        });

        // Pagination
        $grid->paginate(20);

        // Export
        // $grid->exporter(new PosOrderExporter());
        $grid->disableCreateButton();
        $grid->disableExport();

        return $grid;
    }

    /**
     * Make a show builder with workflow history.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $order = PosOrder::with('workflowHistories.workflow', 'workflowHistories.workflowStatus')
                         ->findOrFail($id);

        $show = new Show($order);

        // Thông tin đơn hàng cơ bản
        $show->field('order_id', 'Mã đơn hàng');
        $show->field('system_id', 'System ID');
        $show->field('page_id', 'Page ID');
        
        $show->divider();
        
        $show->field('customer_name', 'Tên khách hàng');
        $show->field('customer_phone', 'Số điện thoại');
        $show->field('customer_id', 'Customer ID');
        $show->field('customer_fb_id', 'Facebook ID');
        
        $show->divider();
        
        $show->field('cod', 'COD')->as(function ($cod) {
            return number_format($cod, 0, ',', '.') . ' VND';
        });
        $show->field('total_quantity', 'Tổng số lượng');
        $show->field('items_length', 'Số loại sản phẩm');
        
        $show->divider();
        
        $show->field('status', 'Trạng thái')->using(PosOrder::getStatusOptions());
        $show->field('sub_status', 'Trạng thái phụ');
        $show->field('order_sources_name', 'Nguồn đơn hàng');
        
        $show->divider();
        
        $show->field('order_link', 'Link đơn hàng')->link();
        $show->field('link_confirm_order', 'Link xác nhận')->link();
        $show->field('conversation_id', 'Conversation ID');
        $show->field('post_id', 'Post ID');
        
        $show->divider();
        
        $show->field('time_send_partner', 'Thời gian gửi đối tác');
        $show->field('pos_updated_at', 'Cập nhật từ POS');
        $show->field('created_at', 'Ngày tạo');
        $show->field('updated_at', 'Ngày cập nhật');

        // ================ WORKFLOW HISTORY SECTION ================
        $show->divider();
        
        // Fix: Gọi method từ model instance
        $show->field('workflow_histories', 'Lịch sử Workflow')->as(function () {
            return $this->renderWorkflowHistoryTable();
        })->escape(false);

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PosOrder());

        $form->text('order_id', 'Mã đơn hàng')->required();
        $form->number('system_id', 'System ID')->required();
        $form->text('page_id', 'Page ID')->required();
        
        $form->divider();
        
        $form->text('customer_name', 'Tên khách hàng')->required();
        $form->mobile('customer_phone', 'Số điện thoại')->required();
        $form->text('customer_id', 'Customer ID');
        $form->text('customer_fb_id', 'Facebook ID');
        
        $form->divider();
        
        $form->currency('cod', 'COD')->symbol('VND');
        $form->number('total_quantity', 'Tổng số lượng')->default(0);
        $form->number('items_length', 'Số loại sản phẩm')->default(0);
        
        $form->divider();
        
        $form->select('status', 'Trạng thái')->options(PosOrder::getStatusOptions())->required();
        $form->number('sub_status', 'Trạng thái phụ');
        $form->select('dataset_status', 'Dataset')->options(PosOrderStatus::getSelectOptions());
        $form->text('status_name', 'Tên trạng thái');
        
        $form->select('order_sources', 'Nguồn đơn hàng')->options([
            -1 => 'Facebook',
            1 => 'Website',
            2 => 'Zalo',
            3 => 'Phone'
        ])->required();
        
        $form->text('order_sources_name', 'Tên nguồn');
        
        $form->divider();
        
        $form->url('order_link', 'Link đơn hàng');
        $form->url('link_confirm_order', 'Link xác nhận');
        $form->text('conversation_id', 'Conversation ID');
        $form->text('post_id', 'Post ID');
        
        $form->divider();
        
        $form->datetime('time_send_partner', 'Thời gian gửi đối tác');
        $form->datetime('pos_updated_at', 'Cập nhật từ POS');

        return $form;
    }


    /**
     * Get delivery contacts for an order via AJAX
     * Chỉ tìm kiếm bằng shipment_id
     */
    public function getDeliveryContacts(Request $request)
    {
        try {
            $shipmentId = $request->get('shipment_id');
            
            // Validate shipment_id
            if (empty($shipmentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu mã vận đơn (shipment_id)'
                ], 400);
            }

            // Tìm delivery contacts chỉ theo shipment_id
            $deliveryContacts = DB::table('shipment_delivery_contacts')
                ->where('shipment_id', $shipmentId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format dữ liệu trả về
            $formattedContacts = $deliveryContacts->map(function ($contact) {
                return [
                    'delivery_name' => $contact->delivery_name ?? '',
                    'delivery_phone' => $contact->delivery_phone ?? '',
                    'created_at' => $contact->created_at ? Carbon::parse($contact->created_at)->format('d/m/Y H:i') : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedContacts,
                'shipment_id' => $shipmentId,
                'total' => $formattedContacts->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getDeliveryContacts: ' . $e->getMessage(), [
                'shipment_id' => $request->get('shipment_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu'
            ], 500);
        }
    }


    /**
     * Generate delivery contacts script
     */
    private function getDeliveryContactsScript()
    {
        $adminUrl = admin_url('delivery/contacts');
        
        return <<<HTML
        <script>
        $(document).ready(function() {
            
            /**
             * Handle click on delivery contacts button
             */
            $(document).on('click', '.btn-delivery-contacts', function(e) {
                e.preventDefault();
                
                var shipmentId = $(this).data('shipment-id');
                
                // Kiểm tra shipment_id
                if (!shipmentId) {
                    alert('Không có mã vận đơn để tìm kiếm');
                    return;
                }
                
                // Show modal
                $('#deliveryContactsModal').modal('show');
                
                // Load delivery contacts data
                loadDeliveryContacts(shipmentId);
            });
            
            /**
             * Load delivery contacts via AJAX
             */
            function loadDeliveryContacts(shipmentId) {
                // Reset modal state
                resetModalState();
                
                // Show loading
                $('#delivery-contacts-loading').show();
                
                // Set order info
                $('#modal-shipment-id').text(shipmentId || 'N/A');
                $('#delivery-contacts-order-info').show();
                
                // AJAX request
                $.ajax({
                    url: '{$adminUrl}',
                    type: 'GET',
                    data: {
                        shipment_id: shipmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#delivery-contacts-loading').hide();
                        
                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                // Has data - show table
                                renderDeliveryContactsTable(response.data);
                                $('#delivery-contacts-total').text(response.total);
                                $('#delivery-contacts-table-wrapper').show();
                            } else {
                                // No data
                                $('#delivery-contacts-no-data').show();
                            }
                        } else {
                            // Error from server
                            showError(response.message || 'Có lỗi xảy ra khi tải dữ liệu');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#delivery-contacts-loading').hide();
                        
                        var errorMessage = 'Có lỗi xảy ra khi tải dữ liệu';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            errorMessage = 'Không tìm thấy API endpoint';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Lỗi máy chủ nội bộ';
                        } else if (xhr.status === 400) {
                            errorMessage = 'Dữ liệu không hợp lệ';
                        }
                        
                        showError(errorMessage);
                    }
                });
            }
            
            /**
             * Render delivery contacts table
             */
            function renderDeliveryContactsTable(contacts) {
                var tbody = $('#delivery-contacts-table-body');
                tbody.empty();
                
                $.each(contacts, function(index, contact) {
                    var row = '<tr>' +
                        '<td>' + escapeHtml(contact.delivery_name) + '</td>' +
                        '<td><span class="label label-info">' + escapeHtml(contact.delivery_phone) + '</span></td>' +
                        '<td><small class="text-muted">' + escapeHtml(contact.created_at) + '</small></td>' +
                        '</tr>';
                    tbody.append(row);
                });
            }
            
            /**
             * Show error message
             */
            function showError(message) {
                $('#delivery-contacts-error-message').text(message);
                $('#delivery-contacts-error').show();
            }
            
            /**
             * Reset modal state
             */
            function resetModalState() {
                $('#delivery-contacts-loading').hide();
                $('#delivery-contacts-order-info').hide();
                $('#delivery-contacts-no-data').hide();
                $('#delivery-contacts-table-wrapper').hide();
                $('#delivery-contacts-error').hide();
                $('#delivery-contacts-table-body').empty();
                $('#delivery-contacts-total').text('0');
            }
            
            /**
             * Escape HTML to prevent XSS
             */
            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }
            
            /**
             * Reset modal when closed
             */
            $('#deliveryContactsModal').on('hidden.bs.modal', function() {
                resetModalState();
            });
        });
        </script>
        HTML;
    }


    

}