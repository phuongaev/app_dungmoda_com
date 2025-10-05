{{-- resources/views/admin/shift-calendar/index.blade.php --}}

<link rel="stylesheet" href="{{ admin_asset('assets/css/shift-calendar.css') }}">

<div class="shift-calendar-container" 
     data-is-admin="{{ $is_admin ? 'true' : 'false' }}"
     data-events-url="{{ route('admin.shifts.events') }}"
     data-update-url="{{ route('admin.shifts.update') }}"
     data-swap-url="{{ route('admin.shifts.swap') }}"
     data-change-person-url="{{ route('admin.shifts.change_person') }}"
     data-create-shift-url="{{ route('admin.shifts.create') }}"
     data-delete-url="{{ route('admin.shifts.delete') }}"
     data-available-users-url="{{ route('admin.shifts.available_users') }}"
     data-available-shifts-url="{{ route('admin.shifts.available') }}"
     data-create-leave-url="{{ route('admin.shifts.create_leave') }}"
     data-csrf-token="{{ csrf_token() }}">
    
    <div class="calendar-page-container">
        <!-- Main Calendar -->
        <div id="calendar-wrapper">
            <div id="calendar"></div>
        </div>

        <!-- Legend Sidebar -->
        <div id="legend-wrapper">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-users"></i> Danh sách nhân viên
                    </h3>
                </div>
                <div class="box-body">
                    <ul id="staff-legend">
                        <!-- Sẽ được populate bởi JavaScript -->
                    </ul>
                </div>
            </div>

            @if($is_admin)
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> Hướng dẫn
                    </h3>
                </div>
                <div class="box-body">
                    <div class="help-text">
                        <p><strong>Click vào ngày trống:</strong> Thêm ca trực mới</p>
                        <p><strong>Click vào tên người:</strong> Quản lý ca trực</p>
                        <p><strong>Kéo thả:</strong> Kéo ca trực để chuyển ngày</p>
                        <p><strong>Thay đổi người:</strong> Chọn nhân viên khác trực thay</p>
                        <p><strong>Hoán đổi ca:</strong> Đổi với ca trực khác</p>
                        <p><strong>Xóa ca trực:</strong> Click vào tên người rồi chọn "Xóa ca trực"</p>
                        <p><strong>Lưu ý:</strong> Một ngày có thể có nhiều người trực</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@if($is_admin)
<!-- Manage Shift Modal -->
@include('admin.shift-calendar.components.swap-modal')
@endif

<script src="{{ admin_asset('assets/js/shift-calendar.js') }}"></script>