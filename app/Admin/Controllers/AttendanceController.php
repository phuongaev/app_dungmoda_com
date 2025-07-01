<?php

namespace App\Admin\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

use Encore\Admin\Auth\Database\Administrator;
use App\Services\AttendanceWebhookService; // Import webhook service

class AttendanceController extends Controller
{
    use HasResourceActions;

    protected $webhookService;

    public function __construct(AttendanceWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('Chấm công'))
            ->description(trans('Hệ thống chấm công'))
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Attendance());
        $grid->model()->orderBy('work_date', 'desc')->orderBy('check_in_time', 'desc');

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Nhân viên'))->filter('like');
        
        $grid->column('work_date', __('Ngày làm việc'))
            ->display(function ($value) {
                return $value ? date('d/m/Y', strtotime($value)) : '';
            })
            ->filter('date')
            ->sortable();

        $grid->column('check_in_time', __('Giờ vào'))
            ->display(function ($value) {
                return $value ? date('H:i:s', strtotime($value)) : '';
            })
            ->sortable();

        $grid->column('check_out_time', __('Giờ ra'))
            ->display(function ($value) {
                return $value ? date('H:i:s', strtotime($value)) : 'Chưa ra';
            })
            ->sortable();

        $grid->column('total_work_time', __('Thời gian làm'))
            ->display(function () {
                // Nếu đã có giá trị work_hours/work_minutes được lưu
                if ($this->work_hours || $this->work_minutes) {
                    return sprintf('%02d:%02d', $this->work_hours, $this->work_minutes);
                }
                
                // Tính toán trực tiếp từ check_in_time và check_out_time
                if ($this->check_in_time && $this->check_out_time) {
                    $checkIn = is_string($this->check_in_time) 
                        ? \Carbon\Carbon::parse($this->check_in_time) 
                        : $this->check_in_time;
                        
                    $checkOut = is_string($this->check_out_time) 
                        ? \Carbon\Carbon::parse($this->check_out_time) 
                        : $this->check_out_time;
                        
                    $diff = $checkOut->diff($checkIn);
                    $hours = $diff->h + ($diff->days * 24);
                    $minutes = $diff->i;
                    
                    return sprintf('%02d:%02d', $hours, $minutes);
                }
                
                // Nếu đang trong ca (chưa checkout)
                if ($this->check_in_time && !$this->check_out_time) {
                    $checkIn = is_string($this->check_in_time) 
                        ? \Carbon\Carbon::parse($this->check_in_time) 
                        : $this->check_in_time;
                        
                    $diff = now()->diff($checkIn);
                    $hours = $diff->h + ($diff->days * 24);
                    $minutes = $diff->i;
                    
                    return sprintf('<span class="label label-warning">%02d:%02d (đang làm)</span>', $hours, $minutes);
                }
                
                return '<span class="text-muted">--:--</span>';
            })
            ->sortable();
            
        $grid->column('status', __('Trạng thái'))->label([
            'checked_in' => 'success',
            'checked_out' => 'primary',
            'incomplete' => 'warning'
        ]);

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
        $show = new Show(Attendance::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('user.name', __('Nhân viên'));
        $show->field('work_date', __('Ngày làm việc'));
        $show->field('check_in_time', __('Giờ vào'));
        $show->field('check_out_time', __('Giờ ra'));
        $show->field('check_in_ip', __('IP vào'));
        $show->field('check_out_ip', __('IP ra'));
        $show->field('work_hours', __('Số giờ làm'));
        $show->field('work_minutes', __('Số phút làm'));
        $show->field('status', __('Trạng thái'));
        $show->field('notes', __('Ghi chú'));
        $show->field('created_at', __('Tạo lúc'));
        $show->field('updated_at', __('Cập nhật lúc'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Attendance());

        $form->select('user_id', __('Nhân viên'))
            ->options(Administrator::all()->pluck('name', 'id'))
            ->required();

        $form->date('work_date', __('Ngày làm việc'))->default(date('Y-m-d'));
        $form->datetime('check_in_time', __('Giờ vào'));
        $form->datetime('check_out_time', __('Giờ ra'));
        $form->text('check_in_ip', __('IP vào'));
        $form->text('check_out_ip', __('IP ra'));
        $form->number('work_hours', __('Số giờ làm'))->default(0);
        $form->number('work_minutes', __('Số phút làm'))->default(0);
        $form->select('status', __('Trạng thái'))->options([
            'checked_in' => 'Đã vào',
            'checked_out' => 'Đã ra',
            'incomplete' => 'Chưa hoàn thành'
        ])->default('incomplete');
        $form->textarea('notes', __('Ghi chú'));

        // Auto calculate work time when saving
        $form->saving(function (Form $form) {
            // Lấy dữ liệu từ form
            $checkInTime = $form->check_in_time;
            $checkOutTime = $form->check_out_time;
            
            // Nếu có cả giờ vào và giờ ra, tính thời gian làm việc
            if ($checkInTime && $checkOutTime) {
                $checkIn = \Carbon\Carbon::parse($checkInTime);
                $checkOut = \Carbon\Carbon::parse($checkOutTime);
                
                $diff = $checkOut->diff($checkIn);
                $form->work_hours = $diff->h + ($diff->days * 24);
                $form->work_minutes = $diff->i;
                
                // Cập nhật status nếu có checkout
                $form->status = 'checked_out';
            } elseif ($checkInTime && !$checkOutTime) {
                // Nếu chỉ có check in, set status là checked_in
                $form->status = 'checked_in';
            }
        });

        return $form;
    }

    // API endpoint cho chấm công vào
    public function checkIn()
    {
        $userId = \Admin::user()->id;
        $today = today();
        $ip = request()->ip();

        // Tìm session hiện tại (chưa checkout)
        $currentSession = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->whereNull('check_out_time')
            ->first();

        if ($currentSession) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đang trong ca làm việc! Vui lòng chấm công ra trước khi vào ca mới.'
            ]);
        }

        // Tạo session mới
        $attendance = Attendance::create([
            'user_id' => $userId,
            'work_date' => $today,
            'check_in_time' => now(),
            'check_in_ip' => $ip,
            'status' => 'checked_in'
        ]);

        // Gửi webhook cho check-in
        if (config('attendance.webhook_enabled', true)) {
            $this->webhookService->sendCheckInWebhook($attendance);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chấm công vào thành công!',
            'time' => now()->format('H:i:s'),
            'session_id' => $attendance->id
        ]);
    }

    // API endpoint cho chấm công ra
    public function checkOut()
    {
        $userId = \Admin::user()->id;
        $today = today();
        $ip = request()->ip();

        // Tìm session hiện tại (chưa checkout)
        $currentSession = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->whereNull('check_out_time')
            ->orderBy('check_in_time', 'desc')
            ->first();

        if (!$currentSession) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa chấm công vào hoặc đã kết thúc tất cả ca làm việc!'
            ]);
        }

        // Update check out cho session hiện tại
        $currentSession->update([
            'check_out_time' => now(),
            'check_out_ip' => $ip
        ]);

        // Calculate work time
        $currentSession->calculateWorkTime();

        // Gửi webhook cho check-out
        if (config('attendance.webhook_enabled', true)) {
            $this->webhookService->sendCheckOutWebhook($currentSession);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chấm công ra thành công!',
            'time' => now()->format('H:i:s'),
            'work_time' => $currentSession->total_work_time,
            'session_id' => $currentSession->id
        ]);
    }

    // Lấy thông tin chấm công hôm nay
    public function todayStatus()
    {
        $userId = \Admin::user()->id;
        $today = today();

        // Lấy tất cả sessions hôm nay
        $allSessions = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->orderBy('check_in_time', 'desc')
            ->get();

        // Tìm session hiện tại (chưa checkout)
        $currentSession = $allSessions->whereNull('check_out_time')->first();

        // Tính tổng thời gian làm việc trong ngày
        $totalMinutes = $allSessions->where('status', 'checked_out')->sum(function($session) {
            return ($session->work_hours * 60) + $session->work_minutes;
        });

        $totalHours = intval($totalMinutes / 60);
        $totalMins = $totalMinutes % 60;
        $totalWorkTime = sprintf('%02d:%02d', $totalHours, $totalMins);

        return response()->json([
            'current_session' => $currentSession,
            'all_sessions' => $allSessions,
            'total_sessions' => $allSessions->count(),
            'completed_sessions' => $allSessions->where('status', 'checked_out')->count(),
            'total_work_time' => $totalWorkTime,
            'can_check_in' => !$currentSession, // Có thể vào nếu không có session đang mở
            'can_check_out' => !!$currentSession // Có thể ra nếu có session đang mở
        ]);
    }

    // Lịch sử chấm công hôm nay
    public function todayHistory()
    {
        $userId = \Admin::user()->id;
        $today = today();

        $sessions = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->map(function($session, $index) {
                return [
                    'session' => $index + 1,
                    'check_in' => $session->check_in_time ? $session->check_in_time->format('H:i:s') : null,
                    'check_out' => $session->check_out_time ? $session->check_out_time->format('H:i:s') : 'Đang làm việc',
                    'work_time' => $session->total_work_time,
                    'status' => $session->status_label
                ];
            });

        return response()->json([
            'sessions' => $sessions,
            'total_sessions' => $sessions->count()
        ]);
    }

}