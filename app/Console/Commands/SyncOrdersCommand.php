<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PosApiService;

class SyncOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync 
                            {--start-page=1 : Page để bắt đầu sync}
                            {--max-pages= : Số page tối đa để sync}
                            {--resume : Resume từ job bị dừng}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync đơn hàng từ POS API';

    protected $posApiService;

    /**
     * Create a new command instance.
     */
    public function __construct(PosApiService $posApiService)
    {
        parent::__construct();
        $this->posApiService = $posApiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            if ($this->option('resume')) {
                $this->info('Resume sync từ job bị dừng...');
                $result = $this->posApiService->resumeSync();
            } else {
                $startPage = (int) $this->option('start-page');
                $maxPages = $this->option('max-pages') ? (int) $this->option('max-pages') : null;
                
                $this->info("Bắt đầu sync từ page {$startPage}" . ($maxPages ? " (tối đa {$maxPages} pages)" : ""));
                $result = $this->posApiService->syncOrders($startPage, 50, $maxPages);
            }

            $this->info('✅ Sync thành công!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Records synced', $result['synced_records']],
                    ['Pages processed', $result['pages_processed']],
                    ['Job ID', $result['sync_job']->id],
                ]
            );

        } catch (\Exception $e) {
            $this->error('❌ Lỗi sync: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}