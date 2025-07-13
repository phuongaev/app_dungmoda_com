// public/vendor/laravel-admin/js/shift-calendar.js

(function() {
    'use strict';
    
    // Global variables
    let calendar = null;
    let userColors = {};
    let isInitialized = false;
    
    // Configuration
    const COLORS = [
        '#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', 
        '#34495e', '#1abc9c', '#e67e22', '#d35400', '#c0392b'
    ];

    /**
     * Load external CSS file
     */
    function loadCss(url) {
        if (!document.querySelector(`link[href="${url}"]`)) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            document.head.appendChild(link);
        }
    }

    /**
     * Load external JavaScript file
     */
    function loadScript(url, callback) {
        const existingScript = document.querySelector(`script[src="${url}"]`);
        if (existingScript) {
            if (callback) callback();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.onload = callback || function() {};
        script.onerror = function() {
            console.error('Failed to load script:', url);
        };
        document.head.appendChild(script);
    }

    /**
     * Get container element and configuration
     */
    function getContainerConfig() {
        const container = document.querySelector('.shift-calendar-container');
        if (!container) return null;

        return {
            container: container,
            isAdmin: container.dataset.isAdmin === 'true',
            eventsUrl: container.dataset.eventsUrl,
            updateUrl: container.dataset.updateUrl,
            swapUrl: container.dataset.swapUrl,
            changePersonUrl: container.dataset.changePersonUrl,
            availableUsersUrl: container.dataset.availableUsersUrl,
            availableShiftsUrl: container.dataset.availableShiftsUrl,
            csrfToken: container.dataset.csrfToken
        };
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        if (window.toastr) {
            window.toastr[type](message);
        } else if (window.swal) {
            window.swal({
                title: type === 'success' ? 'Thành công' : 'Lỗi',
                text: message,
                type: type === 'success' ? 'success' : 'error'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Update staff legend
     */
    function updateStaffLegend(events) {
        const legendElement = document.getElementById('staff-legend');
        if (!legendElement) return;

        const users = new Map();
        
        // Process events to extract unique users with their colors
        events.forEach(event => {
            if (event.extendedProps && event.extendedProps.userId) {
                const userId = event.extendedProps.userId;
                const userName = event.extendedProps.userName || event.title;
                const color = event.extendedProps.userColor || event.color; // Use explicit userColor first
                
                if (!users.has(userId)) {
                    users.set(userId, { name: userName, color: color });
                }
            }
        });

        // Generate legend HTML
        let legendHtml = '';
        if (users.size === 0) {
            legendHtml = '<li><em>Chưa có ca trực nào</em></li>';
        } else {
            users.forEach((user, userId) => {
                const safeColor = user.color || '#cccccc'; // Fallback color
                legendHtml += `
                    <li>
                        <div class="legend-color-box" style="background-color: ${safeColor}"></div>
                        <span class="legend-user-name">${user.name}</span>
                    </li>
                `;
            });
        }

        legendElement.innerHTML = legendHtml;
    }

    /**
     * Load available users for person change
     */
    function loadAvailableUsers(config) {
        const userSelect = document.getElementById('newPersonSelect');
        if (!userSelect) return;

        // Clear existing options
        userSelect.innerHTML = '<option value="">-- Đang tải... --</option>';

        fetch(config.availableUsersUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            userSelect.innerHTML = '<option value="">-- Chọn nhân viên --</option>';
            
            if (data.status === 'success' && data.data) {
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    userSelect.appendChild(option);
                });
            }

            // Reinitialize Select2 if available
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(userSelect).select2({
                    dropdownParent: window.jQuery('#manageShiftModal')
                });
            }
        })
        .catch(error => {
            console.error('Error loading available users:', error);
            userSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
        });
    }

    /**
     * Load available shifts for swap modal
     */
    function loadAvailableShifts(excludeId, config) {
        const targetSelect = document.getElementById('targetShiftSelect');
        if (!targetSelect) return;

        // Clear existing options
        targetSelect.innerHTML = '<option value="">-- Đang tải... --</option>';

        fetch(`${config.availableShiftsUrl}?exclude_id=${excludeId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            targetSelect.innerHTML = '<option value="">-- Chọn ca trực để hoán đổi --</option>';
            
            if (data.status === 'success' && data.data) {
                data.data.forEach(shift => {
                    const option = document.createElement('option');
                    option.value = shift.id;
                    option.textContent = shift.text;
                    targetSelect.appendChild(option);
                });
            }

            // Reinitialize Select2 if available
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(targetSelect).select2({
                    dropdownParent: window.jQuery('#manageShiftModal')
                });
            }
        })
        .catch(error => {
            console.error('Error loading available shifts:', error);
            targetSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
        });
    }

    /**
     * Handle change person confirmation
     */
    function handleChangePersonConfirmation(config) {
        const confirmBtn = document.getElementById('confirmChangePersonBtn');
        const userSelect = document.getElementById('newPersonSelect');
        
        if (!confirmBtn || !userSelect) return;

        const shiftId = confirmBtn.dataset.shiftId;
        const newUserId = userSelect.value;

        if (!newUserId) {
            showToast('warning', 'Vui lòng chọn nhân viên.');
            return;
        }

        // Show loading state
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(config.changePersonUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                shift_id: shiftId,
                new_user_id: newUserId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('success', data.message);
                
                // Hide modal
                if (window.jQuery) {
                    window.jQuery('#manageShiftModal').modal('hide');
                }
                
                // Refresh calendar (this will trigger eventsSet and update legend)
                if (calendar) {
                    calendar.refetchEvents();
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(error => {
            console.error('Change person error:', error);
            showToast('error', 'Lỗi kết nối. Không thể thay đổi người trực.');
        })
        .finally(() => {
            // Reset button state
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-save"></i> Thay đổi người trực';
        });
    }

    /**
     * Handle swap confirmation
     */
    function handleSwapConfirmation(config) {
        const confirmBtn = document.getElementById('confirmSwapBtn');
        const targetSelect = document.getElementById('targetShiftSelect');
        
        if (!confirmBtn || !targetSelect) return;

        const sourceId = confirmBtn.dataset.sourceId;
        const targetId = targetSelect.value;

        if (!targetId) {
            showToast('warning', 'Vui lòng chọn một ca trực để hoán đổi.');
            return;
        }

        // Show loading state
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(config.swapUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                source_id: sourceId,
                target_id: targetId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('success', data.message);
                
                // Hide modal
                if (window.jQuery) {
                    window.jQuery('#manageShiftModal').modal('hide');
                }
                
                // Refresh calendar (this will trigger eventsSet and update legend)
                if (calendar) {
                    calendar.refetchEvents();
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(error => {
            console.error('Swap error:', error);
            showToast('error', 'Lỗi kết nối. Không thể hoán đổi ca trực.');
        })
        .finally(() => {
            // Reset button state
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-exchange"></i> Xác nhận hoán đổi';
        });
    }

    /**
     * Initialize FullCalendar
     */
    function initializeCalendar(config) {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // Destroy existing calendar if exists
        if (calendar) {
            calendar.destroy();
            calendar = null;
        }

        // Create new calendar instance
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'vi',
            timeZone: 'Asia/Ho_Chi_Minh',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            
            // Event source
            events: {
                url: config.eventsUrl,
                failure: function() {
                    showToast('error', 'Lỗi khi tải dữ liệu lịch.');
                }
            },

            // Event rendering
            eventDidMount: function(info) {
                // Add tooltip
                if (info.event.title && window.jQuery) {
                    window.jQuery(info.el).tooltip({
                        title: info.event.title + ' - ' + (info.event.extendedProps.shiftDate || ''),
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },

            // Events loaded callback - Update legend when events are loaded/changed
            eventsSet: function(events) {
                updateStaffLegend(events);
            },

            // Events loaded callback - Also handle when events change
            eventChange: function() {
                calendar.refetchEvents();
            },

            // Event interactions
            eventClick: function(info) {
                if (!config.isAdmin) return;

                info.jsEvent.preventDefault();
                
                const event = info.event;
                const currentShiftInfo = document.getElementById('currentShiftInfo');
                const confirmSwapBtn = document.getElementById('confirmSwapBtn');
                const confirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');

                if (currentShiftInfo && confirmSwapBtn && confirmChangePersonBtn) {
                    // Update current shift info
                    currentShiftInfo.innerHTML = `
                        <strong>${event.extendedProps.userName}</strong><br>
                        <small>Ngày: ${event.extendedProps.shiftDate || event.start.toLocaleDateString('vi-VN')}</small>
                    `;
                    
                    // Set shift ID for both buttons
                    confirmSwapBtn.dataset.sourceId = event.id;
                    confirmChangePersonBtn.dataset.shiftId = event.id;
                    
                    // Load data for both tabs
                    loadAvailableUsers(config);
                    loadAvailableShifts(event.id, config);
                    
                    // Show modal
                    if (window.jQuery) {
                        window.jQuery('#manageShiftModal').modal('show');
                    }
                }
            },

            // Drag and drop (admin only)
            editable: config.isAdmin,
            eventDrop: function(info) {
                if (!config.isAdmin) {
                    info.revert();
                    return;
                }

                const newDate = info.event.start.toISOString().split('T')[0];
                
                // Không cần confirm vì cho phép nhiều người cùng ngày
                fetch(config.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        id: info.event.id,
                        date: newDate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('success', data.message);
                    } else {
                        showToast('error', data.message || 'Có lỗi xảy ra.');
                        info.revert();
                    }
                })
                .catch(error => {
                    console.error('Update error:', error);
                    showToast('error', 'Lỗi kết nối. Không thể cập nhật.');
                    info.revert();
                });
            }
        });

        calendar.render();
    }

    /**
     * Initialize modal event handlers
     */
    function initializeModalHandlers(config) {
        // Handle change person button
        const confirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');
        if (confirmChangePersonBtn) {
            // Remove existing event listeners
            confirmChangePersonBtn.replaceWith(confirmChangePersonBtn.cloneNode(true));
            const newConfirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');
            
            newConfirmChangePersonBtn.addEventListener('click', function() {
                handleChangePersonConfirmation(config);
            });
        }

        // Handle swap button
        const confirmSwapBtn = document.getElementById('confirmSwapBtn');
        if (confirmSwapBtn) {
            // Remove existing event listeners
            confirmSwapBtn.replaceWith(confirmSwapBtn.cloneNode(true));
            const newConfirmSwapBtn = document.getElementById('confirmSwapBtn');
            
            newConfirmSwapBtn.addEventListener('click', function() {
                handleSwapConfirmation(config);
            });
        }

        // Initialize Select2 when modal is shown
        if (window.jQuery) {
            window.jQuery('#manageShiftModal').off('shown.bs.modal').on('shown.bs.modal', function() {
                const userSelect = document.getElementById('newPersonSelect');
                const shiftSelect = document.getElementById('targetShiftSelect');
                
                if (userSelect && window.jQuery.fn.select2) {
                    window.jQuery(userSelect).select2({
                        dropdownParent: window.jQuery('#manageShiftModal')
                    });
                }
                
                if (shiftSelect && window.jQuery.fn.select2) {
                    window.jQuery(shiftSelect).select2({
                        dropdownParent: window.jQuery('#manageShiftModal')
                    });
                }
            });

            // Reset form when modal is hidden
            window.jQuery('#manageShiftModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                // Reset dropdowns
                const userSelect = document.getElementById('newPersonSelect');
                const shiftSelect = document.getElementById('targetShiftSelect');
                
                if (userSelect) {
                    userSelect.selectedIndex = 0;
                    if (window.jQuery.fn.select2) {
                        window.jQuery(userSelect).val(null).trigger('change');
                    }
                }
                
                if (shiftSelect) {
                    shiftSelect.selectedIndex = 0;
                    if (window.jQuery.fn.select2) {
                        window.jQuery(shiftSelect).val(null).trigger('change');
                    }
                }

                // Reset to first tab
                window.jQuery('#changePersonTab').tab('show');
            });
        }
    }

    /**
     * Main initialization function
     */
    function initializeShiftCalendar() {
        const config = getContainerConfig();
        if (!config) return;

        // Prevent multiple initializations
        if (isInitialized) {
            console.log('Shift calendar already initialized, skipping...');
            return;
        }

        // Load FullCalendar CSS and JS
        loadCss('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
        
        loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', function() {
            loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js', function() {
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        initializeCalendar(config);
                        initializeModalHandlers(config);
                        isInitialized = true;
                    });
                } else {
                    initializeCalendar(config);
                    initializeModalHandlers(config);
                    isInitialized = true;
                }
            });
        });
    }

    /**
     * Clean up function for Pjax navigation
     */
    function cleanup() {
        if (calendar) {
            calendar.destroy();
            calendar = null;
        }
        isInitialized = false;
        userColors = {};
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', initializeShiftCalendar);
    
    // Handle Pjax navigation
    if (window.jQuery) {
        window.jQuery(document).on('pjax:start', cleanup);
        window.jQuery(document).on('pjax:end', function() {
            // Small delay to ensure DOM is updated
            setTimeout(initializeShiftCalendar, 100);
        });
    }

    // Fallback for page navigation without Pjax
    window.addEventListener('beforeunload', cleanup);

})();