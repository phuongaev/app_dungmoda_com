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
        
        $grid->column('swap_summary', 'Tóm tắt hoán đổi')->display(function () {
            return $this->swap_summary;
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
            
            $filter->like('requester.name', 'Người yêu cầu');
            $filter->like('target_user.name', 'Người được đề xuất');
            $filter->equal('status', 'Trạng thái')->select(ShiftSwapRequest::getStatuses());
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
        $show = new Show(ShiftSwapRequest::findOrFail($id));

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
     * Approve swap request
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
            \DB::beginTransaction();

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

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã duyệt đơn hoán đổi ca thành công!'
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
     * Reject swap request
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
            \DB::beginTransaction();

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

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã từ chối đơn hoán đổi ca!'
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
     * Cancel approved swap request
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
            \DB::beginTransaction();

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

            \DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã hủy đơn hoán đổi ca và khôi phục lịch cũ!'
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