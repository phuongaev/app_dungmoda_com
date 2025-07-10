<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Models\EveningShift;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftCalendarController extends AdminController
{
    protected $title = 'Lịch làm việc ca tối';

    public function index(Content $content)
    {
        $is_admin = Admin::user()->isRole('administrator');
        return $content
            ->title($this->title)
            ->description('Xem và quản lý lịch trực')
            ->body(view('admin.calendar', ['is_admin' => $is_admin]));
    }

    /**
     * API cung cấp dữ liệu sự kiện cho FullCalendar
     */
    public function events(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        $shifts = EveningShift::with('user')
            ->whereBetween('shift_date', [$start, $end])
            ->get();
            
        $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', '#34495e', '#1abc9c', '#e67e22', '#d35400', '#c0392b'];
        $userColors = [];
        $colorIndex = 0;

        $events = $shifts->map(function ($shift) use (&$userColors, &$colors, &$colorIndex) {
            if (!$shift->user) {
                return null;
            }

            $userId = $shift->user->id;
            $userName = $shift->user->name;

            if (!isset($userColors[$userId])) {
                $userColors[$userId] = $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }

            return [
                'id'        => $shift->id,
                'title'     => $userName,
                'start'     => $shift->shift_date,
                'color'     => $userColors[$userId],
                'allDay'    => true,
                // Truyền thêm dữ liệu tùy chỉnh
                'extendedProps' => [
                    'userId' => $userId,
                    'userName' => $userName,
                ]
            ];
        })->filter();

        return response()->json($events);
    }

    /**
     * API cập nhật lịch khi Admin kéo-thả
     */
    public function updateShift(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json(['status' => 'error', 'message' => 'Không có quyền thực hiện hành động này.'], 403);
        }

        $request->validate([
            'id' => 'required|integer|exists:evening_shifts,id',
            'date' => 'required|date',
        ]);

        $shift = EveningShift::findOrFail($request->input('id'));
        $newDate = $request->input('date');

        // Logic hoán đổi nếu ngày mới đã có người trực
        // $existingShiftsOnNewDate = EveningShift::where('shift_date', $newDate)->get();
        
        // if ($existingShiftsOnNewDate->isNotEmpty()) {
        //     // Nếu ngày mới đã có người, không cho kéo thả, yêu cầu dùng chức năng hoán đổi
        //      return response()->json([
        //         'status' => 'error', 
        //         'message' => 'Ngày ' . Carbon::parse($newDate)->format('d/m/Y') . ' đã có người trực. Vui lòng dùng chức năng "Hoán đổi".'
        //     ], 422);
        // }

        $shift->shift_date = $newDate;
        $shift->save();

        return response()->json(['status' => 'success', 'message' => 'Cập nhật ca trực thành công.']);
    }

    /**
     * API để hoán đổi hai ca trực
     */
    public function swap(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return response()->json(['status' => 'error', 'message' => 'Không có quyền.'], 403);
        }

        $validated = $request->validate([
            'source_id' => 'required|integer|exists:evening_shifts,id',
            'target_id' => 'required|integer|exists:evening_shifts,id',
        ]);

        if ($validated['source_id'] == $validated['target_id']) {
            return response()->json(['status' => 'error', 'message' => 'Không thể hoán đổi với chính nó.'], 422);
        }

        try {
            DB::transaction(function () use ($validated) {
                $sourceShift = EveningShift::findOrFail($validated['source_id']);
                $targetShift = EveningShift::findOrFail($validated['target_id']);

                $sourceUserId = $sourceShift->admin_user_id;
                $targetUserId = $targetShift->admin_user_id;

                $sourceShift->admin_user_id = $targetUserId;
                $targetShift->admin_user_id = $sourceUserId;

                $sourceShift->save();
                $targetShift->save();
            });
        } catch (\Exception $e) {
            \Log::error('Shift Swap Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lỗi server khi hoán đổi ca trực.'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'Hoán đổi ca trực thành công.']);
    }
}
