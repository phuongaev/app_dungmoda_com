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
</style>

<!-- === HTML CHO WIDGET === -->
<div class="box box-primary shift-calendar-widget-container">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-calendar"></i> Lịch trực ca tối</h3>
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

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: '' // Đã bỏ các nút bên phải theo yêu cầu
                    },
                    initialView: 'dayGridMonth',
                    locale: 'vi',
                    timeZone: 'Asia/Ho_Chi_Minh',
                    
                    // --- CÀI ĐẶT CHẾ ĐỘ CHỈ XEM ---
                    editable: false,
                    selectable: false,
                    eventClick: function(info) {
                        info.jsEvent.preventDefault(); // Chặn mọi hành động khi click
                    },
                    
                    events: {
                        url: '{{ route("admin.shifts.events") }}',
                        failure: function() {
                            console.error("Lỗi khi tải dữ liệu lịch cho dashboard.");
                        }
                    },
                    
                    eventDidMount: function(info) {
                        if (info.event.title) {
                            $(info.el).tooltip({
                                title: info.event.title,
                                placement: 'top',
                                trigger: 'hover',
                                container: 'body'
                            });
                        }
                    },
                });
                calendar.render();
            }
        }

        // --- SỬA LỖI F5 ---
        // Hàm chính để chạy việc khởi tạo
        function runCalendarInitialization() {
            // Chỉ chạy nếu tìm thấy element của lịch trên trang
            if (!document.getElementById('dashboard-calendar-widget')) {
                return;
            }

            loadCss('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
            loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', function() {
                loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js', function() {
                    // Đảm bảo jQuery đã load xong trước khi dùng tooltip
                    if (window.jQuery) {
                        initializeDashboardCalendar();
                    } else {
                        // Nếu jQuery chưa có, load nó rồi mới khởi tạo lịch
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