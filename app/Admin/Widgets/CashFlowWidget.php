<?php

namespace App\Admin\Widgets;

use App\Models\Cash;
use Carbon\Carbon;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Widget;

class CashFlowWidget extends Box
{
    protected $title = 'Tổng quan dòng tiền';
    
    public function __construct()
    {
        parent::__construct();
        $this->content($this->getCashFlowSummary());
    }
    
    protected function getCashFlowSummary()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        
        // Thu chi tháng hiện tại
        $currentThu = Cash::where('type', Cash::$THU)
            ->whereMonth('time', $currentMonth->month)
            ->whereYear('time', $currentMonth->year)
            ->sum('amount');
            
        $currentChi = Cash::where('type', Cash::$CHI)
            ->whereMonth('time', $currentMonth->month)
            ->whereYear('time', $currentMonth->year)
            ->sum('amount');
        
        // Thu chi tháng trước
        $lastThu = Cash::where('type', Cash::$THU)
            ->whereMonth('time', $lastMonth->month)
            ->whereYear('time', $lastMonth->year)
            ->sum('amount');
            
        $lastChi = Cash::where('type', Cash::$CHI)
            ->whereMonth('time', $lastMonth->month)
            ->whereYear('time', $lastMonth->year)
            ->sum('amount');
        
        $currentBalance = $currentThu - $currentChi;
        $lastBalance = $lastThu - $lastChi;
        
        // Tính phần trăm thay đổi
        $percentChange = $lastBalance != 0 ? (($currentBalance - $lastBalance) / abs($lastBalance)) * 100 : 0;
        
        return view('admin.widgets.cashflow', compact(
            'currentThu',
            'currentChi',
            'currentBalance',
            'percentChange'
        ));
    }
}