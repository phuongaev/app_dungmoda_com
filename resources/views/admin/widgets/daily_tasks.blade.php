{{-- resources/views/admin/widgets/daily_tasks.blade.php --}}

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-tasks"></i> Công việc hàng ngày 
            <small>({{ $today->format('d/m/Y') }})</small>
        </h3>
        
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">
        <!-- Progress Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="progress-group">
                    <span class="progress-text">{{ $completedTasks }}/{{ $totalTasks }} công việc hoàn thành</span>
                    <span class="float-right"><b>{{ $completionRate }}%</b></span>
                    <div class="progress progress-sm" style="height: 10px !important; margin: 5px 0 0 0;">
                        <div class="progress-bar 
                            @if($completionRate < 30) progress-bar-danger
                            @elseif($completionRate < 70) progress-bar-warning  
                            @else progress-bar-success
                            @endif" 
                            style="width: {{ $completionRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>


        <hr>

        <!-- Tasks List -->
        @if($tasks->count() > 0)
            <div class="tasks-list">
                @foreach($tasks->groupBy('category.name') as $categoryName => $categoryTasks)
                    <div class="task-category-section">
                        <h4 class="category-header">
                            @if($categoryTasks->first()->category)
                                <i class="fa {{ $categoryTasks->first()->category->icon ?? 'fa-tasks' }}"></i>
                                <span style="color: {{ $categoryTasks->first()->category->color }}">
                                    {{ $categoryName ?: 'Không phân loại' }}
                                </span>
                            @else
                                <i class="fa fa-tasks"></i> Không phân loại
                            @endif
                        </h4>

                        @foreach($categoryTasks as $task)
                            @php
                                $completion = $task->completions->first();
                                $isCompleted = $completion && $completion->status === 'completed';
                            @endphp
                            
                            <div class="task-item {{ $isCompleted ? 'task-completed' : '' }}" data-task-id="{{ $task->id }}">
                                <div class="task-content">
                                    <div class="task-header">
                                        <label class="task-checkbox-label">
                                            <input type="checkbox" 
                                                   class="task-checkbox" 
                                                   data-task-id="{{ $task->id }}"
                                                   {{ $isCompleted ? 'checked' : '' }}>
                                            <span class="task-title">{{ $task->title }}</span>
                                        </label>
                                        
                                        <div class="task-meta">
                                            <!-- Priority Badge -->
                                            <span class="priority-badge priority-{{ $task->priority }}">
                                                {{ $task->priority_label }}
                                            </span>
                                            
                                            <!-- Time Info -->
                                            @if($task->suggested_time)
                                                <span class="suggested-time">
                                                    <i class="fa fa-clock-o"></i> {{ date('H:i', strtotime($task->suggested_time)) }}
                                                </span>
                                            @endif
                                            
                                            @if($task->estimated_minutes)
                                                <span class="estimated-time">
                                                    <i class="fa fa-hourglass-half"></i> {{ $task->estimated_minutes }}p
                                                </span>
                                            @endif
                                            
                                            <!-- Required Badge -->
                                            @if($task->is_required)
                                                <span class="required-badge">
                                                    <i class="fa fa-exclamation-circle"></i> Bắt buộc
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($task->description)
                                        <div class="task-description">
                                            {{ $task->description }}
                                        </div>
                                    @endif
                                    
                                    <div class="task-actions">
                                        <button class="btn btn-xs btn-default add-note-btn" 
                                                data-task-id="{{ $task->id }}"
                                                data-current-note="{{ $completion->notes ?? '' }}">
                                            <i class="fa fa-comment{{ $completion && $completion->notes ? '' : '-o' }}"></i>
                                            {{ $completion && $completion->notes ? 'Đã có ghi chú' : 'Thêm ghi chú' }}
                                        </button>
                                        
                                        @if($isCompleted)
                                            <span class="completion-time text-success">
                                                <i class="fa fa-check"></i>
                                                Hoàn thành lúc: {{ $completion->completed_at_time ? date('H:i', strtotime($completion->completed_at_time)) : 'N/A' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-tasks">
                <div class="text-center text-muted">
                    <i class="fa fa-smile-o fa-3x"></i>
                    <h4>Không có công việc nào hôm nay!</h4>
                    <p>Bạn có thể nghỉ ngơi hoặc liên hệ quản lý để được giao thêm công việc.</p>
                </div>
            </div>
        @endif
    </div>

    @if($totalTasks > 0)
        <div class="box-footer">
            <div class="row">
                <div class="col-sm-6">
                    <div class="completion-stats">
                        <small class="text-muted">
                            Tiến độ hôm nay: <strong>{{ $completedTasks }}/{{ $totalTasks }}</strong>
                        </small>
                    </div>
                </div>
                <div class="col-sm-6 text-right">
                    @if($completionRate === 100)
                        <span class="text-success">
                            <i class="fa fa-trophy"></i> Hoàn thành xuất sắc!
                        </span>
                    @elseif($completionRate >= 70)
                        <span class="text-info">
                            <i class="fa fa-thumbs-up"></i> Tiến độ tốt!
                        </span>
                    @elseif($completionRate >= 30)
                        <span class="text-warning">
                            <i class="fa fa-clock-o"></i> Cần cố gắng thêm!
                        </span>
                    @else
                        <span class="text-danger">
                            <i class="fa fa-exclamation-triangle"></i> Cần hoàn thành gấp!
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="task-note-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Ghi chú công việc</h4>
            </div>
            <div class="modal-body">
                <form id="task-note-form">
                    <input type="hidden" id="modal-task-id">
                    
                    <div class="form-group">
                        <label for="modal-task-notes">Nội dung ghi chú:</label>
                        <textarea class="form-control" id="modal-task-notes" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-task-note">Hoàn thành</button>
            </div>
        </div>
    </div>
</div>

<style>
.task-item {
    padding: 10px;
    margin-bottom: 8px;
    border-left: 3px solid #ddd;
    background: #fff;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.task-item:hover {
    background: #f9f9f9;
    border-left-color: #3c8dbc;
}

.task-item.task-completed {
    background: #f0f9ff;
    border-left-color: #00a65a;
    opacity: 0.8;
}

.task-item.task-completed .task-title {
    text-decoration: line-through;
    color: #999;
}

.task-checkbox-label {
    margin-bottom: 0;
    font-weight: normal;
    cursor: pointer;
}

.task-title {
    font-size: 14px;
    margin-left: 8px;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 5px;
}

.task-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.priority-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}

.priority-low { background-color: #28a745; }
.priority-medium { background-color: #ffc107; color: #212529; }
.priority-high { background-color: #fd7e14; }
.priority-urgent { background-color: #dc3545; }

.suggested-time, .estimated-time {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #6c757d;
}

.required-badge {
    background: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

.task-description {
    margin: 8px 0;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 3px;
    font-size: 12px;
    color: #666;
}

.task-actions {
    margin-top: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.completion-time {
    font-size: 12px;
}

.category-header {
    margin: 15px 0 10px 0;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}

.no-tasks {
    padding: 40px 20px;
}

.progress-group {
    margin-bottom: 15px;
}

.completion-stats {
    line-height: 30px;
}
</style>