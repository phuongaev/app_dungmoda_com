<div class="row">
    <div class="col-md-4 text-center">
        <h4 class="text-success">Thu nhập</h4>
        <h3>{{ number_format($currentThu) }}đ</h3>
    </div>
    <div class="col-md-4 text-center">
        <h4 class="text-danger">Chi phí</h4>
        <h3>{{ number_format($currentChi) }}đ</h3>
    </div>
    <div class="col-md-4 text-center">
        <h4 class="{{ $currentBalance >= 0 ? 'text-primary' : 'text-warning' }}">Balance</h4>
        <h3>{{ number_format($currentBalance) }}đ</h3>
        <small class="{{ $percentChange >= 0 ? 'text-success' : 'text-danger' }}">
            <i class="fa fa-{{ $percentChange >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
            {{ abs(round($percentChange, 1)) }}% so với tháng trước
        </small>
    </div>
</div>
<div class="text-center mt-3">
    <a href="{{ admin_url('cashflow-statistics') }}" class="btn btn-sm btn-primary">
        <i class="fa fa-line-chart"></i> Xem chi tiết
    </a>
</div>