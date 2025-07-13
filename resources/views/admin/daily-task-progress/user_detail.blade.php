{{-- resources/views/admin/daily_task_progress/user_detail.blade.php --}}

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
                <a href="{{ admin_url('daily-task-progress/daily?date=' . $targetDate->format('Y-m-d')) }}" class="btn btn-info">
                    <i class="fa fa-calendar"></i> Xem theo ngày
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-chart-pie"></i> Thống kê ngày {{ $targetDate->format('d/m/Y') }}
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-tasks"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng CV</span>
                                <span class="info-box-number">{{ $totalTasks }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Hoàn thành</span>
                                <span class="info-box-number">{{ $completedTasks }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-sign-in"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Bỏ qua</span>
                                <span class="info-box-number">{{ $skippedTasks }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon bg-red"><i class="fa fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Thất bại</span>
                                <span class="info-box-number">{{ $failedTasks }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon bg-gray"><i class="fa fa-clock-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Chưa làm</span>
                                <span class="info-box-number">{{ $pendingTasks }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="info-box">
                            <span class="info-box-icon 
                                @if($completionRate >= 80) bg-green
                                @elseif($completionRate >= 50) bg-yellow
                                @else bg-red
                                @endif">
                                <i class="fa fa-percent"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tỷ lệ</span>
                                <span class="info-box-number">{{ $completionRate }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress bar -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="progress" style="height: 25px; margin-top: 15px;">
                            <div class="progress-bar 
                                @if($completionRate >= 80) progress-bar-success
                                @elseif($completionRate >= 50) progress-bar-warning
                                @else progress-bar-danger
                                @endif" 
                                 style="width: {{ $completionRate }}%">
                                {{ $completionRate }}% hoàn thành
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority breakdown -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Thống kê theo độ ưu tiên</h3>
            </div>
            <div class="box-body">
                <div class="row">
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
                        
                        <div class="col-md-3">
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
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks by category -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list"></i> Chi tiết công việc theo danh mục
                </h3>
            </div>
            <div class="box-body">
                @foreach($tasksByCategory as $categoryName => $categoryTasks)
                    <div class="category-section" style="margin-bottom: 30px;">
                        <h4 class="category-header" style="margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #eee;">
                            @if($categoryTasks->first()->category)
                                <i class="fa {{ $categoryTasks->first()->category->icon ?? 'fa-tasks' }}"></i>
                                <span style="color: {{ $categoryTasks->first()->category->color }}">
                                    {{ $categoryName }}
                                </span>
                            @else
                                <i class="fa fa-tasks"></i> {{ $categoryName }}
                            @endif
                            <small class="text-muted">({{ $categoryTasks->count() }} công việc)</small>
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th>Công việc</th>
                                        <th>Ưu tiên</th>
                                        <th>Thời gian gợi ý</th>
                                        <th>Trạng thái</th>
                                        <th>Hoàn thành lúc</th>
                                        <th>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryTasks as $task)
                                        @php
                                            $completion = $task->completions->first();
                                            $priorityLabels = [
                                                'urgent' => ['Khẩn cấp', 'danger'],
                                                'high' => ['Cao', 'warning'],
                                                'medium' => ['Trung bình', 'info'],
                                                'low' => ['Thấp', 'success']
                                            ];
                                            $statusLabels = [
                                                'completed' => ['Hoàn thành', 'success'],
                                                'skipped' => ['Bỏ qua', 'warning'],
                                                'failed' => ['Thất bại', 'danger']
                                            ];
                                            $priorityInfo = $priorityLabels[$task->priority] ?? [$task->priority, 'default'];
                                        @endphp
                                        
                                        <tr>
                                            <td>
                                                <strong>{{ $task->title }}</strong>
                                                @if($task->description)
                                                    <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="label label-{{ $priorityInfo[1] }}">{{ $priorityInfo[0] }}</span>
                                            </td>
                                            <td>
                                                {{ $task->suggested_time ? \Carbon\Carbon::parse($task->suggested_time)->format('H:i') : '-' }}
                                            </td>
                                            <td>
                                                @if($completion)
                                                    @php
                                                        $statusInfo = $statusLabels[$completion->status] ?? [$completion->status, 'default'];
                                                    @endphp
                                                    <span class="label label-{{ $statusInfo[1] }}">{{ $statusInfo[0] }}</span>
                                                @else
                                                    <span class="label label-default">Chưa thực hiện</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $completion && $completion->completed_at_time ? \Carbon\Carbon::parse($completion->completed_at_time)->format('H:i:s') : '-' }}
                                            </td>
                                            <td>
                                                {{ $completion && $completion->notes ? Str::limit($completion->notes, 30) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                
                @if($tasksByCategory->isEmpty())
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-info-circle"></i> Không có công việc nào được giao cho ngày này
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.progress-xs {
    height: 8px;
}

.priority-stat {
    padding: 5px 0;
}

.category-header {
    font-size: 16px;
}

.table-condensed td {
    padding: 5px 8px;
    vertical-align: middle;
}
</style>