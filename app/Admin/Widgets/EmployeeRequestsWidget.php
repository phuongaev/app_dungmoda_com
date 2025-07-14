<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Widget;
use App\Models\LeaveRequest;
use App\Models\ShiftSwapRequest;
use Carbon\Carbon;

class EmployeeRequestsWidget extends Widget
{
    protected $view = 'admin.widgets.employee-requests';
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the data for the widget
     */
    public function data()
    {
        // Lấy các đơn xin nghỉ của nhân viên
        $leaveRequests = LeaveRequest::where('admin_user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Lấy các đơn hoán đổi ca của nhân viên (cả làm người yêu cầu và người được đề xuất)
        $swapRequests = ShiftSwapRequest::with(['requester', 'targetUser', 'requesterShift', 'targetShift'])
            ->where(function ($query) {
                $query->where('requester_id', $this->user->id)
                      ->orWhere('target_user_id', $this->user->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Đếm số lượng theo trạng thái
        $pendingLeaves = LeaveRequest::where('admin_user_id', $this->user->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        $approvedLeaves = LeaveRequest::where('admin_user_id', $this->user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->count();

        $pendingSwaps = ShiftSwapRequest::where('requester_id', $this->user->id)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->count();

        $approvedSwaps = ShiftSwapRequest::where(function ($query) {
                $query->where('requester_id', $this->user->id)
                      ->orWhere('target_user_id', $this->user->id);
            })
            ->where('status', ShiftSwapRequest::STATUS_APPROVED)
            ->count();

        // Lấy đơn nghỉ đang active (hiện tại đang trong thời gian nghỉ)
        $activeLeave = LeaveRequest::where('admin_user_id', $this->user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '<=', Carbon::today())
            ->where('end_date', '>=', Carbon::today())
            ->first();

        // Lấy đơn nghỉ sắp tới (trong 7 ngày)
        $upcomingLeaves = LeaveRequest::where('admin_user_id', $this->user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '>', Carbon::today())
            ->where('start_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('start_date')
            ->get();

        return [
            'user' => $this->user,
            'leave_requests' => $leaveRequests,
            'swap_requests' => $swapRequests,
            'pending_leaves' => $pendingLeaves,
            'approved_leaves' => $approvedLeaves,
            'pending_swaps' => $pendingSwaps,
            'approved_swaps' => $approvedSwaps,
            'total_pending' => $pendingLeaves + $pendingSwaps,
            'total_approved' => $approvedLeaves + $approvedSwaps,
            'active_leave' => $activeLeave,
            'upcoming_leaves' => $upcomingLeaves
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