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
                var scriptConfig = $scriptVars;
                var currentAjaxRequest = null; // Biến để lưu trữ request đang được thực hiện

                // --- Mở Modal và xử lý Ghi chú ---
                $('#task-note-modal').on('click', '#save-task-note', function() {
                    var taskId = $('#modal-task-id').val();
                    var notes = $('#modal-task-notes').val();
                    
                    // Lấy thông tin về request gốc đã được lưu khi mở modal
                    var originalRequest = $('#task-note-modal').data('originalRequest');
                    
                    // Đóng modal
                    $('#task-note-modal').modal('hide');

                    // Gắn ghi chú vào request và thực hiện nó
                    originalRequest.data.notes = notes;
                    currentAjaxRequest = originalRequest;
                    $.ajax(currentAjaxRequest);
                });

                // --- Sự kiện Toggle Checkbox ---
                $('.task-checkbox').on('change', function() {
                    var checkbox = $(this);
                    var taskId = checkbox.data('task-id');
                    var isCompleted = checkbox.is(':checked');
                    var taskRow = checkbox.closest('.task-item');
                    
                    // Chuẩn bị sẵn request AJAX
                    var ajaxRequest = {
                        url: scriptConfig.toggleUrl,
                        method: 'POST',
                        data: {
                            task_id: taskId,
                            completed: isCompleted,
                            notes: '', // Sẽ được điền sau nếu cần
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
                                checkbox.prop('checked', !isCompleted); // Revert nếu lỗi
                                toastr.error(response.message || 'Có lỗi xảy ra!');
                            }
                        },
                        error: function(xhr) {
                            checkbox.prop('checked', !isCompleted); // Revert nếu lỗi
                            console.log('Error:', xhr.responseText);
                            toastr.error('Có lỗi kết nối!');
                        }
                    };

                    if (isCompleted) {
                        // Nếu check 'hoàn thành', mở modal để hỏi ghi chú
                        $('#modal-task-id').val(taskId);
                        $('#modal-task-notes').val('').focus();
                        // Lưu lại request để sau khi modal đóng sẽ thực hiện
                        $('#task-note-modal').data('originalRequest', ajaxRequest);
                        $('#task-note-modal').modal('show');
                    } else {
                        // Nếu bỏ check, thực hiện AJAX ngay lập tức
                        currentAjaxRequest = ajaxRequest;
                        $.ajax(currentAjaxRequest);
                    }
                });
                
                // --- Sự kiện nút "Thêm ghi chú" ---
                $('.add-note-btn').on('click', function() {
                    var button = $(this);
                    var taskId = button.data('task-id');
                    var currentNote = button.data('current-note') || '';
                    
                    // Chuẩn bị sẵn request AJAX
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
                                // Cập nhật lại data và text của nút
                                var newNote = $('#task-note-modal').data('originalRequest').data.notes;
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
                        error: function(xhr) {
                            console.log('Error:', xhr.responseText);
                            toastr.error('Có lỗi kết nối!');
                        }
                    };
                    
                    // Mở modal và truyền ghi chú hiện tại vào
                    $('#modal-task-id').val(taskId);
                    $('#modal-task-notes').val(currentNote).focus();
                    // Lưu lại request
                    $('#task-note-modal').data('originalRequest', ajaxRequest);
                    $('#task-note-modal').modal('show');
                });

                // --- Hàm cập nhật thanh tiến trình (giữ nguyên) ---
                function updateProgressBar() {
                    var total = $('.task-checkbox').length;
                    var completed = $('.task-checkbox:checked').length;
                    var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                    
                    $('.progress-bar').css('width', percentage + '%').text(percentage + '%');
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

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(function($task) {
            return $task->completions->isNotEmpty() && $task->completions->first()->status === 'completed';
        })->count();
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $data = [
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'today' => $today
        ];
        
        return view($this->view, $data)->render();
    }
}