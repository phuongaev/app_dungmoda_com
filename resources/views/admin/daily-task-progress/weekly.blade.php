{{-- resources/views/admin/daily-task-progress/weekly.blade.php --}}

<!-- Week filter -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <form method="GET" class="form-inline">
                    <div class="form-group">
                        <label for="week">Chọn tuần: </label>
                        <input type="week" 
                               name="week" 
                               id="week"
                               value="{{ request('week', $start_week->format('Y-\WW')) }}" 
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

<!-- Week Summary Statistics -->
<div class="row">
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng nhân viên</span>
                <span class="info-box-number">{{ $total_users ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Hoàn thành tuần</span>
                <span class="info-box-number">{{ $weekly_completions ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-tasks"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng công việc</span>
                <span class="info-box-number">{{ $weekly_total_tasks ?? 0 }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon 
                @if($weekly_completion_rate >= 80) bg-green
                @elseif($weekly_completion_rate >= 50) bg-yellow
                @else bg-red
                @endif">
                <i class="fa fa-percent"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Tỷ lệ hoàn thành</span>
                <span class="info-box-number">{{ $weekly_completion_rate ?? 0 }}%</span>
            </div>
        </div>
    </div>
</div>

<!-- Daily Progress Chart -->
@if(isset($daily_stats) && count($daily_stats) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-line-chart"></i> Tiến độ theo ngày
                    <small>({{ $start_week->format('d/m/Y') }} - {{ $end_week->format('d/m/Y') }})</small>
                </h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th width="120">Tổng hoạt động</th>
                                <th width="120">Hoàn thành</th>
                                <th width="120">Bỏ qua</th>
                                <th width="120">Thất bại</th>
                                <th width="150">Tỷ lệ hoàn thành</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($daily_stats as $date => $stats)
                                @php
                                    $dateObj = \Carbon\Carbon::parse($date);
                                    $completionRate = $stats['total_completions'] > 0 ? 
                                        round(($stats['completed_count'] / $stats['total_completions']) * 100) : 0;
                                @endphp
                                <tr class="{{ $dateObj->isToday() ? 'info' : '' }}">
                                    <td>
                                        <strong>{{ $dateObj->format('d/m/Y') }}</strong>
                                        <br><small class="text-muted">{{ $dateObj->format('D') }}</small>
                                        @if($dateObj->isToday())
                                            <span class="label label-primary">Hôm nay</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-blue">{{ $stats['total_completions'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-green">{{ $stats['completed_count'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-yellow">{{ $stats['skipped_count'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-red">{{ $stats['failed_count'] ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <div class="progress progress-sm" style="margin-bottom: 0;">
                                            <div class="progress-bar progress-bar-{{ $completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $completionRate }}%"></div>
                                        </div>
                                        <small>{{ $completionRate }}%</small>
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

<!-- Completion Rate by Category -->
@if(isset($completion_rate_by_category) && $completion_rate_by_category->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-pie-chart"></i> Tỷ lệ hoàn thành theo danh mục
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    @foreach($completion_rate_by_category as $category)
                        <div class="col-md-4" style="margin-bottom: 20px;">
                            <div class="box box-default">
                                <div class="box-header with-border" style="background-color: {{ $category['category_color'] ?? '#007bff' }}; color: white;">
                                    <h4 class="box-title">{{ $category['category_name'] ?? 'N/A' }}</h4>
                                </div>
                                <div class="box-body text-center">
                                    <div class="progress progress-lg">
                                        <div class="progress-bar progress-bar-{{ $category['completion_rate'] >= 80 ? 'success' : ($category['completion_rate'] >= 50 ? 'warning' : 'danger') }}" 
                                             style="width: {{ $category['completion_rate'] }}%"></div>
                                    </div>
                                    <p>
                                        <strong>{{ $category['completion_rate'] }}%</strong><br>
                                        <small class="text-muted">
                                            {{ $category['completed_tasks'] ?? 0 }}/{{ $category['total_tasks'] ?? 0 }} công việc
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if((!isset($daily_stats) || count($daily_stats) == 0) && (!isset($completion_rate_by_category) || $completion_rate_by_category->count() == 0))
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body text-center" style="padding: 60px;">
                <i class="fa fa-info-circle fa-3x text-muted" style="margin-bottom: 20px;"></i>
                <h4 class="text-muted">Không có dữ liệu</h4>
                <p class="text-muted">Chưa có dữ liệu cho tuần {{ $start_week->format('d/m/Y') }} - {{ $end_week->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endif