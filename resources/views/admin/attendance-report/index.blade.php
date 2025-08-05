{{-- AdminLTE Additional Styles --}}
<style>
.small-box .icon {
    position: absolute;
    top: -10px;
    right: 10px;
    z-index: 0;
    font-size: 70px;
    color: rgba(0, 0, 0, 0.15);
}
.widget-user-2 .widget-user-header {
    padding: 20px;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
}
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Filter Form -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Lọc dữ liệu</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('/admin/attendance-reports') }}" id="filter-form">
                    <div class="row">
                        <!-- Filter Type -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Loại filter:</label>
                                <select name="filter_type" class="form-control" id="filter-type">
                                    <option value="month" {{ $filters['filter_type'] == 'month' ? 'selected' : '' }}>Theo tháng</option>
                                    <option value="custom" {{ $filters['filter_type'] == 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
                                </select>
                            </div>
                        </div>

                        <!-- Month Filter -->
                        <div class="col-md-3" id="month-filter" style="{{ (isset($filters['filter_type']) && $filters['filter_type'] == 'month') ? '' : 'display:none;' }}">
                            <div class="form-group">
                                <label>Tháng:</label>
                                <input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? date('Y-m') }}">
                            </div>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="col-md-6" id="custom-filter" style="{{ (isset($filters['filter_type']) && $filters['filter_type'] == 'custom') ? '' : 'display:none;' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Từ ngày:</label>
                                        <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? date('Y-m-01') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Đến ngày:</label>
                                        <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? date('Y-m-t') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Employee Filter -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nhân viên:</label>
                                <select name="user_id" class="form-control">
                                    <option value="">-- Tất cả nhân viên --</option>
                                    @if(isset($reportData['users']) && $reportData['users'])
                                        @foreach($reportData['users'] as $user)
                                            <option value="{{ $user->id }}" {{ (isset($filters['user_id']) && $filters['user_id'] == $user->id) ? 'selected' : '' }}>
                                                {{ $user->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- Role Filter -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Chức vụ:</label>
                                <select name="role_id" class="form-control">
                                    <option value="">-- Tất cả chức vụ --</option>
                                    @if(isset($reportData['roles']) && $reportData['roles'])
                                        @foreach($reportData['roles'] as $role)
                                            <option value="{{ $role->id }}" {{ (isset($filters['role_id']) && $filters['role_id'] == $role->id) ? 'selected' : '' }}>
                                                {{ $role->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Lọc dữ liệu
                                </button>
                                <a href="{{ url('/admin/attendance-reports') }}" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3>{{ $reportData['overview']['total_employees'] ?? 0 }}</h3>
                        <p>Tổng nhân viên</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ $reportData['overview']['total_work_time'] ?? '0h' }}</h3>
                        <p>Tổng giờ làm việc</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ $reportData['overview']['total_check_ins'] ?? 0 }}</h3>
                        <p>Tổng lượt chấm công</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ $reportData['overview']['total_work_days'] ?? 0 }}</h3>
                        <p>Tổng ngày làm việc</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Employees -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-trophy"></i> Bảng thi đua Top 3 nhân viên</h3>
                <div class="box-tools pull-right">
                    <span class="label label-success">
                        {{ isset($reportData['period']['start']) ? $reportData['period']['start']->format('d/m/Y') : '' }} - 
                        {{ isset($reportData['period']['end']) ? $reportData['period']['end']->format('d/m/Y') : '' }}
                    </span>
                </div>
            </div>
            <div class="box-body">
                @if(isset($reportData['top_employees']) && count($reportData['top_employees']) > 0)
                    <div class="row">
                        @foreach($reportData['top_employees'] as $index => $employee)
                            @if(isset($employee['user']) && $employee['user'])
                            <div class="col-md-4">
                                <div class="box box-widget widget-user-2">
                                    <div class="widget-user-header bg-{{ $index == 0 ? 'yellow' : ($index == 1 ? 'gray' : 'red') }}">
                                        <div class="widget-user-image">
                                            @if($index == 0)
                                                <i class="fa fa-trophy fa-2x"></i>
                                            @elseif($index == 1)
                                                <i class="fa fa-medal fa-2x"></i>
                                            @else
                                                <i class="fa fa-award fa-2x"></i>
                                            @endif
                                        </div>
                                        <h3 class="widget-user-username">TOP {{ $index + 1 }}</h3>
                                        <h5 class="widget-user-desc">{{ $employee['user']->name ?? 'N/A' }}</h5>
                                    </div>
                                    <div class="box-footer no-padding">
                                        <ul class="nav nav-stacked">
                                            <li><a href="#">Chức vụ <span class="pull-right badge bg-blue">{{ $employee['roles'] ?? 'N/A' }}</span></a></li>
                                            <li><a href="#">Tổng giờ làm <span class="pull-right badge bg-green">{{ $employee['total_work_time'] ?? '0h' }}</span></a></li>
                                            <li><a href="#">Số ngày làm <span class="pull-right badge bg-yellow">{{ $employee['work_days'] ?? 0 }} ngày</span></a></li>
                                            <li><a href="#">Tổng lần chấm công <span class="pull-right badge bg-aqua">{{ $employee['total_check_ins'] ?? 0 }} lần</span></a></li>
                                            <li><a href="#">TB giờ/ngày <span class="pull-right badge bg-purple">{{ number_format($employee['avg_hours_per_day'] ?? 0, 1) }}h</span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Không có dữ liệu chấm công trong khoảng thời gian này.
                    </div>
                @endif
            </div>
        </div>

        <!-- Detailed Employee Stats -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-table"></i> Chi tiết chấm công từng nhân viên</h3>
            </div>
            <div class="box-body">
                @if(isset($reportData['employee_stats']) && count($reportData['employee_stats']) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nhân viên</th>
                                    <th>Chức vụ</th>
                                    <th>Tổng giờ làm</th>
                                    <th>Số ngày làm</th>
                                    <th>Tổng lần chấm công</th>
                                    <th>Ngày hoàn thành</th>
                                    <th>Ngày nghỉ</th>
                                    <th>Tỷ lệ attendance</th>
                                    <th>TB giờ/ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['employee_stats'] as $index => $employee)
                                    @if(isset($employee['user']) && $employee['user'])
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $employee['user']->name ?? 'N/A' }}</strong>
                                            @if($index < 3)
                                                <span class="label label-warning">TOP {{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $employee['roles'] ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-green">{{ $employee['total_work_time'] ?? '0h' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <strong>{{ $employee['work_days'] ?? 0 }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-blue">{{ $employee['total_check_ins'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">{{ $employee['complete_days'] ?? 0 }}</td>
                                        <td class="text-center">
                                            @php
                                                $absentDays = $employee['absent_days'] ?? 0;
                                            @endphp
                                            @if($absentDays > 0)
                                                <span class="badge bg-red">{{ $absentDays }}</span>
                                            @else
                                                <span class="badge bg-green">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $attendanceRate = $employee['attendance_rate'] ?? 0;
                                            @endphp
                                            @if($attendanceRate >= 90)
                                                <span class="badge bg-green">{{ $attendanceRate }}%</span>
                                            @elseif($attendanceRate >= 70)
                                                <span class="badge bg-yellow">{{ $attendanceRate }}%</span>
                                            @else
                                                <span class="badge bg-red">{{ $attendanceRate }}%</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($employee['avg_hours_per_day'] ?? 0, 1) }}h</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fa fa-warning"></i> Không có dữ liệu chấm công nào để hiển thị.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filter-type');
    const monthFilter = document.getElementById('month-filter');
    const customFilter = document.getElementById('custom-filter');

    filterType.addEventListener('change', function() {
        if (this.value === 'month') {
            monthFilter.style.display = '';
            customFilter.style.display = 'none';
        } else {
            monthFilter.style.display = 'none';
            customFilter.style.display = '';
        }
    });
});
</script>