<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cash;
use App\Models\Label;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;

class CashFlowStatisticsController extends Controller
{
    public function index(Content $content, Request $request)
    {
        return $content
            ->title('Thống kê dòng tiền')
            ->description('Phân tích và dự báo dòng tiền')
            ->body($this->getStatisticsView($request));
    }

    protected function getStatisticsView(Request $request)
    {
        // Xử lý filter
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Data cho biểu đồ 12 tháng
        $chartData = $this->getLast12MonthsData();
        
        // Data thu chi tháng hiện tại theo labels
        $currentMonthData = $this->getCurrentMonthByLabels();
        
        // Data theo khoảng thời gian lọc
        $filteredData = $this->getFilteredData($startDate, $endDate);
        
        // Phân tích và dự báo
        $analysis = $this->getCashFlowAnalysis($chartData, $currentMonthData);
        
        return view('admin.cashflow.statistics_content', compact(
            'chartData',
            'currentMonthData',
            'filteredData',
            'analysis',
            'startDate',
            'endDate'
        ));
    }

    protected function getLast12MonthsData()
    {
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $thu = Cash::where('type', Cash::$THU)
                ->whereYear('time', $month->year)
                ->whereMonth('time', $month->month)
                ->sum('amount');
                
            $chi = Cash::where('type', Cash::$CHI)
                ->whereYear('time', $month->year)
                ->whereMonth('time', $month->month)
                ->sum('amount');
            
            $data[] = [
                'month' => $month->format('m/Y'),
                'thu' => $thu,
                'chi' => $chi,
                'balance' => $thu - $chi
            ];
        }
        
        return $data;
    }

    protected function getCurrentMonthByLabels()
    {
        $currentMonth = Carbon::now();
        
        // Thu theo labels
        $thuByLabels = DB::table('cashs')
            ->join('cash_labels', 'cashs.id', '=', 'cash_labels.cash_id')
            ->join('labels', 'cash_labels.label_id', '=', 'labels.id')
            ->where('cashs.type', Cash::$THU)
            ->whereYear('cashs.time', $currentMonth->year)
            ->whereMonth('cashs.time', $currentMonth->month)
            ->whereNull('cashs.deleted_at')
            ->select('labels.id', 'labels.name', DB::raw('SUM(cashs.amount) as total'))
            ->groupBy('labels.id', 'labels.name')
            ->get();
            
        // Chi theo labels
        $chiByLabels = DB::table('cashs')
            ->join('cash_labels', 'cashs.id', '=', 'cash_labels.cash_id')
            ->join('labels', 'cash_labels.label_id', '=', 'labels.id')
            ->where('cashs.type', Cash::$CHI)
            ->whereYear('cashs.time', $currentMonth->year)
            ->whereMonth('cashs.time', $currentMonth->month)
            ->whereNull('cashs.deleted_at')
            ->select('labels.id', 'labels.name', DB::raw('SUM(cashs.amount) as total'))
            ->groupBy('labels.id', 'labels.name')
            ->get();
            
        return [
            'thu' => $thuByLabels,
            'chi' => $chiByLabels,
            'totalThu' => $thuByLabels->sum('total'),
            'totalChi' => $chiByLabels->sum('total')
        ];
    }

    protected function getFilteredData($startDate, $endDate)
    {
        $thu = Cash::where('type', Cash::$THU)
            ->whereBetween('time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');
            
        $chi = Cash::where('type', Cash::$CHI)
            ->whereBetween('time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');
            
        // Chi tiết theo ngày
        $dailyData = Cash::whereBetween('time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('DATE(time) as date, 
                SUM(CASE WHEN type = ' . Cash::$THU . ' THEN amount ELSE 0 END) as thu,
                SUM(CASE WHEN type = ' . Cash::$CHI . ' THEN amount ELSE 0 END) as chi')
            ->groupBy(DB::raw('DATE(time)'))
            ->orderBy('date')
            ->get();
            
        return [
            'totalThu' => $thu,
            'totalChi' => $chi,
            'balance' => $thu - $chi,
            'dailyData' => $dailyData
        ];
    }

    protected function getCashFlowAnalysis($chartData, $currentMonthData)
    {
        // Tính toán xu hướng
        $recentMonths = array_slice($chartData, -3);
        $avgBalance = collect($recentMonths)->avg('balance');
        $trend = $this->calculateTrend($chartData);
        
        // Dự báo 3 tháng tới
        $forecast = $this->forecastNext3Months($chartData);
        
        // Phân tích chi tiêu
        $spendingAnalysis = $this->analyzeSpending($currentMonthData);
        
        // Đề xuất
        $recommendations = $this->generateRecommendations(
            $avgBalance, 
            $trend, 
            $spendingAnalysis
        );
        
        return [
            'trend' => $trend,
            'avgBalance' => $avgBalance,
            'forecast' => $forecast,
            'spendingAnalysis' => $spendingAnalysis,
            'recommendations' => $recommendations
        ];
    }

    protected function calculateTrend($data)
    {
        $recentBalances = array_slice(array_column($data, 'balance'), -6);
        $n = count($recentBalances);
        
        if ($n < 2) return 'stable';
        
        // Linear regression đơn giản
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $recentBalances[$i];
            $sumXY += $i * $recentBalances[$i];
            $sumX2 += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        if ($slope > 1000000) return 'increasing';
        if ($slope < -1000000) return 'decreasing';
        return 'stable';
    }

    protected function forecastNext3Months($data)
    {
        $forecast = [];
        $recentData = array_slice($data, -6);
        
        // Tính trung bình thu chi 6 tháng gần nhất
        $avgThu = collect($recentData)->avg('thu');
        $avgChi = collect($recentData)->avg('chi');
        
        // Tính hệ số tăng trưởng
        $growthRate = $this->calculateGrowthRate($recentData);
        
        for ($i = 1; $i <= 3; $i++) {
            $month = Carbon::now()->addMonths($i);
            $forecastThu = $avgThu * (1 + $growthRate['thu']);
            $forecastChi = $avgChi * (1 + $growthRate['chi']);
            
            $forecast[] = [
                'month' => $month->format('m/Y'),
                'thu' => round($forecastThu),
                'chi' => round($forecastChi),
                'balance' => round($forecastThu - $forecastChi)
            ];
        }
        
        return $forecast;
    }

    protected function calculateGrowthRate($data)
    {
        $n = count($data);
        if ($n < 2) return ['thu' => 0, 'chi' => 0];
        
        $firstThu = $data[0]['thu'];
        $lastThu = $data[$n-1]['thu'];
        $firstChi = $data[0]['chi'];
        $lastChi = $data[$n-1]['chi'];
        
        $thuGrowth = $firstThu > 0 ? (($lastThu - $firstThu) / $firstThu) / $n : 0;
        $chiGrowth = $firstChi > 0 ? (($lastChi - $firstChi) / $firstChi) / $n : 0;
        
        return [
            'thu' => $thuGrowth,
            'chi' => $chiGrowth
        ];
    }

    protected function analyzeSpending($currentMonthData)
    {
        $analysis = [];
        
        // Top 3 nguồn thu
        $topRevenue = collect($currentMonthData['thu'])
            ->sortByDesc('total')
            ->take(3)
            ->values();
            
        // Top 3 khoản chi
        $topExpense = collect($currentMonthData['chi'])
            ->sortByDesc('total')
            ->take(3)
            ->values();
            
        // Tỷ lệ chi/thu
        $ratio = $currentMonthData['totalThu'] > 0 
            ? ($currentMonthData['totalChi'] / $currentMonthData['totalThu']) * 100 
            : 0;
            
        return [
            'topRevenue' => $topRevenue,
            'topExpense' => $topExpense,
            'expenseRatio' => round($ratio, 2)
        ];
    }

    protected function generateRecommendations($avgBalance, $trend, $analysis)
    {
        $recommendations = [];
        
        // Phân tích xu hướng
        if ($trend === 'decreasing') {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Xu hướng giảm',
                'content' => 'Dòng tiền có xu hướng giảm trong 6 tháng gần đây. Cần xem xét cắt giảm chi phí không cần thiết.'
            ];
        }
        
        // Phân tích tỷ lệ chi/thu
        if ($analysis['expenseRatio'] > 80) {
            $recommendations[] = [
                'type' => 'danger',
                'title' => 'Chi phí cao',
                'content' => 'Tỷ lệ chi/thu đang ở mức ' . $analysis['expenseRatio'] . '%. Nên duy trì dưới 80% để đảm bảo dòng tiền khỏe mạnh.'
            ];
        }
        
        // Phân tích balance
        if ($avgBalance < 0) {
            $recommendations[] = [
                'type' => 'danger',
                'title' => 'Âm dòng tiền',
                'content' => 'Dòng tiền trung bình đang âm. Cần tăng cường nguồn thu hoặc cắt giảm chi phí ngay lập tức.'
            ];
        } elseif ($avgBalance < 5000000) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Dòng tiền thấp',
                'content' => 'Dòng tiền dự phòng thấp. Nên xây dựng quỹ dự phòng ít nhất 3-6 tháng chi phí hoạt động.'
            ];
        }
        
        // Đề xuất tối ưu
        if (count($analysis['topExpense']) > 0) {
            $topExpense = $analysis['topExpense']->first();
            if ($topExpense->total > $avgBalance * 0.3) {
                $recommendations[] = [
                    'type' => 'info',
                    'title' => 'Tối ưu chi phí',
                    'content' => 'Khoản chi "' . $topExpense->name . '" chiếm tỷ trọng lớn. Xem xét đàm phán lại hoặc tìm nhà cung cấp thay thế.'
                ];
            }
        }
        
        // Đề xuất theo mùa
        $currentMonth = Carbon::now()->month;
        if (in_array($currentMonth, [11, 12, 1])) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Mùa cao điểm',
                'content' => 'Đang trong mùa mua sắm cuối năm. Cần chuẩn bị nguồn vốn lưu động đủ lớn để tận dụng cơ hội kinh doanh.'
            ];
        }
        
        return $recommendations;
    }
}