<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Widget;
use App\Models\LeaveRequest;
use App\Models\ShiftSwapRequest;
use Carbon\Carbon;

class UpcomingRequestsWidget extends Widget
{
    protected $view = 'admin.widgets.upcoming-requests';

    /**
     * Get the data for the widget
     */
    public function data()
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(7);

        // Lấy đơn xin nghỉ trong 7 ngày tới (cả pending và approved)
        $leaveRequests = LeaveRequest::with(['employee'])
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->inDateRange($startDate, $endDate)
            ->orderBy('start_date')
            ->get();

        // Lấy đơn hoán đổi ca trong 7 ngày tới (cả pending và approved)
        $swapRequests = ShiftSwapRequest::with(['requester', 'targetUser', 'requesterShift', 'targetShift'])
            ->whereIn('status', [ShiftSwapRequest::STATUS_PENDING, ShiftSwapRequest::STATUS_APPROVED])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('original_requester_shift_date', [$startDate, $endDate])
                      ->orWhereBetween('original_target_shift_date', [$startDate, $endDate]);
            })
            ->orderBy('original_requester_shift_date')
            ->get();

        // Đếm số lượng theo trạng thái
        $pendingLeaves = $leaveRequests->where('status', LeaveRequest::STATUS_PENDING)->count();
        $approvedLeaves = $leaveRequests->where('status', LeaveRequest::STATUS_APPROVED)->count();
        $pendingSwaps = $swapRequests->where('status', ShiftSwapRequest::STATUS_PENDING)->count();
        $approvedSwaps = $swapRequests->where('status', ShiftSwapRequest::STATUS_APPROVED)->count();

        return [
            'leave_requests' => $leaveRequests,
            'swap_requests' => $swapRequests,
            'pending_leaves' => $pendingLeaves,
            'approved_leaves' => $approvedLeaves,
            'pending_swaps' => $pendingSwaps,
            'approved_swaps' => $approvedSwaps,
            'total_pending' => $pendingLeaves + $pendingSwaps,
            'total_approved' => $approvedLeaves + $approvedSwaps,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    /**
     * Render the widget
     */
    public function render()
    {
        return view($this->view, $this->data())->render();
    }
}