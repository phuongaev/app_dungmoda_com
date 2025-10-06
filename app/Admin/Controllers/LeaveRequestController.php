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

use Illuminate\Support\Facades\Log;

class LeaveRequestController extends AdminController
{
    protected $title = 'Quản lý đơn xin nghỉ';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LeaveRequest());

        $grid->column('id', 'ID')->sortable();
        
        $grid->column('employee.name', 'Nhân viên')->display(function ($name) {
            return $name ?: 'N/A';
        });
        
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
            
            $filter->like('employee.name', 'Nhân viên');
            $filter->equal('status', 'Trạng thái')->select(LeaveRequest::getStatuses());
            $filter->between('start_date', 'Từ ngày')->date();
            $filter->between('end_date', 'Đến ngày')->date();
            $filter->between('created_at', 'Ngày tạo')->datetime();
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            
            if ($actions->row->status === LeaveRequest::STATUS_PENDING) {
                $actions->add(new \App\Admin\Actions\ApproveLeaveAction());
                $actions->add(new \App\Admin\Actions\RejectLeaveAction());
            }
            
            if ($actions->row->status === LeaveRequest::STATUS_APPROVED) {
                $actions->add(new \App\Admin\Actions\CancelLeaveAction());
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

        $show->field('id', 'ID');
        $show->field('employee.name', 'Nhân viên');
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
        $show->field('admin_notes', 'Ghi chú admin');
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
     * Approve leave request
     */
    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn này đã được xử lý rồi!'
            ]);
        }

        try {
            \DB::beginTransaction();

            $leaveRequest->status = LeaveRequest::STATUS_APPROVED;
            $leaveRequest->approved_by = Admin::user()->id;
            $leaveRequest->approved_at = now();
            $leaveRequest->admin_notes = $request->input('admin_notes');
            $leaveRequest->save();

            // Lưu lịch sử
            $leaveRequest->addHistory(
                RequestHistory::ACTION_APPROVED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã duyệt đơn xin nghỉ thành công!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reject leave request
     */
    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn này đã được xử lý rồi!'
            ]);
        }

        try {
            \DB::beginTransaction();

            $leaveRequest->status = LeaveRequest::STATUS_REJECTED;
            $leaveRequest->approved_by = Admin::user()->id;
            $leaveRequest->approved_at = now();
            $leaveRequest->admin_notes = $request->input('admin_notes');
            $leaveRequest->save();

            // Lưu lịch sử
            $leaveRequest->addHistory(
                RequestHistory::ACTION_REJECTED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã từ chối đơn xin nghỉ!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel approved leave request
     */
    public function cancel(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        if ($leaveRequest->status !== LeaveRequest::STATUS_APPROVED) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ có thể hủy đơn đã duyệt!'
            ]);
        }

        try {
            \DB::beginTransaction();

            $leaveRequest->status = LeaveRequest::STATUS_CANCELLED;
            $leaveRequest->admin_notes = $request->input('admin_notes');
            $leaveRequest->save();

            // Lưu lịch sử
            $leaveRequest->addHistory(
                RequestHistory::ACTION_CANCELLED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn xin nghỉ!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Change leave request person
     */
    public function changePerson(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json([
                'status' => false,
                'message' => 'Không có quyền thực hiện hành động này.'
            ], 403);
        }

        $request->validate([
            'leave_request_id' => 'required|exists:leave_requests,id',
            'change_date' => 'required|date',
            'new_user_id' => 'required|exists:admin_users,id'
        ]);

        try {
            $service = new \App\Services\LeaveRequestService();
            
            $result = $service->changePersonOnDate(
                $request->input('leave_request_id'),
                $request->input('change_date'),
                $request->input('new_user_id')
            );

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'message' => $result['message'],
                    'data' => [
                        'old_user' => $result['old_user'],
                        'new_user' => $result['new_user']
                    ]
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error changing leave person: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }



}