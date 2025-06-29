{{-- resources/views/admin/widgets/online-employees.blade.php --}}
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-users text-green"></i> Nhân viên đang online
            <span class="badge bg-green">{{ $total_online }}</span>
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" onclick="window.location.reload()" data-toggle="tooltip" title="Refresh">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">

        @if($online_employees->count() > 0)
            <div class="online-list">
                
                @foreach($online_employees as $attendance)
                    @if($attendance->user)
                    <div class="employee-item">
                        <div class="employee-avatar">
                            @if($attendance->user->avatar)
                                <img src="{{ $attendance->user->avatar }}" alt="{{ $attendance->user->name }}" class="avatar-img">
                            @else
                                <img src="{{ config('admin.default_avatar') }}" alt="{{ $attendance->user->name }}" class="avatar-img">
                            @endif
                            <span class="online-dot"></span>
                        </div>
                        <div class="employee-info">
                            <strong>{{ $attendance->user->name }}</strong>
                            @if($attendance->user->username)
                                <span class="text-muted">({{ $attendance->user->username }})</span>
                            @endif
                            <br>
                            @if($is_ceo)
                                <small class="text-muted">
                                    <i class="fa fa-clock-o"></i>
                                    Vào ca: {{ $attendance->check_in_time->format('H:i:s') }}
                                    ({{ $attendance->check_in_time->diffForHumans() }})
                                </small>
                                <br>
                                <small class="text-success">
                                    <i class="fa fa-circle"></i>
                                    Đang làm việc: {{ $attendance->check_in_time->diffInHours(now()) }}h {{ $attendance->check_in_time->diffInMinutes(now()) % 60 }}p
                                </small>
                            @else
                                <small class="text-success">
                                    <i class="fa fa-circle"></i>
                                    Đang làm việc
                                </small>
                            @endif
                        </div>
                        <div class="employee-status">
                            <span class="label label-success">
                                <i class="fa fa-circle"></i> Online
                            </span>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="text-center text-muted" style="padding: 20px;">
                <i class="fa fa-user-times fa-3x"></i>
                <h4>Chưa có nhân viên nào vào ca</h4>
                <p>Hiện tại chưa có nhân viên nào chấm công vào làm việc</p>
            </div>
        @endif
    </div>
</div>

<style>
.employee-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    background: #f9f9f9;
    border-left: 3px solid #00a65a;
    border-radius: 3px;
    transition: background 0.3s ease;
}

.employee-item:hover {
    background: #f0f0f0;
}

.employee-avatar {
    position: relative;
    margin-right: 15px;
}

.avatar-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #00a65a;
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #00a65a;
    border: 2px solid white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.employee-info {
    flex: 1;
}

.employee-status {
    margin-left: 10px;
}

.online-list h5 {
    margin-bottom: 12px;
    color: #555;
}

.info-box {
    margin-bottom: 15px;
}

.box-title .badge {
    font-size: 12px;
    margin-left: 5px;
}
</style>