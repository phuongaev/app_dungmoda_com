<?php

namespace App\Services;

use App\Models\EveningShift;
use App\Models\LeaveRequest;
use App\Models\ShiftSwapRequest;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftCalendarService
{
    /**
     * Get events for calendar display
     */
    public function getEvents($start, $end)
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();
        
        // Lấy tất cả ca trực trong khoảng thời gian
        $shifts = EveningShift::getCalendarData($startDate, $endDate);
        
        // Lấy thông tin người nghỉ để hiển thị
        $leaveEvents = $this->getLeaveEvents($startDate, $endDate);
        
        // Merge shifts và leave events
        $allEvents = $shifts->concat($leaveEvents);
        
        return $allEvents->toArray();
    }

    /**
     * Get leave events for calendar
     */
    private function getLeaveEvents($startDate, $endDate)
    {
        $leaveRequests = LeaveRequest::with('employee')
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->inDateRange($startDate, $endDate)
            ->get();

        $leaveEvents = collect();

        foreach ($leaveRequests as $leave) {
            // Tạo event cho mỗi ngày trong khoảng nghỉ
            $currentDate = $leave->start_date->copy();
            while ($currentDate->lte($leave->end_date)) {
                // Kiểm tra xem ngày này có ca trực không
                $hasShift = EveningShift::where('shift_date', $currentDate)->exists();
                
                $leaveEvents->push([
                    'id' => 'leave-' . $leave->id . '-' . $currentDate->format('Y-m-d'),
                    'title' => '🏠 ' . $leave->employee->name . ' (nghỉ)',
                    'start' => $currentDate->format('Y-m-d'),
                    'allDay' => true,
                    'className' => 'leave-event',
                    'backgroundColor' => '#dc3545',
                    'borderColor' => '#c82333',
                    'textColor' => '#ffffff',
                    'overlap' => true,
                    'rendering' => $hasShift ? 'background' : 'normal',
                    'leave_info' => [
                        'employee_name' => $leave->employee->name,
                        'leave_id' => $leave->id,
                        'reason' => $leave->reason,
                        'start_date' => $leave->start_date->format('d/m/Y'),
                        'end_date' => $leave->end_date->format('d/m/Y'),
                        'total_days' => $leave->total_days
                    ]
                ]);
                
                $currentDate->addDay();
            }
        }

        return $leaveEvents;
    }

    /**
     * Get available users for shift assignment
     */
    public function getAvailableUsers()
    {
        // Get sale_team role
        $saleTeamRole = Role::where('slug', 'sale_team')->first();
        
        if (!$saleTeamRole) {
            return [];
        }

        // Get user IDs with sale_team role
        $users = $saleTeamRole->administrators()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => $user->name,
                'name' => $user->name
            ];
        })->toArray();
    }

    /**
     * Create a new shift
     */
    public function createShift($date, $userId)
    {
        try {
            DB::beginTransaction();

            // Fix: Frontend đang gửi sai thứ tự parameters
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $userId) && is_numeric($date) && $date < 100) {
                $realDate = $userId;        // "2025-07-19"
                $realUserId = $date;        // "9" (đây mới là userId thực)
                $date = $realDate;
                $userId = $realUserId;
            }

            // Parse date
            if (is_numeric($date)) {
                if ($date > 1000000000) {
                    $shiftDate = Carbon::createFromTimestamp($date);
                } else {
                    $day = intval($date);
                    $shiftDate = Carbon::now()->day($day);
                    if ($shiftDate->isPast()) {
                        $shiftDate = $shiftDate->addMonth();
                    }
                }
            } elseif (strlen($date) <= 2) {
                $day = intval($date);
                $shiftDate = Carbon::now()->day($day);
                if ($shiftDate->isPast()) {
                    $shiftDate = $shiftDate->addMonth();
                }
            } else {
                $shiftDate = Carbon::parse($date);
            }

            // Validate date
            if ($shiftDate->lt(Carbon::today())) {
                return [
                    'success' => false,
                    'message' => 'Không thể tạo ca trực cho ngày đã qua.'
                ];
            }

            // Validate user
            $userId = intval($userId);
            if ($userId <= 0 || $userId > 1000) {
                return [
                    'success' => false,
                    'message' => 'ID người dùng không hợp lệ.'
                ];
            }

            $user = Administrator::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Người dùng không tồn tại.'
                ];
            }

            // Check if user already has shift on this date
            $userHasShift = EveningShift::where('admin_user_id', $userId)
                ->where('shift_date', $shiftDate->format('Y-m-d'))
                ->exists();
                
            if ($userHasShift) {
                return [
                    'success' => false,
                    'message' => $user->name . ' đã có ca trực vào ngày ' . $shiftDate->format('d/m/Y')
                ];
            }

            // Check if user is on leave
            $isOnLeave = LeaveRequest::where('admin_user_id', $userId)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $shiftDate)
                ->where('end_date', '>=', $shiftDate)
                ->exists();

            if ($isOnLeave) {
                return [
                    'success' => false,
                    'message' => $user->name . ' đang trong thời gian nghỉ phép vào ngày ' . $shiftDate->format('d/m/Y')
                ];
            }

            // Create shift
            $shift = EveningShift::create([
                'admin_user_id' => $userId,
                'shift_date' => $shiftDate->format('Y-m-d')
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tạo ca trực thành công cho ' . $user->name . ' vào ngày ' . $shiftDate->format('d/m/Y'),
                'shift' => $shift
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Lỗi khi tạo ca trực: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update shift date
     */
    public function updateShiftDate($shiftId, $newDate)
    {
        try {
            DB::beginTransaction();

            $shift = EveningShift::findOrFail($shiftId);
            $newShiftDate = Carbon::parse($newDate);

            // Validate new date
            if ($newShiftDate->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Không thể chuyển ca trực về ngày đã qua.'
                ];
            }

            // Check if user is on leave on new date
            $isOnLeave = LeaveRequest::where('admin_user_id', $shift->admin_user_id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $newShiftDate)
                ->where('end_date', '>=', $newShiftDate)
                ->exists();

            if ($isOnLeave) {
                return [
                    'success' => false,
                    'message' => $shift->user->name . ' đang nghỉ phép vào ngày ' . $newShiftDate->format('d/m/Y')
                ];
            }

            $shift->shift_date = $newDate;
            $shift->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Cập nhật ca trực thành công.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating shift date: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Change person assigned to shift
     */
    public function changePerson($shiftId, $newUserId)
    {
        try {
            DB::beginTransaction();

            $shift = EveningShift::findOrFail($shiftId);
            $newUser = Administrator::findOrFail($newUserId);

            // Check if new user is on leave
            $isOnLeave = LeaveRequest::where('admin_user_id', $newUserId)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $shift->shift_date)
                ->where('end_date', '>=', $shift->shift_date)
                ->exists();

            if ($isOnLeave) {
                return [
                    'success' => false,
                    'message' => $newUser->name . ' đang nghỉ phép vào ngày ' . $shift->shift_date->format('d/m/Y')
                ];
            }

            $shift->admin_user_id = $newUserId;
            $shift->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Thay đổi người trực thành công.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing shift person: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Lỗi khi thay đổi người trực.'
            ];
        }
    }

    /**
     * Swap two shifts
     */
    public function swapShifts($sourceId, $targetId)
    {
        try {
            DB::beginTransaction();

            $sourceShift = EveningShift::findOrFail($sourceId);
            $targetShift = EveningShift::findOrFail($targetId);

            // Check if users are on leave on the swapped dates
            $sourceUserOnLeave = LeaveRequest::where('admin_user_id', $sourceShift->admin_user_id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $targetShift->shift_date)
                ->where('end_date', '>=', $targetShift->shift_date)
                ->exists();

            if ($sourceUserOnLeave) {
                return [
                    'success' => false,
                    'message' => $sourceShift->user->name . ' đang nghỉ phép vào ngày ' . $targetShift->shift_date->format('d/m/Y')
                ];
            }

            $targetUserOnLeave = LeaveRequest::where('admin_user_id', $targetShift->admin_user_id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $sourceShift->shift_date)
                ->where('end_date', '>=', $sourceShift->shift_date)
                ->exists();

            if ($targetUserOnLeave) {
                return [
                    'success' => false,
                    'message' => $targetShift->user->name . ' đang nghỉ phép vào ngày ' . $sourceShift->shift_date->format('d/m/Y')
                ];
            }

            // Swap user assignments
            $tempUserId = $sourceShift->admin_user_id;
            $sourceShift->admin_user_id = $targetShift->admin_user_id;
            $targetShift->admin_user_id = $tempUserId;

            $sourceShift->save();
            $targetShift->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Hoán đổi ca trực thành công.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error swapping shifts: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Lỗi khi hoán đổi ca trực.'
            ];
        }
    }

    /**
     * Get available shifts for swapping (excluding current shift)
     */
    public function getAvailableShiftsForSwap($excludeId)
    {
        // Get sale_team role
        $saleTeamRole = Role::where('slug', 'sale_team')->first();
        
        if (!$saleTeamRole) {
            return collect([]);
        }

        // Get user IDs with sale_team role
        $saleTeamUserIds = $saleTeamRole->administrators()->pluck('id')->toArray();

        $shifts = EveningShift::with('user')
            ->where('id', '!=', $excludeId)
            ->whereIn('admin_user_id', $saleTeamUserIds) // Only sale team users
            ->where('shift_date', '>=', Carbon::now()->format('Y-m-d'))
            ->orderBy('shift_date')
            ->get();

        return $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'text' => $shift->user->name . ' - ' . $shift->shift_date->format('d/m/Y'),
                'user_name' => $shift->user->name,
                'shift_date' => $shift->shift_date->format('d/m/Y'),
                'user_id' => $shift->admin_user_id
            ];
        });
    }

    /**
     * Get shifts with leave information for a specific date range
     */
    public function getShiftsWithLeaveInfo($startDate, $endDate)
    {
        $shifts = EveningShift::with(['user'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('shift_date')
            ->get();

        return $shifts->map(function ($shift) {
            $display = $shift->calendar_display;
            
            return [
                'id' => $shift->id,
                'date' => $shift->shift_date->format('Y-m-d'),
                'formatted_date' => $shift->shift_date->format('d/m/Y'),
                'user' => [
                    'id' => $shift->admin_user_id,
                    'name' => $display['user_name']
                ],
                'is_on_leave' => $display['is_on_leave'],
                'is_swapped' => $display['is_swapped'],
                'swap_info' => $display['swap_info'] ?? null,
                'on_leave_users' => $display['on_leave_users']->toArray()
            ];
        });
    }

    /**
     * Get shifts by date range (alias method)
     */
    public function getShiftsByDateRange($startDate, $endDate)
    {
        return $this->getShiftsWithLeaveInfo($startDate, $endDate);
    }

    /**
     * Format shifts for calendar display
     */
    public function formatShiftsForCalendar($shifts)
    {
        return $shifts->map(function ($shift) {
            return [
                'id' => $shift['id'],
                'title' => $shift['user']['name'],
                'start' => $shift['date'],
                'allDay' => true,

                'className' => $this->getCalendarClassName($shift),
                'backgroundColor' => $this->getCalendarBackgroundColor($shift),
                'borderColor' => $this->getCalendarBorderColor($shift),

                'extendedProps' => [
                    'user_id' => $shift['user']['id'],
                    'userName' => $shift['user']['name'],
                    'shiftDate' => $shift['date'],
                    'is_on_leave' => $shift['is_on_leave'],
                    'is_swapped' => $shift['is_swapped'],
                    'swap_info' => $shift['swap_info'],
                    'on_leave_users' => $shift['on_leave_users']
                ]
            ];
        });
    }

    /**
     * Get calendar class name for shift
     */
    private function getCalendarClassName($shift)
    {
        $classes = ['shift-event'];
        
        if ($shift['is_on_leave']) {
            $classes[] = 'on-leave';
        }
        
        if ($shift['is_swapped']) {
            $classes[] = 'swapped';
        }
        
        return implode(' ', $classes);
    }

    /**
     * Get calendar background color for shift
     */
    private function getCalendarBackgroundColor($shift)
    {
        // Ưu tiên trạng thái đặc biệt
        if ($shift['is_on_leave']) {
            return '#d9534f'; // Red for on leave
        }
        
        if ($shift['is_swapped']) {
            return '#f0ad4e'; // Orange for swapped
        }
        
        // Màu sắc riêng cho từng nhân viên
        return $this->getUserColor($shift['user']['id']);
    }

    /**
     * Get calendar border color for shift
     */
    private function getCalendarBorderColor($shift)
    {
        // Ưu tiên trạng thái đặc biệt
        if ($shift['is_on_leave']) {
            return '#d43f3a';
        }
        
        if ($shift['is_swapped']) {
            return '#eea236';
        }
        
        // Màu viền tương ứng với màu nền
        return $this->getUserBorderColor($shift['user']['id']);
    }

    /**
     * Get unique color for each user
     */
    private function getUserColor($userId)
    {
        // Palette màu đẹp cho các nhân viên
        $colors = [
            '#3498db', // Blue
            '#2ecc71', // Green  
            '#9b59b6', // Purple
            '#e74c3c', // Red
            '#f39c12', // Orange
            '#1abc9c', // Teal
            '#34495e', // Dark Gray
            '#e67e22', // Dark Orange
            '#8e44ad', // Dark Purple
            '#27ae60', // Dark Green
            '#2980b9', // Dark Blue
            '#c0392b', // Dark Red
            '#16a085', // Dark Teal
            '#d35400', // Pumpkin
            '#7f8c8d', // Gray
            '#95a5a6', // Light Gray
        ];
        
        // Sử dụng user_id để lấy màu cố định
        $index = $userId % count($colors);
        return $colors[$index];
    }

    /**
     * Get border color for user (darker version)
     */
    private function getUserBorderColor($userId)
    {
        // Palette màu viền (tối hơn màu nền)
        $borderColors = [
            '#2980b9', // Dark Blue
            '#27ae60', // Dark Green  
            '#8e44ad', // Dark Purple
            '#c0392b', // Dark Red
            '#d68910', // Dark Orange
            '#148f77', // Dark Teal
            '#2c3e50', // Darker Gray
            '#ca6f1e', // Darker Orange
            '#6c3483', // Darker Purple
            '#1e8449', // Darker Green
            '#1f618d', // Darker Blue
            '#a93226', // Darker Red
            '#138d75', // Darker Teal
            '#ba4a00', // Darker Pumpkin
            '#566573', // Darker Gray
            '#85929e', // Darker Light Gray
        ];
        
        $index = $userId % count($borderColors);
        return $borderColors[$index];
    }

    /**
     * Get user legend for calendar (màu sắc từng nhân viên)
     */
    public function getUserLegend()
    {
        // Get sale_team role
        $saleTeamRole = Role::where('slug', 'sale_team')->first();
        
        if (!$saleTeamRole) {
            return [];
        }

        // Get user IDs with sale_team role
        $users = $saleTeamRole->administrators()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'color' => $this->getUserColor($user->id),
                'border_color' => $this->getUserBorderColor($user->id)
            ];
        })->toArray();
    }


    /**
     * Delete a shift
     */
    public function deleteShift($shiftId)
    {
        try {
            DB::beginTransaction();

            $shift = EveningShift::find($shiftId);
            if (!$shift) {
                return [
                    'success' => false,
                    'message' => 'Ca trực không tồn tại.'
                ];
            }

            // Check if shift is in the past
            if ($shift->shift_date->lt(Carbon::today())) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa ca trực trong quá khứ.'
                ];
            }

            // Check if there are pending swap requests involving this shift
            $pendingSwapRequests = ShiftSwapRequest::where('status', ShiftSwapRequest::STATUS_PENDING)
                ->where(function ($query) use ($shiftId) {
                    $query->where('requester_shift_id', $shiftId)
                          ->orWhere('target_shift_id', $shiftId);
                })
                ->exists();

            if ($pendingSwapRequests) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa ca trực này vì đang có đơn hoán đổi ca chờ duyệt.'
                ];
            }

            $userName = $shift->user ? $shift->user->name : 'N/A';
            $shiftDate = $shift->shift_date->format('d/m/Y');

            $shift->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => "Đã xóa ca trực của {$userName} vào ngày {$shiftDate}."
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa ca trực: ' . $e->getMessage()
            ];
        }
    }



    
}