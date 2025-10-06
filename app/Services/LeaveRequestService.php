<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\RequestHistory;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestService
{
    /**
     * Change leave request from one person to another on a specific date
     * 
     * @param int $leaveRequestId Original leave request ID
     * @param string $changeDate The date to change (Y-m-d format)
     * @param int $newUserId New user ID
     * @return array
     */
    public function changePersonOnDate($leaveRequestId, $changeDate, $newUserId)
    {
        try {
            DB::beginTransaction();

            // Find original leave request
            $originalLeave = LeaveRequest::findOrFail($leaveRequestId);
            
            // Validate new user exists
            $newUser = Administrator::findOrFail($newUserId);
            
            // Parse dates
            $changeDate = Carbon::parse($changeDate);
            $startDate = Carbon::parse($originalLeave->start_date);
            $endDate = Carbon::parse($originalLeave->end_date);
            
            // Validate change date is within leave period
            if ($changeDate->lt($startDate) || $changeDate->gt($endDate)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Ngày ' . $changeDate->format('d/m/Y') . ' không nằm trong khoảng nghỉ phép từ ' . $startDate->format('d/m/Y') . ' đến ' . $endDate->format('d/m/Y')
                ];
            }
            
            // Check if new user already has approved leave on this date
            $existingLeave = LeaveRequest::where('admin_user_id', $newUserId)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where('start_date', '<=', $changeDate)
                ->where('end_date', '>=', $changeDate)
                ->where('id', '!=', $leaveRequestId)
                ->exists();
                
            if ($existingLeave) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $newUser->name . ' đã có đơn nghỉ phép vào ngày ' . $changeDate->format('d/m/Y')
                ];
            }
            
            $oldUser = $originalLeave->employee;
            
            // Case 1: Single day leave request
            if ($startDate->eq($endDate)) {
                // Add history before delete
                if (method_exists($originalLeave, 'addHistory')) {
                    $originalLeave->addHistory(
                        'cancelled',
                        Admin::user()->id,
                        'Đổi sang ' . $newUser->name
                    );
                }
                
                // Delete old leave request
                $originalLeave->delete();
                
                // Create new leave for new user
                $newLeave = LeaveRequest::create([
                    'admin_user_id' => $newUserId,
                    'start_date' => $changeDate,
                    'end_date' => $changeDate,
                    'reason' => 'Thay thế ' . $oldUser->name,
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_by' => Admin::user()->id,
                    'approved_at' => now(),
                    'created_by_admin' => true,
                    'admin_notes' => 'Tạo từ chức năng thay đổi người nghỉ'
                ]);
                
                // Add history
                if (method_exists($newLeave, 'addHistory')) {
                    $newLeave->addHistory(
                        'approved',
                        Admin::user()->id,
                        'Admin tạo từ chức năng thay đổi người nghỉ'
                    );
                }
                
                DB::commit();
                
                return [
                    'success' => true,
                    'message' => 'Đã đổi ngày nghỉ từ ' . $oldUser->name . ' sang ' . $newUser->name,
                    'old_user' => $oldUser->name,
                    'new_user' => $newUser->name
                ];
            }
            
            // Case 2: Multi-day leave request - need to split
            
            // Case 2a: Change first day
            if ($changeDate->eq($startDate)) {
                // Update original leave to start from next day
                $originalLeave->start_date = $startDate->copy()->addDay();
                $originalLeave->save();
                
                // Add history
                if (method_exists($originalLeave, 'addHistory')) {
                    $originalLeave->addHistory(
                        'updated',
                        Admin::user()->id,
                        'Chuyển ngày ' . $changeDate->format('d/m/Y') . ' sang ' . $newUser->name
                    );
                }
                
                // Create new leave for new user (1 day only)
                $newLeave = LeaveRequest::create([
                    'admin_user_id' => $newUserId,
                    'start_date' => $changeDate,
                    'end_date' => $changeDate,
                    'reason' => 'Thay thế ' . $oldUser->name,
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_by' => Admin::user()->id,
                    'approved_at' => now(),
                    'created_by_admin' => true,
                    'admin_notes' => 'Tạo từ chức năng thay đổi người nghỉ'
                ]);
                
                if (method_exists($newLeave, 'addHistory')) {
                    $newLeave->addHistory(
                        'approved',
                        Admin::user()->id,
                        'Admin tạo từ chức năng thay đổi người nghỉ'
                    );
                }
            }
            // Case 2b: Change last day
            elseif ($changeDate->eq($endDate)) {
                // Update original leave to end at previous day
                $originalLeave->end_date = $endDate->copy()->subDay();
                $originalLeave->save();
                
                // Add history
                if (method_exists($originalLeave, 'addHistory')) {
                    $originalLeave->addHistory(
                        'updated',
                        Admin::user()->id,
                        'Chuyển ngày ' . $changeDate->format('d/m/Y') . ' sang ' . $newUser->name
                    );
                }
                
                // Create new leave for new user (1 day only)
                $newLeave = LeaveRequest::create([
                    'admin_user_id' => $newUserId,
                    'start_date' => $changeDate,
                    'end_date' => $changeDate,
                    'reason' => 'Thay thế ' . $oldUser->name,
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_by' => Admin::user()->id,
                    'approved_at' => now(),
                    'created_by_admin' => true,
                    'admin_notes' => 'Tạo từ chức năng thay đổi người nghỉ'
                ]);
                
                if (method_exists($newLeave, 'addHistory')) {
                    $newLeave->addHistory(
                        'approved',
                        Admin::user()->id,
                        'Admin tạo từ chức năng thay đổi người nghỉ'
                    );
                }
            }
            // Case 2c: Change middle day - need to split into 2 periods
            else {
                // Create first period (start to day before change)
                $originalLeave->end_date = $changeDate->copy()->subDay();
                $originalLeave->save();
                
                // Add history
                if (method_exists($originalLeave, 'addHistory')) {
                    $originalLeave->addHistory(
                        'updated',
                        Admin::user()->id,
                        'Tách do chuyển ngày ' . $changeDate->format('d/m/Y') . ' sang ' . $newUser->name
                    );
                }
                
                // Create second period for old user (day after change to end)
                $secondPeriod = LeaveRequest::create([
                    'admin_user_id' => $originalLeave->admin_user_id,
                    'start_date' => $changeDate->copy()->addDay(),
                    'end_date' => $endDate,
                    'reason' => $originalLeave->reason,
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_by' => Admin::user()->id,
                    'approved_at' => now(),
                    'created_by_admin' => true,
                    'admin_notes' => 'Tiếp tục sau khi tách (thay thế bởi ' . $newUser->name . ' ngày ' . $changeDate->format('d/m/Y') . ')'
                ]);
                
                if (method_exists($secondPeriod, 'addHistory')) {
                    $secondPeriod->addHistory(
                        'approved',
                        Admin::user()->id,
                        'Phần tiếp theo sau khi tách'
                    );
                }
                
                // Create leave for new user (1 day only)
                $newLeave = LeaveRequest::create([
                    'admin_user_id' => $newUserId,
                    'start_date' => $changeDate,
                    'end_date' => $changeDate,
                    'reason' => 'Thay thế ' . $oldUser->name,
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_by' => Admin::user()->id,
                    'approved_at' => now(),
                    'created_by_admin' => true,
                    'admin_notes' => 'Tạo từ chức năng thay đổi người nghỉ'
                ]);
                
                if (method_exists($newLeave, 'addHistory')) {
                    $newLeave->addHistory(
                        'approved',
                        Admin::user()->id,
                        'Admin tạo từ chức năng thay đổi người nghỉ'
                    );
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Đã đổi ngày nghỉ ' . $changeDate->format('d/m/Y') . ' từ ' . $oldUser->name . ' sang ' . $newUser->name,
                'old_user' => $oldUser->name,
                'new_user' => $newUser->name
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing leave person: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete leave request with history
     */
    private function deleteLeaveWithHistory($leaveRequest, $reason)
    {
        $leaveRequest->addHistory(
            RequestHistory::ACTION_CANCELLED,
            Admin::user()->id,
            $reason
        );
        
        $leaveRequest->delete();
    }
    
    /**
     * Create leave request for user
     */
    private function createLeaveForUser($userId, $startDate, $endDate, $reason)
    {
        $leave = LeaveRequest::create([
            'admin_user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_by' => Admin::user()->id,
            'approved_at' => now(),
            'created_by_admin' => true,
            'admin_notes' => 'Tạo từ chức năng thay đổi người nghỉ'
        ]);
        
        $leave->addHistory(
            RequestHistory::ACTION_APPROVED,
            Admin::user()->id,
            'Admin tạo từ chức năng thay đổi người nghỉ'
        );
        
        return $leave;
    }
    
    /**
     * Get leave events by date range for calendar display
     * Returns data grouped by date with leave_request_id
     */
    public function getLeaveEventsByDateRange($startDate, $endDate)
    {
        // Lấy tất cả leave requests đã approved trong khoảng thời gian
        $leaveRequests = LeaveRequest::with('employee')
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Leave overlaps with date range
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($subQ) use ($startDate, $endDate) {
                          $subQ->where('start_date', '<=', $startDate)
                               ->where('end_date', '>=', $endDate);
                      });
                });
            })
            ->get();

        // Group leave data by date
        $leaveByDate = [];
        
        foreach ($leaveRequests as $leave) {
            $currentDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            
            // Loop through each day of the leave period
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                
                if (!isset($leaveByDate[$dateKey])) {
                    $leaveByDate[$dateKey] = [];
                }
                
                $leaveByDate[$dateKey][] = [
                    'id' => $leave->employee->id,
                    'name' => $leave->employee->name,
                    'created_by_admin' => $leave->created_by_admin,
                    'leave_request_id' => $leave->id  // IMPORTANT: Include leave request ID
                ];
                
                $currentDate->addDay();
            }
        }
        
        return $leaveByDate;
    }
    
    /**
     * Get all leave requests for a specific date
     */
    public function getLeaveRequestsByDate($date)
    {
        $date = Carbon::parse($date);
        
        return LeaveRequest::with('employee')
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get()
            ->map(function($leave) use ($date) {
                return [
                    'id' => $leave->id,
                    'user_id' => $leave->admin_user_id,
                    'user_name' => $leave->employee->name,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'is_single_day' => $leave->start_date->eq($leave->end_date),
                    'change_date' => $date->format('Y-m-d')
                ];
            });
    }
}