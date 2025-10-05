<?php

namespace App\Admin\Controllers;

use App\Models\LeaveRequest;
use App\Models\RequestHistory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmployeeLeaveRequestController extends AdminController
{
    protected $title = 'Đơn xin nghỉ của tôi';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Đơn xin nghỉ của tôi')
            ->description('Quản lý đơn xin nghỉ')
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
            ->header('Chi tiết đơn xin nghỉ')
            ->description('Xem thông tin chi tiết')
            ->body($this->detail($id));
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
            ->header('Tạo đơn xin nghỉ')
            ->description('Gửi đơn xin nghỉ mới')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LeaveRequest());

        // Chỉ hiển thị đơn của nhân viên hiện tại
        $grid->model()->where('admin_user_id', Admin::user()->id)->orderBy('created_at', 'desc');

        $grid->column('id', 'ID')->sortable();
        
        $grid->column('start_date', 'Từ ngày')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
        })->sortable();
        
        $grid->column('end_date', 'Đến ngày')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
        })->sortable();
        
        $grid->column('total_days', 'Số ngày')->display(function () {
            return $this->total_days . ' ngày';
        });
        
        $grid->column('reason', 'Lý do')->limit(50);
        
        $grid->column('status', 'Trạng thái')->display(function ($status) {
            return $this->status_badge;
        });
        
        $grid->column('approver.name', 'Người duyệt')->display(function ($name) {
            return $name ?: '-';
        });
        
        $grid->column('approved_at', 'Ngày duyệt')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y H:i') : '-';
        })->sortable();
        
        $grid->column('created_at', 'Ngày tạo')->display(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y H:i');
        })->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('status', 'Trạng thái')->select(LeaveRequest::getStatuses());
            $filter->between('start_date', 'Từ ngày')->date();
            $filter->between('end_date', 'Đến ngày')->date();
            $filter->between('created_at', 'Ngày tạo')->datetime();
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            
            // Chỉ cho phép hủy đơn pending VÀ không phải do admin tạo
            if ($actions->row->status === LeaveRequest::STATUS_PENDING && !$actions->row->created_by_admin) {
                $actions->add(new \App\Admin\Actions\EmployeeCancelLeaveAction());
            }
        });

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
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
        $show = new Show(LeaveRequest::findOrFail($id));

        // Chỉ cho phép xem đơn của chính mình
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('admin_user_id', Admin::user()->id)
            ->firstOrFail();

        $show->field('id', 'ID');
        $show->field('start_date', 'Từ ngày')->as(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        });
        $show->field('end_date', 'Đến ngày')->as(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        });
        $show->field('total_days', 'Số ngày')->as(function () {
            return $this->total_days . ' ngày';
        });
        $show->field('reason', 'Lý do');
        $show->field('attachment_file', 'File đính kèm')->file();
        $show->field('status', 'Trạng thái')->as(function ($status) {
            return LeaveRequest::getStatuses()[$status] ?? $status;
        });
        $show->field('admin_notes', 'Ghi chú từ quản lý');
        $show->field('approver.name', 'Người duyệt');
        $show->field('approved_at', 'Ngày duyệt')->as(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y H:i') : '-';
        });
        $show->field('created_at', 'Ngày tạo')->as(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y H:i');
        });

        // Hiển thị lịch sử
        $show->field('histories', 'Lịch sử')->as(function () {
            $html = '<table class="table table-striped table-bordered">';
            $html .= '<thead><tr><th>Hành động</th><th>Người thực hiện</th><th>Ghi chú</th><th>Thời gian</th></tr></thead><tbody>';
            
            foreach ($this->histories as $history) {
                $html .= '<tr>';
                $html .= '<td>' . $history->action_badge . '</td>';
                $html .= '<td>' . ($history->admin->name ?? 'N/A') . '</td>';
                $html .= '<td>' . ($history->notes ?? '-') . '</td>';
                $html .= '<td>' . $history->created_at->format('d/m/Y H:i') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            return $html;
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
        $form = new Form(new LeaveRequest());

        $form->hidden('admin_user_id')->value(Admin::user()->id);
        
        $form->date('start_date', 'Từ ngày')
            ->rules('required|date|after_or_equal:today')
            ->help('Chọn ngày bắt đầu nghỉ (từ hôm nay trở đi)');
        
        $form->date('end_date', 'Đến ngày')
            ->rules('required|date|after_or_equal:start_date')
            ->help('Chọn ngày kết thúc nghỉ');
        
        $form->textarea('reason', 'Lý do nghỉ')
            ->rules('required|min:10')
            ->rows(4)
            ->help('Mô tả chi tiết lý do xin nghỉ (tối thiểu 10 ký tự)');
        
        $form->file('attachment_file', 'File đính kèm')
            ->help('Tải lên file đính kèm nếu có (đơn xin nghỉ, giấy bác sĩ...)');

        $form->hidden('status')->value(LeaveRequest::STATUS_PENDING);

        // Validation tùy chỉnh
        $form->saving(function (Form $form) {
            $startDate = Carbon::parse($form->start_date);
            $endDate = Carbon::parse($form->end_date);
            
            // Kiểm tra số ngày nghỉ tối đa 3 ngày
            $totalDays = $startDate->diffInDays($endDate) + 1;
            if ($totalDays > 3) {
                return back()->withErrors(['end_date' => 'Chỉ được phép xin nghỉ tối đa 3 ngày liên tiếp.']);
            }

            // Kiểm tra trùng lặp đơn trong khoảng thời gian
            $existingRequest = LeaveRequest::where('admin_user_id', Admin::user()->id)
                ->where('status', '!=', LeaveRequest::STATUS_REJECTED)
                ->where('status', '!=', LeaveRequest::STATUS_CANCELLED)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->exists();

            if ($existingRequest) {
                return back()->withErrors(['start_date' => 'Đã có đơn xin nghỉ trong khoảng thời gian này.']);
            }
        });

        // Sau khi lưu thành công
        $form->saved(function (Form $form, $result) {
            $leaveRequest = $form->model();
            
            // Lưu lịch sử
            $leaveRequest->addHistory(
                RequestHistory::ACTION_SUBMITTED,
                Admin::user()->id,
                'Nhân viên gửi đơn xin nghỉ'
            );
        });

        return $form;
    }

    /**
     * Cancel own leave request
     */
    public function cancel(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('admin_user_id', Admin::user()->id)
            ->firstOrFail();
        
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ có thể hủy đơn đang chờ duyệt!'
            ]);
        }

        try {
            \DB::beginTransaction();

            $leaveRequest->status = LeaveRequest::STATUS_CANCELLED;
            $leaveRequest->save();

            // Lưu lịch sử
            $leaveRequest->addHistory(
                RequestHistory::ACTION_CANCELLED,
                Admin::user()->id,
                'Nhân viên tự hủy đơn'
            );

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn xin nghỉ thành công!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }
}