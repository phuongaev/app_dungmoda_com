<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8' />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js'></script>

    <!-- === CSS TÙY CHỈNH CHO GIAO DIỆN MỚI === -->
    <style>
        .calendar-page-container { display: flex; flex-wrap: wrap; gap: 20px; }
        #calendar-wrapper { flex: 1 1 70%; min-width: 600px; }
        #legend-wrapper { flex: 1 1 25%; min-width: 250px; }
        #staff-legend { list-style: none; padding: 0; margin: 0; }
        #staff-legend li { display: flex; align-items: center; margin-bottom: 10px; font-size: 14px; }
        .legend-color-box { width: 18px; height: 18px; border-radius: 4px; margin-right: 10px; border: 1px solid rgba(0,0,0,0.1); }
        #calendar { max-width: 100%; margin: 0 auto; }
        .fc .fc-toolbar-title { font-size: 20px !important; font-weight: 600; }
        .fc .fc-button-primary { background-color: #3c8dbc !important; border-color: #3c8dbc !important; box-shadow: none !important; }
        .fc .fc-daygrid-event { border-radius: 4px; padding: 5px 8px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s ease-in-out; color: #fff !important; overflow: hidden; }
        .fc .fc-daygrid-event:hover { opacity: 0.85; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .fc-event-main-frame::before { font-family: FontAwesome; content: "\f007"; margin-right: 6px; }
        #swapModal .modal-header { background-color: #3c8dbc; color: white; }
        #swapModal .modal-title { font-size: 18px; }
        #swapModal .close { color: white; opacity: 0.9; }
        #sourceShiftInfo { font-weight: 600; color: #3c8dbc; }
        #targetShiftSelect { width: 100% !important; }
    </style>
</head>
<body>
    <div class="calendar-page-container" 
         id="calendar-container-main"
         data-is-admin="{{ $is_admin ? 'true' : 'false' }}"
         data-events-url="{{ route('admin.shifts.events') }}"
         data-update-url="{{ route('admin.shifts.update') }}"
         data-swap-url="{{ route('admin.shifts.swap') }}">
        
        <!-- CỘT LỊCH CHÍNH -->
        <div id="calendar-wrapper" class="box box-primary">
            <div class="box-body no-padding">
                <div id='calendar'></div>
            </div>
        </div>
        <!-- CỘT CHÚ THÍCH -->
        <div id="legend-wrapper">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-tags"></i> Chú thích nhân viên</h3></div>
                <div class="box-body"><ul id="staff-legend"></ul></div>
            </div>
        </div>
    </div>
    <!-- MODAL CHO CHỨC NĂNG HOÁN ĐỔI -->
    <div class="modal fade" id="swapModal" tabindex="-1" role="dialog" aria-labelledby="swapModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="swapModalLabel">Hoán đổi ca trực</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Bạn đang chọn ca trực của: <strong id="sourceShiftInfo"></strong></p>
                    <hr>
                    <div class="form-group">
                        <label for="targetShiftSelect">Hoán đổi với ca trực của:</label>
                        <select class="form-control" id="targetShiftSelect"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="confirmSwapBtn">Xác nhận hoán đổi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        function initializeFullCalendar() {
            var mainContainer = document.getElementById('calendar-container-main');
            var calendarEl = document.getElementById('calendar');
            
            if (!mainContainer || !calendarEl || calendarEl.classList.contains('fc-loaded')) {
                return;
            }
            calendarEl.classList.add('fc-loaded');

            // Đọc dữ liệu từ thuộc tính data-*
            const isAdmin = mainContainer.dataset.isAdmin === 'true';
            const eventsUrl = mainContainer.dataset.eventsUrl;
            const updateUrl = mainContainer.dataset.updateUrl;
            const swapUrl = mainContainer.dataset.swapUrl;

            var legendEl = document.getElementById('staff-legend');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let allEvents = [];
            
            function updateUserLegend(events) {
                legendEl.innerHTML = '';
                const uniqueUsers = {};
                if (Array.isArray(events)) {
                    events.forEach(event => {
                        if (event.extendedProps && event.extendedProps.userId) {
                            const userId = event.extendedProps.userId;
                            if (!uniqueUsers[userId]) {
                                uniqueUsers[userId] = { name: event.title, color: event.color };
                            }
                        }
                    });
                }
                for (const userId in uniqueUsers) {
                    const user = uniqueUsers[userId];
                    const li = document.createElement('li');
                    li.innerHTML = `<div class="legend-color-box" style="background-color: ${user.color};"></div><span>${user.name}</span>`;
                    legendEl.appendChild(li);
                }
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                initialView: 'dayGridMonth',
                locale: 'vi',
                timeZone: 'Asia/Ho_Chi_Minh',
                editable: isAdmin,
                droppable: isAdmin,

                events: function(fetchInfo, successCallback, failureCallback) {
                    const params = new URLSearchParams({ start: fetchInfo.startStr, end: fetchInfo.endStr });
                    fetch(`${eventsUrl}?${params}`)
                        .then(response => response.json())
                        .then(data => { allEvents = data; updateUserLegend(data); successCallback(data); })
                        .catch(error => { console.error('Error fetching events:', error); failureCallback(error); });
                },

                eventClick: function(info) {
                    if (!isAdmin) return;
                    const sourceId = info.event.id;
                    document.getElementById('sourceShiftInfo').innerText = `${info.event.title} (Ngày ${info.event.start.toLocaleDateString('vi-VN')})`;
                    const targetSelect = document.getElementById('targetShiftSelect');
                    targetSelect.innerHTML = '<option value="">-- Chọn ca để hoán đổi --</option>';
                    allEvents.forEach(event => {
                        if (event.id != sourceId) {
                            const option = document.createElement('option');
                            option.value = event.id;
                            option.innerText = `${event.title} (Ngày ${new Date(event.start).toLocaleDateString('vi-VN')})`;
                            targetSelect.appendChild(option);
                        }
                    });
                    $(targetSelect).select2();
                    document.getElementById('confirmSwapBtn').dataset.sourceId = sourceId;
                    $('#swapModal').modal('show');
                },

                eventDrop: function(info) {
                    if (!confirm("Bạn có chắc muốn đổi ca trực của " + info.event.title + " sang ngày " + info.event.start.toLocaleDateString('vi-VN') + "?")) {
                        info.revert();
                        return;
                    }
                    
                    fetch(updateUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            id: info.event.id,
                            date: info.event.start.toISOString().slice(0, 10)
                        })
                    })
                    .then(response => response.ok ? response.json() : response.json().then(err => Promise.reject(err)))
                    .then(data => {
                        if (data.status === 'success') {
                            window.toastr.success(data.message);
                            calendar.refetchEvents();
                        } else {
                            window.toastr.error(data.message || 'Có lỗi xảy ra.');
                            info.revert();
                        }
                    })
                    .catch(error => {
                        window.toastr.error(error.message || 'Lỗi kết nối. Không thể cập nhật.');
                        info.revert();
                    });
                }
            });

            calendar.render();

            document.getElementById('confirmSwapBtn').addEventListener('click', function() {
                const sourceId = this.dataset.sourceId;
                const targetId = document.getElementById('targetShiftSelect').value;
                if (!targetId) { window.toastr.warning('Vui lòng chọn một ca trực để hoán đổi.'); return; }
                fetch(swapUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ source_id: sourceId, target_id: targetId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.toastr.success(data.message);
                        $('#swapModal').modal('hide');
                        calendar.refetchEvents();
                    } else {
                        window.toastr.error(data.message || 'Có lỗi xảy ra.');
                    }
                }).catch(err => window.toastr.error('Lỗi kết nối.'));
            });
            
            $('#swapModal').on('shown.bs.modal', function () {
                $('#targetShiftSelect').select2({ dropdownParent: $('#swapModal') });
            });
        }

        // Chạy khi trang tải lần đầu
        document.addEventListener('DOMContentLoaded', initializeFullCalendar);
        
        // Chạy mỗi khi laravel-admin tải trang xong bằng PJAX
        $(document).on('pjax:end', initializeFullCalendar);
    })();
    </script>
</body>
</html>
