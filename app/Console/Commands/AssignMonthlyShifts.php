<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Role;
use App\Models\EveningShift; // Chúng ta sẽ tạo Model này ở bước tiếp theo
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssignMonthlyShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:assign-monthly-shifts {--month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign evening shifts to sales team for a whole month';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to assign monthly shifts...');

        // 1. Xác định tháng cần xếp lịch
        $monthInput = $this->option('month');
        $targetDate = $monthInput ? Carbon::createFromFormat('Y-m', $monthInput) : Carbon::now();
        $month = $targetDate->month;
        $year = $targetDate->year;

        $this->info("Processing schedule for: {$targetDate->format('F Y')}");

        // 2. Lấy danh sách nhân viên sale_team
        $saleTeamRole = Role::where('slug', 'sale_team')->first();
        if (!$saleTeamRole) {
            $this->error('Role "sale_team" not found.');
            return 1;
        }
        $salesStaff = $saleTeamRole->administrators()->pluck('id')->toArray();

        if (empty($salesStaff)) {
            $this->warn('No staff found in "sale_team". Aborting.');
            return 1;
        }
        
        $this->info('Found ' . count($salesStaff) . ' staff in sale_team.');

        // 3. Xóa lịch cũ của tháng đó để tạo lại
        EveningShift::whereYear('shift_date', $year)->whereMonth('shift_date', $month)->delete();
        $this->info('Cleared old shifts for the month.');

        // 4. Lặp qua các ngày trong tháng và phân công
        $daysInMonth = $targetDate->daysInMonth;
        $assignments = [];

        // Tạo một "vòng lặp" nhân viên để phân bổ đều
        $staffCycle = $salesStaff;
        shuffle($staffCycle); // Xáo trộn danh sách ban đầu để ngẫu nhiên
        $staffPointer = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);

            // Nếu vòng lặp đã đi hết, xáo trộn lại và bắt đầu lại
            if ($staffPointer >= count($staffCycle)) {
                $staffPointer = 0;
                shuffle($staffCycle);
            }
            
            $assignedUserId = $staffCycle[$staffPointer];

            $assignments[] = [
                'admin_user_id' => $assignedUserId,
                'shift_date' => $currentDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $staffPointer++;
        }

        // 5. Lưu lịch mới vào database
        EveningShift::insert($assignments);

        $this->info("Successfully assigned shifts for {$daysInMonth} days in {$targetDate->format('F Y')}.");
        Log::info("Evening shifts for {$targetDate->format('F Y')} have been assigned.");
        return 0;
    }
}