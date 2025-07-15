<?php

namespace App\Admin\Controllers;

use App\Models\ShiftSwapRequest;
use App\Models\RequestHistory;
use App\Models\EveningShift;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeShiftSwapController extends AdminController
{
    protected $title = 'Đơn hoán đổi ca trực của tôi';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Đơn hoán đổi ca trực của tôi')
            ->description('Quản lý đơn hoán đổi ca trực')
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
            ->header('Chi tiết đơn hoán đổi ca')
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
            ->header('Tạo đơn hoán đổi ca')
            ->description('Gửi đơn hoán đổi ca trực mới')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ShiftSwapRequest());

        // Eager load relationships để tránh N+1 query
        $grid->model()->with(['requester', 'targetUser', 'approver']);

        // Chỉ hiển thị đơn của nhân viên hiện tại (cả làm người yêu cầu và người được đề xuất)
        $grid->model()->where(function ($query) {
            $query->where('requester_id', Admin::user()->id)
                  ->orWhere('target_user_id', Admin::user()->id);
        })->orderBy('created_at', 'desc');

        $grid->column('id', 'ID')->sortable();
        
        $grid->column('requester.name', 'Người yêu cầu')->display(function ($name) {
            return $name ?: 'N/A';
        });
        
        $grid->column('original_requester_shift_date', 'Ca của người YC')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'N/A';
        })->sortable();
        
        $grid->column('targetUser.name', 'Người được đề xuất')->display(function ($name) {
            return $name ?: 'N/A';
        });
        
        $grid->column('original_target_shift_date', 'Ca được đề xuất')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'N/A';
        })->sortable();
        
        $grid->column('role', 'Vai trò của tôi')->display(function () {
            if ($this->requester_id == Admin::user()->id) {
                return '<span class="label label-primary">Người yêu cầu</span>';
            } else {
                return '<span class="label label-info">Người được đề xuất</span>';
            }
        });
        
        $grid->column('reason', 'Lý do')->limit(50);
        
        $grid->column('status', 'Trạng thái')->display(function ($status) {
            $badges = [
                'pending' => '<span class="label label-warning">Chờ duyệt</span>',
                'approved' => '<span class="label label-success">Đã duyệt</span>',
                'rejected' => '<span class="label label-danger">Từ chối</span>',
                'cancelled' => '<span class="label label-default">Đã hủy</span>'
            ];
            return $badges[$status] ?? $status;
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
            
            $filter->equal('status', 'Trạng thái')->select([
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
                'cancelled' => 'Đã hủy'
            ]);
            $filter->between('original_requester_shift_date', 'Ca người YC')->date();
            $filter->between('original_target_shift_date', 'Ca được đề xuất')->date();
            $filter->between('created_at', 'Ngày tạo')->datetime();
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            
            // Chỉ cho phép hủy đơn pending và là người yêu cầu
            if ($actions->row->status === ShiftSwapRequest::STATUS_PENDING && 
                $actions->row->requester_id == Admin::user()->id) {
                $actions->add(new \App\Admin\Actions\EmployeeCancelSwapAction());
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
        $show = new Show(ShiftSwapRequest::with(['requester', 'targetUser', 'approver'])->findOrFail($id));

        $show->field('id', 'ID');
        $show->field('requester.name', 'Người yêu cầu hoán đổi')->as(function () {
            return $this->requester ? (string)$this->requester->name : 'N/A';
        });
        $show->field('original_requester_shift_date', 'Ca trực của người yêu cầu')->as(function () {
            return $this->original_requester_shift_date ? $this->original_requester_shift_date->format('d/m/Y') : 'N/A';
        });
        $show->field('targetUser.name', 'Người được đề xuất hoán đổi')->as(function () {
            return $this->targetUser ? (string)$this->targetUser->name : 'N/A';
        });
        $show->field('original_target_shift_date', 'Ca trực được đề xuất')->as(function () {
            return $this->original_target_shift_date ? $this->original_target_shift_date->format('d/m/Y') : 'N/A';
        });
        $show->field('swap_summary', 'Tóm tắt hoán đổi')->as(function () {
            $requesterName = $this->requester ? (string)$this->requester->name : 'N/A';
            $targetName = $this->targetUser ? (string)$this->targetUser->name : 'N/A';
            $requesterDate = $this->original_requester_shift_date ? $this->original_requester_shift_date->format('d/m/Y') : 'N/A';
            $targetDate = $this->original_target_shift_date ? $this->original_target_shift_date->format('d/m/Y') : 'N/A';
            return "{$requesterName} ({$requesterDate}) ↔ {$targetName} ({$targetDate})";
        });
        $show->field('reason', 'Lý do hoán đổi')->as(function () {
            return (string)$this->reason ?: 'N/A';
        });
        $show->field('status', 'Trạng thái')->as(function () {
            $statuses = [
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
                'cancelled' => 'Đã hủy'
            ];
            return $statuses[$this->status] ?? (string)$this->status;
        });
        $show->field('admin_notes', 'Ghi chú của admin')->as(function () {
            return (string)$this->admin_notes ?: 'Không có ghi chú';
        });
        $show->field('approver.name', 'Người duyệt')->as(function () {
            return $this->approver ? (string)$this->approver->name : 'Chưa có';
        });
        $show->field('approved_at', 'Ngày duyệt')->as(function () {
            return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : 'Chưa duyệt';
        });
        $show->field('created_at', 'Ngày tạo')->as(function () {
            return $this->created_at ? $this->created_at->format('d/m/Y H:i') : 'N/A';
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
        $form = new Form(new ShiftSwapRequest());

        // Chỉ cho phép nhân viên tạo đơn cho chính mình
        $form->hidden('requester_id')->default(Admin::user()->id);

        // Get shifts của nhân viên hiện tại (từ hôm nay trở đi)
        $userShifts = EveningShift::forUserUpcoming(Admin::user()->id)->get();
        $shiftOptions = $userShifts->mapWithKeys(function ($shift) {
            return [$shift->id => $shift->shift_date->format('d/m/Y')];
        });

        $form->select('requester_shift_id', 'Ca trực của tôi muốn đổi')
            ->options($shiftOptions)
            ->required()
            ->help('Chọn ca trực của bạn muốn hoán đổi');

        // Get all available shifts for swapping (excluding user's own shifts)
        $availableShifts = EveningShift::with('user')
            ->where('admin_user_id', '!=', Admin::user()->id)
            ->where('shift_date', '>=', Carbon::today())
            ->get();

        $targetShiftOptions = $availableShifts->mapWithKeys(function ($shift) {
            return [$shift->id => $shift->user->name . ' - ' . $shift->shift_date->format('d/m/Y')];
        });

        $form->select('target_shift_id', 'Ca trực muốn nhận')
            ->options($targetShiftOptions)
            ->required()
            ->help('Chọn ca trực bạn muốn nhận');

        // Hidden field để lưu target_user_id
        $form->hidden('target_user_id');

        $form->textarea('reason', 'Lý do hoán đổi')
            ->required()
            ->help('Vui lòng nêu rõ lý do cần hoán đổi ca trực');

        $form->hidden('status')->default(ShiftSwapRequest::STATUS_PENDING);

        // Add JavaScript to auto-fill target_user_id when target_shift_id changes
        $form->html('<script>
            $(document).ready(function() {
                $("select[name=\'target_shift_id\']").change(function() {
                    var selectedShiftId = $(this).val();
                    if (selectedShiftId) {
                        var shiftData = ' . json_encode($availableShifts->mapWithKeys(function ($shift) {
                            return [$shift->id => $shift->admin_user_id];
                        })) . ';
                        var targetUserId = shiftData[selectedShiftId];
                        if (targetUserId) {
                            $("input[name=\'target_user_id\']").val(targetUserId);
                        }
                    }
                });
            });
        </script>');

        // Save target_user_id when target_shift_id is selected
        $form->saving(function (Form $form) {
            // Double check target_user_id is set
            if (!$form->model()->target_user_id && $form->target_shift_id) {
                $targetShift = EveningShift::find($form->target_shift_id);
                if ($targetShift) {
                    $form->model()->target_user_id = $targetShift->admin_user_id;
                }
            }

            // Save original shift dates
            if ($form->requester_shift_id) {
                $requesterShift = EveningShift::find($form->requester_shift_id);
                if ($requesterShift) {
                    $form->model()->original_requester_shift_date = $requesterShift->shift_date;
                }
            }

            if ($form->target_shift_id) {
                $targetShift = EveningShift::find($form->target_shift_id);
                if ($targetShift) {
                    $form->model()->original_target_shift_date = $targetShift->shift_date;
                }
            }
        });

        $form->saved(function (Form $form, $result) {
            try {
                // Lấy model instance để thêm lịch sử
                $model = $form->model();
                if ($model && method_exists($model, 'addHistory')) {
                    $model->addHistory(RequestHistory::ACTION_SUBMITTED, Admin::user()->id, 'Nhân viên gửi đơn hoán đổi ca');
                }
            } catch (\Exception $e) {
                // Ignore history errors
            }
        });

        return $form;
    }

    /**
     * Cancel own swap request
     */
    public function cancel(Request $request, $id)
    {
        $swapRequest = ShiftSwapRequest::where('id', $id)
            ->where('requester_id', Admin::user()->id)
            ->firstOrFail();
        
        if ($swapRequest->status !== ShiftSwapRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ có thể hủy đơn đang chờ duyệt!'
            ]);
        }

        try {
            DB::beginTransaction();

            $swapRequest->status = ShiftSwapRequest::STATUS_CANCELLED;
            $swapRequest->save();

            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_CANCELLED,
                Admin::user()->id,
                'Nhân viên tự hủy đơn'
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn hoán đổi ca thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }
}