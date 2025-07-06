<div class="row">
    <!-- Filter Section -->
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Bộ lọc thời gian</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ admin_url('cashflow-statistics') }}" class="form-inline">
                    <div class="form-group">
                        <label>Từ ngày:</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="form-group">
                        <label>Đến ngày:</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <a href="{{ admin_url('cashflow-statistics') }}" class="btn btn-default">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 12 tháng -->
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Biểu đồ dòng tiền 12 tháng gần đây</h3>
            </div>
            <div class="box-body">
                <canvas id="cashFlowChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Thu Chi tháng hiện tại -->
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Thu nhập tháng hiện tại theo danh mục</h3>
            </div>
            <div class="box-body">
                <canvas id="revenueChart" height="200"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th class="text-right">Số tiền</th>
                                <th class="text-right">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentMonthData['thu'] as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="text-right">{{ number_format($item->total) }}đ</td>
                                <td class="text-right">
                                    {{ $currentMonthData['totalThu'] > 0 ? round(($item->total / $currentMonthData['totalThu']) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Tổng cộng</th>
                                <th class="text-right">{{ number_format($currentMonthData['totalThu']) }}đ</th>
                                <th class="text-right">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Chi phí tháng hiện tại theo danh mục</h3>
            </div>
            <div class="box-body">
                <canvas id="expenseChart" height="200"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th class="text-right">Số tiền</th>
                                <th class="text-right">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentMonthData['chi'] as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="text-right">{{ number_format($item->total) }}đ</td>
                                <td class="text-right">
                                    {{ $currentMonthData['totalChi'] > 0 ? round(($item->total / $currentMonthData['totalChi']) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Tổng cộng</th>
                                <th class="text-right">{{ number_format($currentMonthData['totalChi']) }}đ</th>
                                <th class="text-right">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kết quả lọc -->
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Thống kê theo khoảng thời gian đã lọc</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng thu</span>
                                <span class="info-box-number">{{ number_format($filteredData['totalThu']) }}đ</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng chi</span>
                                <span class="info-box-number">{{ number_format($filteredData['totalChi']) }}đ</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box {{ $filteredData['balance'] >= 0 ? 'bg-blue' : 'bg-yellow' }}">
                            <span class="info-box-icon"><i class="fa fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Số dư</span>
                                <span class="info-box-number">{{ number_format($filteredData['balance']) }}đ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ chi tiết theo ngày -->
                @if($filteredData['dailyData']->count() <= 31)
                <div class="mt-3">
                    <h4>Chi tiết theo ngày</h4>
                    <canvas id="dailyChart" height="100"></canvas>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Phân tích và dự báo -->
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Phân tích và dự báo dòng tiền</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <!-- Xu hướng -->
                    <div class="col-md-3">
                        <div class="small-box bg-{{ $analysis['trend'] === 'increasing' ? 'green' : ($analysis['trend'] === 'decreasing' ? 'red' : 'yellow') }}">
                            <div class="inner">
                                <h3>{{ $analysis['trend'] === 'increasing' ? '↑' : ($analysis['trend'] === 'decreasing' ? '↓' : '→') }}</h3>
                                <p>Xu hướng: {{ $analysis['trend'] === 'increasing' ? 'Tăng' : ($analysis['trend'] === 'decreasing' ? 'Giảm' : 'Ổn định') }}</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-line-chart"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Balance trung bình -->
                    <div class="col-md-3">
                        <div class="small-box bg-aqua">
                            <div class="inner">
                                <h3>{{ number_format($analysis['avgBalance']) }}</h3>
                                <p>Balance TB 3 tháng</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-calculator"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tỷ lệ chi/thu -->
                    <div class="col-md-3">
                        <div class="small-box bg-{{ $analysis['spendingAnalysis']['expenseRatio'] > 80 ? 'red' : 'green' }}">
                            <div class="inner">
                                <h3>{{ $analysis['spendingAnalysis']['expenseRatio'] }}%</h3>
                                <p>Tỷ lệ Chi/Thu</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-percent"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Dự báo tháng tới -->
                    <div class="col-md-3">
                        <div class="small-box bg-purple">
                            <div class="inner">
                                <h3>{{ number_format($analysis['forecast'][0]['balance'] ?? 0) }}</h3>
                                <p>Dự báo tháng tới</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-magic"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dự báo 3 tháng -->
                <h4>Dự báo 3 tháng tới</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th class="text-right">Thu dự kiến</th>
                                <th class="text-right">Chi dự kiến</th>
                                <th class="text-right">Balance dự kiến</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analysis['forecast'] as $forecast)
                            <tr>
                                <td>{{ $forecast['month'] }}</td>
                                <td class="text-right text-success">{{ number_format($forecast['thu']) }}đ</td>
                                <td class="text-right text-danger">{{ number_format($forecast['chi']) }}đ</td>
                                <td class="text-right {{ $forecast['balance'] >= 0 ? 'text-primary' : 'text-warning' }}">
                                    {{ number_format($forecast['balance']) }}đ
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Top Labels -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Top 3 nguồn thu</h4>
                        <ul class="list-group">
                            @forelse($analysis['spendingAnalysis']['topRevenue'] as $item)
                            <li class="list-group-item">
                                <span class="badge bg-green pull-right">{{ number_format($item->total) }}đ</span>
                                {{ $item->name }}
                            </li>
                            @empty
                            <li class="list-group-item">Không có dữ liệu</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Top 3 khoản chi</h4>
                        <ul class="list-group">
                            @forelse($analysis['spendingAnalysis']['topExpense'] as $item)
                            <li class="list-group-item">
                                <span class="badge bg-red pull-right">{{ number_format($item->total) }}đ</span>
                                {{ $item->name }}
                            </li>
                            @empty
                            <li class="list-group-item">Không có dữ liệu</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Khuyến nghị -->
                <h4 class="mt-4">Khuyến nghị từ chuyên gia</h4>
                @foreach($analysis['recommendations'] as $recommendation)
                <div class="alert alert-{{ $recommendation['type'] }}">
                    <h5><i class="icon fa fa-{{ $recommendation['type'] === 'danger' ? 'ban' : ($recommendation['type'] === 'warning' ? 'warning' : 'info') }}"></i> 
                        {{ $recommendation['title'] }}
                    </h5>
                    {{ $recommendation['content'] }}
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biểu đồ 12 tháng
var ctx1 = document.getElementById('cashFlowChart').getContext('2d');
var cashFlowChart = new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_column($chartData, 'month')) !!},
        datasets: [{
            label: 'Thu nhập',
            data: {!! json_encode(array_column($chartData, 'thu')) !!},
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }, {
            label: 'Chi phí',
            data: {!! json_encode(array_column($chartData, 'chi')) !!},
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }, {
            label: 'Balance',
            data: {!! json_encode(array_column($chartData, 'balance')) !!},
            type: 'line',
            fill: false,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});

// Biểu đồ thu nhập theo danh mục
var ctx2 = document.getElementById('revenueChart').getContext('2d');
var revenueChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($currentMonthData['thu']->pluck('name')) !!},
        datasets: [{
            data: {!! json_encode($currentMonthData['thu']->pluck('total')) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.parsed;
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value.toLocaleString('vi-VN') + 'đ (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Biểu đồ chi phí theo danh mục
var ctx3 = document.getElementById('expenseChart').getContext('2d');
var expenseChart = new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($currentMonthData['chi']->pluck('name')) !!},
        datasets: [{
            data: {!! json_encode($currentMonthData['chi']->pluck('total')) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var label = context.label || '';
                        var value = context.parsed;
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value.toLocaleString('vi-VN') + 'đ (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Biểu đồ chi tiết theo ngày (nếu có)
@if($filteredData['dailyData']->count() <= 31)
var ctx4 = document.getElementById('dailyChart').getContext('2d');
var dailyChart = new Chart(ctx4, {
    type: 'line',
    data: {
        labels: {!! json_encode($filteredData['dailyData']->pluck('date')) !!},
        datasets: [{
            label: 'Thu nhập',
            data: {!! json_encode($filteredData['dailyData']->pluck('thu')) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Chi phí',
            data: {!! json_encode($filteredData['dailyData']->pluck('chi')) !!},
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});
@endif
</script>