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
        return $content
            ->header(trans('Quản lý đơn hàng'))
            ->description(trans('Thông tin cơ bản các đơn hàng'))
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
        $grid = new Grid(new PosOrder());

        $grid->model()->orderBy('inserted_at', 'desc');

        // Tối ưu query với select specific columns và relationship
        $grid->model()->select([
            'id', 'order_id', 'customer_name', 'customer_phone', 
            'cod', 'status', 'sub_status', 'dataset_status', 'status_name', 'order_sources_name', 
            'total_quantity', 'created_at', 'pos_updated_at', 'inserted_at', 'order_link'
        ])->withStatusInfo();

        // Header tools
        // $grid->header(function ($query) {
        //     $stats = DB::table('pos_orders')
        //         ->selectRaw('
        //             COUNT(*) as total,
        //             SUM(CASE WHEN status = 16 THEN 1 ELSE 0 END) as completed,
        //             SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) as cancelled,
        //             SUM(cod) as total_cod
        //         ')
        //         ->first();

        //     return new Box('Thống kê đơn hàng', view('admin.pos_orders.stats', compact('stats')));
        // });

        // Filters - tối ưu với index
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // Tìm kiếm chính - sử dụng composite index
            $filter->where(function ($query) {
                $query->where('customer_phone', 'like', "%{$this->input}%")
                      ->orWhere('order_id', 'like', "%{$this->input}%");
            }, 'Tìm kiếm SĐT/Mã đơn hàng', 'search');

            // Filter theo trạng thái - có index
            $filter->equal('status', 'Trạng thái')->select(PosOrderStatus::getSelectOptions());
            
            // Filter theo dataset_status (Dataset)
            $filter->where(function ($query) {
                if ($this->input === 'not_null') {
                    $query->whereNotNull('dataset_status');
                } else {
                    $query->where('dataset_status', $this->input);
                }
            }, 'Dataset')->select(array_merge(
                ['not_null' => '--- Có Dataset ---'],
                PosOrderStatus::getSelectOptions()
            ));
            
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
            ->width(170);

        $grid->column('customer_name', 'Khách hàng')
            ->limit(20)
            ->width(170);

        $grid->column('customer_phone', 'SĐT')
            ->display(function ($phone) {
                return $this->formatted_phone ?? $phone;
            })
            ->copyable()
            ->width(120);

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
        // $grid->tools(function ($tools) {
        //     $tools->append('<a href="' . route('admin.pos-orders.import') . '" class="btn btn-sm btn-success">
        //         <i class="fa fa-upload"></i> Import đơn hàng
        //     </a>');
        // });

        // Pagination
        $grid->paginate(20);

        // Export
        // $grid->exporter(new PosOrderExporter());
        $grid->disableCreateButton();
        $grid->disableExport();

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
        $show = new Show(PosOrder::findOrFail($id));

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
        $show->field('dataset_status', 'Dataset')->as(function ($datasetStatus) {
            if (!$datasetStatus) {
                return 'Không có';
            }
            
            $statusInfo = PosOrderStatus::where('status_code', $datasetStatus)->first();
            return $statusInfo ? $statusInfo->status_name : 'Không xác định';
        });
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
}