{{-- resources/views/admin/widgets/online-employees.blade.php --}}
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-users text-green"></i> Nhân viên đang Online
            <span class="badge bg-green">{{ $total_online }}</span>
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" onclick="window.location.reload()" data-toggle="tooltip" title="Refresh">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Nhân viên đang làm việc</span>
                <span class="info-box-number">{{ $total_online }}/{{ $total_employees }}</span>
                <div class="progress">
                    <div class="progress-bar bg-green" style="width: {{ $total_employees > 0 ? ($total_online / $total_employees * 100) : 0 }}%"></div>
                </div>
                <span class="progress-description">
                    {{ $total_employees > 0 ? round($total_online / $total_employees * 100, 1) : 0 }}% nhân viên đã vào ca
                </span>
            </div>
        </div>

        @if($online_employees->count() > 0)
            <div class="online-list">
                <h5><i class="fa fa-list"></i> Danh sách nhân viên đang online:</h5>
                
                @foreach($online_employees as $attendance)
                    @if($attendance->user)
                    <div class="employee-item">
                        <div class="employee-avatar">
                            <i class="fa fa-user-circle text-green"></i>
                        </div>
                        <div class="employee-info">
                            <strong>{{ $attendance->user->name }}</strong>
                            <br>
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
    margin-right: 12px;
    font-size: 24px;
}

.employee-info {
    flex: 1;
}

.employee-status {
    margin-left: 10px;
}

.online-list {
    margin-top: 15px;
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