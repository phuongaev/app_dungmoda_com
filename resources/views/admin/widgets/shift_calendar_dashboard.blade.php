<!-- === CSS TÙY CHỈNH CHO GIAO DIỆN MỚI === -->
<style>
    /* Thêm padding và nền cho box-body để lịch không bị sát viền */
    .shift-calendar-widget-container .box-body {
        padding: 15px;
        background-color: #fff;
    }

    /* Tùy chỉnh FullCalendar */
    #dashboard-calendar-widget {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Làm nổi bật ngày hôm nay */
    #dashboard-calendar-widget .fc-daygrid-day.fc-day-today {
        background-color: rgb(120 17 219 / 15%);
    }

    /* Tùy chỉnh sự kiện (ca trực) */
    #dashboard-calendar-widget .fc .fc-daygrid-event {
        border-radius: 4px;
        padding: 5px 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        color: #fff !important;
        overflow: hidden;
        cursor: default !important; /* Đổi con trỏ để người dùng biết là không click được */
    }
     #dashboard-calendar-widget .fc-event-main-frame::before {
        font-family: FontAwesome;
        content: "\f007"; /* Icon user */
        margin-right: 6px;
    }

    /* Tùy chỉnh thanh công cụ */
    #dashboard-calendar-widget .fc .fc-toolbar-title {
        font-size: 1.2rem !important;
        font-weight: 600;
    }
    #dashboard-calendar-widget .fc .fc-button {
        padding: .3rem .6rem !important;
        font-size: .875rem;
    }
    .fc-event .fc-event-main{
        padding: 3px 5px 3px;
    }

    /* Style cho thông tin nhân viên nghỉ phép hiển thị bên dưới event */
    .leave-info-below {
        color: #9f4d13 !important;
        font-size: 11px !important;
        font-weight: normal !important;
        line-height: 1.2;
        margin-top: 4px;
        padding: 2px 5px;
        background-color: rgba(108, 117, 125, 0.1);
        border-radius: 3px;
        display: block;
    }
</style>

<!-- === HTML CHO WIDGET === -->
<div class="box box-primary shift-calendar-widget-container">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-calendar"></i> Lịch làm việc</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <div id="dashboard-calendar-widget" style="height: 450px; width: 100%;"></div>
    </div>
</div>


<script>
    (function() {
        function loadScript(url, callback) {
            if (!document.querySelector(`script[src="${url}"]`)) {
                let script = document.createElement('script');
                script.src = url;
                if (callback) script.onload = callback;
                document.head.appendChild(script);
            } else if (callback) {
                callback();
            }
        }

        function loadCss(url) {
            if (!document.querySelector(`link[href="${url}"]`)) {
                let link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                document.head.appendChild(link);
            }
        }

        function initializeDashboardCalendar() {
            var calendarEl = document.getElementById('dashboard-calendar-widget');
            
            if (calendarEl && !calendarEl.classList.contains('fc-loaded')) {
                calendarEl.classList.add('fc-loaded');

                // Object để lưu dữ liệu nghỉ phép theo ngày
                var leaveDataByDate = {};
                var calendar;

                // Function để thêm leave info vào calendar cells
                function addLeaveInfoToCells() {
                    // Tìm tất cả các day cells
                    var dayCells = calendarEl.querySelectorAll('.fc-daygrid-day');
                    
                    dayCells.forEach(function(dayCell) {
                        // Lấy date từ data attribute
                        var dateAttr = dayCell.getAttribute('data-date');
                        if (!dateAttr) return;
                        
                        var leaveUsers = leaveDataByDate[dateAttr] || [];
                        
                        if (leaveUsers.length > 0) {
                            var dayFrame = dayCell.querySelector('.fc-daygrid-day-frame');
                            if (dayFrame) {
                                // Xóa leave info cũ nếu có
                                var existingLeaveInfo = dayFrame.querySelector('.leave-info-below');
                                if (existingLeaveInfo) {
                                    existingLeaveInfo.remove();
                                }
                                
                                // Tạo text hiển thị
                                var leaveUsersText = leaveUsers.map(function(user) {
                                    return user.name + ' (nghỉ)';
                                }).join(', ');
                                
                                // Tạo element
                                var leaveInfoEl = document.createElement('div');
                                leaveInfoEl.className = 'leave-info-below';
                                leaveInfoEl.textContent = leaveUsersText;
                                
                                // Thêm vào cuối day cell
                                dayFrame.appendChild(leaveInfoEl);
                            }
                        }
                    });
                }

                // Function để fetch dữ liệu nghỉ phép
                function fetchLeaveData() {
                    if (!calendar) return;
                    
                    var view = calendar.view;
                    var start = view.activeStart.toISOString().split('T')[0];
                    var end = view.activeEnd.toISOString().split('T')[0];
                    
                    $.ajax({
                        url: '{{ route("admin.shifts.leave_events") }}',
                        method: 'GET',
                        data: {
                            start: start,
                            end: end
                        },
                        success: function(data) {
                            console.log('Leave data loaded:', data);
                            leaveDataByDate = data;
                            // Thêm leave info vào cells
                            addLeaveInfoToCells();
                        },
                        error: function(xhr, status, error) {
                            console.error("Lỗi khi tải dữ liệu nghỉ phép:", error);
                        }
                    });
                }

                calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: ''
                    },
                    initialView: 'dayGridMonth',
                    locale: 'vi',
                    timeZone: 'Asia/Ho_Chi_Minh',
                    
                    // Chế độ chỉ xem
                    editable: false,
                    selectable: false,
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                    },
                    
                    // Load shift events (ca trực)
                    events: {
                        url: '{{ route("admin.shifts.events") }}',
                        failure: function() {
                            console.error("Lỗi khi tải dữ liệu lịch ca trực.");
                        }
                    },
                    
                    // Callback khi calendar đã render xong
                    eventDidMount: function(info) {
                        // Tooltip cho event (ca trực)
                        if (info.event.title) {
                            $(info.el).tooltip({
                                title: info.event.title,
                                placement: 'top',
                                trigger: 'hover',
                                container: 'body'
                            });
                        }
                    },

                    // Callback khi thay đổi view/tháng
                    datesSet: function(info) {
                        // Fetch leave data mỗi khi đổi tháng
                        fetchLeaveData();
                    },
                });

                calendar.render();
                
                // Fetch leave data lần đầu sau khi render
                setTimeout(function() {
                    fetchLeaveData();
                }, 500);
            }
        }

        // Hàm chính để chạy việc khởi tạo
        function runCalendarInitialization() {
            if (!document.getElementById('dashboard-calendar-widget')) {
                return;
            }

            loadCss('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
            loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', function() {
                loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js', function() {
                    if (window.jQuery) {
                        initializeDashboardCalendar();
                    } else {
                        loadScript('{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js") }}', initializeDashboardCalendar);
                    }
                });
            });
        }

        // Chạy khi trang tải lần đầu
        document.addEventListener('DOMContentLoaded', runCalendarInitialization);
        
        // Chạy mỗi khi laravel-admin tải trang xong bằng PJAX
        $(document).on('pjax:end', runCalendarInitialization);

    })();
</script>