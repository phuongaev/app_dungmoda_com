{{-- resources/views/admin/daily-task-progress/user_detail.blade.php --}}

<div id="app">
    <section class="content-header">
        <h1>
            Chi tiết tiến độ: {{ $user->name }}
            <small>Thống kê chi tiết công việc ngày {{ $targetDate->format('d/m/Y') }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ admin_url('/') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="{{ admin_url('daily-task-progress') }}">Tiến độ công việc</a></li>
            <li class="active">Chi tiết: {{ $user->name }}</li>
        </ol>
    </section>

    <section class="content">
        <!-- User info và date filter -->
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-body">
                <div style="display: flex; align-items: center;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0;">
                            <i class="fa fa-user"></i> {{ $user->name }}
                            <small class="text-muted">({{ $user->username }})</small>
                        </h4>
                        <p style="margin: 5px 0 0 0;">
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="label label-default">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Chưa có vai trò</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <label for="date">Ngày: </label>
                                <input type="date" 
                                       name="date" 
                                       id="date"
                                       value="{{ request('date', $targetDate->format('Y-m-d')) }}" 
                                       class="form-control"
                                       style="margin-left: 10px;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                                <i class="fa fa-search"></i> Xem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-body text-center">
                <a href="{{ admin_url('daily-task-progress') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Quay lại tổng quan
                </a>
                <a href="{{ admin_url('daily-task-progress/daily?date=' . $targetDate->format('Y-m-d')) }}" class="btn btn-primary">
                    <i class="fa fa-eye"></i> Xem cá nhân
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Summary -->
<div class="row">
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-list"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng công việc</span>
                <span class="info-box-number">{{ $total ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đã hoàn thành</span>
                <span class="info-box-number">{{ $completed ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Còn lại</span>
                <span class="info-box-number">{{ $pending ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon 
                @if($completion_rate >= 80) bg-green
                @elseif($completion_rate >= 50) bg-yellow
                @else bg-red
                @endif">
                <i class="fa fa-percent"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Tỷ lệ hoàn thành</span>
                <span class="info-box-number">{{ $completion_rate ?? 0 }}%</span>
            </div>
        </div>
    </div>
</div>

<!-- Week History -->
@if(isset($week_history) && $week_history->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-line-chart"></i> Lịch sử 7 ngày gần đây
                </h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th width="100">Tổng</th>
                                <th width="100">Hoàn thành</th>
                                <th width="100">Còn lại</th>
                                <th width="150">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($week_history as $day)
                                <tr class="{{ $day['date']->isToday() ? 'info' : '' }}">
                                    <td>
                                        <strong>{{ $day['date']->format('d/m/Y') }}</strong>
                                        <small class="text-muted">({{ $day['day_name'] }})</small>
                                        @if($day['date']->isToday())
                                            <span class="label label-primary">Hôm nay</span>
                                        @endif
                                    </td>
                                    <td>{{ $day['stats']['total'] ?? 0 }}</td>
                                    <td>{{ $day['stats']['completed'] ?? 0 }}</td>
                                    <td>{{ $day['stats']['pending'] ?? 0 }}</td>
                                    <td>
                                        @php
                                            $rate = $day['stats']['completion_rate'] ?? 0;
                                        @endphp
                                        <div class="progress progress-sm" style="margin-bottom: 0;">
                                            <div class="progress-bar progress-bar-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $rate }}%"></div>
                                        </div>
                                        <small>{{ $rate }}%</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Tasks Detail by Category -->
@if(isset($tasks_by_category) && $tasks_by_category->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-tasks"></i> Chi tiết công việc ngày {{ $targetDate->format('d/m/Y') }}
                </h3>
            </div>
            <div class="box-body">
                @foreach($tasks_by_category as $categoryName => $categoryTasks)
                    <div class="box box-default" style="margin-bottom: 15px;">
                        <div class="box-header with-border">
                            <h4 class="box-title">
                                @if($categoryTasks->first()->category)
                                    <i class="fa {{ $categoryTasks->first()->category->icon ?? 'fa-tasks' }}" 
                                       style="color: {{ $categoryTasks->first()->category->color ?? '#007bff' }}"></i>
                                @else
                                    <i class="fa fa-tasks"></i>
                                @endif
                                {{ $categoryName }}
                                <span class="label label-default">{{ $categoryTasks->count() }} công việc</span>
                            </h4>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th width="40"></th>
                                            <th>Công việc</th>
                                            <th width="100">Ưu tiên</th>
                                            <th width="120">Trạng thái</th>
                                            <th width="100">Thời gian</th>
                                            <th width="200">Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categoryTasks as $task)
                                            @php
                                                $completion = $task->completions->first();
                                                $isCompleted = $completion && $completion->status === 'completed';
                                                $needsReview = $completion && $completion->review_status == 1;
                                                $inProcess = $completion && $completion->status == 'in_process';
                                                $isOverdue = $task->task_type === 'one_time' && method_exists($task, 'isOverdue') && $task->isOverdue();
                                            @endphp
                                            <tr class="{{ $isCompleted ? 'success' : ($isOverdue ? 'danger' : '') }}">
                                                <td>
                                                    @if($isCompleted && !$needsReview)
                                                        <i class="fa fa-check-circle text-success"></i>
                                                    @elseif($needsReview)
                                                        <i class="fa fa-exclamation-triangle text-warning"></i>
                                                    @elseif($inProcess)
                                                        <i class="fa fa-clock-o text-info"></i>
                                                    @else
                                                        <i class="fa fa-circle-o text-muted"></i>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $task->title }}</strong>
                                                    @if($task->description)
                                                        <br><small class="text-muted">{{ Str::limit($task->description, 100) }}</small>
                                                    @endif
                                                    @if($task->task_type === 'one_time')
                                                        <br><span class="label label-warning">One Time</span>
                                                        @if($task->end_date)
                                                            <small class="text-muted">Deadline: {{ $task->end_date->format('d/m/Y') }}</small>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $priorityColors = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                                                        $priorityLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
                                                    @endphp
                                                    <span class="label label-{{ $priorityColors[$task->priority] ?? 'default' }}">
                                                        {{ $priorityLabels[$task->priority] ?? $task->priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($completion)
                                                        @php
                                                            $statusColors = [
                                                                'completed' => 'success',
                                                                'in_process' => 'info',
                                                                'skipped' => 'warning',
                                                                'failed' => 'danger'
                                                            ];
                                                            $statusLabels = [
                                                                'completed' => 'Hoàn thành',
                                                                'in_process' => 'Đang làm',
                                                                'skipped' => 'Bỏ qua',
                                                                'failed' => 'Thất bại'
                                                            ];
                                                        @endphp
                                                        <span class="label label-{{ $statusColors[$completion->status] ?? 'default' }}">
                                                            {{ $statusLabels[$completion->status] ?? $completion->status }}
                                                        </span>
                                                        @if($needsReview)
                                                            <br><small class="text-warning">
                                                                <i class="fa fa-exclamation-triangle"></i> Cần review
                                                            </small>
                                                        @endif
                                                    @else
                                                        <span class="label label-default">Chưa bắt đầu</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($task->suggested_time)
                                                        <small class="text-muted">
                                                            Đề xuất: {{ is_string($task->suggested_time) ? date('H:i', strtotime($task->suggested_time)) : $task->suggested_time->format('H:i') }}
                                                        </small>
                                                    @endif
                                                    @if($completion && $completion->completed_at_time)
                                                        <br><small class="text-success">
                                                            <i class="fa fa-check"></i> {{ $completion->completed_at_time->format('H:i') }}
                                                        </small>
                                                    @endif
                                                    @if($task->estimated_minutes)
                                                        <br><small class="text-info">
                                                            <i class="fa fa-clock-o"></i> {{ $task->estimated_minutes }}p
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($completion && $completion->notes)
                                                        <small>{{ Str::limit($completion->notes, 50) }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body text-center" style="padding: 60px;">
                <i class="fa fa-info-circle fa-3x text-muted" style="margin-bottom: 20px;"></i>
                <h4 class="text-muted">Không có công việc nào</h4>
                <p class="text-muted">{{ $user->name }} chưa có công việc được giao cho ngày {{ $targetDate->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Advanced Stats (if available) -->
@if(isset($advanced_stats))
<div class="row">
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-star"></i> Thống kê theo mức độ ưu tiên
                </h3>
            </div>
            <div class="box-body">
                @foreach(['urgent' => 'Khẩn cấp', 'high' => 'Cao', 'medium' => 'Trung bình', 'low' => 'Thấp'] as $priority => $label)
                    @if(($advanced_stats['priority_stats'][$priority] ?? 0) > 0)
                        @php
                            $total = $advanced_stats['priority_stats'][$priority];
                            $completed = $advanced_stats['priority_completed'][$priority] ?? 0;
                            $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
                            $colors = ['urgent' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'];
                        @endphp
                        <div style="margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <span class="label label-{{ $colors[$priority] }}">{{ $label }}</span>
                                    <small class="text-muted">{{ $completed }}/{{ $total }}</small>
                                </span>
                                <small>{{ $rate }}%</small>
                            </div>
                            <div class="progress progress-xs" style="margin: 5px 0 0 0;">
                                <div class="progress-bar progress-bar-{{ $colors[$priority] }}" 
                                     style="width: {{ $rate }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-warning"></i> Cảnh báo
                </h3>
            </div>
            <div class="box-body">
                @if(($advanced_stats['needs_review'] ?? 0) > 0)
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>{{ $advanced_stats['needs_review'] }}</strong> công việc cần được review lại
                    </div>
                @endif
                
                @if(($advanced_stats['overdue_tasks'] ?? 0) > 0)
                    <div class="alert alert-danger">
                        <i class="fa fa-clock-o"></i>
                        <strong>{{ $advanced_stats['overdue_tasks'] }}</strong> công việc one-time đã quá hạn
                    </div>
                @endif
                
                @if(($advanced_stats['needs_review'] ?? 0) == 0 && ($advanced_stats['overdue_tasks'] ?? 0) == 0)
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        Không có cảnh báo nào. Tất cả đều ổn!
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif