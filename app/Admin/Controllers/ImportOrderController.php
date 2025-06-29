<?php

namespace App\Admin\Controllers;

use App\Models\ImportOrder;
use App\Models\Shipment;
use App\Models\Package;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ImportOrderController extends Controller
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
            ->header(trans('Phiếu nhập'))
            ->description(trans('Quản lý phiếu nhập hàng'))
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
        $grid = new Grid(new ImportOrder());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('#id'))->sortable()->width(70);

        $grid->column('order_code', __('Mã phiếu nhập'))
            ->display(function ($value) {
                return  "<span style='margin: 0 0 0 10px;'><strong><a href='/admin/import-orders/{$this->id}'># {$value}</a></strong></span>";
            })
            ->filter('like')->width(150)->copyable();
        $grid->column('button_edit', __('#edit'))
            ->display(function () {
                return "<a href='/admin/import-orders/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
            })->width(100);
        
        
        $grid->column('quantity', __('Số lượng'))->sortable()->width(100);
        $grid->column('quantity_bill', __('SL bill'))->sortable()->editable()->width(100);
        // $grid->column('pancake_id', __('Pancake ID'))->filter('like');
        // $grid->column('supplier_code', __('Supplier Code'))->filter('like');


        // Vận đơn liên quan
        $grid->column('shipments_list', __('Danh sách vận đơn'))
            ->display(function () {
                $shipments = $this->shipments;
                
                // Màu sắc cho dải status shipment
                $statusColors = [
                    'pending' => '#f0ad4e',
                    'processing' => '#5bc0de',
                    'shipped' => '#337ab7',
                    'delivered' => '#5cb85c',
                    'cancelled' => '#d9534f'
                ];

                // Labels cho shipping partners
                $partnerLabels = [
                    'atan' => 'A Tần',
                    'other' => 'Khác',
                    'oanh' => 'Oanh',
                    'nga' => 'Nga', 
                    'fe' => 'Xuân Phê'
                ];
                
                $shipment_links = [];
                foreach ($shipments as $shipment) {
                    $status = $shipment->shipment_status ?? 'pending';
                    $statusColor = $statusColors[$status] ?? '#777';

                    // Tạo title với thông tin đầy đủ
                    $partner = $shipment->shipping_partner ?? 'Chưa có';
                    $partnerLabel = $partnerLabels[$partner] ?? $partner;
                    $statusLabel = $shipment->shipment_status_label ?? 'Chưa có';
                    
                    $title = "Trạng thái: {$statusLabel} | Đối tác vc: {$partnerLabel}";
                    
                    $shipment_links[] = "<a href='/admin/shipments/{$shipment->id}' 
                                  class='btn btn-xs btn-default' 
                                  style='margin: 1px; font-weight: 600; 
                                         border-left: 4px solid {$statusColor}; 
                                         padding-left: 8px;'
                                  title='{$title}'>
                                  {$shipment->tracking_code}
                                </a>";
                }
                
                // Giới hạn hiển thị 4 shipments đầu tiên
                if (count($shipment_links) > 4) {
                    $visibleLinks = array_slice($shipment_links, 0, 4);
                    $hiddenCount = count($shipment_links) - 4;
                    return implode('<br>', $visibleLinks) . "<br><small class='text-muted'>+{$hiddenCount} khác</small>";
                }
                
                return implode('<br>', $shipment_links);
            })
            ->width(170);

        // Đối tác vận chuyển từ shipments
        $grid->column('shipment_partners', __('Đối tác VC'))
            ->display(function () {
                $shipments = $this->shipments;
                if ($shipments->isEmpty()) {
                    return '<span class="label label-default"></span>';
                }
                
                // Lấy unique shipping partners từ shipments
                $partners = [];
                foreach ($shipments as $shipment) {
                    if ($shipment->shipping_partner) {
                        $partners[] = $shipment->shipping_partner;
                    }
                }
                
                $uniquePartners = array_unique($partners);
                
                if (empty($uniquePartners)) {
                    return '<span class="label label-default">Chưa có</span>';
                }
                
                // Hiển thị partners với màu sắc
                $partnerLabels = [
                    'atan' => 'A Tần',
                    'other' => 'Khác',
                    'oanh' => 'Oanh',
                    'nga' => 'Nga', 
                    'fe' => 'Xuân Phê'
                ];
                
                $partnerColors = [
                    'atan' => 'primary',
                    'oanh' => 'success',
                    'khac' => 'info',
                    'nga' => 'default',
                    'fe' => 'default'
                ];
                
                $labels = [];
                foreach ($uniquePartners as $partner) {
                    $label = $partnerLabels[$partner] ?? strtoupper($partner);
                    $color = $partnerColors[$partner] ?? 'default';
                    $labels[] = "<span class='label label-{$color}'>{$label}</span>";
                }
                
                return implode('<br>', $labels);
            })
            ->width(120);

        // Kiện hàng liên quan với phiếu nhập
        $grid->column('packages_list', __('Mã kiện hàng'))
            ->display(function () {
                // Lấy packages thông qua shipments
                $packages = collect();
                foreach ($this->shipments as $shipment) {
                    $packages = $packages->merge($shipment->packages);
                }
                
                // Loại bỏ duplicate packages
                $uniquePackages = $packages->unique('id');
                
                if ($uniquePackages->isEmpty()) {
                    return '<span class="label label-default"></span>';
                }
                
                // Màu sắc cho dải status package
                $statusColors = [
                    'pending' => '#d9534f', // #f0ad4e
                    'in_transit' => '#5bc0de',
                    'delivered_vn' => '#da32e7',
                    'delivered' => '#5cb85c',
                    'cancelled' => '#d9534f'
                ];
                
                // Labels cho shipping partners
                $partnerLabels = [
                    'atan' => 'A Tần',
                    'other' => 'Khác',
                    'oanh' => 'Oanh',
                    'nga' => 'Nga', 
                    'fe' => 'Xuân Phê'
                ];
                
                // Labels cho status
                $statusLabels = [
                    'pending' => 'Chờ xử lý',
                    'in_transit' => 'Đang vận chuyển',
                    'delivered_vn' => 'Nhập kho VN',
                    'delivered' => 'Đã giao',
                    'cancelled' => 'Đã hủy'
                ];
                
                $links = [];
                foreach ($uniquePackages as $package) {
                    $status = $package->package_status ?? 'pending';
                    $statusColor = $statusColors[$status] ?? '#777';
                    
                    // Tạo title với thông tin đầy đủ
                    $partner = $package->shipping_partner ?? 'Chưa có';
                    $partnerLabel = $partnerLabels[$partner] ?? $partner;
                    $statusLabel = $statusLabels[$status] ?? $status;
                    $weight = $package->weight ? $package->weight . ' kg' : 'N/A';
                    
                    $title = "Trạng thái: {$statusLabel} | Đối tác vc: {$partnerLabel} | Cân nặng: {$weight}";
                    
                    $links[] = "<a href='/admin/packages/{$package->id}' 
                                  class='btn btn-xs btn-default' 
                                  style='margin: 1px; font-weight: 600; 
                                         border-left: 4px solid {$statusColor}; 
                                         padding-left: 8px;'
                                  title='{$title}'>
                                  {$package->package_code}
                               </a>";
                }
                
                // Giới hạn hiển thị 4 packages đầu tiên
                if (count($links) > 4) {
                    $visibleLinks = array_slice($links, 0, 4);
                    $hiddenCount = count($links) - 4;
                    return implode('<br>', $visibleLinks) . "<br><small class='text-muted'>+{$hiddenCount} khác</small>";
                }
                
                return implode('<br>', $links);
            })
            ->width(150);

        $grid->column('import_status', __('Trạng thái'))
            ->display(function ($value) {
                return $this->import_status_label;
            })
            ->label([
                'pending' => 'danger',
                'processing' => 'warning',
                'in_transit' => 'primary',
                'completed' => 'success',
                'cancelled' => 'danger'
            ])
            ->filter([
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'in_transit' => 'Đang vận chuyển',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy'
            ])->width(150);

        $grid->column('notes', __('Ghi chú'))->filter('like');


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
            $filter->like('order_code', 'Mã phiếu nhập');

            // Filter theo tracking_code của shipments
            $filter->where(function ($query) {
                $query->whereHas('shipments', function ($query) {
                    $query->where('tracking_code', 'like', "%{$this->input}%");
                });
            }, 'Mã vận đơn');

            // $filter->like('supplier_code', 'Supplier Code');
            // $filter->like('pancake_id', 'Pancake ID');

            // Filter theo shipping_partner của shipments
            $filter->where(function ($query) {
                $query->whereHas('shipments', function ($query) {
                    $query->where('shipping_partner', $this->input);
                });
            }, 'Đối tác vận chuyển')->select([
                'atan' => 'A Tần',
                'oanh' => 'Oanh',
                'other' => 'Khác'
            ]);

            // Filter theo package_code
            $filter->where(function ($query) {
                $query->whereHas('shipments.packages', function ($query) {
                    $query->where('package_code', 'like', "%{$this->input}%");
                });
            }, 'Mã kiện hàng');


        });

        // Enable quick create
        $grid->quickCreate(function ($form) {
            $form->text('order_code', 'Mã phiếu nhập')->required();
            $form->text('notes', 'Ghi chú');
            $form->select('import_status', 'Trạng thái')->options([
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'in_transit' => 'Đang vận chuyển',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy'
            ])->default('pending');
        });


        $grid->tools(function ($tools) {
            $tools->append('<a href="/admin/shipments" class="btn btn-sm btn-default"><i class="fa fa-info"></i> Vận đơn</a>');
            $tools->append('<a href="/admin/packages" class="btn btn-sm btn-default"><i class="fa fa-info"></i> Kiện hàng</a>');
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();

            // Thêm action "Cập nhật trạng thái"
            $batch->add(new \App\Admin\Actions\UpdateImportOrderStatus());
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
        $show = new Show(ImportOrder::findOrFail($id));

        // $show->field('id', __('ID'));
        $show->field('order_code', __('Mã phiếu'));
        // $show->field('pancake_id', __('Pancake ID'));
        $show->field('quantity', __('Số lượng hàng'));
        $show->field('quantity_bill', __('Số lượng trên bill'));
        // $show->field('supplier_code', __('Supplier Code'));
        $show->field('notes', __('Ghi chú'));
        $show->field('import_status_label', __('Trạng thái'));
        // $show->field('created_at', __('Created At'));
        // $show->field('updated_at', __('Updated At'));

        // Grid phụ hiển thị shipments liên kết
        $show->shipments('Mã vận đơn liên quan', function ($shipments) use ($id) {
            $shipments->column('tracking_code', __('Mã vận đơn'))
                ->display(function ($value) {
                    return "<strong><a href='/admin/shipments/{$this->id}'>{$value}</a></strong>";
                })
            ->filter('like')->width(250);
            $shipments->column('button_edit', __('#edit'))
                ->display(function () {
                    return "<a href='/admin/shipments/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
                })->width(100);

            $shipments->column('shipment_status', 'Trạng thái')
                ->display(function ($value) {
                    $labels = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'shipped' => 'Đã gửi hàng',
                        'delivered' => 'Đã giao hàng',
                        'cancelled' => 'Đã hủy'
                    ];
                    $label = $labels[$value] ?? $value;
                    $colors = [
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $color = $colors[$value] ?? 'default';
                    return "<span class='label label-{$color}'>{$label}</span>";
                })->width(150);


            // Mã kiện hàng liên quan
            $shipments->column('package_codes', __('Mã kiện hàng'))
                ->display(function () {
                    $packages = $this->packages;
                    $links_package = [];
                    
                    // Màu sắc cho dải status
                    $statusColors = [
                        'pending' => '#f0ad4e',
                        'in_transit' => '#5bc0de', 
                        'delivered_vn' => '#da32e7',
                        'delivered' => '#5cb85c',
                        'cancelled' => '#d9534f'
                    ];
                    
                    // Tooltip text tiếng Việt
                    $statusLabels = [
                        'pending' => 'Chờ xử lý',
                        'in_transit' => 'Đang vận chuyển',
                        'delivered_vn' => 'Nhập kho VN',
                        'delivered' => 'Đã giao',
                        'cancelled' => 'Đã hủy'
                    ];

                    // Labels cho shipping partners
                    $partnerLabels = [
                        'atan' => 'A Tần',
                        'oanh' => 'Oanh',
                        'other' => 'Khác',
                        'nga' => 'Nga', 
                        'fe' => 'Xuân Phê'
                    ];
                    
                    foreach ($packages as $package) {
                        $status = $package->package_status ?? 'pending';
                        $statusColor = $statusColors[$status] ?? '#777';
                        
                        // Tạo title với thông tin đầy đủ
                        $partner = $package->shipping_partner ?? 'Chưa có';
                        $partnerLabel = $partnerLabels[$partner] ?? $partner;
                        $statusLabel = $statusLabels[$status] ?? $status;
                        
                        $title = "Trạng thái: {$statusLabel} | {$partnerLabel}";
                        
                        $links_package[] = "<a href='/admin/packages/{$package->id}' 
                                                class='btn btn-xs btn-default' 
                                                style='margin-right: 5px; margin-bottom: 2px; font-weight: 600; 
                                                    border-left: 5px solid {$statusColor}; 
                                                    padding-left: 5px;'
                                                title='{$title}'>
                                                # {$package->package_code}
                                            </a>";
                    }
                    
                    return implode('<br>', $links_package);
                })
                ->width(200);

            // Đối tác vận chuyển từ shipments
            $shipments->column('shipment_partners', __('Đối tác vận chuyển'))
                ->display(function () {
                    $currentShipment = $this;
                    
                    if (!$currentShipment->shipping_partner) {
                        return '<span class="label label-default"></span>';
                    }
                    
                    // Hiển thị shipping partner của shipment hiện tại
                    $partnerLabels = [
                        'atan' => 'A Tần',
                        'other' => 'Khác',
                        'oanh' => 'Oanh',
                        'nga' => 'Nga', 
                        'fe' => 'Xuân Phê'
                    ];
                    
                    $partnerColors = [
                        'atan' => 'primary',
                        'oanh' => 'info',
                        'other' => 'success',
                        'nga' => 'default',
                        'fe' => 'default'
                    ];
                    
                    $partner = $currentShipment->shipping_partner;
                    $label = $partnerLabels[$partner] ?? strtoupper($partner);
                    $color = $partnerColors[$partner] ?? 'default';
                    
                    return "<span class='label label-{$color}'>{$label}</span>";
                })
                ->width(150);

            $shipments->column('notes', 'Ghi chú')->editable('textarea');

            $shipments->column('created_at', 'Ngày tạo')
                ->display(function ($created_at) {
                    return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
                })->width(150);
            $shipments->column('updated_at', __('Cập nhật'))
                ->display(function ($updated_at) {
                    return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
                })->sortable()->width(150);


            // Tắt lọc
            $shipments->disableFilter();
            $shipments->disableExport();
            $shipments->disableCreateButton();

            // Button để gỡ liên kết
            $shipments->actions(function ($actions) {
                $actions->disableView();
                $actions->disableEdit();
                $actions->disableDelete();
                $actions->add(new \App\Admin\Actions\DetachShipmentFromOrder);
            });

            $shipments->tools(function ($tools) use ($id) {
                $tools->append('<a href="/admin/shipments/create?import_order_id='.$id.'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Tạo nhanh vận đơn</a>');
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
        $form = new Form(new ImportOrder());

        $form->text('order_code', __('Mã phiếu nhập'))->required();
        // $form->text('pancake_id', __('Pancake ID'))->required();
        $form->number('quantity', __('Số lượng'))->default(0);
        $form->number('quantity_bill', __('Số lượng trên bill'))->default(0);
        // $form->text('supplier_code', __('Supplier Code'))->required();
        $form->textarea('notes', __('Ghi chú'));
        $form->select('import_status', __('Trạng thái'))->options([
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'in_transit' => 'Đang vận chuyển',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ])->default('pending');


        // Tạo nhanh ImportOrder từ Shipment
        if (request()->has('shipment_id')) {
            $shipmentId = request('shipment_id');
            $form->multipleSelect('shipments', __('Mã vận đơn'))
            ->options(Shipment::all()->mapWithKeys(function ($shipments) {
                return [$shipments->id => $shipments->tracking_code . ' (' . $shipments->created_at->format('d/m/Y') . ')'];
            }))
            ->default([$shipmentId]);
        } else {
            $form->multipleSelect('shipments', __('Mã vận đơn'))
            ->options(Shipment::all()->mapWithKeys(function ($shipments) {
                return [$shipments->id => $shipments->tracking_code . ' (' . $shipments->created_at->format('d/m/Y') . ')'];
            }));
        }


        // Handle form saving với API call
        $form->saved(function (Form $form) {
            $importOrder = $form->model();
            
            // Gửi API request để update note
            $this->updatePurchaseNote($importOrder);
        });

        return $form;
    }


    /**
     * Gửi API request để update purchase note
     * 
     * @param ImportOrder $importOrder
     * @return void
     */
    private function updatePurchaseNote($importOrder)
    {
        try {
            $apiKey = config('pancake_api_key');
            $baseUrl = config('pancake_api_base_url');
            
            // Lấy purchase_id từ pancake_id
            $purchaseId = $importOrder->pancake_id;
            
            if (!$purchaseId) {
                \Log::warning('Pancake ID (Purchase ID) not found for ImportOrder: ' . $importOrder->id);
                return;
            }

            // Tạo API URL
            $url = "{$baseUrl}/purchases/{$purchaseId}?api_key={$apiKey}";
            
            // Chuẩn bị data
            $data = [
                'purchase' => [
                    'note'          => $importOrder->notes ?? '',
                    'warehouse_id'  => '0573e29d-3881-42e1-b8b9-28cb49848791'
                ]
            ];

            // Gửi PUT request
            $response = \Http::timeout(30)->put($url, $data);

            if ($response->successful()) {
                // \Log::info('Purchase note updated successfully', [
                //     'import_order_id' => $importOrder->id,
                //     'pancake_id' => $purchaseId,
                //     'order_code' => $importOrder->order_code,
                //     'note' => $importOrder->notes
                // ]);
                
                // // Hiển thị thông báo thành công
                admin_toastr('Note đã được đồng bộ với Pancake thành công!', 'success');
            } else {
                \Log::error('Failed to update purchase note', [
                    'import_order_id' => $importOrder->id,
                    'pancake_id' => $purchaseId,
                    'order_code' => $importOrder->order_code,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'api_url' => $url
                ]);
                
                // Hiển thị thông báo lỗi
                admin_toastr('Lỗi đồng bộ note với Pancake: HTTP ' . $response->status(), 'error');
            }

        } catch (\Exception $e) {
            \Log::error('Exception when updating purchase note', [
                'import_order_id' => $importOrder->id,
                'pancake_id' => $importOrder->pancake_id,
                'order_code' => $importOrder->order_code,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            // Hiển thị thông báo lỗi
            admin_toastr('Lỗi kết nối API Pancake: ' . $e->getMessage(), 'error');
        }
    }








}