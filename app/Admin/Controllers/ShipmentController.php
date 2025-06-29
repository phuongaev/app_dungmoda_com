<?php

namespace App\Admin\Controllers;

use App\Models\Shipment;
use App\Models\ImportOrder;
use App\Models\Package;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ShipmentController extends Controller
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
            ->header(trans('Quản lý mã vận đơn'))
            ->description(trans('Mã vận đơn nội địa TQ'))
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
            ->header(trans('Chi tiết vận đơn'))
            ->description(trans('Thông tin vận đơn nội địa TQ'))
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
        $grid = new Grid(new Shipment());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('#id'))->sortable()->width(70);
        
        $grid->column('tracking_code', __('Mã vận đơn'))
            ->display(function ($value) {
                return "<strong><a href='/admin/shipments/{$this->id}'>{$value}</a></strong>";
            })
            ->filter('like')->width(200)->copyable();
        $grid->column('button_edit', __('#edit'))
            ->display(function () {
                return "<a href='/admin/shipments/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
            })->width(100);

        // Hiển thị order codes liên kết
        $grid->column('order_codes', __('Mã phiếu nhập'))
            ->display(function () {
                $orders = $this->importOrders;
                $links = [];
                foreach ($orders as $order) {
                    $links[] = "<a href='/admin/import-orders/{$order->id}' 
                                  class='btn btn-xs btn-default' 
                                  style='margin-right: 10px; font-weight: 600;'>
                                  # {$order->order_code}
                               </a>";
                }
                return implode('<br>', $links);
            })->width(150);


        // Mã kiện hàng liên quan
        $grid->column('package_codes', __('Mã kiện hàng'))
            ->display(function () {
                $packages = $this->packages;
                
                $links_package = [];
                
                // Màu sắc cho dải status
                $statusColors = [
                    'pending' => '#d9534f',// f0ad4e
                    'in_transit' => '#5bc0de',
                    'delivered_vn' => '#d9534f',
                    'delivered' => '#5cb85c',
                    'cancelled' => '#d9534f'
                ];
                
                // Tooltip text tiếng Việt
                $statusLabels = [
                    'pending' => 'Chờ xử lý',
                    'in_transit' => 'Đang vận chuyển',
                    'delivered_vn' => 'Nhập kho VN',
                    'delivered' => 'Đã nhận hàng',
                    'cancelled' => 'Đã hủy'
                ];
                
                foreach ($packages as $package) {
                    $status = $package->package_status ?? 'pending';
                    $statusColor = $statusColors[$status] ?? '#777';
                    
                    $links_package[] = "<a href='/admin/packages/{$package->id}' 
                                      class='btn btn-xs btn-default' 
                                      style='margin-right: 5px; margin-bottom: 2px; font-weight: 600; 
                                             border-left: 5px solid {$statusColor}; 
                                             padding-left: 5px;'
                                      title='Trạng thái: {$package->package_status_label}'>
                                      # {$package->package_code}
                                   </a>";
                }
                
                return implode('<br>', $links_package);
            })
            ->width(150);

        // Đối tác vận chuyển
        $grid->column('shipping_partner', __('Đối tác VC'))
            ->display(function ($value) {
                return $this->shipping_partner_label;
            })
            ->filter([
                'atan' => 'A Tần',
                'other' => 'Khác',
                'oanh' => 'Oanh',
            ])->width(150);

        $grid->column('shipment_status', __('Trạng thái'))
            ->display(function ($value) {
                return $this->shipment_status_label;
            })
            ->label([
                'pending' => 'warning',
                'processing' => 'info',
                'shipped' => 'primary',
                'delivered' => 'success',
                'cancelled' => 'danger'
            ])
            ->filter([
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'shipped' => 'Đã gửi',
                'delivered' => 'Đã giao',
                'cancelled' => 'Đã hủy'
            ])->width(150);

        // Sửa nhanh notes
        $grid->column('notes', __('Ghi chú'))->editable('textarea');

        $grid->column('created_at', __('Ngày tạo'))
            ->display(function ($created_at) {
                return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
            })->sortable()->width(150);
        $grid->column('updated_at', __('Cập nhật'))
            ->display(function ($updated_at) {
                return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
            })->sortable()->width(150);

        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            $filter->like('tracking_code', 'Mã vận đơn');
            
            // Filter theo order_code
            $filter->where(function ($query) {
                $query->whereHas('importOrders', function ($query) {
                    $query->where('order_code', 'like', "%{$this->input}%");
                });
            }, 'Mã phiếu nhập');

            // Filter theo package_code
            $filter->where(function ($query) {
                $query->whereHas('packages', function ($query) {
                    $query->where('package_code', 'like', "%{$this->input}%");
                });
            }, 'Mã kiện hàng');
        });

        // Enable quick create
        $grid->quickCreate(function ($form) {
            $form->text('tracking_code', 'Mã vận đơn')->required();
            $form->text('notes', 'Ghi chú');
            $form->select('order_code', 'Mã phiếu nhập')->options(['' => 'No Import Order'] + 
                ImportOrder::all()->pluck('order_code', 'order_code')->toArray()
            )->help('Chọn phiếu nhập để liên kết');
            $form->select('package_code', 'Mã kiện hàng')->options(['' => 'No Package'] + 
                Package::all()->mapWithKeys(function ($package) {
                    return [$package->package_code => $package->package_code . ' (' . $package->created_at->format('d/m/Y') . ')'];
                })->toArray()
            )->help('Chọn kiện hàng để liên kết');

            $form->select('shipping_partner', 'Đối tác vận chuyển')->options([
                'atan' => 'A Tần',
                'other' => 'Khác',
                'oanh' => 'Oanh',
            ])->default('atan');

            $form->select('shipment_status', 'Trạng thái')->options([
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'shipped' => 'Đã gửi',
                'delivered' => 'Đã giao',
                'cancelled' => 'Đã hủy'
            ])->default('pending');
        });

        $grid->tools(function ($tools) {
            $tools->append('<a href="/admin/import-orders" class="btn btn-sm btn-warning"><i class="fa fa-info"></i> Phiếu nhập</a>');
            $tools->append('<a href="/admin/packages" class="btn btn-sm btn-default"><i class="fa fa-info"></i> Kiện hàng</a>');
        });

        $grid->batchActions(function ($batch) {
            // (Tùy chọn) Tắt chức năng xóa hàng loạt mặc định
            $batch->disableDelete();

            // Thêm action mới ta vừa tạo cho Shipment
            $batch->add(new \App\Admin\Actions\UpdateShipmentStatus());

            // Thêm action "Cập nhật đối tác vận chuyển"
            $batch->add(new \App\Admin\Actions\UpdateShippingPartner());
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
        $show = new Show(Shipment::findOrFail($id));

        // $show->field('id', __('ID'));
        $show->field('tracking_code', __('Mã vận đơn'));
        $show->field('order_codes', __('Mã phiếu nhập'));
        $show->field('package_codes', __('Mã bao hàng'));
        $show->field('shipping_partner_label', __('Đối tác vận chuyển'));
        $show->field('notes', __('Ghi chú'));
        $show->field('shipment_status_label', __('Trạng thái'));
        $show->field('created_at', __('Ngày tạo'));
        // $show->field('updated_at', __('Updated At'));

        // Grid phụ hiển thị Phiếu nhập liên kết
        $show->importOrders('Phiếu nhập liên quan', function ($importOrders) use ($id) {
            $importOrders->column('order_code', __('Mã phiếu nhập'))
                ->display(function ($value) {
                    return "<strong><a href='/admin/import-orders/{$this->id}/edit'>{$value}</a></strong>";
                })
                ->filter('like')->width(200);
            $importOrders->column('button_edit', __('#edit'))
                ->display(function () {
                    return "<a href='/admin/import-orders/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
                });

            $importOrders->column('import_status', 'Trạng thái')
                ->display(function ($value) {
                    $labels = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'in_transit' => 'Đang vận chuyển',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy'
                    ];
                    $label = $labels[$value] ?? $value;
                    $colors = [
                        'pending' => 'danger',
                        'processing' => 'warning',
                        'in_transit' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $color = $colors[$value] ?? 'default';
                    return "<span class='label label-{$color}'>{$label}</span>";
                })->width(150);

            $importOrders->column('notes', 'Ghi chú')->editable('textarea')->width(400);

            $importOrders->column('created_at', 'Ngày tạo')
                ->display(function ($created_at) {
                    return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
                })->width(150);
            $importOrders->column('updated_at', __('Cập nhật'))
                ->display(function ($updated_at) {
                    return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
                })->sortable()->width(150);

            // Tắt lọc
            $importOrders->disableFilter();
            $importOrders->disableExport();
            $importOrders->disableCreateButton();

            // Button để gỡ liên kết
            $importOrders->actions(function ($actions) {
                $actions->disableView();
                $actions->disableEdit();
                $actions->disableDelete();
                $actions->add(new \App\Admin\Actions\DetachShipmentFromOrder);
            });

            $importOrders->tools(function ($tools) use ($id) {
                $tools->append('<a href="/admin/import-orders/create?shipment_id='.$id.'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Tạo nhanh phiếu nhập</a>');
            });
        });


        // Grid phụ hiển thị Mã kiện hàng liên kết
        $show->packages('Kiện hàng liên quan', function ($packages) use ($id) {
            $packages->column('package_code', __('Mã kiện hàng'))
            ->display(function ($value) {
                return "<strong><a href='/admin/packages/{$this->id}/edit'>{$value}</a></strong>";
            })
            ->filter('like')->width(200);
            $packages->column('button_edit', __('#edit'))
                ->display(function () {
                    return "<a href='/admin/packages/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
                });

            $packages->column('package_status', 'Trạng thái')
                ->display(function ($value) {
                    $labels = [
                        'pending' => 'Chờ xử lý',
                        'in_transit' => 'Đang vận chuyển',
                        'delivered_vn' => 'Nhập kho VN',
                        'delivered' => 'Đã giao hàng',
                        'cancelled' => 'Đã hủy'
                    ];
                    $label = $labels[$value] ?? $value;
                    $colors = [
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'delivered_vn' => 'danger',
                        'delivered' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $color = $colors[$value] ?? 'default';
                    return "<span class='label label-{$color}'>{$label}</span>";
                })->width(150);

            $packages->column('notes', 'Ghi chú')->editable('textarea')->width(400);

            $packages->column('created_at', 'Ngày tạo')
                ->display(function ($created_at) {
                    return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
                })->width(150);
            $packages->column('updated_at', __('Cập nhật'))
                ->display(function ($updated_at) {
                    return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
                })->sortable()->width(150);

            // Tắt lọc
            $packages->disableFilter();
            $packages->disableExport();
            $packages->disableCreateButton();

            // Button để gỡ liên kết
            $packages->actions(function ($actions) {
                $actions->disableView();
                $actions->disableEdit();
                $actions->disableDelete();
                $actions->add(new \App\Admin\Actions\DetachShipmentFromOrder);
            });

            $packages->tools(function ($tools) use ($id) {
                $tools->append('<a href="/admin/packages/create?shipment_id='.$id.'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Tạo nhanh kiện hàng</a>');
            });
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Shipment());

        $form->text('tracking_code', __('Mã vận đơn'))->required();

        // Tạo nhanh Shipment từ Import Order
        if (request()->has('import_order_id')) {
            $importOrderId = request('import_order_id');
            $form->multipleSelect('importOrders', __('Mã phiếu nhập'))
                ->options(ImportOrder::all()->pluck('order_code', 'id'))
                ->default([$importOrderId]);
        } else {
            $form->multipleSelect('importOrders', __('Mã phiếu nhập'))
            ->options(ImportOrder::all()->pluck('order_code', 'id'));
        }

        // Tạo nhanh Shipment từ Package
        if (request()->has('package_id')) {
            $packageId = request('package_id');
            $form->multipleSelect('packages', __('Mã kiện hàng'))
            ->options(Package::all()->mapWithKeys(function ($package) {
                return [$package->id => $package->package_code . ' (' . $package->created_at->format('d/m/Y') . ')'];
            }))
            ->default([$packageId]);
        } else {
            $form->multipleSelect('packages', __('Mã kiện hàng'))
            ->options(Package::all()->mapWithKeys(function ($package) {
                return [$package->id => $package->package_code . ' (' . $package->created_at->format('d/m/Y') . ')'];
            }));
        }

        $form->select('shipping_partner', __('Đối tác vận chuyển'))->options([
            'atan' => 'A Tần',
            'other' => 'Khác',
            'oanh' => 'Oanh',
        ])->default('atan');

        $form->select('shipment_status', __('Trạng thái'))->options([
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã gửi hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy'
        ])->default('pending');

        $form->textarea('notes', __('Ghi chú'));
        
        // Handle form saving with custom logic
        $form->saved(function (Form $form) {
            // Handle relationships after model is saved
            $shipment = $form->model();
            
            // Handle order_code if provided (for QuickCreate)
            if (request()->has('order_code') && !empty(request('order_code'))) {
                $importOrder = ImportOrder::where('order_code', request('order_code'))->first();
                if ($importOrder) {
                    $shipment->importOrders()->syncWithoutDetaching([$importOrder->id]);
                }
            }

            // Handle package_code if provided (for QuickCreate)
            if (request()->has('package_code') && !empty(request('package_code'))) {
                $package = Package::where('package_code', request('package_code'))->first();
                if ($package) {
                    $shipment->packages()->syncWithoutDetaching([$package->id]);
                }
            }
        });

        return $form;
    }


}
