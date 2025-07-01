{{-- resources/views/admin/widgets/daily_tasks_improved.blade.php --}}

{{-- Include CSS --}}
<link rel="stylesheet" href="{{ asset('assets/css/daily-task.css') }}">

<div class="box box-solid">
    <div class="box-header with-border" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 4px 4px 0 0;">
        <h3 class="box-title">
            <i class="fa fa-calendar-check-o"></i> Công việc hôm nay
            <small style="opacity: 0.8;">({{ $today->format('d/m/Y') }})</small>
        </h3>
        
        <div class="box-tools pull-right">
            <button type="button" 
                    class="btn btn-box-tool toggle-completed-btn" 
                    id="toggle-completed-tasks"
                    data-toggle="tooltip" 
                    data-placement="bottom"
                    title="Ẩn/Hiện task đã hoàn thành"
                    style="color: white; margin-right: 10px;">
                <i class="fa fa-eye-slash"></i>
                <span class="toggle-text">Ẩn hoàn thành</span>
            </button>
            <span class="progress-badge">{{ $completedTasks }}/{{ $totalTasks }}</span>
            <button type="button" class="btn btn-box-tool" data-widget="collapse" style="color: white;">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body" style="padding: 15px;">
        <!-- Enhanced Progress Section -->
        <div class="progress-overview">
            <div class="progress-stats">
                <div class="stat-card completed">
                    <div class="stat-number">{{ $completedTasks }}</div>
                    <div class="stat-label">Hoàn thành</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number">{{ $totalTasks - $completedTasks }}</div>
                    <div class="stat-label">Còn lại</div>
                </div>
                <div class="stat-card percentage">
                    <div class="stat-number">{{ $completionRate }}%</div>
                    <div class="stat-label">Tiến độ</div>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress progress-enhanced">
                    <div class="progress-bar progress-bar-animated
                        @if($completionRate < 30) progress-bar-danger
                        @elseif($completionRate < 70) progress-bar-warning  
                        @else progress-bar-success
                        @endif" 
                        style="width: {{ $completionRate }}%"
                        data-completion="{{ $completionRate }}">
                        <span class="progress-text">{{ $completionRate }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Tasks List -->
        @if($tasks->count() > 0)
            <div class="tasks-container">
                @foreach($tasks->groupBy('category.name') as $categoryName => $categoryTasks)
                    <div class="category-section">
                        <div class="category-header">
                            @if($categoryTasks->first()->category)
                                <div class="category-info">
                                    <i class="fa {{ $categoryTasks->first()->category->icon ?? 'fa-tasks' }}" 
                                       style="color: {{ $categoryTasks->first()->category->color }}"></i>
                                    <span class="category-name">{{ $categoryName ?: 'Không phân loại' }}</span>
                                    <span class="category-count">{{ $categoryTasks->count() }} việc</span>
                                </div>
                                <div class="category-progress">
                                    @php
                                        $categoryCompleted = $categoryTasks->filter(function($task) {
                                            $completion = $task->completions->first();
                                            return $completion && $completion->status === 'completed';
                                        })->count();
                                        $categoryRate = $categoryTasks->count() > 0 ? round(($categoryCompleted / $categoryTasks->count()) * 100) : 0;
                                    @endphp
                                    <small class="category-rate">{{ $categoryCompleted }}/{{ $categoryTasks->count() }}</small>
                                </div>
                            @else
                                <div class="category-info">
                                    <i class="fa fa-tasks"></i>
                                    <span class="category-name">Không phân loại</span>
                                </div>
                            @endif
                        </div>

                        <div class="tasks-list">
                            @foreach($categoryTasks as $task)
                                @php
                                    $completion = $task->completions->first();
                                    $isCompleted = $completion && $completion->status === 'completed';
                                    $priorityColors = [
                                        'low' => '#28a745',
                                        'medium' => '#17a2b8', 
                                        'high' => '#ffc107',
                                        'urgent' => '#dc3545'
                                    ];
                                    $priorityColor = $priorityColors[$task->priority] ?? '#6c757d';
                                @endphp
                                
                                <div class="task-card {{ $isCompleted ? 'completed' : '' }}" data-task-id="{{ $task->id }}">
                                    <div class="task-main">
                                        <div class="task-checkbox-wrapper">
                                            <label class="checkbox-container">
                                                <input type="checkbox" 
                                                       class="task-checkbox" 
                                                       data-task-id="{{ $task->id }}"
                                                       {{ $isCompleted ? 'checked' : '' }}>
                                                <span class="checkmark-custom"></span>
                                            </label>
                                        </div>
                                        
                                        <div class="task-content">
                                            <div class="task-title">
                                                {{ $task->title }}
                                                <span class="priority-indicator" style="background-color: {{ $priorityColor }}"></span>
                                            </div>
                                            
                                            @if($task->description)
                                                <div class="task-description">{{ Str::limit($task->description, 60) }}</div>
                                            @endif
                                            
                                            <div class="task-meta">
                                                @if($task->suggested_time)
                                                    <span class="meta-item">
                                                        <i class="fa fa-clock-o"></i>
                                                        {{ date('H:i', strtotime($task->suggested_time)) }}
                                                    </span>
                                                @endif
                                                
                                                @if($task->estimated_minutes)
                                                    <span class="meta-item">
                                                        <i class="fa fa-hourglass-half"></i>
                                                        {{ $task->estimated_minutes }}p
                                                    </span>
                                                @endif
                                                
                                                @if($isCompleted && $completion->completed_at_time)
                                                    <span class="meta-item completed-time">
                                                        <i class="fa fa-check-circle"></i>
                                                        {{ date('H:i', strtotime($completion->completed_at_time)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="task-actions">
                                        <button type="button" 
                                                class="btn-note {{ $completion && $completion->notes ? 'has-note' : '' }}"
                                                data-task-id="{{ $task->id }}"
                                                data-current-note="{{ $completion->notes ?? '' }}"
                                                data-toggle="modal" 
                                                data-target="#task-note-modal"
                                                title="{{ $completion && $completion->notes ? 'Xem/Sửa ghi chú' : 'Thêm ghi chú' }}">
                                            <i class="fa fa-comment{{ $completion && $completion->notes ? '' : '-o' }}"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa fa-smile-o"></i>
                </div>
                <h4 class="empty-title">Tuyệt vời!</h4>
                <p class="empty-description">Không có công việc nào hôm nay. Bạn có thể nghỉ ngơi hoặc liên hệ quản lý để được giao thêm việc.</p>
            </div>
        @endif
    </div>

    @if($totalTasks > 0)
        <div class="box-footer enhanced-footer">
            <div class="footer-content">
                <div class="achievement">
                    @if($completionRate === 100)
                        <span class="achievement-badge gold">
                            <i class="fa fa-trophy"></i> Hoàn thành xuất sắc!
                        </span>
                    @elseif($completionRate >= 80)
                        <span class="achievement-badge silver">
                            <i class="fa fa-star"></i> Tiến độ rất tốt!
                        </span>
                    @elseif($completionRate >= 60)
                        <span class="achievement-badge bronze">
                            <i class="fa fa-thumbs-up"></i> Tiến độ tốt!
                        </span>
                    @elseif($completionRate >= 30)
                        <span class="achievement-badge warning">
                            <i class="fa fa-clock-o"></i> Cần cố gắng thêm!
                        </span>
                    @else
                        <span class="achievement-badge danger">
                            <i class="fa fa-exclamation-triangle"></i> Cần hoàn thành gấp!
                        </span>
                    @endif
                </div>
                
                <div class="quick-stats">
                    <small>Cập nhật lần cuối: {{ now()->format('H:i') }}</small>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Enhanced Modal for Notes -->
<div class="modal fade" id="task-note-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-comment"></i> Ghi chú công việc
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-task-id">
                <div class="form-group">
                    <label for="modal-task-notes">Ghi chú của bạn:</label>
                    <textarea id="modal-task-notes" 
                              class="form-control" 
                              rows="4" 
                              placeholder="Nhập ghi chú về công việc này..."></textarea>
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

{{-- Include JavaScript and Config --}}
<script>
// Configuration for JavaScript
window.dailyTasksConfig = {
    toggleUrl: '{{ admin_url("daily-tasks/toggle-completion") }}',
    addNoteUrl: '{{ admin_url("daily-tasks/add-note") }}',
    csrfToken: '{{ csrf_token() }}'
};
</script>

{{-- Include main.js --}}
<script src="{{ asset('assets/js/daily-task.js') }}"></script>