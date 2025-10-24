<?php

namespace App\Admin\Controllers;

use App\Models\MediaList;
use App\Models\MediaSource;
use App\Models\Product;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class MediaListController extends Controller
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
            ->header(trans('Quản lý hình ảnh'))
            ->description(trans('Hệ thống tài nguyên hình ảnh'))
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
        $grid = new Grid(new MediaList);
        
        // Sắp xếp theo priority giảm dần (ưu tiên trước), sau đó theo id giảm dần
        $grid->model()->orderBy('priority', 'desc')->orderBy('id', 'desc');
        // Eager load cho nhanh
        $grid->model()->with(['products', 'source']);


        // FILTER
        $grid->filter(function ($filter) {
            // Filter nhập keyword cho media_url
            $filter->like('media_url', 'Link ảnh');

            // Nhập keyword mã sản phẩm (product_code)
            $filter->where(function ($query) {
                if ($this->input) {
                    $query->whereHas('products', function ($q) {
                        $q->where('product_code', 'like', "%{$this->input}%");
                    });
                }
            }, 'Mã sản phẩm');

            // Nhập keyword mã mẫu mã (variations_code)
            $filter->where(function ($query) {
                if ($this->input) {
                    $query->whereHas('products', function ($q) {
                        $q->where('variations_code', 'like', "%{$this->input}%");
                    });
                }
            }, 'Mã mẫu mã');
        });


        $grid->id('ID')->sortable();

        // Hiển thị ảnh + button copy + edit
        $grid->media_url('Hình ảnh')->display(function($url) {
            // ID của hàng
            $id = $this->id;
            $editUrl = admin_url("media-lists/$id/edit");

            // Img
            $imgHtml = "
                <div style='float:left; margin-right:8px;'>
                    <img src='$url' style='width:100px; height:auto; border-radius:4px;' />
                </div>
            ";

            // Buttons (Copy, Copy Local, Edit)
            $btnHtml = "<div style='float:left;'>";
            
            // Copy URL
            $btnHtml .= "
                <button
                    class='btn btn-xs btn-outline-primary'
                    style='padding:2px 10px;font-size:12px; margin-bottom:4px;'
                    onclick=\"
                        let u='$url';
                        navigator.clipboard.writeText(u);
                        let b=this;
                        b.innerText='Copied!';
                        setTimeout(()=>{b.innerText='Copy';},1000);
                    \"
                >Copy</button>
            ";

            // Copy Local nếu có
            if (!empty($this->local_url)) {
                $localUrl = $this->local_url;
                $btnHtml .= "
                    <button
                        class='btn btn-xs btn-outline-info'
                        style='padding:2px 10px;font-size:12px; margin-bottom:4px; margin-left:4px;'
                        onclick=\"
                            let u='$localUrl';
                            navigator.clipboard.writeText(u);
                            let b=this;
                            b.innerText='Copied!';setTimeout(()=>{b.innerText='Local';},1000);\"
                        >Local</button>
                    ";
            }

            $btnHtml .= "
                <a
                    href='$editUrl'
                    class='btn btn-xs btn-outline-success'
                    style='padding:2px 10px;font-size:12px;'
                    target='_blank'
                >Edit</a>
                </div>
            ";

            // Gộp lại, clear fix cho đẹp
            return "<div style='overflow:auto; min-width:200px;'>$imgHtml$btnHtml<div style='clear:both;'></div></div>";
        });


        // Hiển thị nhiều mã sản phẩm liên kết
        $grid->column('products', 'Mã sản phẩm')->display(function($products) {
            return collect($products)->pluck('product_code')->implode('<br>');
        });

        // ============= THÊM CỘT PRIORITY SWITCH Ở ĐÂY =============
        $grid->column('priority', 'Ưu tiên')->switch();
        // ===========================================================

        // Hiển thị thông tin mẫu mã
        $grid->column('variations', 'Mã mẫu mã')->display(function ($variations) {
            // dd($variations);
            return collect($variations)->map(function ($item) {
                return '<strong>' . $item['variations_code'] . '</strong> / ' . $item['variations_name'];
            })->implode('<br>');
        });



        // $grid->source_id('source_id');
        // Hiển thị tên nguồn lấy từ bảng media_sources
        $grid->column('source.source_name', 'Nguồn media');
        
        $grid->media_order('Bộ ảnh');
        $grid->type('Loại');
        $grid->status('Trạng thái');

        // $grid->created_at(trans('admin.created_at'));
        // $grid->updated_at(trans('admin.updated_at'));
        $grid->column('created_at', __('Tạo lúc'))
            ->display(function ($created_at) {
                return empty($created_at) ? '' : date("Y-m-d H:i:s", strtotime($created_at));
            })->filter('range', 'datetime')->width(150);
        $grid->column('updated_at', __('Cập nhật'))
            ->display(function ($updated_at) {
                return empty($updated_at) ? '' : date("Y-m-d H:i:s", strtotime($updated_at));
            })->width(150);

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
        $show = new Show(MediaList::findOrFail($id));

        $show->id('ID');
        $show->source_id('source_id');
        $show->media_url('media_url');
        $show->media_order('media_order');
        $show->type('type');
        $show->status('status');
        $show->priority('priority');
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new MediaList);

        // $form->display('ID');
        $form->multipleSelect('products', 'Sản phẩm liên kết')
            ->options(Product::all()->pluck('variations_code', 'id'));

        // $form->text('source_id', 'source_id');
        $form->select('source_id', 'Nguồn media')->options(
            MediaSource::all()->pluck('source_name', 'id')
        );

        $form->text('media_url', 'Link ảnh');
        $form->text('local_url', 'Link ảnh nội bộ');
        $form->text('media_order', 'Bộ ảnh');
        $form->select('type', 'Loại media')->options([
            1 => 'Hình ảnh',
            2 => 'Video'
        ]);
        $form->select('status', 'Trạng thái')->options([
            1 => 'Mới'
        ]);
        
        // Thêm switch cho priority trong form
        $form->switch('priority', 'Ưu tiên')->default(0);
        
        // $form->display(trans('admin.created_at'));
        // $form->display(trans('admin.updated_at'));

        return $form;
    }
}