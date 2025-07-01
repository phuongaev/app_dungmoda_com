<?php
// app/Admin/Widgets/DailyTasksWidget.php

namespace App\Admin\Widgets;

use App\Models\DailyTask;
use App\Models\UserTaskCompletion;
use Encore\Admin\Widgets\Widget;
use Encore\Admin\Facades\Admin;
use Carbon\Carbon;

class DailyTasksWidget extends Widget
{
    protected $view = 'admin.widgets.daily_tasks';

    public function script()
    {
        $toggleUrl = admin_url('daily-tasks/toggle-completion');
        $addNoteUrl = admin_url('daily-tasks/add-note');
        
        // Dùng hàm json_encode để truyền biến PHP vào JS một cách an toàn
        $scriptVars = json_encode([
            'toggleUrl' => $toggleUrl,
            'addNoteUrl' => $addNoteUrl,
            'csrfToken' => csrf_token()
        ]);

        return <<<SCRIPT
            
            $(function() {
                
                $('[data-toggle="tooltip"]').tooltip();

                var scriptConfig = $scriptVars;
                var currentAjaxRequest = null;
                var modalSaved = false;

                // --- SỰ KIỆN CỦA MODAL (ĐỂ XỬ LÝ LỖI LOGIC) ---
                var \$noteModal = $('#task-note-modal');

                // Khi modal chuẩn bị hiển thị
                \$noteModal.on('show.bs.modal', function() {
                    // Reset cờ mỗi khi mở modal
                    modalSaved = false; 
                });

                // Khi modal đã bị ẩn đi (bằng bất kỳ cách nào)
                \$noteModal.on('hidden.bs.modal', function() {
                    // Lấy checkbox đã kích hoạt modal (nếu có)
                    var triggeringCheckbox = \$noteModal.data('triggeringCheckbox');

                    // Nếu modal bị đóng mà không phải do bấm "Lưu" VÀ nó được kích hoạt bởi checkbox
                    if (!modalSaved && triggeringCheckbox) {
                        // Trả checkbox về trạng thái cũ (chưa check)
                        triggeringCheckbox.prop('checked', false);
                    }
                    
                    // Xóa tham chiếu sau khi xử lý xong
                    \$noteModal.removeData('triggeringCheckbox');
                });

                // --- SỰ KIỆN NÚT LƯU TRONG MODAL ---
                \$noteModal.on('click', '#save-task-note', function() {
                    // Đánh dấu là đã bấm lưu
                    modalSaved = true;

                    var taskId = $('#modal-task-id').val();
                    var notes = $('#modal-task-notes').val();
                    
                    var originalRequest = \$noteModal.data('originalRequest');
                    
                    \$noteModal.modal('hide');

                    originalRequest.data.notes = notes;
                    $.ajax(originalRequest);
                });

                // --- SỰ KIỆN TOGGLE CHECKBOX ---
                $('.task-checkbox').on('change', function() {
                    var checkbox = $(this);
                    var taskId = checkbox.data('task-id');
                    var isCompleted = checkbox.is(':checked');
                    var taskRow = checkbox.closest('.task-item');
                    
                    var ajaxRequest = {
                        url: scriptConfig.toggleUrl,
                        method: 'POST',
                        data: {
                            task_id: taskId,
                            completed: isCompleted,
                            notes: '',
                            _token: scriptConfig.csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                if (isCompleted) {
                                    taskRow.addClass('task-completed');
                                    taskRow.find('.completion-time').text('Hoàn thành lúc: ' + response.completion_time);
                                } else {
                                    taskRow.removeClass('task-completed');
                                    taskRow.find('.completion-time').text('');
                                }
                                updateProgressBar();
                                toastr.success(response.message);
                            } else {
                                checkbox.prop('checked', !isCompleted);
                                toastr.error(response.message || 'Có lỗi xảy ra!');
                            }
                        },
                        error: function(xhr) {
                            checkbox.prop('checked', !isCompleted);
                            console.log('Error:', xhr.responseText);
                            toastr.error('Có lỗi kết nối!');
                        }
                    };

                    if (isCompleted) {
                        // Mở modal để hỏi ghi chú
                        $('#modal-task-id').val(taskId);
                        $('#modal-task-notes').val('').focus();
                        
                        // Lưu request và cả checkbox đã kích hoạt nó
                        \$noteModal.data('originalRequest', ajaxRequest);
                        \$noteModal.data('triggeringCheckbox', checkbox);                        
                        \$noteModal.modal('show');
                    } else {
                        // Nếu bỏ check, thực hiện AJAX ngay
                        $.ajax(ajaxRequest);
                    }
                });

                // --- CHẾ ĐỘ TẬP TRUNG ---
                $('#focus-mode-toggle').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.task-completed').slideUp();
                    } else {
                        $('.task-completed').slideDown();
                    }
                });
                
                // --- SỰ KIỆN NÚT "THÊM GHI CHÚ" (Tương tự, nhưng đơn giản hơn) ---
                $('.add-note-btn').on('click', function() {
                    var button = $(this);
                    var taskId = button.data('task-id');
                    var currentNote = button.data('current-note') || '';
                    
                    var ajaxRequest = {
                        url: scriptConfig.addNoteUrl,
                        method: 'POST',
                        data: {
                            task_id: taskId,
                            notes: '', // Sẽ được điền sau
                            _token: scriptConfig.csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                var newNote = \$noteModal.data('originalRequest').data.notes;
                                button.data('current-note', newNote);
                                if (newNote) {
                                    button.html('<i class="fa fa-comment"></i> Đã có ghi chú');
                                } else {
                                    button.html('<i class="fa fa-comment-o"></i> Thêm ghi chú');
                                }
                                toastr.success('Đã cập nhật ghi chú!');
                            } else {
                                toastr.error(response.message || 'Có lỗi xảy ra!');
                            }
                        },
                        error: function(xhr) { /* ... */ }
                    };
                    
                    $('#modal-task-id').val(taskId);
                    $('#modal-task-notes').val(currentNote).focus();
                    \$noteModal.data('originalRequest', ajaxRequest);
                    \$noteModal.modal('show');
                });

                // --- Hàm cập nhật thanh tiến trình (giữ nguyên) ---
                function updateProgressBar() {
                    var total = $('.task-checkbox').length;
                    var completed = $('.task-checkbox:checked').length;
                    var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                    
                    $('.progress-bar').css('width', percentage + '%');
                    $('.progress-text').text(completed + '/' + total + ' công việc hoàn thành');
                    
                    $('.progress-bar').removeClass('progress-bar-danger progress-bar-warning progress-bar-success');
                    if (percentage < 30) {
                        $('.progress-bar').addClass('progress-bar-danger');
                    } else if (percentage < 70) {
                        $('.progress-bar').addClass('progress-bar-warning');
                    } else {
                        $('.progress-bar').addClass('progress-bar-success');
                    }
                }
                
                // Cập nhật thanh tiến trình lần đầu khi tải trang
                updateProgressBar();
            });

        SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());

        $user = Admin::user();
        $today = Carbon::today();
        
        $userRoles = $user && $user->roles ? $user->roles->pluck('slug')->toArray() : [];
            
        $tasks = DailyTask::with(['category', 'completions' => function($query) use ($user, $today) {
            $query->where('user_id', $user->id)->where('completion_date', $today);
        }])
        ->where('is_active', 1)
        ->where(function($query) use ($today) {
            $query->where(function($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })->where(function($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
        })
        ->orderBy('sort_order')
        ->orderBy('priority', 'desc')
        ->get()
        ->filter(function($task) use ($user, $userRoles) {
            return $task->isActiveToday() && $task->isAssignedToUser($user->id, $userRoles);
        });

        $groupedTasks = $tasks->groupBy(function($task) {
            // Gom nhóm theo tên category, nếu không có thì cho vào nhóm "Chung"
            return optional($task->category)->name ?? 'Công việc chung';
        });

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
        })->count();
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $data = [
            'groupedTasks' => $groupedTasks,
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'today' => $today
        ];
        
        return view($this->view, $data)->render();
    }
}