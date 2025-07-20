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
        $toggleUrl = admin_url('daily-tasks/ajax/toggle-completion');
        $addNoteUrl = admin_url('daily-tasks/ajax/add-note');
        
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
                    var \$triggerCheckbox = \$(this).data('triggerCheckbox');
                    
                    // Nếu modal đóng mà chưa save => hoàn tác checkbox
                    if (!modalSaved && \$triggerCheckbox) {
                        var currentState = \$triggerCheckbox.prop('checked');
                        \$triggerCheckbox.prop('checked', !currentState);
                    }
                    
                    // Clean up
                    \$(this).removeData('triggerCheckbox');
                    \$(this).removeData('originalRequest');
                });

                // --- CLICK CHỨC NĂNG SAVE NOTE TRONG MODAL ---
                $('#save-task-note').on('click', function() {
                    var taskId = $('#modal-task-id').val();
                    var notes = $('#modal-task-notes').val();
                    
                    $.ajax({
                        url: scriptConfig.addNoteUrl,
                        method: 'POST',
                        data: {
                            task_id: taskId,
                            notes: notes,
                            _token: scriptConfig.csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                modalSaved = true; // Đánh dấu đã save
                                \$noteModal.modal('hide');
                                
                                // Thực hiện request gốc từ checkbox
                                var originalRequest = \$noteModal.data('originalRequest');
                                if (originalRequest) {
                                    \$.ajax(originalRequest);
                                }
                                
                                toastr.success('Đã lưu ghi chú!');
                            } else {
                                toastr.error(response.message || 'Có lỗi xảy ra!');
                            }
                        },
                        error: function(xhr) { 
                            console.log('Error:', xhr.responseText);
                            toastr.error('Có lỗi kết nối!');
                        }
                    });
                });

                // --- CLICK TASK CHECKBOX ---
                $('.task-checkbox').on('change', function() {
                    var taskId = $(this).data('task-id');
                    var isCompleted = $(this).prop('checked');
                    var taskRow = $(this).closest('.task-item');
                    var checkbox = $(this);
                    
                    // Nếu bỏ check và task chưa có note => hiện modal
                    if (!isCompleted) {
                        var hasNote = taskRow.find('.note-link').length > 0;
                        if (!hasNote) {
                            // Lưu reference để có thể revert
                            \$noteModal.data('triggerCheckbox', checkbox);
                            
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
                                        taskRow.removeClass('task-completed');
                                        taskRow.find('.completion-time').text('');
                                        updateProgressBar();
                                        toastr.success(response.message);
                                        
                                        // Reload trang sau 1 giây để cập nhật trạng thái
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 1000);
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
                            
                            $('#modal-task-id').val(taskId);
                            $('#modal-task-notes').val('').focus();
                            \$noteModal.data('originalRequest', ajaxRequest);
                            \$noteModal.modal('show');
                            return;
                        }
                    }

                    // Thực hiện toggle completion thông thường
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
                                    taskRow.removeClass('needs-review');
                                    taskRow.find('.completion-time').text('Hoàn thành lúc: ' + response.completion_time);
                                } else {
                                    taskRow.removeClass('task-completed');
                                    taskRow.find('.completion-time').text('');
                                }
                                updateProgressBar();
                                toastr.success(response.message);
                                
                                // Reload trang sau 1 giây để cập nhật trạng thái
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
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

                    $.ajax(ajaxRequest);
                });

                // Focus mode toggle
                $('#focus-mode-toggle').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.task-completed').fadeOut();
                    } else {
                        $('.task-completed').fadeIn();
                    }
                });

                // --- Hàm cập nhật thanh tiến trình ---
                function updateProgressBar() {
                    var total = $('.task-checkbox').length;
                    var completed = $('.task-checkbox:checked').length;
                    var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                    
                    $('.progress-bar').css('width', percentage + '%');
                    $('.progress-text').text(completed + '/' + total);
                    
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
        if (!$user) {
            return view($this->view, [
                'groupedTasks' => collect(),
                'tasks' => collect(),
                'reviewTasks' => collect(),
                'totalTasks' => 0,
                'completedTasks' => 0,
                'completionRate' => 0,
                'recurringTasks' => 0,
                'oneTimeTasks' => 0,
                'overdueTasks' => 0,
                'reviewTasksCount' => 0,
                'today' => Carbon::today()
            ])->render();
        }

        $today = Carbon::today();
        $userRoles = $user->roles ? $user->roles->pluck('slug')->toArray() : [];
        $userId = $user->id;
            
        // Lấy tất cả tasks assigned cho user (load all completion data)
        $allAssignedTasks = DailyTask::with(['category', 'completions' => function($query) use ($userId) {
            $query->where('user_id', $userId); // Load tất cả completion của user, không filter ngày
        }])
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->orderBy('priority', 'desc')
        ->get()
        ->filter(function($task) use ($user, $userRoles) {
            return $task->isAssignedToUser($user->id, $userRoles);
        });

        // Filter tasks theo logic mới
        $tasks = $allAssignedTasks->filter(function($task) use ($userId, $today) {
            if ($task->task_type === 'recurring') {
                // Recurring tasks: chỉ hiển thị nếu active hôm nay
                return $task->isActiveOnDate($today);
            } 
            
            if ($task->task_type === 'one_time') {
                // One-time tasks: logic chi tiết
                
                // 1. Nếu task cần review: luôn hiển thị
                if ($task->needsReviewBy($userId)) {
                    return true;
                }
                
                // 2. Check xem đã hoàn thành chưa (bất kỳ ngày nào)
                $isCompleted = $task->isCompletedBy($userId);
                
                // 3. Nếu trong khoảng active (start_date ≤ today ≤ end_date)
                if ($task->isActiveOnDate($today)) {
                    // Nếu đã hoàn thành: chỉ hiển thị ở ngày hoàn thành
                    if ($isCompleted) {
                        $completionDate = $task->completions()
                            ->where('user_id', $userId)
                            ->where('status', 'completed')
                            ->where(function($q) {
                                $q->where('review_status', 0)->orWhereNull('review_status');
                            })
                            ->first();
                        
                        // Chỉ hiển thị nếu completion_date = today
                        return $completionDate && $completionDate->completion_date->isSameDay($today);
                    }
                    
                    // Nếu chưa hoàn thành: hiển thị bình thường
                    return true;
                }
                
                // 4. Nếu quá deadline: chỉ hiển thị nếu chưa hoàn thành
                if ($task->isOverdue($today)) {
                    return !$isCompleted;
                }
                
                return false;
            }
            
            return false;
        });

        // Load completion data phù hợp cho từng loại task để hiển thị trong view
        $tasks = $tasks->map(function($task) use ($userId, $today) {
            if ($task->task_type === 'recurring') {
                // Recurring task: query lại completion cho hôm nay để chắc chắn
                $relevantCompletion = UserTaskCompletion::where('daily_task_id', $task->id)
                    ->where('user_id', $userId)
                    ->where('completion_date', $today->format('Y-m-d'))
                    ->first();
            } else {
                // One-time task: load completion mới nhất (để hiển thị trạng thái)
                $relevantCompletion = $task->completions->sortByDesc('created_at')->first();
            }
            
            // Set completion data để blade view sử dụng
            $task->setRelation('completions', $relevantCompletion ? collect([$relevantCompletion]) : collect([]));
            
            return $task;
        });

        // Không cần lấy thêm reviewTasks nữa vì đã bao gồm trong logic filter trên
        $allTasks = $tasks;

        // Group tasks theo category 
        $groupedTasks = $allTasks->groupBy(function($task) {
            $categoryName = optional($task->category)->name ?? 'Công việc chung';
            $taskTypeLabel = $task->task_type === 'one_time' ? ' (Một lần)' : '';
            return $categoryName . $taskTypeLabel;
        });

        // Tính toán thống kê - dựa trên completion hôm nay (chỉ cho recurring) hoặc trạng thái task (cho one-time)
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->filter(function($task) use ($userId, $today) {
            if ($task->task_type === 'recurring') {
                // Recurring: query lại completion hôm nay để chắc chắn
                $todayCompletion = UserTaskCompletion::where('daily_task_id', $task->id)
                    ->where('user_id', $userId)
                    ->where('completion_date', $today->format('Y-m-d'))
                    ->where('status', 'completed')
                    ->where(function($q) {
                        $q->where('review_status', 0)->orWhereNull('review_status');
                    })
                    ->exists();
                return $todayCompletion;
            } else {
                // One-time: check trạng thái hoàn thành tổng thể
                return $task->isCompletedBy($userId);
            }
        })->count();
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Thống kê theo loại task (chỉ đếm tasks được hiển thị)
        $recurringTasks = $allTasks->where('task_type', 'recurring')->count();
        $oneTimeTasks = $allTasks->where('task_type', 'one_time')->count();
        
        // Đếm tasks cần review (dựa trên completion hiển thị)
        $reviewTasksCount = $allTasks->filter(function($task) {
            $completion = $task->completions->first(); // Completion hiển thị
            return $completion && 
                   $completion->review_status == 1 && 
                   $completion->status == 'in_process';
        })->count();

        // Đếm tasks đang trong quá trình thực hiện (dựa trên completion hiển thị)
        $inProcessTasks = $allTasks->filter(function($task) {
            $completion = $task->completions->first(); // Completion hiển thị
            return $completion && 
                   $completion->status == 'in_process' && 
                   (!$completion->review_status || $completion->review_status == 0);
        })->count();

        // Đếm tasks quá hạn (one-time tasks đã quá deadline nhưng chưa hoàn thành)
        $overdueTasks = $allTasks->filter(function($task) use ($today, $userId) {
            return $task->task_type === 'one_time' && 
                   $task->isOverdue($today) && 
                   !$task->isCompletedBy($userId); // Check completion tổng thể, không chỉ hôm nay
        })->count();

        $data = [
            'groupedTasks' => $groupedTasks,
            'tasks' => $allTasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'recurringTasks' => $recurringTasks,
            'oneTimeTasks' => $oneTimeTasks,
            'reviewTasksCount' => $reviewTasksCount,
            'inProcessTasks' => $inProcessTasks,
            'overdueTasks' => $overdueTasks,
            'today' => $today
        ];
        
        return view($this->view, $data)->render();
    }
}