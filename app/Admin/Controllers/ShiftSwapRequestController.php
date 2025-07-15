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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftSwapRequestController extends AdminController
{
    protected $title = 'Quản lý đơn hoán đổi ca trực';

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

        $grid->column('id', 'ID')->sortable();
        
        $grid->column('requester.name', 'Người yêu cầu')->display(function () {
            return $this->requester ? (string)$this->requester->name : 'N/A';
        });
        
        $grid->column('original_requester_shift_date', 'Ca của người YC')->display(function () {
            return $this->original_requester_shift_date ? $this->original_requester_shift_date->format('d/m/Y') : 'N/A';
        })->sortable();
        
        $grid->column('targetUser.name', 'Người được đề xuất')->display(function () {
            return $this->targetUser ? (string)$this->targetUser->name : 'N/A';
        });
        
        $grid->column('original_target_shift_date', 'Ca được đề xuất')->display(function () {
            return $this->original_target_shift_date ? $this->original_target_shift_date->format('d/m/Y') : 'N/A';
        })->sortable();
        
        $grid->column('swap_summary', 'Tóm tắt hoán đổi')->display(function () {
            $requesterName = $this->requester ? (string)$this->requester->name : 'N/A';
            $targetName = $this->targetUser ? (string)$this->targetUser->name : 'N/A';
            $requesterDate = $this->original_requester_shift_date ? $this->original_requester_shift_date->format('d/m/Y') : 'N/A';
            $targetDate = $this->original_target_shift_date ? $this->original_target_shift_date->format('d/m/Y') : 'N/A';
            return "{$requesterName} ({$requesterDate}) ↔ {$targetName} ({$targetDate})";
        });
        
        $grid->column('reason', 'Lý do')->display(function () {
            return (string)$this->reason ?: 'N/A';
        });
        
        $grid->column('status', 'Trạng thái')->display(function () {
            $badges = [
                'pending' => '<span class="label label-warning">Chờ duyệt</span>',
                'approved' => '<span class="label label-success">Đã duyệt</span>',
                'rejected' => '<span class="label label-danger">Từ chối</span>',
                'cancelled' => '<span class="label label-default">Đã hủy</span>'
            ];
            return $badges[$this->status] ?? (string)$this->status;
        });
        
        $grid->column('approver.name', 'Người duyệt')->display(function () {
            return $this->approver ? (string)$this->approver->name : '-';
        });
        
        $grid->column('approved_at', 'Ngày duyệt')->display(function () {
            return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
        })->sortable();
        
        $grid->column('created_at', 'Ngày tạo')->display(function () {
            return $this->created_at ? $this->created_at->format('d/m/Y H:i') : '-';
        })->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->where(function ($query) {
                $query->whereHas('requester', function ($q) {
                    $q->where('name', 'like', "%{$this->input}%");
                });
            }, 'Người yêu cầu');
            
            $filter->where(function ($query) {
                $query->whereHas('targetUser', function ($q) {
                    $q->where('name', 'like', "%{$this->input}%");
                });
            }, 'Người được đề xuất');
            
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
            
            if ($actions->row->status === ShiftSwapRequest::STATUS_PENDING) {
                $actions->add(new \App\Admin\Actions\ApproveSwapAction());
                $actions->add(new \App\Admin\Actions\RejectSwapAction());
            }
            
            if ($actions->row->status === ShiftSwapRequest::STATUS_APPROVED) {
                $actions->add(new \App\Admin\Actions\CancelSwapAction());
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
     * Approve shift swap request
     */
    public function approve(Request $request, $id)
    {
        $swapRequest = ShiftSwapRequest::findOrFail($id);
        
        if ($swapRequest->status !== ShiftSwapRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn này đã được xử lý rồi!'
            ]);
        }

        try {
            DB::beginTransaction();

            $swapRequest->status = ShiftSwapRequest::STATUS_APPROVED;
            $swapRequest->approved_by = Admin::user()->id;
            $swapRequest->approved_at = now();
            $swapRequest->admin_notes = $request->input('admin_notes');
            $swapRequest->save();

            // Thực hiện hoán đổi ca
            if (!$swapRequest->executeSwap()) {
                throw new \Exception('Không thể thực hiện hoán đổi ca');
            }

            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_APPROVED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã duyệt đơn hoán đổi ca!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reject shift swap request
     */
    public function reject(Request $request, $id)
    {
        $swapRequest = ShiftSwapRequest::findOrFail($id);
        
        if ($swapRequest->status !== ShiftSwapRequest::STATUS_PENDING) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn này đã được xử lý rồi!'
            ]);
        }

        try {
            DB::beginTransaction();

            $swapRequest->status = ShiftSwapRequest::STATUS_REJECTED;
            $swapRequest->approved_by = Admin::user()->id;
            $swapRequest->approved_at = now();
            $swapRequest->admin_notes = $request->input('admin_notes');
            $swapRequest->save();

            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_REJECTED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã từ chối đơn hoán đổi ca!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel approved shift swap request
     */
    public function cancel(Request $request, $id)
    {
        $swapRequest = ShiftSwapRequest::findOrFail($id);
        
        if ($swapRequest->status !== ShiftSwapRequest::STATUS_APPROVED) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ có thể hủy đơn đã duyệt!'
            ]);
        }

        try {
            DB::beginTransaction();

            // Khôi phục lại ca trực ban đầu
            if (!$swapRequest->revertSwap()) {
                throw new \Exception('Không thể khôi phục ca trực ban đầu');
            }

            $swapRequest->status = ShiftSwapRequest::STATUS_CANCELLED;
            $swapRequest->admin_notes = $request->input('admin_notes');
            $swapRequest->save();

            // Lưu lịch sử
            $swapRequest->addHistory(
                RequestHistory::ACTION_CANCELLED,
                Admin::user()->id,
                $request->input('admin_notes')
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn hoán đổi ca và khôi phục lịch cũ!'
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