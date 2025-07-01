<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-clock-o"></i> Chấm công hôm nay
        </h3>
        <div class="box-tools pull-right">
            <span class="label label-info">{{ $allSessions->count() }} ca làm việc</span>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-green">
                        <i class="fa fa-sign-in"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ca hiện tại</span>
                        <span class="info-box-number" id="current-session">
                            @if($currentSession)
                                <small>Vào: {{ $currentSession->check_in_time->format('H:i:s') }}</small>
                            @else
                                <small>Chưa vào ca</small>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-blue">
                        <i class="fa fa-clock-o"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tổng thời gian</span>
                        <span class="info-box-number" id="total-work-time">
                            {{ $totalWorkTime }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow">
                        <i class="fa fa-list"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ca hoàn thành</span>
                        <span class="info-box-number" id="completed-sessions">
                            {{ $allSessions->where('status', 'checked_out')->count() }}/{{ $allSessions->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lịch sử ca làm việc hôm nay -->
        @if($allSessions->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <h5>Lịch sử ca làm việc hôm nay:</h5>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Ca</th>
                                <th>Giờ vào</th>
                                <th>Giờ ra</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="sessions-history">
                            @foreach($allSessions->reverse() as $index => $session)
                            <tr class="{{ !$session->check_out_time ? 'warning' : 'success' }}">
                                <td>Ca {{ $index + 1 }}</td>
                                <td>{{ $session->check_in_time ? $session->check_in_time->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if($session->check_out_time)
                                        {{ $session->check_out_time->format('H:i:s') }}
                                    @else
                                        <span class="text-warning">Đang làm việc</span>
                                    @endif
                                </td>
                                <td>{{ $session->total_work_time }}</td>
                                <td>
                                    <span class="label label-{{ $session->status == 'checked_out' ? 'success' : 'warning' }}">
                                        {{ $session->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Buttons chấm công -->
        <div class="row">
            <div class="col-md-6">
                <button type="button" class="btn btn-success btn-block btn-lg" id="checkin-btn" 
                        {{ !$canCheckIn ? 'disabled' : '' }}>
                    <i class="fa fa-sign-in"></i> 
                    @if($currentSession)
                        Đang trong ca làm việc
                    @else
                        Chấm công vào ca mới
                    @endif
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-danger btn-block btn-lg" id="checkout-btn"
                        {{ !$canCheckOut ? 'disabled' : '' }}>
                    <i class="fa fa-sign-out"></i> 
                    @if($currentSession)
                        Chấm công ra
                    @else
                        Chưa có ca để kết thúc
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    // Check in
    $('#checkin-btn').click(function() {
        $.post('/admin/attendance/check-in', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Check out
    $('#checkout-btn').click(function() {
        $.post('/admin/attendance/check-out', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(response) {
            if (response.success) {
                toastr.success(response.message + ' - Thời gian làm: ' + response.work_time);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Auto refresh every 60 seconds
    setInterval(function() {
        $.get('/admin/attendance/today-status', function(data) {
            // Update buttons
            $('#checkin-btn').prop('disabled', !data.can_check_in);
            $('#checkout-btn').prop('disabled', !data.can_check_out);
            
            // Update total work time
            $('#total-work-time').text(data.total_work_time);
            
            // Update completed sessions count
            $('#completed-sessions').text(data.completed_sessions + '/' + data.total_sessions);
            
            // Update current session info
            if (data.current_session) {
                const checkInTime = new Date(data.current_session.check_in_time).toLocaleTimeString();
                $('#current-session').html('<small>Vào: ' + checkInTime + '</small>');
            } else {
                $('#current-session').html('<small>Chưa vào ca</small>');
            }
        });
    }, 60000);
});
</script>