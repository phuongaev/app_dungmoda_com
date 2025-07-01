/**
 * Daily Tasks Widget JavaScript
 * Handles task completion, notes, and UI interactions
 */

$(function() {
    // Configuration
    var DailyTasksWidget = {
        config: {
            toggleUrl: window.dailyTasksConfig?.toggleUrl || '/admin/daily-tasks/toggle-completion',
            addNoteUrl: window.dailyTasksConfig?.addNoteUrl || '/admin/daily-tasks/add-note',
            csrfToken: window.dailyTasksConfig?.csrfToken || $('meta[name="csrf-token"]').attr('content')
        },
        
        init: function() {
            console.log('Daily Tasks Widget initialized');
            console.log('Config:', this.config);
            
            this.bindEvents();
            this.updateProgressBar();
        },
        
        bindEvents: function() {
            // Toggle completed tasks
            $(document).on('click', '#toggle-completed-tasks', this.toggleCompletedTasks);
            
            // Checkbox change event
            $(document).on('change', '.task-checkbox', this.handleCheckboxChange.bind(this));
            
            // Note button click
            $(document).on('click', '.btn-note', this.handleNoteButtonClick.bind(this));
            
            // Save note button
            $(document).on('click', '#save-task-note', this.handleSaveNote.bind(this));
        },
        
        toggleCompletedTasks: function() {
            var button = $(this);
            var icon = button.find('i');
            var text = button.find('.toggle-text');
            var container = $('.tasks-container');
            
            if (container.hasClass('hide-completed')) {
                // Show completed tasks
                container.removeClass('hide-completed');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
                text.text('Ẩn hoàn thành');
                button.attr('title', 'Ẩn task đã hoàn thành');
                
                console.log('Showing completed tasks');
            } else {
                // Hide completed tasks
                container.addClass('hide-completed');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
                text.text('Hiện hoàn thành');
                button.attr('title', 'Hiện task đã hoàn thành');
                
                console.log('Hiding completed tasks');
            }
            
            // Update tooltip if available
            if (button.tooltip) {
                button.tooltip('fixTitle');
            }
        },
        
        handleCheckboxChange: function(e) {
            console.log('Checkbox changed');
            
            var checkbox = $(e.target);
            var taskId = checkbox.data('task-id');
            var isCompleted = checkbox.is(':checked');
            var taskCard = checkbox.closest('.task-card');
            
            console.log('Task ID:', taskId, 'Completed:', isCompleted);
            
            // Prepare AJAX request
            var ajaxRequest = {
                url: this.config.toggleUrl,
                method: 'POST',
                data: {
                    task_id: taskId,
                    completed: isCompleted,
                    notes: '', // Will be filled later if needed
                    _token: this.config.csrfToken
                },
                success: function(response) {
                    console.log('Toggle success:', response);
                    
                    if (response.success) {
                        if (isCompleted) {
                            taskCard.addClass('completed');
                            // Update completion time
                            var completedTime = taskCard.find('.meta-item.completed-time');
                            if (completedTime.length) {
                                completedTime.html('<i class="fa fa-check-circle"></i> ' + response.completion_time);
                            } else {
                                taskCard.find('.task-meta').append(
                                    '<span class="meta-item completed-time">' +
                                    '<i class="fa fa-check-circle"></i> ' + response.completion_time +
                                    '</span>'
                                );
                            }
                        } else {
                            taskCard.removeClass('completed');
                            taskCard.find('.meta-item.completed-time').remove();
                        }
                        DailyTasksWidget.updateProgressBar();
                        DailyTasksWidget.showMessage(response.message, 'success');
                    } else {
                        checkbox.prop('checked', !isCompleted);
                        DailyTasksWidget.showMessage(response.message || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('Toggle error:', xhr.responseText);
                    checkbox.prop('checked', !isCompleted);
                    DailyTasksWidget.showMessage('Có lỗi kết nối!', 'error');
                }
            };

            if (isCompleted) {
                // If checking 'complete', open modal to ask for notes
                $('#modal-task-id').val(taskId);
                $('#modal-task-notes').val('').focus();
                $('#task-note-modal').data('originalRequest', ajaxRequest);
                $('#task-note-modal').modal('show');
            } else {
                // If unchecking, execute AJAX immediately
                $.ajax(ajaxRequest);
            }
        },
        
        handleNoteButtonClick: function(e) {
            e.preventDefault();
            console.log('Note button clicked');
            
            var button = $(e.target).closest('.btn-note');
            var taskId = button.data('task-id');
            var currentNote = button.data('current-note') || '';
            
            console.log('Task ID:', taskId, 'Current note:', currentNote);
            
            // Show modal with current note
            $('#modal-task-id').val(taskId);
            $('#modal-task-notes').val(currentNote).focus();
            
            // Prepare request for note update only
            var ajaxRequest = {
                url: this.config.addNoteUrl,
                method: 'POST',
                data: {
                    task_id: taskId,
                    notes: '', // Will be filled later
                    _token: this.config.csrfToken
                },
                success: function(response) {
                    console.log('Note update success:', response);
                    
                    if (response.success) {
                        // Update data attribute
                        var newNote = $('#modal-task-notes').val();
                        button.data('current-note', newNote);
                        
                        // Update icon and class
                        if (newNote.trim()) {
                            button.addClass('has-note');
                            button.html('<i class="fa fa-comment"></i>');
                        } else {
                            button.removeClass('has-note');
                            button.html('<i class="fa fa-comment-o"></i>');
                        }
                        
                        DailyTasksWidget.showMessage('Đã cập nhật ghi chú!', 'success');
                    } else {
                        DailyTasksWidget.showMessage(response.message || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('Note update error:', xhr.responseText);
                    DailyTasksWidget.showMessage('Có lỗi kết nối!', 'error');
                }
            };
            
            $('#task-note-modal').data('originalRequest', ajaxRequest);
            $('#task-note-modal').modal('show');
        },
        
        handleSaveNote: function(e) {
            console.log('Save note clicked');
            
            var taskId = $('#modal-task-id').val();
            var notes = $('#modal-task-notes').val();
            var originalRequest = $('#task-note-modal').data('originalRequest');
            
            console.log('Saving note for task:', taskId, 'Notes:', notes);
            
            $('#task-note-modal').modal('hide');

            if (originalRequest) {
                originalRequest.data.notes = notes;
                $.ajax(originalRequest);
            }
        },
        
        updateProgressBar: function() {
            var totalTasks = $('.task-card').length;
            var completedTasks = $('.task-card.completed').length;
            var completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
            
            console.log('Updating progress:', completedTasks + '/' + totalTasks + ' = ' + completionRate + '%');
            
            // Update progress bar
            $('.progress-bar').css('width', completionRate + '%').attr('data-completion', completionRate);
            $('.progress-text').text(completionRate + '%');
            
            // Update stats cards
            $('.stat-card.completed .stat-number').text(completedTasks);
            $('.stat-card.pending .stat-number').text(totalTasks - completedTasks);
            $('.stat-card.percentage .stat-number').text(completionRate + '%');
            
            // Update progress badge in header
            $('.progress-badge').text(completedTasks + '/' + totalTasks);
            
            // Update achievement badge
            var achievementBadge = $('.achievement-badge');
            achievementBadge.removeClass('gold silver bronze warning danger');
            
            if (completionRate === 100) {
                achievementBadge.addClass('gold').html('<i class="fa fa-trophy"></i> Hoàn thành xuất sắc!');
            } else if (completionRate >= 80) {
                achievementBadge.addClass('silver').html('<i class="fa fa-star"></i> Tiến độ rất tốt!');
            } else if (completionRate >= 60) {
                achievementBadge.addClass('bronze').html('<i class="fa fa-thumbs-up"></i> Tiến độ tốt!');
            } else if (completionRate >= 30) {
                achievementBadge.addClass('warning').html('<i class="fa fa-clock-o"></i> Cần cố gắng thêm!');
            } else {
                achievementBadge.addClass('danger').html('<i class="fa fa-exclamation-triangle"></i> Cần hoàn thành gấp!');
            }
            
            // Update progress bar color
            $('.progress-bar').removeClass('progress-bar-danger progress-bar-warning progress-bar-success');
            if (completionRate < 30) {
                $('.progress-bar').addClass('progress-bar-danger');
            } else if (completionRate < 70) {
                $('.progress-bar').addClass('progress-bar-warning');
            } else {
                $('.progress-bar').addClass('progress-bar-success');
            }
        },
        
        showMessage: function(message, type) {
            if (typeof toastr !== 'undefined') {
                if (type === 'success') {
                    toastr.success(message);
                } else {
                    toastr.error(message);
                }
            } else {
                alert(message);
            }
        }
    };
    
    // Initialize widget
    DailyTasksWidget.init();
    
    // Make it globally accessible for debugging
    window.DailyTasksWidget = DailyTasksWidget;
});