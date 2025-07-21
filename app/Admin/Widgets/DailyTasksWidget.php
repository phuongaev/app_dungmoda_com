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
        $config = [
            'toggleUrl' => admin_url('daily-tasks/ajax/toggle-completion'),
            'addNoteUrl' => admin_url('daily-tasks/ajax/add-note'),
            'csrfToken' => csrf_token()
        ];

        return <<<SCRIPT
$(function() {
    const config = {$this->jsonEncode($config)};
    
    // Toggle task completion
    $('.task-checkbox').on('change', function() {
        const taskId = $(this).data('task-id');
        const isCompleted = $(this).prop('checked');
        const checkbox = $(this);
        const taskRow = checkbox.closest('.task-item');
        
        $.ajax({
            url: config.toggleUrl,
            method: 'POST',
            data: {
                task_id: taskId,
                completed: isCompleted,
                _token: config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    taskRow.toggleClass('task-completed', isCompleted);
                    updateProgressBar();
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    checkbox.prop('checked', !isCompleted);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Có lỗi xảy ra!');
                    }
                }
            },
            error: function() {
                checkbox.prop('checked', !isCompleted);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Có lỗi kết nối!');
                }
            }
        });
    });

    // Note modal handlers
    $('.add-note-btn').on('click', function(e) {
        e.preventDefault();
        const taskId = $(this).data('task-id');
        const currentNote = $(this).data('current-note') || '';
        
        $('#modal-task-id').val(taskId);
        $('#modal-task-notes').val(currentNote);
        $('#task-note-modal').modal('show');
    });

    $('#save-task-note').on('click', function() {
        const taskId = $('#modal-task-id').val();
        const notes = $('#modal-task-notes').val();
        
        $.ajax({
            url: config.addNoteUrl,
            method: 'POST',
            data: {
                task_id: taskId,
                notes: notes,
                _token: config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $('#task-note-modal').modal('hide');
                    updateNoteIcon(taskId, notes);
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Đã lưu ghi chú!');
                    }
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Có lỗi kết nối!');
                }
            }
        });
    });

    // Focus mode toggle
    $('#focus-mode-toggle').on('change', function() {
        $('.task-completed').toggle(!this.checked);
    });

    // Update progress bar
    function updateProgressBar() {
        const total = $('.task-checkbox').length;
        const completed = $('.task-checkbox:checked').length;
        const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
        
        $('.progress-bar').css('width', percentage + '%')
            .removeClass('progress-bar-danger progress-bar-warning progress-bar-success')
            .addClass(percentage < 30 ? 'progress-bar-danger' : 
                     percentage < 70 ? 'progress-bar-warning' : 'progress-bar-success');
        
        $('.progress-text').text(completed + '/' + total);
    }

    // Update note icon
    function updateNoteIcon(taskId, notes) {
        const noteBtn = $('.add-note-btn[data-task-id="' + taskId + '"]');
        const icon = noteBtn.find('i');
        
        noteBtn.attr('data-current-note', notes);
        if (notes.trim()) {
            icon.removeClass('fa-comment-o').addClass('fa-comment');
            noteBtn.attr('title', 'Ghi chú: ' + notes.substring(0, 50) + '...');
        } else {
            icon.removeClass('fa-comment').addClass('fa-comment-o');
            noteBtn.attr('title', 'Thêm ghi chú');
        }
    }

    updateProgressBar();
    $('[data-toggle="tooltip"]').tooltip();
});
SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());

        $user = Admin::user();
        if (!$user) {
            return $this->renderEmpty();
        }

        $data = $this->getUserTasks($user);
        return view($this->view, $data)->render();
    }

    private function getUserTasks($user)
    {
        $today = Carbon::today();
        $userId = $user->id;
        $userRoles = $user->roles->pluck('slug')->toArray();

        // Optimized query - load tasks with completions in one query
        $tasks = DailyTask::with(['category', 'completions' => function($query) use ($userId, $today) {
                $query->where('user_id', $userId)->where('completion_date', $today->format('Y-m-d'));
            }])
            ->where('is_active', 1)
            ->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->get()
            ->filter(function($task) use ($user, $userRoles, $today) {
                return $this->isTaskAvailable($task, $user->id, $userRoles, $today);
            });

        // Add review tasks (one-time only)
        $reviewTasks = $this->getReviewTasks($userId, $userRoles);
        $allTasks = $tasks->merge($reviewTasks)->unique('id');

        // Group by category
        $groupedTasks = $allTasks->groupBy(function($task) {
            return optional($task->category)->name ?? 'Công việc chung';
        });

        // Calculate stats
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->filter(function($task) {
            $completion = $task->completions->first();
            return $completion && 
                   $completion->status === 'completed' && 
                   !$completion->review_status;
        })->count();

        return [
            'groupedTasks' => $groupedTasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
            'today' => $today
        ];
    }

    private function isTaskAvailable($task, $userId, $userRoles, $date)
    {
        // Check assignment
        if (!$task->isAssignedToUser($userId, $userRoles)) {
            return false;
        }

        // Check if active on date
        if (!$task->isActiveOnDate($date)) {
            return false;
        }

        // Special logic for one-time completed tasks
        if ($task->task_type === 'one_time') {
            $completion = $task->completions->first();
            if ($completion && 
                $completion->status === 'completed' && 
                !$completion->review_status) {
                // Only show on completion date
                return $completion->completion_date->isSameDay($date);
            }
        }

        return true;
    }

    private function getReviewTasks($userId, $userRoles)
    {
        return DailyTask::with(['category', 'completions' => function($query) use ($userId) {
                $query->where('user_id', $userId)->where('review_status', 1);
            }])
            ->where('is_active', 1)
            ->where('task_type', 'one_time')
            ->get()
            ->filter(function($task) use ($userId, $userRoles) {
                return $task->isAssignedToUser($userId, $userRoles) && 
                       $task->completions->isNotEmpty() &&
                       $task->completions->first()->review_status == 1;
            });
    }

    private function renderEmpty()
    {
        return view($this->view, [
            'groupedTasks' => collect(),
            'totalTasks' => 0,
            'completedTasks' => 0,
            'completionRate' => 0,
            'today' => Carbon::today()
        ])->render();
    }

    private function jsonEncode($data)
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}