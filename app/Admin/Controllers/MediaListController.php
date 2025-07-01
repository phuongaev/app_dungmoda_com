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
        
        // Sắp xếp theo id giảm dần (DESC)
        $grid->model()->orderBy('id', 'desc');
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

            // Filter dropdown Nguồn (source_name)
            $filter->where(function ($query) {
                if ($this->input) {
                    $query->whereHas('source', function ($q) {
                        $q->where('source_name', $this->input);
                    });
                }
            }, 'Nguồn')->select(
                MediaSource::all()->pluck('source_name', 'source_name')
            );
        });

        // Tạo nhanh data
        $grid->quickCreate(function ($form) {
            $form->text('media_url', 'Ảnh');
            $form->select('variations_code', 'Mã mẫu mã')->options(
                Product::all()->pluck('variations_code', 'variations_code')
            );
            $form->select('source_id', 'Nguồn')->options(
                MediaSource::all()->pluck('source_name', 'id')
            );
            $form->select('type', 'Loại media')->options([1 => 'Hình ảnh', 2 => 'Video'])->default(1);
        });


        $grid->id('ID');

        // $grid->media_url('media_url');
        $grid->column('media_url', 'Ảnh')->display(function ($url) {
            $id = uniqid('copy_');
            $editUrl = "/admin/media-lists/{$this->id}/edit";
            $imgHtml = "
                <a href='$url' data-toggle='lightbox' style='float:left;'>
                    <img src='$url' style='max-width:80px;max-height:80px;border-radius:8px;box-shadow:0 1px 4px #bbb;cursor:pointer'/>
                </a>
            ";

            $btnHtml = "
                <div style='float:right; display:flex; align-items:center; gap:8px;'>
                    <button
                        type='button'
                        class='btn btn-xs btn-outline-primary'
                        style='padding:2px 10px;font-size:12px;'
                        id='$id'
                        onclick=\"navigator.clipboard.writeText('$url');var b=document.getElementById('$id');b.innerText='Đã copy!';setTimeout(()=>{b.innerText='Pancake';},1000);\"
                    >Pancake</button>
            ";

            // Nút Copy Local nếu có
            if (!empty($this->local_url)) {
                $localId = uniqid('copy_local_');
                $localUrl = url($this->local_url);
                $btnHtml .= "
                    <button
                        type='button'
                        class='btn btn-xs btn-outline-warning'
                        style='padding:2px 10px;font-size:12px;'
                        id='$localId'
                        onclick=\"navigator.clipboard.writeText('$localUrl');var b=document.getElementById('$localId');b.innerText='Đã copy!';setTimeout(()=>{b.innerText='Local';},1000);\"
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
        // $form->display(trans('admin.created_at'));
        // $form->display(trans('admin.updated_at'));

        return $form;
    }
}
