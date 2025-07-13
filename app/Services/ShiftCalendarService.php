<?php

namespace App\Services;

use App\Models\EveningShift;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Role;
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
     * Static user colors cache to persist across requests
     */
    private static $userColorsCache = [];
    private static $colorIndex = 0;

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
        return $shifts->map(function ($shift) {
            if (!$shift->user) {
                return null;
            }

            $userId = $shift->user->id;
            $userName = $shift->user->name;

            // Assign color to user if not already assigned
            if (!isset(self::$userColorsCache[$userId])) {
                self::$userColorsCache[$userId] = self::USER_COLORS[self::$colorIndex % count(self::USER_COLORS)];
                self::$colorIndex++;
            }

            return [
                'id' => $shift->id,
                'title' => $userName,
                'start' => $shift->shift_date,
                'color' => self::$userColorsCache[$userId],
                'allDay' => true,
                'extendedProps' => [
                    'userId' => $userId,
                    'userName' => $userName,
                    'userColor' => self::$userColorsCache[$userId], // Add explicit color to extendedProps
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
            
            // Bỏ check conflict vì cho phép nhiều người cùng ngày
            // $conflict = $this->checkDateConflict($newDate, $shiftId);
            // if ($conflict) {
            //     throw new \Exception('Ngày ' . Carbon::parse($newDate)->format('d/m/Y') . ' đã có người trực.');
            // }

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
                'text' => $shift->user->name . ' - ' . Carbon::parse($shift->shift_date)->format('d/m/Y'),
                'date' => $shift->shift_date,
                'user_name' => $shift->user->name
            ];
        });
    }

    /**
     * Get count of people working on specific date
     */
    public function getShiftCountByDate($date)
    {
        return EveningShift::where('shift_date', $date)->count();
    }

    /**
     * Get all people working on specific date
     */
    public function getShiftsByDate($date)
    {
        return EveningShift::with('user')
            ->where('shift_date', $date)
            ->get();
    }

    /**
     * Check if date has existing shift (for reporting/statistics purposes)
     * Note: Not used for conflict checking since multiple people can work on same day
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
     * Get user colors mapping for legend
     */
    public function getUserColorsMapping()
    {
        return self::$userColorsCache;
    }

    /**
     * Clear user colors cache (useful for testing or resetting)
     */
    public function clearUserColorsCache()
    {
        self::$userColorsCache = [];
        self::$colorIndex = 0;
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
            // Bỏ check conflict vì cho phép nhiều người cùng ngày
            // if ($this->checkDateConflict($shiftDate)) {
            //     throw new \Exception('Ngày ' . Carbon::parse($shiftDate)->format('d/m/Y') . ' đã có người trực.');
            // }

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

    /**
     * Get all available users for shift assignment (only sale_team role)
     */
    public function getAvailableUsers()
    {
        try {
            // Get sale_team role
            $saleTeamRole = Role::where('slug', 'sale_team')->first();
            
            if (!$saleTeamRole) {
                Log::warning('Sale team role not found');
                return collect([]);
            }

            // Get users with sale_team role
            $users = $saleTeamRole->administrators()
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username
                    ];
                });
            
            return $users;

        } catch (\Exception $e) {
            Log::error('Error loading available users: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Change the assigned person for a shift
     */
    public function changeShiftPerson($shiftId, $newUserId)
    {
        try {
            DB::beginTransaction();

            $shift = EveningShift::findOrFail($shiftId);
            $oldUserId = $shift->admin_user_id;
            
            // Get user names for logging
            $oldUser = Administrator::find($oldUserId);
            $newUser = Administrator::find($newUserId);
            
            if (!$newUser) {
                throw new \Exception('Nhân viên được chọn không tồn tại.');
            }

            // Check if new user has sale_team role
            $saleTeamRole = Role::where('slug', 'sale_team')->first();
            if ($saleTeamRole && !$newUser->roles->contains($saleTeamRole->id)) {
                throw new \Exception('Nhân viên được chọn không thuộc team Sale.');
            }

            $shift->admin_user_id = $newUserId;
            $shift->save();

            DB::commit();

            // Log the change
            Log::info('Shift person changed', [
                'shift_id' => $shiftId,
                'date' => $shift->shift_date,
                'old_user' => $oldUser ? $oldUser->name : 'Unknown',
                'new_user' => $newUser->name
            ]);

            return [
                'success' => true,
                'message' => 'Đã thay đổi người trực thành công.',
                'old_user' => $oldUser ? $oldUser->name : 'Unknown',
                'new_user' => $newUser->name
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing shift person: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Debug: Get current color cache state
     */
    public function debugColorCache()
    {
        return [
            'user_colors' => self::$userColorsCache,
            'color_index' => self::$colorIndex,
            'total_users' => count(self::$userColorsCache)
        ];
    }

    /**
     * Debug: Check sale team role setup
     */
    public function debugSaleTeamRole()
    {
        try {
            $saleTeamRole = Role::where('slug', 'sale_team')->first();
            
            if (!$saleTeamRole) {
                return [
                    'status' => 'error',
                    'message' => 'Sale team role not found',
                    'available_roles' => Role::pluck('name', 'slug')->toArray()
                ];
            }

            $users = $saleTeamRole->administrators()->get(['id', 'name', 'username']);

            return [
                'status' => 'success',
                'role_id' => $saleTeamRole->id,
                'role_name' => $saleTeamRole->name,
                'role_slug' => $saleTeamRole->slug,
                'users_count' => $users->count(),
                'users' => $users->toArray()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}