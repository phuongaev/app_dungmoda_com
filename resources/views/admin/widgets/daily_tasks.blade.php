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
        /* Tăng kích thước checkbox cho dễ bấm */
        transform: scale(1.2);
    }
    .task-list-widget .task-item .task-text { 
        flex-grow: 1; 
        cursor: default;
    }
    /* Làm nổi bật các mức độ ưu tiên */
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
    /* Kiểu cho task đã hoàn thành */
    .task-list-widget .task-item.task-completed .task-text { 
        text-decoration: line-through; 
        color: #95a5a6; 
        font-weight: normal;
    }
    .task-list-widget .task-item.task-completed .fa-fire,
    .task-list-widget .task-item.task-completed .task-meta {
        opacity: 0.6;
    }
    
    /* CSS cho các thông tin bổ sung */
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
    }
    .task-list-widget .task-meta .meta-item.note-link .fa-comment { 
        color: #3498db; 
    }
    .task-list-widget .task-meta .meta-item.completed-time .fa { 
        color: #27ae60; 
    }
</style>

<div class="box box-primary task-list-widget">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-tasks"></i> Công việc hôm nay</h3>
        <div class="box-tools pull-right">
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
                                $isCompleted = $completion && $completion->status === 'completed';
                            @endphp
                            <div class="task-item priority-{{ $task->priority }} {{ $isCompleted ? 'task-completed' : '' }}">
                                <input type="checkbox" class="task-checkbox" data-task-id="{{ $task->id }}" {{ $isCompleted ? 'checked' : '' }}>
                                <span class="task-text">
                                    @if($task->priority === 'urgent' || $task->priority === 'high') <i class="fa fa-fire text-danger"></i> @endif
                                    {{ $task->title }}
                                </span>

                                <!-- PHẦN HIỂN THỊ THÔNG TIN MỚI -->
                                <div class="task-meta">
                                    
                                    <!-- 1. Icon Info với mô tả (tooltip) -->
                                    @if($task->description)
                                    <span class="meta-item info-icon" 
                                          data-toggle="tooltip" 
                                          data-placement="top" 
                                          title="{{ e($task->description) }}">
                                        <i class="fa fa-info-circle"></i>
                                    </span>
                                    @endif

                                    <!-- 2. Thời gian gợi ý -->
                                    @if($task->suggested_time)
                                    <span class="meta-item suggested-time">
                                        <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($task->suggested_time)->format('H:i') }}
                                    </span>
                                    @endif
                                    
                                    <!-- 3. Thời gian hoàn thành (chỉ hiện khi đã xong) -->
                                    @if($isCompleted && $completion->completed_at_time)
                                    <span class="meta-item completed-time">
                                        <i class="fa fa-check-circle"></i> {{ \Carbon\Carbon::parse($completion->completed_at_time)->format('H:i') }}
                                    </span>
                                    @endif

                                    <!-- 4. Icon Ghi chú -->
                                    <span class="meta-item note-link">
                                        <a href="javascript:void(0);" class="add-note-btn" data-task-id="{{ $task->id }}" data-current-note="{{ e(optional($completion)->notes) }}">
                                            @if(optional($completion)->notes)
                                                <i class="fa fa-comment" title="Sửa ghi chú: {{ e($completion->notes) }}"></i>
                                            @else
                                                <i class="fa fa-comment-o" title="Thêm ghi chú"></i>
                                            @endif
                                        </a>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div style="padding: 30px; text-align: center;">
                <h4>Tuyệt vời!</h4>
                <p class="text-muted">Hôm nay bạn không có công việc nào. Chúc một ngày tốt lành! 🎉</p>
            </div>
        @endif
    </div>
</div>

<!-- ======================================================= -->
<!-- == PHẦN MODAL ĐẦY ĐỦ == -->
<!-- ======================================================= -->
<div class="modal fade" id="task-note-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Ghi chú công việc</h4>
            </div>
            <div class="modal-body">
                <form id="task-note-form" onsubmit="return false;">
                    <!-- Trường ẩn để lưu task_id -->
                    <input type="hidden" id="modal-task-id">
                    
                    <div class="form-group">
                        <label for="modal-task-notes">Nội dung ghi chú (tùy chọn):</label>
                        <textarea class="form-control" id="modal-task-notes" rows="4" placeholder="Nhập ghi chú của bạn..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-task-note">Hoàn thành</button>
            </div>
        </div>
    </div>
</div>
