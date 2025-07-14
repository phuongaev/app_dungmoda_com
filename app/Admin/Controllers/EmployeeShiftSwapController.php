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
        
        $grid->column('target_user.name', 'Người được đề xuất')->display(function ($name) {
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
            
            $filter->equal('status', 'Trạng thái')->select(ShiftSwapRequest::getStatuses());
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
        $show = new Show(ShiftSwapRequest::findOrFail($id));

        // Chỉ cho phép xem đơn liên quan đến mình
        $swapRequest = ShiftSwapRequest::where('id', $id)
            ->where(function ($query) {
                $query->where('requester_id', Admin::user()->id)
                      ->orWhere('target_user_id', Admin::user()->id);
            })
            ->firstOrFail();

        $show->field('id', 'ID');
        $show->field('requester.name', 'Người yêu cầu hoán đổi');
        $show->field('original_requester_shift_date', 'Ca trực của người yêu cầu')->as(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        });
        $show->field('target_user.name', 'Người được đề xuất hoán đổi');
        $show->field('original_target_shift_date', 'Ca trực được đề xuất')->as(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        });
        $show->field('swap_summary', 'Tóm tắt hoán đổi');
        $show->field('reason', 'Lý do hoán đổi');
        $show->field('status', 'Trạng thái')->as(function ($status) {
            return ShiftSwapRequest::getStatuses()[$status] ?? $status;
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
        $form = new Form(new ShiftSwapRequest());

        $form->hidden('requester_id')->value(Admin::user()->id);

        // Lấy ca trực của user hiện tại (từ hôm nay trở đi)
        $userShifts = EveningShift::where('admin_user_id', Admin::user()->id)
            ->where('shift_date', '>=', Carbon::today())
            ->orderBy('shift_date')
            ->get()
            ->pluck('shift_date_formatted', 'id');

        if ($userShifts->isEmpty()) {
            return '<div class="alert alert-warning">Bạn không có ca trực nào từ hôm nay trở đi để hoán đổi.</div>';
        }

        $form->select('requester_shift_id', 'Ca trực của tôi muốn đổi')
            ->options($userShifts)
            ->rules('required')
            ->help('Chọn ca trực của bạn muốn hoán đổi');

        // Lấy danh sách nhân viên sale team (trừ mình)
        $saleTeamRole = Role::where('slug', 'sale_team')->first();
        $saleTeamUsers = [];
        
        if ($saleTeamRole) {
            $saleTeamUsers = $saleTeamRole->administrators()
                ->where('id', '!=', Admin::user()->id)
                ->pluck('name', 'id');
        }

        $form->select('target_user_id', 'Người muốn hoán đổi với')
            ->options($saleTeamUsers)
            ->rules('required')
            ->help('Chọn đồng nghiệp bạn muốn hoán đổi ca trực')
            ->load('target_shift_id', '/admin/employee-shift-swaps/get-user-shifts');

        $form->select('target_shift_id', 'Ca trực của người đó')
            ->rules('required')
            ->help('Chọn ca trực của đồng nghiệp mà bạn muốn nhận');

        $form->textarea('reason', 'Lý do hoán đổi')
            ->rules('required|min:10')
            ->rows(4)
            ->help('Mô tả lý do tại sao bạn muốn hoán đổi ca trực (tối thiểu 10 ký tự)');

        $form->hidden('status')->value(ShiftSwapRequest::STATUS_PENDING);

        // Validation tùy chỉnh
        $form->saving(function (Form $form) {
            // Kiểm tra ca trực của requester
            $requesterShift = EveningShift::find($form->requester_shift_id);
            if (!$requesterShift || $requesterShift->admin_user_id != Admin::user()->id) {
                return back()->withErrors(['requester_shift_id' => 'Ca trực không hợp lệ hoặc không thuộc về bạn.']);
            }

            // Kiểm tra ca trực của target
            $targetShift = EveningShift::find($form->target_shift_id);
            if (!$targetShift || $targetShift->admin_user_id != $form->target_user_id) {
                return back()->withErrors(['target_shift_id' => 'Ca trực của đồng nghiệp không hợp lệ.']);
            }

            // Lưu thông tin ngày gốc để có thể khôi phục
            $form->original_requester_shift_date = $requesterShift->shift_date;
            $form->original_target_shift_date = $targetShift->shift_date;

            // Kiểm tra xem có đơn hoán đổi nào đang pending cho 2 ca này không
            $existingRequest = ShiftSwapRequest::where('status', ShiftSwapRequest::STATUS_PENDING)
                ->where(function ($query) use ($form) {
                    $query->where(function ($q) use ($form) {
                        $q->where('requester_shift_id', $form->requester_shift_id)
                          ->orWhere('target_shift_id', $form->requester_shift_id);
                    })->orWhere(function ($q) use ($form) {
                        $q->where('requester_shift_id', $form->target_shift_id)
                          ->orWhere('target_shift_id', $form->target_shift_id);
                    });
                })
                ->exists();

            if ($existingRequest) {
                return back()->withErrors(['requester_shift_id' => 'Đã có đơn hoán đổi đang chờ duyệt cho một trong các ca này.']);
            }
        });

        // Sau khi lưu thành công
        $form->saved(function (Form $form, $result) {
            $swapRequest = $form->model();
            
            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_SUBMITTED,
                Admin::user()->id,
                'Nhân viên gửi đơn hoán đổi ca'
            );
        });

        return $form;
    }

    /**
     * Get user shifts for ajax
     */
    public function getUserShifts(Request $request)
    {
        $userId = $request->get('q');
        
        if (!$userId) {
            return response()->json([]);
        }

        $shifts = EveningShift::where('admin_user_id', $userId)
            ->where('shift_date', '>=', Carbon::today())
            ->orderBy('shift_date')
            ->get()
            ->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'text' => $shift->shift_date->format('d/m/Y')
                ];
            });

        return response()->json($shifts);
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
            \DB::beginTransaction();

            $swapRequest->status = ShiftSwapRequest::STATUS_CANCELLED;
            $swapRequest->save();

            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_CANCELLED,
                Admin::user()->id,
                'Nhân viên tự hủy đơn'
            );

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn hoán đổi ca thành công!'
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