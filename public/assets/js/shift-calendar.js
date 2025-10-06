// public/admin/assets/js/shift-calendar.js

(function() {
    'use strict';
    
    // Global variables
    let calendar = null;
    let userColors = {};
    let isInitialized = false;
    let leaveDataByDate = {}; // NEW: Store leave data by date
    
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
            leaveEventsUrl: container.dataset.eventsUrl.replace('/events', '/leave-events'),
            changeLeavePersonUrl: container.dataset.changeLeavePersonUrl || '/admin/leave-requests/change-person', // NEW
            updateUrl: container.dataset.updateUrl,
            swapUrl: container.dataset.swapUrl,
            changePersonUrl: container.dataset.changePersonUrl,
            createShiftUrl: container.dataset.createShiftUrl,
            deleteUrl: container.dataset.deleteUrl,
            availableUsersUrl: container.dataset.availableUsersUrl,
            availableShiftsUrl: container.dataset.availableShiftsUrl,
            createLeaveUrl: container.dataset.createLeaveUrl,
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
     * NEW: Add leave info to calendar cells
     */
    function addLeaveInfoToCells(config) {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // Find all day cells
        const dayCells = calendarEl.querySelectorAll('.fc-daygrid-day');
        
        dayCells.forEach(function(dayCell) {
            // Get date from data attribute
            const dateAttr = dayCell.getAttribute('data-date');
            if (!dateAttr) return;
            
            const leaveUsers = leaveDataByDate[dateAttr] || [];
            
            if (leaveUsers.length > 0) {
                const dayFrame = dayCell.querySelector('.fc-daygrid-day-frame');
                if (dayFrame) {
                    // Remove existing leave info if any
                    const existingLeaveInfo = dayFrame.querySelector('.leave-info-below');
                    if (existingLeaveInfo) {
                        existingLeaveInfo.remove();
                    }
                    
                    // Create text to display
                    const leaveUsersText = leaveUsers.map(function(user) {
                        return user.name + ' (nghỉ)';
                    }).join(', ');
                    
                    // Create element
                    const leaveInfoEl = document.createElement('div');
                    leaveInfoEl.className = 'leave-info-below';
                    leaveInfoEl.textContent = leaveUsersText;
                    
                    // NEW: Make it clickable for admin
                    if (config.isAdmin) {
                        leaveInfoEl.style.cursor = 'pointer';
                        leaveInfoEl.title = 'Click để thay đổi người nghỉ';
                        
                        leaveInfoEl.addEventListener('click', function(e) {
                            e.stopPropagation();
                            handleLeaveInfoClick(dateAttr, leaveUsers, config);
                        });
                    }
                    
                    // Add to day cell
                    dayFrame.appendChild(leaveInfoEl);
                }
            }
        });
    }
    
    /**
     * NEW: Handle click on leave info
     */
    function handleLeaveInfoClick(date, leaveUsers, config) {
        // Show modal to change leave person
        const modalTitle = document.getElementById('manageShiftModalLabel');
        const currentShiftInfoGroup = document.getElementById('currentShiftInfoGroup');
        const selectedDateInfoGroup = document.getElementById('selectedDateInfoGroup');
        const changeLeavePersonTab = document.getElementById('changeLeavePersonTab');
        
        if (!changeLeavePersonTab) {
            showToast('error', 'Modal chưa được cập nhật. Vui lòng liên hệ admin.');
            return;
        }
        
        // Update modal content
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fa fa-exchange"></i> Thay đổi người nghỉ phép';
        }
        
        if (currentShiftInfoGroup) currentShiftInfoGroup.style.display = 'none';
        if (selectedDateInfoGroup) selectedDateInfoGroup.style.display = 'none';
        
        // Populate leave change form
        populateLeaveChangeForm(date, leaveUsers, config);
        
        // Show modal
        if (window.jQuery) {
            window.jQuery('#manageShiftModal').modal('show');
            window.jQuery('#changeLeavePersonTab').tab('show');
        }
    }
    
    /**
     * NEW: Populate leave change form
     */
    function populateLeaveChangeForm(date, leaveUsers, config) {
        const changeLeaveInfo = document.getElementById('changeLeaveInfo');
        const changeLeaveSelect = document.getElementById('changeLeavePersonSelect');
        const confirmChangeLeaveBtn = document.getElementById('confirmChangeLeavePersonBtn');
        
        if (!changeLeaveInfo || !changeLeaveSelect || !confirmChangeLeaveBtn) return;
        
        // Format date
        const formattedDate = new Date(date).toLocaleDateString('vi-VN');
        
        // Build leave users list HTML with proper styling
        let leaveUsersHtml = '<div class="list-group" style="margin-top: 10px;">';
        leaveUsers.forEach(function(user) {
            leaveUsersHtml += `
                <label class="list-group-item" style="cursor: pointer; margin-bottom: 5px; background-color: #f9f9f9; border: 1px solid #ddd;">
                    <input type="radio" name="selected_leave_user" value="${user.leave_request_id}" data-user-name="${user.name}" style="margin-right: 10px;">
                    <strong style="color: #333;">${user.name}</strong>
                    <small class="text-muted">(ID phiếu: ${user.leave_request_id})</small>
                </label>
            `;
        });
        leaveUsersHtml += '</div>';
        
        // Update info
        changeLeaveInfo.innerHTML = `
            <div class="alert alert-warning" style="margin-bottom: 15px;">
                <strong><i class="fa fa-calendar"></i> Ngày: ${formattedDate}</strong><br>
                <small>Chọn người nghỉ phép cần thay đổi:</small>
                ${leaveUsersHtml}
            </div>
        `;
        
        // Add change event for radio buttons to show selected user
        const radios = changeLeaveInfo.querySelectorAll('input[name="selected_leave_user"]');
        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Remove all active states
                changeLeaveInfo.querySelectorAll('.list-group-item').forEach(function(item) {
                    item.style.backgroundColor = '#f9f9f9';
                    item.style.borderColor = '#ddd';
                });
                
                // Add active state to selected
                if (this.checked) {
                    const label = this.closest('.list-group-item');
                    label.style.backgroundColor = '#d9edf7';
                    label.style.borderColor = '#bce8f1';
                }
            });
        });
        
        // Load available users
        loadAvailableUsersForLeaveChange(config);
        
        // Store date in button
        confirmChangeLeaveBtn.dataset.changeDate = date;
    }
    
    /**
     * NEW: Load available users for leave change
     */
    function loadAvailableUsersForLeaveChange(config) {
        const changeLeaveSelect = document.getElementById('changeLeavePersonSelect');
        if (!changeLeaveSelect) return;

        changeLeaveSelect.innerHTML = '<option value="">-- Đang tải... --</option>';

        fetch(config.availableUsersUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            changeLeaveSelect.innerHTML = '<option value="">-- Chọn người mới --</option>';
            
            if (data.status === 'success' && data.data) {
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    changeLeaveSelect.appendChild(option);
                });
            }

            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(changeLeaveSelect).select2({
                    dropdownParent: window.jQuery('#manageShiftModal')
                });
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            changeLeaveSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
        });
    }
    
    /**
     * NEW: Handle change leave person confirmation
     */
    function handleChangeLeavePersonConfirmation(config) {
        const confirmBtn = document.getElementById('confirmChangeLeavePersonBtn');
        const newUserSelect = document.getElementById('changeLeavePersonSelect');
        const changeLeaveInfo = document.getElementById('changeLeaveInfo');
        
        if (!confirmBtn || !newUserSelect || !changeLeaveInfo) return;

        // Get selected original user
        const selectedRadio = changeLeaveInfo.querySelector('input[name="selected_leave_user"]:checked');
        if (!selectedRadio) {
            showToast('warning', 'Vui lòng chọn người nghỉ phép cần thay đổi.');
            return;
        }

        const leaveRequestId = selectedRadio.value;
        const changeDate = confirmBtn.dataset.changeDate;
        const newUserId = newUserSelect.value;

        if (!newUserId) {
            showToast('warning', 'Vui lòng chọn người mới.');
            return;
        }

        // Show loading
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(config.container.dataset.changeLeavePersonUrl || '/admin/leave-requests/change-person', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                leave_request_id: leaveRequestId,
                change_date: changeDate,
                new_user_id: newUserId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                showToast('success', data.message);
                
                if (window.jQuery) {
                    window.jQuery('#manageShiftModal').modal('hide');
                }
                
                // Refresh calendar and leave data
                if (calendar) {
                    calendar.refetchEvents();
                    fetchLeaveData(config);
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(error => {
            console.error('Change leave person error:', error);
            showToast('error', 'Lỗi kết nối. Không thể thay đổi.');
        })
        .finally(() => {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-exchange"></i> Xác nhận thay đổi';
        });
    }

    /**
     * NEW: Fetch leave data from server
     */
    function fetchLeaveData(config) {
        if (!calendar || !config) return;
        
        const view = calendar.view;
        const start = view.activeStart.toISOString().split('T')[0];
        const end = view.activeEnd.toISOString().split('T')[0];
        
        fetch(`${config.leaveEventsUrl}?start=${start}&end=${end}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Leave data loaded:', data);
            leaveDataByDate = data;
            // Add leave info to cells - FIX: Pass config parameter
            addLeaveInfoToCells(config);
        })
        .catch(error => {
            console.error('Error loading leave data:', error);
        });
    }

    /**
     * Update staff legend - CLEAN VERSION after Service fix
     */
    function updateStaffLegend(events) {
        const legendElement = document.getElementById('staff-legend');
        if (!legendElement) return;

        const users = new Map();
        
        // Process events to extract unique users with their colors
        events.forEach(event => {
            // Bây giờ data đã được đưa vào extendedProps trong Service
            if (event.extendedProps && event.extendedProps.user_id) {
                const userId = event.extendedProps.user_id;
                const userName = event.extendedProps.userName || event.title;
                const color = event.backgroundColor || event.color || '#5cb85c';
                
                if (!users.has(userId)) {
                    users.set(userId, { name: userName, color: color });
                }
            }
        });

        // Generate legend HTML
        let legendHtml = '';
        if (users.size === 0) {
            legendHtml = '<li><em style="color: #999;">Chưa có ca trực nào</em></li>';
        } else {
            users.forEach((user, userId) => {
                const safeColor = user.color || '#5cb85c';
                legendHtml += `
                    <li style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div class="legend-color-box" style="width: 16px; height: 16px; background-color: ${safeColor}; margin-right: 8px; border-radius: 3px; border: 1px solid #ddd;"></div>
                        <span class="legend-user-name" style="font-size: 13px;">${user.name}</span>
                    </li>
                `;
            });
        }

        legendElement.innerHTML = legendHtml;
    }

    /**
     * Show/hide tabs and delete button based on mode (add or edit)
     */
    function showTabsForMode(mode) {
        const addShiftTabLi = document.getElementById('addShiftTabLi');
        const changePersonTabLi = document.getElementById('changePersonTabLi');
        const swapShiftTabLi = document.getElementById('swapShiftTabLi');
        const addLeaveTabLi = document.getElementById('addLeaveTabLi');
        const deleteShiftBtn = document.getElementById('deleteShiftBtn');

        if (mode === 'add') {
            // Show add shift tab and add leave tab, hide others
            if (addShiftTabLi) addShiftTabLi.style.display = 'block';
            if (addLeaveTabLi) addLeaveTabLi.style.display = 'block';
            if (changePersonTabLi) changePersonTabLi.style.display = 'none';
            if (swapShiftTabLi) swapShiftTabLi.style.display = 'none';
            
            // Hide delete button for add mode
            if (deleteShiftBtn) deleteShiftBtn.style.display = 'none';
            
            // Activate add shift tab
            if (window.jQuery) {
                window.jQuery('#addShiftTab').tab('show');
            }
        } else if (mode === 'edit') {
            // Hide add shift tab and add leave tab, show others
            if (addShiftTabLi) addShiftTabLi.style.display = 'none';
            if (addLeaveTabLi) addLeaveTabLi.style.display = 'none';
            if (changePersonTabLi) changePersonTabLi.style.display = 'block';
            if (swapShiftTabLi) swapShiftTabLi.style.display = 'block';
            
            // Show delete button for edit mode
            if (deleteShiftBtn) deleteShiftBtn.style.display = 'block';
            
            // Activate change person tab
            if (window.jQuery) {
                window.jQuery('#changePersonTab').tab('show');
            }
        }
    }

    /**
     * Load available users for add shift
     */
    function loadAvailableUsersForAdd(config) {
        const userSelect = document.getElementById('addShiftUserSelect');
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
            console.error('Error loading available users for add:', error);
            userSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
        });
    }

    /**
     * Load available users for add leave
     */
    function loadAvailableUsersForLeave(config) {
        const userSelect = document.getElementById('leaveEmployeeSelect');
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
            console.error('Error loading available users for leave:', error);
            userSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
        });
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
     * Handle add shift confirmation
     */
    function handleAddShiftConfirmation(config) {
        const confirmBtn = document.getElementById('confirmAddShiftBtn');
        const userSelect = document.getElementById('addShiftUserSelect');
        
        if (!confirmBtn || !userSelect) return;

        const selectedDate = confirmBtn.dataset.selectedDate;
        const userId = userSelect.value;

        if (!userId) {
            showToast('warning', 'Vui lòng chọn nhân viên.');
            return;
        }

        // Show loading state
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(config.createShiftUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                admin_user_id: userId,
                shift_date: selectedDate
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
                
                // Refresh calendar
                if (calendar) {
                    calendar.refetchEvents();
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(error => {
            console.error('Add shift error:', error);
            showToast('error', 'Lỗi kết nối. Không thể thêm ca trực.');
        })
        .finally(() => {
            // Reset button state
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-plus"></i> Thêm ca trực';
        });
    }

    /**
     * Handle add leave confirmation
     */
    function handleAddLeaveConfirmation(config) {
        const confirmBtn = document.getElementById('confirmAddLeaveBtn');
        const userSelect = document.getElementById('leaveEmployeeSelect');
        
        if (!confirmBtn || !userSelect) return;

        const selectedDate = confirmBtn.dataset.selectedDate;
        const userId = userSelect.value;

        if (!userId) {
            showToast('warning', 'Vui lòng chọn nhân viên.');
            return;
        }

        // Show loading state
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(config.createLeaveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                admin_user_id: userId,
                leave_date: selectedDate
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
                
                // Refresh calendar and leave data
                if (calendar) {
                    calendar.refetchEvents();
                    // NEW: Also refetch leave data
                    fetchLeaveData(config);
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(error => {
            console.error('Add leave error:', error);
            showToast('error', 'Lỗi kết nối. Không thể tạo ngày nghỉ.');
        })
        .finally(() => {
            // Reset button state
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa fa-bed"></i> Tạo ngày nghỉ';
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
     * Handle delete shift confirmation
     */
    function handleDeleteShiftConfirmation(config) {
        const deleteBtn = document.getElementById('deleteShiftBtn');
        
        if (!deleteBtn) return;

        const shiftId = deleteBtn.dataset.shiftId;

        if (!shiftId) {
            showToast('error', 'Không tìm thấy thông tin ca trực để xóa.');
            return;
        }

        // Show loading state
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xóa...';

        fetch(config.deleteUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                shift_id: shiftId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('success', data.message || 'Đã xóa ca trực thành công.');
                
                // Hide modal
                if (window.jQuery) {
                    window.jQuery('#manageShiftModal').modal('hide');
                }
                
                // Refresh calendar
                if (calendar) {
                    calendar.refetchEvents();
                }
            } else {
                showToast('error', data.message || 'Có lỗi xảy ra khi xóa ca trực.');
            }
        })
        .catch(error => {
            console.error('Delete shift error:', error);
            showToast('error', 'Lỗi kết nối. Không thể xóa ca trực.');
        })
        .finally(() => {
            // Reset button state
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fa fa-trash"></i> Xóa ca trực';
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
                        title: info.event.title + ' - ' + (info.event.startStr || ''),
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

            // NEW: Callback when calendar view changes (month change)
            datesSet: function(info) {
                // Fetch leave data when month changes
                fetchLeaveData(config);
            },

            // Enable date clicking for admins
            selectable: config.isAdmin,
            dateClick: function(info) {
                if (!config.isAdmin) return;

                // Setup modal for adding new shift or changing leave
                const selectedDateInfo = document.getElementById('selectedDateInfo');
                const confirmAddShiftBtn = document.getElementById('confirmAddShiftBtn');
                const confirmAddLeaveBtn = document.getElementById('confirmAddLeaveBtn');
                const currentShiftInfoGroup = document.getElementById('currentShiftInfoGroup');
                const selectedDateInfoGroup = document.getElementById('selectedDateInfoGroup');
                const addShiftTabLi = document.getElementById('addShiftTabLi');
                const addLeaveTabLi = document.getElementById('addLeaveTabLi');
                const changeLeavePersonTabLi = document.getElementById('changeLeavePersonTabLi');
                const changePersonTabLi = document.getElementById('changePersonTabLi');
                const swapShiftTabLi = document.getElementById('swapShiftTabLi');

                if (selectedDateInfo && confirmAddShiftBtn && confirmAddLeaveBtn) {
                    // Update selected date info
                    const formattedDate = new Date(info.dateStr).toLocaleDateString('vi-VN');
                    const dateKey = info.dateStr;
                    const leaveUsers = leaveDataByDate[dateKey] || [];
                    
                    // Build info HTML
                    let infoHtml = `<strong>${formattedDate}</strong><br>`;
                    
                    if (leaveUsers.length > 0) {
                        infoHtml += `<small class="text-info">Có ${leaveUsers.length} người đang nghỉ phép ngày này</small>`;
                    } else {
                        infoHtml += `<small>Thêm ca trực hoặc ngày nghỉ cho ngày này</small>`;
                    }
                    
                    selectedDateInfo.innerHTML = infoHtml;
                    
                    // Set selected date for buttons
                    confirmAddShiftBtn.dataset.selectedDate = info.dateStr;
                    confirmAddLeaveBtn.dataset.selectedDate = info.dateStr;
                    
                    // Show/hide appropriate groups
                    if (currentShiftInfoGroup) currentShiftInfoGroup.style.display = 'none';
                    if (selectedDateInfoGroup) selectedDateInfoGroup.style.display = 'block';
                    
                    // Update modal title
                    const modalTitle = document.getElementById('manageShiftModalLabel');
                    if (modalTitle) {
                        modalTitle.innerHTML = '<i class="fa fa-calendar-plus-o"></i> Quản lý ngày ' + formattedDate;
                    }
                    
                    // Show/hide tabs based on whether there are leave users
                    if (addShiftTabLi) addShiftTabLi.style.display = 'block';
                    if (addLeaveTabLi) addLeaveTabLi.style.display = 'block';
                    if (changePersonTabLi) changePersonTabLi.style.display = 'none';
                    if (swapShiftTabLi) swapShiftTabLi.style.display = 'none';
                    
                    // If there are leave users on this date, show change leave tab
                    if (leaveUsers.length > 0) {
                        if (changeLeavePersonTabLi) {
                            changeLeavePersonTabLi.style.display = 'block';
                            // Populate the change leave form
                            populateLeaveChangeForm(dateKey, leaveUsers, config);
                        }
                    } else {
                        if (changeLeavePersonTabLi) changeLeavePersonTabLi.style.display = 'none';
                    }
                    
                    // Load available users for both add shift and add leave
                    loadAvailableUsersForAdd(config);
                    loadAvailableUsersForLeave(config);
                    
                    // Show modal and activate appropriate tab
                    if (window.jQuery) {
                        window.jQuery('#manageShiftModal').modal('show');
                        
                        // If there are leave users, suggest change leave tab, otherwise add shift
                        if (leaveUsers.length > 0) {
                            window.jQuery('#changeLeavePersonTab').tab('show');
                        } else {
                            window.jQuery('#addShiftTab').tab('show');
                        }
                    }
                }
            },

            // Event interactions
            eventClick: function(info) {
                if (!config.isAdmin) return;

                info.jsEvent.preventDefault();
                
                const event = info.event;
                const currentShiftInfo = document.getElementById('currentShiftInfo');
                const confirmSwapBtn = document.getElementById('confirmSwapBtn');
                const confirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');
                const deleteShiftBtn = document.getElementById('deleteShiftBtn');
                const currentShiftInfoGroup = document.getElementById('currentShiftInfoGroup');
                const selectedDateInfoGroup = document.getElementById('selectedDateInfoGroup');

                if (currentShiftInfo && confirmSwapBtn && confirmChangePersonBtn) {
                    const userName = event.extendedProps.userName || event.title || 'Chưa có tên';
                    const shiftDate = event.extendedProps.shiftDate || event.startStr || '';
                    const formattedDate = shiftDate ? new Date(shiftDate).toLocaleDateString('vi-VN') : '';
                    
                    // Update current shift info
                    currentShiftInfo.innerHTML = `
                        <div class="alert alert-info">
                            <strong><i class="fa fa-user"></i> ${userName}</strong><br>
                            <small><i class="fa fa-calendar"></i> Ngày: ${formattedDate}</small>
                        </div>
                    `;
                    
                    // Set shift ID for all buttons including delete button
                    confirmSwapBtn.dataset.sourceId = event.id;
                    confirmChangePersonBtn.dataset.shiftId = event.id;
                    // Set shift ID for delete button
                    if (deleteShiftBtn) {
                        deleteShiftBtn.dataset.shiftId = event.id;
                    }
                    
                    // Show/hide appropriate groups
                    if (currentShiftInfoGroup) currentShiftInfoGroup.style.display = 'block';
                    if (selectedDateInfoGroup) selectedDateInfoGroup.style.display = 'none';
                    
                    // Update modal title
                    const modalTitle = document.getElementById('manageShiftModalLabel');
                    if (modalTitle) {
                        modalTitle.innerHTML = '<i class="fa fa-edit"></i> Quản lý ca trực';
                    }
                    
                    // Show appropriate tabs
                    showTabsForMode('edit');
                    
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
        
        // NEW: Fetch leave data after calendar is rendered
        setTimeout(function() {
            fetchLeaveData(config);
        }, 500);
    }

    /**
     * Initialize modal event handlers
     */
    function initializeModalHandlers(config) {
        // Handle add shift button
        const confirmAddShiftBtn = document.getElementById('confirmAddShiftBtn');
        if (confirmAddShiftBtn) {
            confirmAddShiftBtn.replaceWith(confirmAddShiftBtn.cloneNode(true));
            const newConfirmAddShiftBtn = document.getElementById('confirmAddShiftBtn');
            
            newConfirmAddShiftBtn.addEventListener('click', function() {
                handleAddShiftConfirmation(config);
            });
        }

        // Handle add leave button
        const confirmAddLeaveBtn = document.getElementById('confirmAddLeaveBtn');
        if (confirmAddLeaveBtn) {
            confirmAddLeaveBtn.replaceWith(confirmAddLeaveBtn.cloneNode(true));
            const newConfirmAddLeaveBtn = document.getElementById('confirmAddLeaveBtn');
            
            newConfirmAddLeaveBtn.addEventListener('click', function() {
                handleAddLeaveConfirmation(config);
            });
        }
        
        // NEW: Handle change leave person button
        const confirmChangeLeavePersonBtn = document.getElementById('confirmChangeLeavePersonBtn');
        if (confirmChangeLeavePersonBtn) {
            confirmChangeLeavePersonBtn.replaceWith(confirmChangeLeavePersonBtn.cloneNode(true));
            const newConfirmChangeLeavePersonBtn = document.getElementById('confirmChangeLeavePersonBtn');
            
            newConfirmChangeLeavePersonBtn.addEventListener('click', function() {
                handleChangeLeavePersonConfirmation(config);
            });
        }

        // Handle change person button
        const confirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');
        if (confirmChangePersonBtn) {
            confirmChangePersonBtn.replaceWith(confirmChangePersonBtn.cloneNode(true));
            const newConfirmChangePersonBtn = document.getElementById('confirmChangePersonBtn');
            
            newConfirmChangePersonBtn.addEventListener('click', function() {
                handleChangePersonConfirmation(config);
            });
        }

        // Handle swap button
        const confirmSwapBtn = document.getElementById('confirmSwapBtn');
        if (confirmSwapBtn) {
            confirmSwapBtn.replaceWith(confirmSwapBtn.cloneNode(true));
            const newConfirmSwapBtn = document.getElementById('confirmSwapBtn');
            
            newConfirmSwapBtn.addEventListener('click', function() {
                handleSwapConfirmation(config);
            });
        }

        // Handle delete shift button
        const deleteShiftBtn = document.getElementById('deleteShiftBtn');
        if (deleteShiftBtn) {
            deleteShiftBtn.replaceWith(deleteShiftBtn.cloneNode(true));
            const newDeleteShiftBtn = document.getElementById('deleteShiftBtn');
            
            newDeleteShiftBtn.addEventListener('click', function() {
                handleDeleteShiftConfirmation(config);
            });
        }

        // Initialize Select2 when modal is shown
        if (window.jQuery) {
            window.jQuery('#manageShiftModal').off('shown.bs.modal').on('shown.bs.modal', function() {
                const addUserSelect = document.getElementById('addShiftUserSelect');
                const leaveEmployeeSelect = document.getElementById('leaveEmployeeSelect');
                const changeLeavePersonSelect = document.getElementById('changeLeavePersonSelect'); // NEW
                const userSelect = document.getElementById('newPersonSelect');
                const shiftSelect = document.getElementById('targetShiftSelect');
                
                if (addUserSelect && window.jQuery.fn.select2) {
                    window.jQuery(addUserSelect).select2({
                        dropdownParent: window.jQuery('#manageShiftModal')
                    });
                }
                
                if (leaveEmployeeSelect && window.jQuery.fn.select2) {
                    window.jQuery(leaveEmployeeSelect).select2({
                        dropdownParent: window.jQuery('#manageShiftModal')
                    });
                }
                
                // NEW: Initialize select2 for change leave person
                if (changeLeavePersonSelect && window.jQuery.fn.select2) {
                    window.jQuery(changeLeavePersonSelect).select2({
                        dropdownParent: window.jQuery('#manageShiftModal')
                    });
                }
                
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
                const addUserSelect = document.getElementById('addShiftUserSelect');
                const leaveEmployeeSelect = document.getElementById('leaveEmployeeSelect');
                const changeLeavePersonSelect = document.getElementById('changeLeavePersonSelect'); // NEW
                const userSelect = document.getElementById('newPersonSelect');
                const shiftSelect = document.getElementById('targetShiftSelect');
                const deleteShiftBtn = document.getElementById('deleteShiftBtn');
                
                if (addUserSelect) {
                    addUserSelect.selectedIndex = 0;
                    if (window.jQuery.fn.select2) {
                        window.jQuery(addUserSelect).val(null).trigger('change');
                    }
                }
                
                if (leaveEmployeeSelect) {
                    leaveEmployeeSelect.selectedIndex = 0;
                    if (window.jQuery.fn.select2) {
                        window.jQuery(leaveEmployeeSelect).val(null).trigger('change');
                    }
                }
                
                // NEW: Reset change leave person select
                if (changeLeavePersonSelect) {
                    changeLeavePersonSelect.selectedIndex = 0;
                    if (window.jQuery.fn.select2) {
                        window.jQuery(changeLeavePersonSelect).val(null).trigger('change');
                    }
                }
                
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

                // Reset delete button state
                if (deleteShiftBtn) {
                    deleteShiftBtn.disabled = false;
                    deleteShiftBtn.innerHTML = '<i class="fa fa-trash"></i> Xóa ca trực';
                    deleteShiftBtn.style.display = 'none';
                    delete deleteShiftBtn.dataset.shiftId;
                }

                // Show all tabs for next use
                const addShiftTabLi = document.getElementById('addShiftTabLi');
                const addLeaveTabLi = document.getElementById('addLeaveTabLi');
                const changePersonTabLi = document.getElementById('changePersonTabLi');
                const swapShiftTabLi = document.getElementById('swapShiftTabLi');
                const changeLeavePersonTabLi = document.getElementById('changeLeavePersonTabLi'); // NEW
                
                if (addShiftTabLi) addShiftTabLi.style.display = 'block';
                if (addLeaveTabLi) addLeaveTabLi.style.display = 'block';
                if (changePersonTabLi) changePersonTabLi.style.display = 'block';
                if (swapShiftTabLi) swapShiftTabLi.style.display = 'block';
                if (changeLeavePersonTabLi) changeLeavePersonTabLi.style.display = 'none'; // NEW: Hide by default

                // Reset to first tab
                window.jQuery('#addShiftTab').tab('show');
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
        leaveDataByDate = {}; // NEW: Clear leave data
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