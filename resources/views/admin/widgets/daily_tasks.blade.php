<!-- resources/views/admin/widgets/daily_tasks.blade.php -->

<style>
    /* CSS để tùy chỉnh giao diện checklist */
    .task-list-widget .task-item { 
        display: flex; 
        align-items: center; 
        padding: 8px 12px; 
        border-bottom: 1px solid #f4f4f4; 
        transition: background-color 0.2s ease;
    }
    .task-list-widget .task-item:last-child { 
        border-bottom: none; 
    }
    .task-list-widget .task-item:hover {
        background-color: #fcfcfc;
    }
    .task-list-widget .task-item .task-checkbox { 
        margin-right: 12px; 
        transform: scale(1.2);
    }
    .task-list-widget .task-item .task-text { 
        flex-grow: 1; 
        cursor: default;
    }
    .task-list-widget .task-item.priority-high .task-text { 
        font-weight: 600; 
    }
    .task-list-widget .task-item.priority-urgent { 
        background-color: #fff9e6; 
    }
    .task-list-widget .task-item.priority-urgent .task-text { 
        color: #c0392b; 
        font-weight: 700; 
    }
    .task-list-widget .task-item.task-completed .task-text { 
        text-decoration: line-through; 
        color: #95a5a6; 
        font-weight: normal;
    }
    .task-list-widget .task-item.task-completed .fa-fire,
    .task-list-widget .task-item.task-completed .task-meta {
        opacity: 0.6;
    }
    
    /* CSS cho tasks cần review */
    .task-list-widget .task-item.needs-review { 
        background-color: #fff3cd !important; 
        border-left: 4px solid #ffc107;
    }
    .task-list-widget .task-item.needs-review .task-text { 
        color: #856404; 
        font-weight: 600;
    }
    .task-list-widget .task-item.needs-review:hover {
        background-color: #ffeaa7 !important;
    }
    .task-list-widget .task-item.needs-review .review-badge {
        background-color: #ffc107;
        color: #212529;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 8px;
    }
    
    /* CSS cho tasks quá hạn */
    .task-list-widget .task-item.overdue { 
        background-color: #f8d7da !important; 
        border-left: 4px solid #dc3545;
    }
    .task-list-widget .task-item.overdue .task-text { 
        color: #721c24; 
        font-weight: 600;
    }
    .task-list-widget .task-item.overdue:hover {
        background-color: #f5c6cb !important;
    }
    .task-list-widget .task-item.overdue .overdue-badge {
        background-color: #dc3545;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 8px;
    }
    
    .task-list-widget .task-meta { 
        display: flex; 
        align-items: center; 
        white-space: nowrap; 
    }
    .task-list-widget .task-meta .meta-item { 
        margin-left: 12px; 
        font-size: 12px; 
        color: #7f8c8d; 
    }
    .task-list-widget .task-meta .meta-item .fa { 
        margin-right: 4px; 
    }
    .task-list-widget .task-meta .meta-item.info-icon { 
        cursor: help; 
    }
    .task-list-widget .task-meta .meta-item.note-link a {
        color: #7f8c8d;
        text-decoration: none;
    }
    .task-list-widget .task-meta .meta-item.note-link a:hover {
        color: #3498db;
    }
    .task-list-widget .task-meta .meta-item.note-link .fa-comment { 
        color: #3498db; 
    }
    .task-list-widget .task-meta .meta-item.note-link .fa-comment-o { 
        color: #7f8c8d; 
    }
    .task-list-widget .task-meta .meta-item.completed-time .fa { 
        color: #27ae60; 
    }

    /* === CSS ĐỂ FIX LỖI RESPONSIVE === */
    .responsive-box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .responsive-box-header .box-title {
        margin-bottom: 0;
    }
    .responsive-box-header .box-tools {
        float: none !important; 
    }
</style>

<div class="box box-primary task-list-widget">
    <div class="box-header with-border responsive-box-header">
        <h3 class="box-title" style="flex-grow: 1;"><i class="fa fa-tasks"></i> Công việc hôm nay</h3>
        <div class="box-tools">
            <label style="font-weight: normal; font-size: 12px; margin-right: 10px; vertical-align: middle;">
                <input type="checkbox" id="focus-mode-toggle"> Ẩn việc đã xong
            </label>
            <span class="progress-text text-muted" style="font-size: 14px; vertical-align: middle;">{{ $completedTasks ?? 0 }}/{{ $totalTasks ?? 0 }}</span>
        </div>
    </div>
    <div class="box-body" style="padding: 0;">
        <!-- Thanh tiến trình tổng -->
        <div class="progress" style="height: 5px; margin: 0; border-radius: 0;">
            @php
                $completionRate = $completionRate ?? 0;
                $colorClass = $completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger');
            @endphp
            <div class="progress-bar progress-bar-{{$colorClass}}" style="width: {{ $completionRate }}%;"></div>
        </div>

        @if(isset($totalTasks) && $totalTasks > 0)
            <!-- Vòng lặp các Danh mục công việc -->
            @foreach($groupedTasks as $categoryName => $tasks)
            <div class="box box-solid" style="margin-bottom: 0; box-shadow: none; border-top: 1px solid #f4f4f4;">
                <div class="box-header with-border" style="background-color: #f9f9f9;">
                    <h4 class="box-title" style="font-size: 15px;">{{ $categoryName }}</h4>
                </div>
                <div class="box-body" style="padding: 0;">
                    <div class="task-list">
                        <!-- Vòng lặp các Task trong danh mục -->
                        @foreach($tasks as $task)
                            @php
                                $completion = $task->completions->first();
                                $isCompleted = $completion && $completion->status === 'completed' && (!$completion->review_status || $completion->review_status == 0);
                                $needsReview = $completion && $completion->review_status == 1;
                                $inProcess = $completion && $completion->status == 'in_process';
                                $isOverdue = $task->task_type === 'one_time' && $task->isOverdue() && !$isCompleted;
                            @endphp
                            <div class="task-item priority-{{ $task->priority }} 
                                {{ $isCompleted ? 'task-completed' : '' }} 
                                {{ $needsReview ? 'needs-review' : '' }}
                                {{ $isOverdue ? 'overdue' : '' }}">
                                
                                <input type="checkbox" 
                                       class="task-checkbox" 
                                       data-task-id="{{ $task->id }}" 
                                       {{ $isCompleted ? 'checked' : '' }}>
                                
                                <div class="task-text">
                                    @if($task->priority === 'urgent' || $task->priority === 'high')
                                    <i class="fa fa-fire text-danger"></i>
                                    @endif
                                    
                                    {{ $task->title }}
                                    
                                    <!-- Badge cho các trạng thái đặc biệt -->
                                    @if($needsReview)
                                        <span class="review-badge">
                                            <i class="fa fa-exclamation-triangle"></i> CẦN REVIEW
                                        </span>
                                    @elseif($isOverdue)
                                        <span class="overdue-badge">
                                            <i class="fa fa-clock-o"></i> QUÁ HẠN
                                        </span>
                                    @elseif($inProcess)
                                        <small class="text-muted">(đang thực hiện)</small>
                                    @endif
                                    
                                    @if($task->priority === 'urgent')
                                        <i class="fa fa-fire text-danger" title="Ưu tiên cao" style="margin-left: 5px;"></i>
                                    @elseif($task->priority === 'high')
                                        <i class="fa fa-exclamation-circle text-warning" title="Ưu tiên" style="margin-left: 5px;"></i>
                                    @endif
                                </div>
                                
                                <div class="task-meta">
                                    @if($task->suggested_time)
                                        <span class="meta-item">
                                            <i class="fa fa-clock-o"></i>{{ date('H:i', strtotime($task->suggested_time)) }}
                                        </span>
                                    @endif
                                    
                                    @if($task->estimated_minutes)
                                        <span class="meta-item">
                                            <i class="fa fa-hourglass-half"></i>{{ $task->estimated_minutes }}p
                                        </span>
                                    @endif
                                    
                                    @if($completion && $completion->completed_at_time)
                                        <span class="meta-item completed-time">
                                            <i class="fa fa-check"></i>{{ $completion->completed_at_time->format('H:i') }}
                                        </span>
                                    @endif
                                    
                                    <!-- Icon ghi chú -->
                                    <span class="meta-item note-link">
                                        <a href="javascript:void(0);" 
                                           class="add-note-btn" 
                                           data-task-id="{{ $task->id }}" 
                                           data-current-note="{{ e(optional($completion)->notes) }}"
                                           title="{{ optional($completion)->notes ? 'Xem/Sửa ghi chú: ' . $completion->notes : 'Thêm ghi chú' }}">
                                            @if(optional($completion)->notes)
                                                <i class="fa fa-comment"></i>
                                            @else
                                                <i class="fa fa-comment-o"></i>
                                            @endif
                                        </a>
                                    </span>
                                    
                                    @if($task->description)
                                        <span class="meta-item info-icon" data-toggle="tooltip" title="{{ $task->description }}">
                                            <i class="fa fa-info-circle"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                <p>Không có công việc nào được giao hôm nay.</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal thêm ghi chú -->
<div class="modal fade" id="task-note-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-comment"></i> Ghi chú công việc</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-task-id">
                <div class="form-group">
                    <label for="modal-task-notes">Ghi chú của bạn:</label>
                    <textarea id="modal-task-notes" 
                              class="form-control" 
                              rows="4" 
                              placeholder="Nhập ghi chú về công việc này... (Ví dụ: Gọi khách hàng ABC lúc 15:00, Cần chuẩn bị tài liệu XYZ, etc.)"></textarea>
                    <small class="text-muted">
                        <i class="fa fa-info-circle"></i> 
                        Ghi chú này sẽ hiển thị cho tất cả mọi người có thể xem task.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-task-note">
                    <i class="fa fa-save"></i> Lưu ghi chú
                </button>
            </div>
        </div>
    </div>
</div>