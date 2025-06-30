{{-- resources/views/admin/daily_task_progress/daily.blade.php --}}

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
                <span class="info-box-text">Tổng hoạt động</span>
                <span class="info-box-number">{{ $totalCompletions }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Hoàn thành</span>
                <span class="info-box-number">{{ $completedCount }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-skip-forward"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bỏ qua</span>
                <span class="info-box-number">{{ $skippedCount }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-times"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Thất bại</span>
                <span class="info-box-number">{{ $failedCount }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter controls -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list"></i> Chi tiết hoạt động ngày {{ $targetDate->format('d/m/Y') }}
                </h3>
            </div>
            <div class="box-body">
                <!-- Filter form -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <select class="form-control" id="filter-user" onchange="filterTable()">
                            <option value="">Tất cả nhân viên</option>
                            @foreach($users as $user)
                                <option value="{{ $user->name }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="filter-status" onchange="filterTable()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="skipped">Bỏ qua</option>
                            <option value="failed">Thất bại</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" 
                               class="form-control" 
                               id="filter-task" 
                               placeholder="Tìm kiếm công việc..."
                               onkeyup="filterTable()">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="completions-table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Công việc</th>
                                <th>Danh mục</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completions as $completion)
                                <tr data-user="{{ $completion->user->name }}" 
                                    data-status="{{ $completion->status }}"
                                    data-task="{{ strtolower($completion->dailyTask->title) }}">
                                    <td>
                                        <strong>{{ $completion->user->name }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ $completion->dailyTask->title }}</strong>
                                        @if($completion->dailyTask->description)
                                            <br><small class="text-muted">{{ Str::limit($completion->dailyTask->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($completion->dailyTask->category)
                                            <span class="label" style="background-color: {{ $completion->dailyTask->category->color ?? '#007bff' }}">
                                                {{ $completion->dailyTask->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        {{ $completion->completed_at_time ? \Carbon\Carbon::parse($completion->completed_at_time)->format('H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        {{ $completion->notes ? Str::limit($completion->notes, 50) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                                        <i class="fa fa-info-circle"></i> Chưa có hoạt động nào trong ngày này
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterTable() {
    var userFilter = document.getElementById("filter-user").value.toLowerCase();
    var statusFilter = document.getElementById("filter-status").value.toLowerCase();
    var taskFilter = document.getElementById("filter-task").value.toLowerCase();
    var table = document.getElementById("completions-table");
    var rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var userName = row.getAttribute("data-user");
        var status = row.getAttribute("data-status");
        var taskName = row.getAttribute("data-task");
        
        if (!userName || !status || !taskName) continue;
        
        var showRow = true;
        
        if (userFilter && userName.toLowerCase().indexOf(userFilter) === -1) {
            showRow = false;
        }
        
        if (statusFilter && status !== statusFilter) {
            showRow = false;
        }
        
        if (taskFilter && taskName.indexOf(taskFilter) === -1) {
            showRow = false;
        }
        
        row.style.display = showRow ? "" : "none";
    }
}
</script>