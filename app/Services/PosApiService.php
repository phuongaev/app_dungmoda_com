<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\SyncJob;
use Exception;

class PosApiService
{
    private $baseUrl = 'https://pos.pages.fm/api/v1';
    private $shopId = '1434896';
    private $apiKey = 'e7dbf89831cf426f9ceec3698e42301d';
    private $timeout = 30; // seconds

    public function __construct()
    {
        // Có thể config từ .env
        $this->apiKey = config('pos.api_key', $this->apiKey);
        $this->shopId = config('pos.shop_id', $this->shopId);
        $this->baseUrl = config('pos.base_url', $this->baseUrl);
    }

    /**
     * Lấy danh sách đơn hàng từ API
     */
    public function getOrders($page = 1, $pageSize = 50, $sortOption = 'inserted_at_asc')
    {
        try {
            $url = "{$this->baseUrl}/shops/{$this->shopId}/orders";
            
            $response = Http::timeout($this->timeout)->get($url, [
                'api_key' => $this->apiKey,
                'page' => $page,
                'page_size' => $pageSize,
                'option_sort' => $sortOption,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log response structure for debugging
                Log::info("POS API Response", [
                    'page' => $page,
                    'status' => $response->status(),
                    'has_data' => isset($data[0]['data']),
                    'total_entries' => $data[0]['total_entries'] ?? null,
                    'total_pages' => $data[0]['total_pages'] ?? null,
                ]);

                return $data[0] ?? null; // API trả về array, lấy phần tử đầu tiên
            }

            throw new Exception("API request failed with status: " . $response->status());

        } catch (Exception $e) {
            Log::error("POS API Error", [
                'page' => $page,
                'error' => $e->getMessage(),
                'url' => $url ?? null,
            ]);
            
            throw $e;
        }
    }

    /**
     * Sync đơn hàng với cơ chế resume từ page cũ
     */
    public function syncOrders($startPage = 1, $pageSize = 50, $maxPages = null)
    {
        // Kiểm tra có job đang chạy không
        if (SyncJob::hasRunningJob()) {
            throw new Exception("Có một sync job khác đang chạy. Vui lòng đợi hoặc dừng job hiện tại.");
        }

        // Tạo hoặc resume sync job
        $syncJob = $this->getOrCreateSyncJob($startPage);
        $syncJob->markAsRunning();

        $currentPage = $syncJob->current_page;
        $syncedRecords = $syncJob->synced_records;
        $totalRecords = 0;
        $totalPages = 0;

        try {
            Log::info("Bắt đầu sync đơn hàng", [
                'sync_job_id' => $syncJob->id,
                'start_page' => $currentPage,
                'page_size' => $pageSize,
            ]);

            while (true) {
                // Kiểm tra giới hạn pages nếu có
                if ($maxPages && ($currentPage - $startPage + 1) > $maxPages) {
                    Log::info("Đã đạt giới hạn số pages", ['max_pages' => $maxPages]);
                    break;
                }

                Log::info("Đang sync page {$currentPage}");

                // Gọi API
                $response = $this->getOrders($currentPage, $pageSize);
                
                if (!$response || !isset($response['data']) || empty($response['data'])) {
                    Log::info("Không có dữ liệu hoặc đã hết trang", ['page' => $currentPage]);
                    break;
                }

                // Lấy thông tin tổng quan lần đầu tiên
                if ($currentPage == $startPage) {
                    $totalRecords = $response['total_entries'] ?? 0;
                    $totalPages = $response['total_pages'] ?? 0;
                    
                    Log::info("Thông tin tổng quan", [
                        'total_records' => $totalRecords,
                        'total_pages' => $totalPages,
                    ]);
                }

                // Xử lý từng đơn hàng
                $orders = $response['data'];
                $pageRecords = 0;

                foreach ($orders as $orderData) {
                    try {
                        Order::createOrUpdateFromApiData($orderData);
                        $pageRecords++;
                        $syncedRecords++;
                    } catch (Exception $e) {
                        Log::warning("Lỗi khi lưu đơn hàng", [
                            'order_id' => $orderData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info("Hoàn thành page {$currentPage}", [
                    'records_in_page' => $pageRecords,
                    'total_synced' => $syncedRecords,
                ]);

                // Cập nhật tiến trình
                $syncJob->updateProgress($currentPage, $syncedRecords, $totalPages, $totalRecords);

                // Chuyển sang page tiếp theo
                $currentPage++;

                // Nghỉ 1 giây để tránh spam API
                sleep(1);
            }

            $syncJob->markAsCompleted();
            
            Log::info("Hoàn thành sync đơn hàng", [
                'sync_job_id' => $syncJob->id,
                'total_synced' => $syncedRecords,
                'pages_processed' => $currentPage - $startPage,
            ]);

            return [
                'success' => true,
                'synced_records' => $syncedRecords,
                'pages_processed' => $currentPage - $startPage,
                'sync_job' => $syncJob,
            ];

        } catch (Exception $e) {
            $syncJob->markAsFailed($e->getMessage());
            
            Log::error("Lỗi trong quá trình sync", [
                'sync_job_id' => $syncJob->id,
                'error' => $e->getMessage(),
                'current_page' => $currentPage,
            ]);

            throw $e;
        }
    }

    /**
     * Resume sync job từ page đã dừng
     */
    public function resumeSync($syncJobId = null)
    {
        $syncJob = $syncJobId 
            ? SyncJob::findOrFail($syncJobId)
            : SyncJob::getLastOrdersSyncJob();

        if (!$syncJob) {
            throw new Exception("Không tìm thấy sync job để resume");
        }

        if ($syncJob->status === SyncJob::STATUS_COMPLETED) {
            throw new Exception("Sync job đã hoàn thành");
        }

        return $this->syncOrders($syncJob->current_page);
    }

    /**
     * Lấy hoặc tạo sync job
     */
    private function getOrCreateSyncJob($startPage)
    {
        // Tìm job pending hoặc paused gần nhất
        $existingJob = SyncJob::orders()
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_PAUSED])
            ->latest()
            ->first();

        if ($existingJob) {
            Log::info("Resume existing sync job", ['job_id' => $existingJob->id]);
            return $existingJob;
        }

        // Tạo job mới
        Log::info("Tạo sync job mới", ['start_page' => $startPage]);
        return SyncJob::createOrdersSyncJob($startPage);
    }

    /**
     * Dừng sync job đang chạy
     */
    public function pauseRunningJob()
    {
        $runningJob = SyncJob::running()->first();
        
        if ($runningJob) {
            $runningJob->markAsPaused();
            Log::info("Đã dừng sync job", ['job_id' => $runningJob->id]);
            return $runningJob;
        }

        return null;
    }

    /**
     * Lấy thông tin trạng thái sync
     */
    public function getSyncStatus()
    {
        $lastJob = SyncJob::getLastOrdersSyncJob();
        
        if (!$lastJob) {
            return [
                'status' => 'never_run',
                'message' => 'Chưa từng chạy sync',
            ];
        }

        return [
            'status' => $lastJob->status,
            'current_page' => $lastJob->current_page,
            'total_pages' => $lastJob->total_pages,
            'synced_records' => $lastJob->synced_records,
            'total_records' => $lastJob->total_records,
            'progress_percentage' => $lastJob->getProgressPercentage(),
            'estimated_time_remaining' => $lastJob->getEstimatedTimeRemaining(),
            'started_at' => $lastJob->started_at,
            'completed_at' => $lastJob->completed_at,
            'error_message' => $lastJob->error_message,
        ];
    }
}