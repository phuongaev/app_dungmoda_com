{{-- File: resources/views/admin/dashboard/packages-full.blade.php --}}

@php
    // Safe variables với giá trị mặc định
    $safePending = $stats['pending'] ?? 0;
    $safeDeliveredVn = $stats['delivered_vn'] ?? 0;
    $safeInTransit = $stats['in_transit'] ?? 0;
    $safeDelivered = $stats['delivered'] ?? 0;
    $safeCancelled = $stats['cancelled'] ?? 0;
    
    $safeTotalNeedAction = ($safePending + $safeDeliveredVn);
    $safeTotalAll = ($safePending + $safeDeliveredVn + $safeInTransit + $safeDelivered + $safeCancelled);
    $safeCompletionRate = $safeTotalAll > 0 ? round(($safeDelivered / $safeTotalAll) * 100, 1) : 0;
    
    $safeUrgentPackages = $urgentPackages ?? 0;
    $safeCriticalPackages = $criticalPackages ?? 0;
@endphp

<div class="row">
    <!-- Thống kê tổng quan TẤT CẢ trạng thái -->
    <div class="col-md-2">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Chờ xử lý</span>
                <span class="info-box-number">{{ $safePending }}</span>
                @if($safePending > 0)
                    <span class="progress-description">Cần xử lý ngay!</span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-truck"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Nhập kho VN</span>
                <span class="info-box-number">{{ $safeDeliveredVn }}</span>
                @if($safeDeliveredVn > 0)
                    <span class="progress-description">Cần lấy hàng</span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box bg-aqua">
            <span class="info-box-icon"><i class="fa fa-plane"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đang vận chuyển</span>
                <span class="info-box-number">{{ $safeInTransit }}</span>
                <span class="progress-description">
                    {{ $safeTotalAll > 0 ? round(($safeInTransit / $safeTotalAll) * 100, 1) : 0 }}% tổng kiện
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đã nhận hàng</span>
                <span class="info-box-number">{{ $safeDelivered }}</span>
                <span class="progress-description">{{ $safeCompletionRate }}% hoàn thành</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-times"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đã hủy</span>
                <span class="info-box-number">{{ $safeCancelled }}</span>
                <span class="progress-description">
                    {{ $safeTotalAll > 0 ? round(($safeCancelled / $safeTotalAll) * 100, 1) : 0 }}% bị hủy
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="info-box bg-purple">
            <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cần xử lý</span>
                <span class="info-box-number">{{ $safeTotalNeedAction }}</span>
                @if($safeTotalNeedAction > 0)
                    <span class="progress-description">
                        @if($safeUrgentPackages > 0)
                            {{ $safeUrgentPackages }} quá hạn!
                        @else
                            Ưu tiên cao
                        @endif
                    </span>
                @else
                    <span class="progress-description">Tuyệt vời!</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tổng quan hệ thống -->
<div class="row">
    <div class="col-md-8">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-pie-chart"></i>
                    Tổng quan toàn hệ thống
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="info-box-content">
                            <span class="info-box-number text-blue">{{ $safeTotalAll }}</span>
                            <span class="info-box-text">Tổng số kiện hàng</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box-content">
                            <span class="info-box-number text-green">{{ $safeCompletionRate }}%</span>
                            <span class="info-box-text">Tỷ lệ hoàn thành</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box-content">
                            <span class="info-box-number text-{{ $safeTotalNeedAction > 0 ? 'red' : 'green' }}">
                                {{ $safeTotalNeedAction }}
                            </span>
                            <span class="info-box-text">Cần xử lý ngay</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Biểu đồ tỷ lệ đối tác vận chuyển -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-pie-chart"></i>
                    Tỷ lệ kiện hàng theo đối tác vận chuyển
                </h3>
            </div>
            <div class="box-body">
                @if(!empty($partnerTotals))
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Canvas cho biểu đồ tròn -->
                            <canvas id="partnerChart" width="400" height="400"></canvas>
                        </div>
                        <div class="col-md-6">
                            <!-- Legend và thông tin chi tiết -->
                            <div class="chart-legend">
                                <h4>Chi tiết theo đối tác:</h4>
                                @php
                                    $partnerLabels = [
                                        'atan' => 'A Tần',
                                        'nga' => 'Nga',
                                        'fe' => 'Xuân Phê',
                                        'oanh' => 'Oanh',
                                        'other' => 'Khác'
                                    ];
                                    $totalAll = array_sum($partnerTotals);
                                    $colors = ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc'];
                                    $colorIndex = 0;
                                @endphp
                                
                                @foreach($partnerTotals as $partner => $total)
                                    @php
                                        $label = $partnerLabels[$partner] ?? $partner;
                                        $percentage = $totalAll > 0 ? round(($total / $totalAll) * 100, 1) : 0;
                                        $color = $colors[$colorIndex % count($colors)];
                                        $colorIndex++;
                                    @endphp
                                    
                                    <div class="legend-item" style="margin-bottom: 10px;">
                                        <span class="legend-color" 
                                              style="display: inline-block; width: 20px; height: 20px; background-color: {{ $color }}; margin-right: 10px; border-radius: 3px;"></span>
                                        <strong>{{ $label }}</strong>
                                        <span class="pull-right">
                                            <a href="{{ admin_url('packages?shipping_partner[]=' . $partner) }}" 
                                               class="badge" 
                                               style="background-color: {{ $color }}; text-decoration: none;">
                                               {{ $total }} kiện ({{ $percentage }}%)
                                            </a>
                                        </span>
                                        <div class="clearfix"></div>
                                    </div>
                                @endforeach
                                
                                <hr>
                                <div class="total-summary">
                                    <strong>Tổng cộng: {{ $totalAll }} kiện hàng</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center" style="padding: 50px;">
                        <i class="fa fa-pie-chart text-muted" style="font-size: 48px;"></i>
                        <h4 class="text-muted">Không có dữ liệu thống kê</h4>
                        <p class="text-muted">
                            Hiện tại chưa có kiện hàng nào trong hệ thống.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        

    </div>
    
    <div class="col-md-4">
        <!-- Thao tác nhanh -->
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-bolt"></i>
                    Thao tác nhanh
                </h3>
            </div>
            <div class="box-body">
                <!-- Hàng 1: Thêm kiện hàng và Xem tất cả -->
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{ admin_url('packages/create') }}" class="btn btn-success btn-block btn-lg">
                            <i class="fa fa-plus"></i><br>
                            <span>Thêm kiện hàng</span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ admin_url('packages') }}" class="btn btn-primary btn-block btn-lg">
                            <i class="fa fa-list"></i><br>
                            <span>Tất cả ({{ $safeTotalAll }})</span>
                        </a>
                    </div>
                </div>
                <br>
                <!-- Hàng 2: Các trạng thái cần xử lý -->
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{ admin_url('packages?package_status[]=pending') }}" class="btn btn-warning btn-block btn-lg">
                            <i class="fa fa-clock-o"></i><br>
                            <span>Chờ xử lý ({{ $safePending }})</span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ admin_url('packages?package_status[]=delivered_vn') }}" class="btn btn-info btn-block btn-lg">
                            <i class="fa fa-truck"></i><br>
                            <span>Nhập kho VN ({{ $safeDeliveredVn }})</span>
                        </a>
                    </div>
                </div>
                <br>
                <button onclick="location.reload()" class="btn btn-default btn-block">
                    <i class="fa fa-refresh"></i> Làm mới dữ liệu
                </button>
            </div>
        </div>


        @if(isset($topUrgentPackages) && $topUrgentPackages->count() > 0)
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-fire"></i>
                    Top 5 kiện cần xử lý gấp
                </h3>
            </div>
            <div class="box-body no-padding">
                <ul class="users-list clearfix">
                    @foreach($topUrgentPackages as $package)
                        <li style="width: 100%; padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>{{ $package->package_code }}</strong>
                            <br>
                            <small class="text-muted">{{ $package->created_at->diffForHumans() }}</small>
                            <br>
                            <span class="label label-{{ $package->package_status == 'pending' ? 'warning' : 'info' }}">
                                {{ $package->package_status_label }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif


    </div>
</div>

<!-- Tìm kiếm đơn giản -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-search"></i>
                    Tìm kiếm nhanh
                </h3>
            </div>
            <div class="box-body">
                <form method="GET" class="form-inline">
                    <div class="form-group" style="margin-right: 10px;">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nhập mã kiện..." 
                               value="{{ request('search') }}"
                               style="width: 200px;">
                    </div>
                    <div class="form-group" style="margin-right: 10px;">
                        <select name="status" class="form-control">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="delivered_vn" {{ request('status') == 'delivered_vn' ? 'selected' : '' }}>Nhập kho VN</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> Tìm
                    </button>
                    <a href="{{ admin_url('dashboard/packages') }}" class="btn btn-default">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bảng danh sách có phân trang -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list"></i>
                    Danh sách kiện hàng cần xử lý
                    @if(isset($pendingPackages) && method_exists($pendingPackages, 'total') && $pendingPackages->total() > 0)
                        <span class="label label-info">{{ $pendingPackages->total() }} kiện</span>
                    @endif
                </h3>
                <div class="box-tools pull-right">
                    <a href="#" onclick="exportData('excel'); return false;" class="btn btn-sm btn-success">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </a>
                    <a href="#" onclick="window.print(); return false;" class="btn btn-sm btn-default">
                        <i class="fa fa-print"></i> In
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive">
                @if(isset($pendingPackages) && $pendingPackages->count() > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="10%">Mã kiện</th>
                                <th width="12%">Đối tác vận chuyển</th>
                                <th width="10%">Trạng thái</th>
                                <th width="12%">Ngày tạo</th>
                                <th width="15%">Danh sách vận đơn</th>
                                <th width="20%">Ghi chú</th>
                                <th width="21%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPackages as $package)
                                @php
                                    $isUrgent = $package->created_at->lt(now()->subHours(24));
                                    $isVeryUrgent = $package->created_at->lt(now()->subHours(48));
                                    
                                    if ($isVeryUrgent) {
                                        $rowClass = 'bg-danger';
                                    } elseif ($isUrgent) {
                                        $rowClass = 'bg-warning';
                                    } elseif ($package->package_status == 'delivered_vn') {
                                        $rowClass = 'bg-info';
                                    } else {
                                        $rowClass = '';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <strong>{{ $package->package_code }}</strong>
                                        @if($isVeryUrgent)
                                            <br><small class="text-danger">
                                                <i class="fa fa-exclamation-triangle"></i> 
                                                Quá hạn {{ $package->created_at->diffForHumans() }}
                                            </small>
                                        @elseif($isUrgent)
                                            <br><small class="text-warning">
                                                <i class="fa fa-clock-o"></i> 
                                                {{ $package->created_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-default">
                                            {{ $package->shipping_partner_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @switch($package->package_status)
                                            @case('pending')
                                                <span class="label label-warning">
                                                    <i class="fa fa-clock-o"></i>
                                                    {{ $package->package_status_label }}
                                                </span>
                                                @break
                                            @case('delivered_vn')
                                                <span class="label label-info">
                                                    <i class="fa fa-truck"></i>
                                                    {{ $package->package_status_label }}
                                                </span>
                                                @break
                                            @case('in_transit')
                                                <span class="label label-primary">
                                                    <i class="fa fa-plane"></i>
                                                    {{ $package->package_status_label }}
                                                </span>
                                                @break
                                            @case('delivered')
                                                <span class="label label-success">
                                                    <i class="fa fa-check"></i>
                                                    {{ $package->package_status_label }}
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="label label-danger">
                                                    <i class="fa fa-times"></i>
                                                    {{ $package->package_status_label }}
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $package->created_at->format('d/m/Y H:i') }}
                                            <br>
                                            <em>({{ $package->created_at->diffForHumans() }})</em>
                                        </small>
                                    </td>
                                    <td>
                                        @if($package->shipments && $package->shipments->count() > 0)
                                            <div class="shipments-list">
                                                @php
                                                    // Màu sắc cho trạng thái shipment
                                                    $statusColors = [
                                                        'pending' => '#f0ad4e',
                                                        'processing' => '#5bc0de',
                                                        'shipped' => '#337ab7',
                                                        'delivered' => '#5cb85c',
                                                        'cancelled' => '#d9534f'
                                                    ];
                                                    
                                                    // Labels cho shipping partners
                                                    $partnerLabels = [
                                                        'atan' => 'A Tần',
                                                        'other' => 'Khác',
                                                        'oanh' => 'Oanh',
                                                        'nga' => 'Nga', 
                                                        'fe' => 'Xuân Phê'
                                                    ];
                                                    
                                                    $shipmentCount = $package->shipments->count();
                                                    $displayShipments = $package->shipments->take(4);
                                                @endphp
                                                
                                                @foreach($displayShipments as $shipment)
                                                    @php
                                                        $status = $shipment->shipment_status ?? 'pending';
                                                        $statusColor = $statusColors[$status] ?? '#777';
                                                        $partner = $shipment->shipping_partner ?? 'Chưa có';
                                                        $partnerLabel = $partnerLabels[$partner] ?? $partner;
                                                        $statusLabel = $shipment->shipment_status_label ?? 'Chưa có';
                                                        $title = "Trạng thái: {$statusLabel} | Đối tác vc: {$partnerLabel}";
                                                    @endphp
                                                    
                                                    <a href="{{ admin_url('shipments/' . $shipment->id) }}" 
                                                       class="btn btn-xs btn-default shipment-btn" 
                                                       style="margin: 1px; font-weight: 600; 
                                                              border-left: 4px solid {{ $statusColor }}; 
                                                              padding-left: 8px; display: inline-block;"
                                                       title="{{ $title }}">
                                                        {{ $shipment->tracking_code ?? 'SH-' . $shipment->id }}
                                                    </a>
                                                    @if(!$loop->last)<br>@endif
                                                @endforeach
                                                
                                                @if($shipmentCount > 4)
                                                    <br><small class="text-muted">+{{ $shipmentCount - 4 }} khác</small>
                                                @endif
                                                
                                                <div style="margin-top: 5px;">
                                                    <small class="text-info">
                                                        <strong>{{ $shipmentCount }}</strong> vận đơn
                                                    </small>
                                                </div>
                                            </div>
                                        @else
                                            <div class="no-shipments">
                                                <span class="text-muted">
                                                    <i class="fa fa-minus-circle"></i>
                                                    Chưa có vận đơn
                                                </span>
                                                <br>
                                                <a href="{{ admin_url('shipments/create?package_id=' . $package->id) }}" 
                                                   class="btn btn-xs btn-success" 
                                                   style="margin-top: 3px;"
                                                   title="Tạo vận đơn mới">
                                                    <i class="fa fa-plus"></i> Tạo vận đơn
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($package->notes)
                                            <span class="text-muted" title="{{ $package->notes }}">
                                                {{ Str::limit($package->notes, 40) }}
                                            </span>
                                        @else
                                            <em class="text-muted">Không có ghi chú</em>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ admin_url('packages/' . $package->id) }}" 
                                               class="btn btn-primary" 
                                               title="Xem chi tiết">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ admin_url('packages/' . $package->id . '/edit') }}" 
                                               class="btn btn-warning" 
                                               title="Chỉnh sửa">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            @if(in_array($package->package_status, ['pending', 'in_transit', 'delivered_vn']))
                                                <a href="{{ admin_url('packages/' . $package->id . '/update-status') }}" 
                                                   class="btn btn-success" 
                                                   title="Cập nhật trạng thái"
                                                   onclick="return confirm('Xác nhận cập nhật trạng thái kiện hàng {{ $package->package_code }}?')">
                                                    <i class="fa fa-arrow-right"></i>
                                                </a>
                                            @endif
                                            <button class="btn btn-info" 
                                                    onclick="copyToClipboard('{{ $package->package_code }}')"
                                                    title="Copy mã kiện">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                        
                                        @if($package->shipments && $package->shipments->count() > 0)
                                            <div style="margin-top: 5px;">
                                                <a href="{{ admin_url('shipments?package_id[]=' . $package->id) }}" 
                                                   class="btn btn-xs btn-default" 
                                                   title="Xem tất cả vận đơn của kiện này">
                                                    <i class="fa fa-list"></i> Tất cả vận đơn ({{ $package->shipments->count() }})
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center" style="padding: 50px;">
                        <i class="fa fa-search text-muted" style="font-size: 48px;"></i>
                        <h4 class="text-muted">Không tìm thấy kiện hàng nào</h4>
                        <p class="text-muted">
                            @if(request()->hasAny(['search', 'status', 'partner']))
                                Thử điều chỉnh bộ lọc hoặc 
                                <a href="{{ admin_url('dashboard/packages') }}">xóa bộ lọc</a>
                            @else
                                Hiện tại không có kiện hàng nào cần xử lý.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
            
            @if(isset($pendingPackages) && method_exists($pendingPackages, 'hasPages') && $pendingPackages->hasPages())
                <div class="box-footer">
                    <div class="pull-left">
                        <small class="text-muted">
                            Hiển thị {{ $pendingPackages->firstItem() }} - {{ $pendingPackages->lastItem() }} 
                            trong tổng số {{ $pendingPackages->total() }} kiện hàng
                        </small>
                    </div>
                    <div class="pull-right">
                        {{ $pendingPackages->appends(request()->query())->links() }}
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
    margin-bottom: 15px;
}

.table > thead > tr > th {
    border-bottom: 2px solid #ddd;
    font-weight: bold;
    background-color: #f5f5f5;
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

.btn-group-sm > .btn {
    padding: 3px 8px;
    font-size: 11px;
    line-height: 1.4;
}

.progress-group {
    margin-bottom: 10px;
}

.progress-text {
    font-weight: 600;
}

.btn-lg {
    padding: 10px 16px;
    font-size: 14px;
}

/* Styles cho biểu đồ */
.chart-legend {
    padding: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.legend-color {
    flex-shrink: 0;
}

.total-summary {
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
    text-align: center;
}

#partnerChart {
    max-height: 400px !important;
}

/* Styles cho vận đơn */
.shipments-list {
    max-height: 80px;
    overflow-y: auto;
    line-height: 1.3;
}

.shipment-btn {
    text-decoration: none !important;
    margin-bottom: 2px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.shipment-btn:hover {
    background-color: #f5f5f5 !important;
    transform: translateX(2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.no-shipments {
    text-align: center;
    padding: 5px;
}

.shipments-list small {
    font-size: 10px;
    line-height: 1.2;
}

@media (max-width: 768px) {
    .col-md-2 {
        margin-bottom: 10px;
    }
    
    .btn-group-sm > .btn {
        display: block;
        width: 100%;
        margin-bottom: 2px;
    }
    
    #partnerChart {
        max-height: 300px !important;
    }
}
</style>


<!-- Tạo thống kê -->
@if(!empty($partnerTotals))
<script>
// Tạo biểu đồ tròn khi DOM ready
var ctx = document.getElementById('partnerChart').getContext('2d');

// Dữ liệu cho biểu đồ
var chartData = {
    @php
        $chartLabels = [];
        $chartValues = [];
        $chartColors = ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc'];
        $colorIndex = 0;
        
        foreach($partnerTotals as $partner => $total) {
            $label = $partnerLabels[$partner] ?? $partner;
            $chartLabels[] = $label;
            $chartValues[] = $total;
        }
    @endphp
    labels: {!! json_encode($chartLabels) !!},
    datasets: [{
        data: {!! json_encode($chartValues) !!},
        backgroundColor: {!! json_encode(array_slice($chartColors, 0, count($chartLabels))) !!},
        borderWidth: 2,
        borderColor: '#fff'
    }]
};

// Tạo biểu đồ doughnut (donut chart)
var chart = new Chart(ctx, {
    type: 'doughnut',
    data: chartData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false // Ẩn legend mặc định vì đã có custom legend
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                        var percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' kiện (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
@endif