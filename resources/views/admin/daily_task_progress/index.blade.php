{{-- resources/views/admin/daily_task_progress/index.blade.php --}}

<!-- Thống kê tổng quan -->
<div class="row">

    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-cogs"></i> Thao tác nhanh</h3>
            </div>
            <div class="box-body">
                <a href="{{ admin_url('daily-task-progress/daily') }}" class="btn btn-primary">
                    <i class="fa fa-calendar"></i> Xem theo ngày
                </a>
                <a href="{{ admin_url('daily-tasks') }}" class="btn btn-default">
                    <i class="fa fa-tasks"></i> Quản lý công việc
                </a>
                <button type="button" class="btn btn-info" onclick="window.location.reload()">
                    <i class="fa fa-refresh"></i> Làm mới
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng nhân viên</span>
                <span class="info-box-number">{{ $totalUsers }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Hoàn thành 100%</span>
                <span class="info-box-number">{{ $totalCompletedUsers }}</span>
                <span class="info-box-more">({{ $userCompletionRate }}% nhân viên)</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-tasks"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Công việc hoàn thành</span>
                <span class="info-box-number">{{ $totalTasksCompleted }}/{{ $totalTasksAssigned }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box">
            <span class="info-box-icon 
                @if($overallCompletionRate >= 80) bg-green
                @elseif($overallCompletionRate >= 50) bg-yellow
                @else bg-red
                @endif">
                <i class="fa fa-percent"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Tỷ lệ hoàn thành</span>
                <span class="info-box-number">{{ $overallCompletionRate }}%</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tiến độ nhân viên -->
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-users"></i> Tiến độ nhân viên hôm nay
                    <small>({{ $today->format('d/m/Y') }})</small>
                </h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Vai trò</th>
                            <th>Tiến độ</th>
                            <th>Khẩn cấp</th>
                            <th>Hoạt động cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userProgressData as $userData)
                            <tr>
                                <td>
                                    <strong>{{ $userData['user']->name }}</strong><br>
                                    <small class="text-muted">{{ $userData['user']->username }}</small>
                                </td>
                                <td>
                                    @if($userData['roles'])
                                        @foreach(explode(', ', $userData['roles']) as $role)
                                            <span class="label label-default">{{ $role }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $rate = $userData['completion_rate'];
                                        $colorClass = $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');
                                    @endphp
                                    <div class="progress progress-sm" style="margin-bottom: 5px;">
                                        <div class="progress-bar progress-bar-{{ $colorClass }}" 
                                             style="width: {{ $rate }}%"></div>
                                    </div>
                                    <small>{{ $userData['completed_tasks'] }}/{{ $userData['total_tasks'] }} ({{ $rate }}%)</small>
                                </td>
                                <td>
                                    @if($userData['urgent_total'] > 0)
                                        @php
                                            $urgentRate = $userData['urgent_total'] > 0 ? round(($userData['urgent_completed'] / $userData['urgent_total']) * 100) : 0;
                                            $urgentColor = $urgentRate == 100 ? 'success' : 'danger';
                                        @endphp
                                        <span class="label label-{{ $urgentColor }}">
                                            {{ $userData['urgent_completed'] }}/{{ $userData['urgent_total'] }} ({{ $urgentRate }}%)
                                        </span>
                                    @else
                                        <span class="text-muted">Không có</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $userData['last_activity'] ?: '-' }}
                                </td>
                                <td>
                                    <a href="{{ admin_url('daily-task-progress/' . $userData['user']->id . '/detail') }}" 
                                       class="btn btn-xs btn-info">
                                        <i class="fa fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                                    <i class="fa fa-info-circle"></i> Chưa có dữ liệu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Hoạt động gần đây -->
    <div class="col-md-4">

        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-clock-o"></i> Hoạt động gần đây</h3>
            </div>
            <div class="box-body">
                @forelse($recentCompletions as $completion)
                    <div class="activity-item" style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <div style="flex: 1;">
                                <strong>{{ $completion->user->name }}</strong>
                                <br>
                                <small>{{ $completion->dailyTask->title }}</small>
                            </div>
                            <div style="text-align: right;">
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'skipped' => 'warning',
                                        'failed' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'completed' => 'Hoàn thành',
                                        'skipped' => 'Bỏ qua',
                                        'failed' => 'Thất bại'
                                    ];
                                @endphp
                                <span class="label label-{{ $statusColors[$completion->status] ?? 'default' }}">
                                    {{ $statusLabels[$completion->status] ?? $completion->status }}
                                </span>
                                <br>
                                <small class="text-muted">
                                    {{ $completion->completed_at_time ? \Carbon\Carbon::parse($completion->completed_at_time)->format('H:i') : '-' }}
                                </small>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center" style="padding: 20px;">
                        <i class="fa fa-info-circle"></i> Chưa có hoạt động nào
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.info-box-more {
    color: #999;
    font-size: 11px;
}

.progress-sm {
    height: 10px;
}

.activity-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
</style>