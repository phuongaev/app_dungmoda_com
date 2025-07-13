<?php

namespace App\Services;

use App\Models\EveningShift;
use Encore\Admin\Auth\Database\Administrator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftCalendarService
{
    /**
     * Color palette for different users
     */
    const USER_COLORS = [
        '#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', 
        '#34495e', '#1abc9c', '#e67e22', '#d35400', '#c0392b'
    ];

    /**
     * Get shifts within date range
     */
    public function getShiftsByDateRange(Carbon $start, Carbon $end)
    {
        return EveningShift::with('user')
            ->whereBetween('shift_date', [$start->startOfDay(), $end->endOfDay()])
            ->orderBy('shift_date')
            ->get();
    }

    /**
     * Format shifts for FullCalendar
     */
    public function formatShiftsForCalendar($shifts)
    {
        $userColors = [];
        $colorIndex = 0;

        return $shifts->map(function ($shift) use (&$userColors, &$colorIndex) {
            if (!$shift->user) {
                return null;
            }

            $userId = $shift->user->id;
            $userName = $shift->user->name;

            // Assign color to user if not already assigned
            if (!isset($userColors[$userId])) {
                $userColors[$userId] = self::USER_COLORS[$colorIndex % count(self::USER_COLORS)];
                $colorIndex++;
            }

            return [
                'id' => $shift->id,
                'title' => $userName,
                'start' => $shift->shift_date,
                'color' => $userColors[$userId],
                'allDay' => true,
                'extendedProps' => [
                    'userId' => $userId,
                    'userName' => $userName,
                    'shiftDate' => Carbon::parse($shift->shift_date)->format('d/m/Y')
                ]
            ];
        })->filter()->values();
    }

    /**
     * Update shift date
     */
    public function updateShiftDate($shiftId, $newDate)
    {
        try {
            DB::beginTransaction();

            $shift = EveningShift::findOrFail($shiftId);
            
            // Check for conflicts
            $conflict = $this->checkDateConflict($newDate, $shiftId);
            if ($conflict) {
                throw new \Exception('Ngày ' . Carbon::parse($newDate)->format('d/m/Y') . ' đã có người trực.');
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
     * Swap two shifts
     */
    public function swapShifts($sourceId, $targetId)
    {
        try {
            DB::beginTransaction();

            $sourceShift = EveningShift::findOrFail($sourceId);
            $targetShift = EveningShift::findOrFail($targetId);

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
        $shifts = EveningShift::with('user')
            ->where('id', '!=', $excludeId)
            ->where('shift_date', '>=', Carbon::now()->format('Y-m-d'))
            ->orderBy('shift_date')
            ->get();

        return $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'text' => $shift->user->name . ' - ' . Carbon::parse($shift->shift_date)->format('d/m/Y'),
                'date' => $shift->shift_date,
                'user_name' => $shift->user->name
            ];
        });
    }

    /**
     * Check if date has existing shift (conflict check)
     */
    public function checkDateConflict($date, $excludeShiftId = null)
    {
        $query = EveningShift::where('shift_date', $date);
        
        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }

        return $query->exists();
    }

    /**
     * Get shifts for a specific month
     */
    public function getMonthlyShifts($year, $month)
    {
        return EveningShift::with('user')
            ->whereYear('shift_date', $year)
            ->whereMonth('shift_date', $month)
            ->orderBy('shift_date')
            ->get();
    }

    /**
     * Get user shift statistics
     */
    public function getUserShiftStats($userId, $startDate = null, $endDate = null)
    {
        $query = EveningShift::where('admin_user_id', $userId);

        if ($startDate) {
            $query->where('shift_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('shift_date', '<=', $endDate);
        }

        return [
            'total_shifts' => $query->count(),
            'upcoming_shifts' => $query->where('shift_date', '>=', Carbon::now()->format('Y-m-d'))->count(),
            'past_shifts' => $query->where('shift_date', '<', Carbon::now()->format('Y-m-d'))->count()
        ];
    }

    /**
     * Create new shift
     */
    public function createShift($userId, $shiftDate)
    {
        try {
            // Check for conflicts
            if ($this->checkDateConflict($shiftDate)) {
                throw new \Exception('Ngày ' . Carbon::parse($shiftDate)->format('d/m/Y') . ' đã có người trực.');
            }

            $shift = EveningShift::create([
                'admin_user_id' => $userId,
                'shift_date' => $shiftDate
            ]);

            return [
                'success' => true,
                'message' => 'Tạo ca trực thành công.',
                'shift' => $shift
            ];

        } catch (\Exception $e) {
            Log::error('Error creating shift: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete shift
     */
    public function deleteShift($shiftId)
    {
        try {
            $shift = EveningShift::findOrFail($shiftId);
            $shift->delete();

            return [
                'success' => true,
                'message' => 'Xóa ca trực thành công.'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting shift: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa ca trực.'
            ];
        }
    }
}