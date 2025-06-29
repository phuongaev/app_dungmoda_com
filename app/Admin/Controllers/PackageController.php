<?php

namespace App\Admin\Controllers;

use App\Models\Package;
use App\Models\Shipment;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

use Encore\Admin\Grid\Tools\BatchAction;
use App\Admin\Extensions\PendingPackagesWidget;

class PackageController extends Controller
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
            ->header(trans('Kiện hàng'))
            ->description(trans('Quản lý các mã bao hàng vận chuyển'))
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
        $grid = new Grid(new Package());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('#id'))->sortable()->width(70);
        
        $grid->column('package_code', __('Mã kiện hàng'))
            ->display(function ($value) {
                return  "<strong><a href='/admin/packages/{$this->id}'># {$value}</a></strong>";
            })
            ->filter('like')->width(200)->copyable();
        $grid->column('button_edit', __('#edit'))
            ->display(function () {
                return "<a href='/admin/packages/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
            })->width(100);

        $grid->column('shipping_partner', __('Đối tác VC'))
            ->display(function ($value) {
                return $this->shipping_partner_label;
            })
            ->filter([
                'atan' => 'A Tần',
                'oanh' => 'Oanh',
                'other' => 'Khác',
                // 'nga' => 'Nga',
                // 'fe' => 'Xuân Phê'
            ])->width(130);

        $grid->column('weight', __('Cân nặng (kg)'))->sortable()
            ->display(function ($value) {
                return $value ? $value : '';
            })
            ->editable()->width(130);

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

        // Cột Phiếu nhập liên quan
        $grid->column('imports_list', __('Mã phiếu nhập'))
            ->display(function () {
                $imports = collect();
                
                // Lấy tất cả imports từ các shipments
                foreach ($this->shipments as $shipment) {
                    if ($shipment->importOrders) {
                        $imports = $imports->merge($shipment->importOrders);
                    }
                }
                
                // Remove duplicates nếu có
                $imports = $imports->unique('id');

                // if ($imports->isEmpty()) {
                //     return "<span class='text-muted'>Chưa có phiếu nhập</span>";
                // }
                
                $import_links = [];
                foreach ($imports as $import) {
                    $import_links[] = "<a href='/admin/import-orders/{$import->id}' 
                                          class='btn btn-xs btn-default' 
                                          style='margin-right: 10px; font-weight: 600;'>
                                          # {$import->order_code}
                                        </a>";
                }
                
                return implode('<br>', $import_links);
            })
            ->width(150);

        $grid->column('package_status', __('Trạng thái'))
            ->display(function ($value) {
                return $this->package_status_label;
            })
            ->label([
                'pending' => 'danger',
                'in_transit' => 'primary',
                'delivered' => 'success',
                'cancelled' => 'danger',
                'delivered_vn' => 'danger',
            ])
            ->filter([
                'pending' => 'Chờ xử lý',
                'in_transit' => 'Đang vận chuyển',
                'delivered_vn' => 'Nhập kho VN',
                'delivered' => 'Đã nhận hàng',
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
            $filter->like('package_code', 'Mã bao hàng');

            // Filter theo tracking_code của shipments
            $filter->where(function ($query) {
                $query->whereHas('shipments', function ($query) {
                    $query->where('tracking_code', 'like', "%{$this->input}%");
                });
            }, 'Mã vận đơn');

            // Filter theo order_code
            $filter->where(function ($query) {
                $query->whereHas('shipments.importOrders', function ($query) {
                    $query->where('order_code', 'like', "%{$this->input}%");
                });
            }, 'Mã phiếu nhập');
        });

        // Enable quick create
        $grid->quickCreate(function ($form) {
            $form->text('package_code', 'Mã bao hàng')->required();
            $form->select('shipping_partner', 'Đối tác vận chuyển')->options([
                'atan' => 'A Tần',
                'oanh' => 'Oanh',
                'other' => 'Khác',
                // 'nga' => 'Nga',
                // 'fe' => 'Xuân Phê'
            ])->default('atan');
            $form->text('weight', 'Cân nặng (kg)')->help('Cân nặng tính bằng kg');
            $form->text('notes', 'Ghi chú');
            $form->select('package_status', 'Trạng thái')->options([
                'pending' => 'Chờ xử lý',
                'in_transit' => 'Đang vận chuyển',
                'delivered_vn' => 'Nhập kho VN',
                'delivered' => 'Đã nhận hàng',
                'cancelled' => 'Đã hủy'
            ])->default('pending');
        });

        $grid->tools(function ($tools) {
            $tools->append('<a href="/admin/import-orders" class="btn btn-sm btn-warning"><i class="fa fa-info"></i> Phiếu nhập</a>');
            $tools->append('<a href="/admin/shipments" class="btn btn-sm btn-default"><i class="fa fa-info"></i> Vận đơn</a>');
        });

        $grid->batchActions(function ($batch) {
            // Tắt action xóa hàng loạt mặc định nếu anh không cần
            $batch->disableDelete();

            // Thêm action mới mà chúng ta vừa tạo
            $batch->add(new \App\Admin\Actions\UpdatePackageStatus());

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
    // app/Admin/Controllers/PackageController.php
    protected function detail($id)
    {
        $show = new Show(Package::findOrFail($id));

        // $show->field('id', __('ID'));
        $show->field('package_code', __('Mã bao hàng'));
        $show->field('shipping_partner_label', __('Đối tác vận chuyển'));
        $show->field('weight', __('Cân nặng (kg)'));
        $show->field('notes', __('Ghi chú'));
        $show->field('package_status_label', __('Trạng thái'));
        // $show->field('created_at', __('Created At'));
        // $show->field('updated_at', __('Updated At'));

        // Grid phụ hiển thị shipments liên kết
        $show->shipments('Mã vận đơn liên kết', function ($shipments) use ($id) {
            $shipments->column('tracking_code', __('Mã vận đơn'))
                ->display(function ($value) {
                    return "<strong><a href='/admin/shipments/{$this->id}/edit'>{$value}</a></strong>";
                })
                ->filter('like')->width(250);
            $shipments->column('button_edit', __('#edit'))
                ->display(function () {
                    return "<a href='/admin/shipments/{$this->id}/edit' class='btn btn-xs btn-default' style='margin: 1px;'>Sửa</a>";
                })->width(100);


            // Danh sách phiếu nhập liên quan
            $shipments->column('order_codes', __('Mã phiếu nhập'))
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
                    return implode(' ', $links);
                })->width(150);

            // Danh sách vận đơn liên quan
            $shipments->column('shipment_status', 'Trạng thái')
                ->display(function ($value) {
                    $labels = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'shipped' => 'Đã gửi',
                        'delivered' => 'Đã giao',
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

            $shipments->column('notes', 'Ghi chú')->editable('textarea')->width(400);

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
                $actions->add(new \App\Admin\Actions\DetachShipmentFromPackage);
            });

            $shipments->tools(function ($tools) use ($id) {
                $tools->append('<a href="/admin/shipments/create?package_id='.$id.'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Tạo nhanh vận đơn</a>');
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
        $form = new Form(new Package());

        $form->text('package_code', __('Mã kiện hàng'))->required();
        $form->select('shipping_partner', __('Đối tác vận chuyển'))->options([
            'atan' => 'A Tần',
            'oanh' => 'Oanh',
            'other' => 'Khác',
            // 'nga' => 'Nga',
            // 'fe' => 'Xuân Phê'
        ])->required();
        $form->decimal('weight', __('Cân nặng (kg)'))->help('Cân nặng tính bằng kg');
        $form->textarea('notes', __('Ghi chú'));
        $form->select('package_status', __('Trạng thái'))->options([
            'pending' => 'Chờ xử lý',
            'in_transit' => 'Đang vận chuyển',
            'delivered_vn' => 'Nhập kho VN',
            'delivered' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy'
        ])->default('pending');


        // Tạo nhanh Package từ Shipment
        if (request()->has('shipment_id')) {
            $shipmentId = request('shipment_id');
            $form->multipleSelect('shipments', __('Danh sách mã vận đơn'))
            ->options(Shipment::all()->mapWithKeys(function ($shipments) {
                return [$shipments->id => $shipments->tracking_code . ' (' . $shipments->created_at->format('d/m/Y') . ')'];
            }))
            ->default([$shipmentId]);
        } else {
            $form->multipleSelect('shipments', __('Danh sách mã vận đơn'))
            ->options(Shipment::all()->mapWithKeys(function ($shipments) {
                return [$shipments->id => $shipments->tracking_code . ' (' . $shipments->created_at->format('d/m/Y') . ')'];
            }));
        }

        return $form;
    }


    
    /**
     * Dashboard chi tiết kiện hàng
     */
    public function packagesDashboard(Content $content)
    {
        $data = $this->getDetailedPackagesData();
        
        return $content
            ->title('Dashboard Kiện hàng')
            ->description('Kiện hàng cần xử lý')
            ->row(function (Row $row) use ($data) {
                $row->column(12, function (Column $column) use ($data) {
                    $column->append(view('admin.dashboard.packages-full', $data));
                });
            });
    }

    /**
     * Lấy dữ liệu chi tiết kiện hàng
     */
    private function getDetailedPackagesData()
    {
        $query = Package::whereIn('package_status', ['pending', 'delivered_vn']);
        
        if (request('search')) {
            $query->where('package_code', 'like', '%' . request('search') . '%');
        }
        
        $pendingPackages = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'pending' => Package::where('package_status', 'pending')->count(),
            'delivered_vn' => Package::where('package_status', 'delivered_vn')->count(),
            'in_transit' => Package::where('package_status', 'in_transit')->count(),
            'delivered' => Package::where('package_status', 'delivered')->count(),
            'cancelled' => Package::where('package_status', 'cancelled')->count(),
        ];

        $stats['total'] = $stats['pending'] + $stats['delivered_vn'];

        $partnerStats = Package::whereIn('package_status', ['pending', 'delivered_vn'])
            ->selectRaw('shipping_partner, count(*) as count')
            ->groupBy('shipping_partner')
            ->pluck('count', 'shipping_partner')
            ->toArray();

        return compact('pendingPackages', 'stats', 'partnerStats');
    }

    // Action cập nhật nhanh trạng thái
    public function updateStatus($id)
    {
        $package = Package::findOrFail($id);
        
        $nextStatus = [
            'pending' => 'in_transit',
            'in_transit' => 'delivered_vn',
            'delivered_vn' => 'delivered'
        ];

        if (isset($nextStatus[$package->package_status])) {
            $package->package_status = $nextStatus[$package->package_status];
            $package->save();

            admin_success('Đã cập nhật trạng thái kiện hàng.');
        } else {
            admin_error('Không thể cập nhật trạng thái.');
        }

        return back();
    }





}
