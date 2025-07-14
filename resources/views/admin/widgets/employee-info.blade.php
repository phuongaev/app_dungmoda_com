{{-- resources/views/admin/widgets/employee-info.blade.php --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-user text-blue"></i> Thông tin của tôi
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" onclick="window.location.reload()" data-toggle="tooltip" title="Refresh">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">
        <!-- User Basic Info -->
        <div class="user-panel" style="margin-bottom: 20px;">
            <div class="pull-left image">
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" class="img-circle" alt="User Image" style="width: 45px; height: 45px;">
                @else
                    <img src="{{ admin_asset('vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg') }}" class="img-circle" alt="User Image">
                @endif
            </div>
            <div class="pull-left info">
                <p>{{ $user->name }}</p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
            <div class="clearfix"></div>
        </div>

        <!-- Today Attendance Status -->
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Chấm công hôm nay</span>
                <span class="info-box-number">
                    @if($today_attendance)
                        @if($today_attendance->status == 'checked_in')
                            <span class="label label-success">Đã vào làm</span>
                        @elseif($today_attendance->status == 'checked_out')
                            <span class="label label-primary">Đã tan làm</span>
                        @else
                            <span class="label label-warning">Chưa hoàn thành</span>
                        @endif
                    @else
                        <span class="label label-warning">Chưa chấm công</span>
                    @endif
                </span>
                @if($today_attendance && $today_attendance->check_in_time)
                    <div class="progress">
                        <div class="progress-bar progress-bar-blue" style="width: {{ $today_attendance->status == 'checked_out' ? '100' : '50' }}%"></div>
                    </div>
                    <span class="progress-description">
                        Vào: {{ $today_attendance->check_in_time->format('H:i') }}
                        @if($today_attendance->check_out_time)
                            | Ra: {{ $today_attendance->check_out_time->format('H:i') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>

        <!-- This Week Shifts -->
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-moon-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ca trực tuần này</span>
                <span class="info-box-number">{{ $week_shifts->count() }} ca</span>
                <div class="progress">
                    <div class="progress-bar progress-bar-green" style="width: {{ min(($week_shifts->count() / 7) * 100, 100) }}%"></div>
                </div>
                <span class="progress-description">
                    {{ $week_start->format('d/m') }} - {{ $week_end->format('d/m') }}
                </span>
            </div>
        </div>

        <!-- Weekly Shifts Schedule -->
        @if($week_shifts->count() > 0)
            <div style="margin-top: 15px;">
                <strong><i class="fa fa-calendar text-blue"></i> Lịch trực tuần này:</strong>
                <div class="table-responsive" style="margin-top: 10px;">
                    <table class="table table-condensed table-striped">
                        <thead>
                            <tr>
                                <th width="30%">Ngày</th>
                                <th width="25%">Thứ</th>
                                <th width="45%">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($week_shifts as $shift)
                                <tr>
                                    <td>{{ $shift->shift_date->format('d/m/Y') }}</td>
                                    <td>{{ $shift->shift_date->translatedFormat('l') }}</td>
                                    <td>
                                        @if($shift->shift_date->isPast())
                                            <span class="label label-default">
                                                <i class="fa fa-check"></i> Đã qua
                                            </span>
                                        @elseif($shift->shift_date->isToday())
                                            <span class="label label-warning">
                                                <i class="fa fa-clock-o"></i> Hôm nay
                                            </span>
                                        @else
                                            <span class="label label-info">
                                                <i class="fa fa-calendar"></i> Sắp tới
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Monthly Statistics -->
        <div class="row" style="margin-top: 15px;">
            <div class="col-md-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ $month_shifts_count }}</h3>
                        <p>Ca trực tháng này</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-moon-o"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ $month_attendance_count }}</h3>
                        <p>Ngày làm việc</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.small-box h3 {
    font-size: 28px;
    font-weight: bold;
    margin: 0 0 10px 0;
    white-space: nowrap;
    padding: 0;
}

.table-condensed > thead > tr > th,
.table-condensed > tbody > tr > th,
.table-condensed > tfoot > tr > th,
.table-condensed > thead > tr > td,
.table-condensed > tbody > tr > td,
.table-condensed > tfoot > tr > td {
    padding: 5px;
}

.user-panel .info {
    padding-left: 10px;
}
</style>