<!-- <div class="row">
    <div class="col-md-4">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Chờ xử lý</span>
                <span class="info-box-number">{{ $stats['pending'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-truck"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Nhập kho VN</span>
                <span class="info-box-number">{{ $stats['delivered_vn'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng cần xử lý</span>
                <span class="info-box-number">{{ $stats['total'] }}</span>
            </div>
        </div>
    </div>
</div> -->

<!-- Bảng danh sách -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-warning text-orange"></i>
                    Kiện hàng cần xử lý ngay
                    @if($urgentPackages->count() > 0)
                        <span class="label label-danger">{{ $urgentPackages->count() }} quá hạn</span>
                    @endif
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" onclick="location.reload()">
                        <i class="fa fa-refresh"></i> Làm mới
                    </button>
                    <a href="{{ admin_url('dashboard/packages') }}" class="btn btn-sm btn-default">
                        <i class="fa fa-external-link"></i> Xem chi tiết
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive">
                @if($pendingPackages->count() > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã kiện</th>
                                <th>Đối tác VC</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPackages->take(10) as $package)
                                @php
                                    $isUrgent = $package->created_at->lt(now()->subHours(24));
                                    $rowClass = $package->package_status == 'pending' ? 
                                        ($isUrgent ? 'bg-danger' : 'bg-warning') : 'bg-info';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <strong>{{ $package->package_code }}</strong>
                                        @if($isUrgent)
                                            <br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> Quá hạn</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-default">
                                            {{ $package->shipping_partner_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($package->package_status == 'pending')
                                            <span class="label label-warning">
                                                <i class="fa fa-clock-o"></i>
                                                 {{ $package->package_status_label }}
                                            </span>
                                        @else
                                            <span class="label label-info">
                                                <i class="fa fa-truck"></i>
                                                 {{ $package->package_status_label }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $package->created_at->format('d/m/Y H:i') }}
                                            <br>
                                            <em>({{ $package->created_at->diffForHumans() }})</em>
                                        </small>
                                    </td>
                                    <td>
                                        @if($package->notes)
                                            <span class="text-muted" title="{{ $package->notes }}">
                                                {{ Str::limit($package->notes, 30) }}
                                            </span>
                                        @else
                                            <em class="text-muted">Không có ghi chú</em>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            <a href="{{ admin_url('packages/' . $package->id) }}" class="btn btn-primary" title="Xem chi tiết">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ admin_url('packages/' . $package->id . '/edit') }}" class="btn btn-warning" title="Chỉnh sửa">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            @if(in_array($package->package_status, ['pending', 'in_transit', 'delivered_vn']))
                                                <a href="{{ admin_url('packages/' . $package->id . '/update-status') }}" 
                                                   class="btn btn-success" 
                                                   title="Cập nhật trạng thái"
                                                   onclick="return confirm('Xác nhận cập nhật trạng thái?')">
                                                    <i class="fa fa-arrow-right"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center" style="padding: 50px;">
                        <i class="fa fa-check-circle text-success" style="font-size: 48px;"></i>
                        <h4 class="text-success">Tuyệt vời!</h4>
                        <p class="text-muted">Hiện tại không có kiện hàng nào cần xử lý.</p>
                    </div>
                @endif
            </div>
            @if($pendingPackages->count() > 0)
                <div class="box-footer">
                    <div class="pull-left">
                        @if($pendingPackages->count() > 10)
                            <small class="text-muted">Hiển thị 10/{{ $pendingPackages->count() }} kiện hàng</small>
                        @endif
                    </div>
                    <div class="pull-right">
                        <a href="{{ admin_url('packages?package_status[]=pending') }}" class="btn btn-warning btn-sm">
                            <i class="fa fa-clock-o"></i> Xem tất cả chờ xử lý
                        </a>
                        <a href="{{ admin_url('packages?package_status[]=delivered_vn') }}" class="btn btn-info btn-sm">
                            <i class="fa fa-truck"></i> Xem tất cả nhập kho VN
                        </a>
                        <a href="{{ admin_url('packages') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-list"></i> Xem tất cả
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.info-box {
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table > thead > tr > th {
    border-bottom: 2px solid #ddd;
    font-weight: bold;
}

.bg-warning {
    background-color: #fcf8e3 !important;
}

.bg-info {
    background-color: #d9edf7 !important;
}

.bg-danger {
    background-color: #f2dede !important;
}

.btn-group-xs > .btn {
    padding: 1px 5px;
    font-size: 10px;
    line-height: 1.5;
}
</style>

<script>
// Auto refresh mỗi 15 phút
setTimeout(function() {
    location.reload();
}, 900000);
</script>