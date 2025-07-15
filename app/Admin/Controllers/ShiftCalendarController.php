<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Services\ShiftCalendarService;
use App\Http\Requests\Admin\ShiftEventsRequest;
use App\Http\Requests\Admin\UpdateShiftRequest;
use App\Http\Requests\Admin\SwapShiftRequest;
use App\Http\Requests\Admin\CreateShiftRequest;
use App\Http\Requests\Admin\GetAvailableShiftsRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShiftCalendarController extends AdminController
{
    protected $title = 'Lịch làm việc ca tối';
    protected $shiftCalendarService;

    public function __construct(ShiftCalendarService $shiftCalendarService)
    {
        $this->shiftCalendarService = $shiftCalendarService;
    }

    /**
     * Display calendar page
     */
    public function index(Content $content)
    {
        $is_admin = Admin::user()->isRole('administrator');
        
        return $content
            ->title($this->title)
            ->description('Xem và quản lý lịch trực')
            ->body(view('admin.shift-calendar.index', ['is_admin' => $is_admin]));
    }

    /**
     * API endpoint for FullCalendar events
     */
    public function events(ShiftEventsRequest $request)
    {
        try {
            $start = Carbon::parse($request->input('start'));
            $end = Carbon::parse($request->input('end'));

            $shifts = $this->shiftCalendarService->getShiftsByDateRange($start, $end);
            $events = $this->shiftCalendarService->formatShiftsForCalendar($shifts);

            return response()->json($events);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar events: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to fetch calendar data'
            ], 500);
        }
    }

    /**
     * Update shift when admin drags and drops
     */
    public function updateShift(UpdateShiftRequest $request)
    {
        $result = $this->shiftCalendarService->updateShiftDate(
            $request->input('id'),
            $request->input('date')
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 422);
    }

    /**
     * Swap two shifts
     */
    public function swap(SwapShiftRequest $request)
    {
        $result = $this->shiftCalendarService->swapShifts(
            $request->input('source_id'),
            $request->input('target_id')
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 500);
    }

    /**
     * Swap two shifts (alias for swap method to match routes)
     */
    public function swapShifts(SwapShiftRequest $request)
    {
        return $this->swap($request);
    }

    /**
     * Get available shifts for swapping
     */
    public function getAvailableShifts(GetAvailableShiftsRequest $request)
    {
        try {
            $shifts = $this->shiftCalendarService->getAvailableShiftsForSwap(
                $request->input('exclude_id')
            );

            return response()->json([
                'status' => 'success',
                'data' => $shifts
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available shifts: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách ca trực.'
            ], 500);
        }
    }

    /**
     * Create new shift
     */
    public function createShift(CreateShiftRequest $request)
    {
        $result = $this->shiftCalendarService->createShift(
            $request->input('admin_user_id'),
            $request->input('shift_date')
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['shift']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 422);
    }

    /**
     * Get shifts by specific date
     */
    public function getShiftsByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $shifts = $this->shiftCalendarService->getShiftsByDate($request->input('date'));
            $count = $this->shiftCalendarService->getShiftCountByDate($request->input('date'));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'shifts' => $shifts,
                    'count' => $count,
                    'date' => $request->input('date')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching shifts by date: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể tải thông tin ca trực.'
            ], 500);
        }
    }

    /**
     * Get available users for shift assignment
     */
    public function getAvailableUsers(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Không có quyền truy cập.'
            ], 403);
        }

        try {
            $users = $this->shiftCalendarService->getAvailableUsers();

            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available users: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách nhân viên.'
            ], 500);
        }
    }

    /**
     * Change person assigned to a shift (method name matching route)
     */
    public function changePerson(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Không có quyền thực hiện hành động này.'
            ], 403);
        }

        $request->validate([
            'shift_id' => 'required|integer|exists:evening_shifts,id',
            'new_user_id' => 'required|integer|exists:admin_users,id',
        ]);

        // Additional validation: Check if user belongs to sale_team
        $newUser = \Encore\Admin\Auth\Database\Administrator::find($request->input('new_user_id'));
        $saleTeamRole = \Encore\Admin\Auth\Database\Role::where('slug', 'sale_team')->first();
        
        if ($saleTeamRole && !$newUser->roles->contains($saleTeamRole->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nhân viên được chọn không thuộc team Sale.'
            ], 422);
        }

        $result = $this->shiftCalendarService->changePerson(
            $request->input('shift_id'),
            $request->input('new_user_id')
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 422);
    }

    /**
     * Change person assigned to a shift (original method)
     */
    public function changeShiftPerson(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Không có quyền thực hiện hành động này.'
            ], 403);
        }

        $request->validate([
            'shift_id' => 'required|integer|exists:evening_shifts,id',
            'new_user_id' => 'required|integer|exists:admin_users,id',
        ]);

        // Additional validation: Check if user belongs to sale_team
        $newUser = \Encore\Admin\Auth\Database\Administrator::find($request->input('new_user_id'));
        $saleTeamRole = \Encore\Admin\Auth\Database\Role::where('slug', 'sale_team')->first();
        
        if ($saleTeamRole && !$newUser->roles->contains($saleTeamRole->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nhân viên được chọn không thuộc team Sale.'
            ], 422);
        }

        $result = $this->shiftCalendarService->changeShiftPerson(
            $request->input('shift_id'),
            $request->input('new_user_id')
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'old_user' => $result['old_user'],
                    'new_user' => $result['new_user']
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 422);
    }

    /**
     * Delete a shift
     */
    public function deleteShift(Request $request)
    {
        try {
            $shiftId = $request->input('shift_id');
            
            if (!$shiftId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID ca trực không được cung cấp.'
                ]);
            }

            $result = $this->shiftCalendarService->deleteShift($shiftId);

            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa ca trực: ' . $e->getMessage()
            ]);
        }
    }
}