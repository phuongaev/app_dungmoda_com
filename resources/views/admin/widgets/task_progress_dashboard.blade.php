{{-- resources/views/admin/widgets/task_progress_dashboard.blade.php --}}

<div class="row">
    <!-- Thống kê tổng quan -->
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-dashboard"></i> Tổng quan tiến độ hôm nay
                    <small>({{ $today->format('d/m/Y') }})</small>
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ admin_url('daily-task-progress') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> Xem chi tiết
                    </a>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng nhân viên</span>
                                <span class="info-box-number">{{ $totalUsers }}</span>
                                <span class="info-box-more">{{ $totalActiveTasks }} công việc hoạt động</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Hoàn thành hôm nay</span>
                                <span class="info-box-number">{{ $todayCompletions }}</span>
                                <span class="info-box-more">/ {{ $todayAssignedTasks }} được giao</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon 
                                @if($todayCompletionRate >= 80) bg-green
                                @elseif($todayCompletionRate >= 50) bg-yellow
                                @else bg-red
                                @endif">
                                <i class="fa fa-percent"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tỷ lệ hoàn thành</span>
                                <span class="info-box-number">{{ $todayCompletionRate }}%</span>
                                <span class="info-box-more">
                                    @if($todayCompletionRate >= 80)
                                        Xuất sắc
                                    @elseif($todayCompletionRate >= 50)
                                        Khá tốt
                                    @else
                                        Cần cải thiện
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-purple"><i class="fa fa-clock-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Cập nhật lúc</span>
                                <span class="info-box-number">{{ now()->format('H:i') }}</span>
                                <span class="info-box-more">{{ now()->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Xu hướng 7 ngày -->
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-line-chart"></i> Xu hướng 7 ngày qua</h3>
            </div>
            <div class="box-body">
                <div class="chart-container" style="height: 200px;">
                    <canvas id="weekly-trend-chart" style="height: 200px;"></canvas>
                </div>
                <div class="trend-details" style="margin-top: 15px;">
                    @foreach($weeklyTrend as $day)
                        <div class="trend-day" style="display: inline-block; margin: 5px; text-align: center;">
                            <div style="font-size: 12px; color: #666;">{{ $day['date'] }}</div>
                            <div class="progress progress-xs" style="width: 60px; margin: 2px auto;">
                                <div class="progress-bar 
                                    @if($day['completion_rate'] >= 80) progress-bar-green
                                    @elseif($day['completion_rate'] >= 50) progress-bar-yellow  
                                    @else progress-bar-red
                                    @endif" 
                                    style="width: {{ $day['completion_rate'] }}%">
                                </div>
                            </div>
                            <div style="font-size: 11px;">{{ $day['completion_rate'] }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thống kê theo độ ưu tiên -->
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Theo độ ưu tiên</h3>
            </div>
            <div class="box-body">
                @foreach($priorityStats as $priority => $stats)
                    @php
                        $priorityLabels = [
                            'urgent' => ['Khẩn cấp', 'danger'],
                            'high' => ['Cao', 'warning'], 
                            'medium' => ['Trung bình', 'info'],
                            'low' => ['Thấp', 'success']
                        ];
                        $label = $priorityLabels[$priority][0];
                        $color = $priorityLabels[$priority][1];
                    @endphp
                    
                    <div class="priority-stat" style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span class="label label-{{ $color }}">{{ $label }}</span>
                            <span style="font-size: 12px;">
                                {{ $stats['completed'] }}/{{ $stats['total'] }} ({{ $stats['rate'] }}%)
                            </span>
                        </div>
                        <div class="progress progress-xs">
                            <div class="progress-bar progress-bar-{{ $color }}" 
                                 style="width: {{ $stats['rate'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top performers -->
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-trophy"></i> Top performers hôm nay</h3>
            </div>
            <div class="box-body">
                @if($topPerformers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <tbody>
                                @foreach($topPerformers as $index => $performer)
                                    <tr>
                                        <td width="30">
                                            @if($index == 0)
                                                <i class="fa fa-trophy text-yellow"></i>
                                            @elseif($index == 1)
                                                <i class="fa fa-medal text-gray"></i>
                                            @elseif($index == 2)
                                                <i class="fa fa-certificate text-orange"></i>
                                            @else
                                                <span class="badge">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $performer['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ $performer['completed_tasks'] }}/{{ $performer['total_tasks'] }} công việc
                                            </small>
                                        </td>
                                        <td width="80" style="text-align: right;">
                                            <span class="badge 
                                                @if($performer['completion_rate'] == 100) bg-green
                                                @elseif($performer['completion_rate'] >= 80) bg-yellow
                                                @else bg-blue
                                                @endif">
                                                {{ $performer['completion_rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center" style="padding: 20px;">
                        <i class="fa fa-info-circle"></i> Chưa có dữ liệu hoàn thành hôm nay
                    </p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Users chưa hoàn thành -->
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-circle"></i> Cần theo dõi</h3>
            </div>
            <div class="box-body">
                @if($incompleteUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <tbody>
                                @foreach($incompleteUsers as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ $user['pending_tasks'] }} công việc chưa hoàn thành
                                            </small>
                                        </td>
                                        <td width="100" style="text-align: right;">
                                            <div class="progress progress-xs" style="margin: 0;">
                                                <div class="progress-bar 
                                                    @if($user['completion_rate'] >= 50) progress-bar-yellow
                                                    @else progress-bar-red
                                                    @endif" 
                                                    style="width: {{ $user['completion_rate'] }}%">
                                                </div>
                                            </div>
                                            <small>{{ $user['completion_rate'] }}%</small>
                                        </td>
                                        <td width="80">
                                            <a href="{{ admin_url('daily-task-progress/' . $user['name'] . '/detail') }}" 
                                               class="btn btn-xs btn-primary">
                                                <i class="fa fa-eye"></i> Xem
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center" style="padding: 20px;">
                        <i class="fa fa-check-circle"></i> Tất cả nhân viên đã hoàn thành công việc!
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.trend-day {
    min-width: 70px;
}

.priority-stat {
    padding: 5px 0;
}

.info-box-more {
    color: #999;
    font-size: 11px;
}

.progress-bar-green {
    background-color: #00a65a !important;
}

.progress-bar-yellow {
    background-color: #f39c12 !important;
}

.progress-bar-red {
    background-color: #dd4b39 !important;
}

.chart-container {
    position: relative;
}

.table-condensed td {
    padding: 8px 5px;
    vertical-align: middle;
}

.badge {
    font-size: 11px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart cho xu hướng 7 ngày (nếu có Chart.js)
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('weekly-trend-chart');
        if (ctx) {
            var weeklyData = @json($weeklyTrend);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: weeklyData.map(item => item.date),
                    datasets: [{
                        label: 'Tỷ lệ hoàn thành (%)',
                        data: weeklyData.map(item => item.completion_rate),
                        borderColor: '#3c8dbc',
                        backgroundColor: 'rgba(60, 141, 188, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var dayData = weeklyData[context.dataIndex];
                                    return dayData.completed + '/' + dayData.total + ' (' + dayData.completion_rate + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>