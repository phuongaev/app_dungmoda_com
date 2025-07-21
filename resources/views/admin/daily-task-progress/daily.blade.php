{{-- resources/views/admin/daily-task-progress/daily.blade.php --}}

<div id="app">
    <section class="content-header">
        <h1>
            Tiến độ công việc cá nhân
            <small>Theo dõi công việc ngày {{ $targetDate->format('d/m/Y') }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ admin_url('/') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="{{ admin_url('daily-task-progress') }}">Tiến độ công việc</a></li>
            <li class="active">Theo ngày</li>
        </ol>
    </section>

    <section class="content">
        <!-- Date filter -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <form method="GET" class="form-inline">
                    <div class="form-group">
                        <label for="date">Chọn ngày: </label>
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
                    <a href="{{ admin_url('daily-task-progress') }}" class="btn btn-default" style="margin-left: 5px;">
                        <i class="fa fa-arrow-left"></i> Quay lại
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
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
                <span class="info-box-text">Đang thực hiện</span>
                <span class="info-box-number">{{ $in_process ?? 0 }}</span>
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

<!-- Tasks by Category -->
@if(isset($tasks_by_category) && $tasks_by_category->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-tasks"></i> Công việc theo danh mục 
                    <small>({{ $targetDate->format('d/m/Y') }})</small>
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
                                            <th width="40">
                                                <i class="fa fa-check"></i>
                                            </th>
                                            <th>Công việc</th>
                                            <th width="100">Ưu tiên</th>
                                            <th width="100">Loại</th>
                                            <th width="120">Trạng thái</th>
                                            <th width="100">Thời gian</th>
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
                                                        <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
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
                                                    <span class="label label-{{ $task->task_type === 'one_time' ? 'warning' : 'info' }}">
                                                        {{ $task->task_type === 'one_time' ? 'One Time' : 'Lặp lại' }}
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
                                                            <br><small class="text-warning">Cần review</small>
                                                        @endif
                                                    @else
                                                        <span class="label label-default">Chưa bắt đầu</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($task->suggested_time)
                                                        <small class="text-muted">
                                                            {{ is_string($task->suggested_time) ? date('H:i', strtotime($task->suggested_time)) : $task->suggested_time->format('H:i') }}
                                                        </small>
                                                    @endif
                                                    @if($completion && $completion->completed_at_time)
                                                        <br><small class="text-success">
                                                            <i class="fa fa-check"></i> {{ $completion->completed_at_time->format('H:i') }}
                                                        </small>
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
                <p class="text-muted">Chưa có công việc được giao cho ngày {{ $targetDate->format('d/m/Y') }}</p>
            </div>
        </div>
        </div>
    </div>
</div>
@endif