<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use Encore\Admin\Facades\Admin;

class AttendanceWebhookService
{
    /**
     * Webhook URL từ config
     */
    private $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('attendance.webhook_url');
    }

    /**
     * Gửi webhook khi chấm công vào
     */
    public function sendCheckInWebhook(Attendance $attendance)
    {
        if (!$this->webhookUrl) {
            return;
        }

        $data = $this->prepareWebhookData($attendance, 'check_in');
        $this->sendWebhook($data);
    }

    /**
     * Gửi webhook khi chấm công ra
     */
    public function sendCheckOutWebhook(Attendance $attendance)
    {
        if (!$this->webhookUrl) {
            return;
        }

        $data = $this->prepareWebhookData($attendance, 'check_out');
        $this->sendWebhook($data);
    }

    /**
     * Chuẩn bị data cho webhook
     */
    private function prepareWebhookData(Attendance $attendance, $action)
    {
        $user = $attendance->user;
        
        // Tính thời gian làm việc
        $workTime = null;
        if ($attendance->check_in_time && $attendance->check_out_time) {
            $checkIn = $attendance->check_in_time;
            $checkOut = $attendance->check_out_time;
            $diffInMinutes = $checkIn->diffInMinutes($checkOut);
            $hours = intval($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $workTime = sprintf('%02d:%02d', $hours, $minutes);
        }

        return [
            'event' => 'attendance_' . $action,
            'timestamp' => now()->toISOString(),
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email ?? null,
            ],
            'attendance_data' => [
                'id' => $attendance->id,
                'work_date' => $attendance->work_date,
                'check_in_time' => $attendance->check_in_time ? $attendance->check_in_time->toISOString() : null,
                'check_out_time' => $attendance->check_out_time ? $attendance->check_out_time->toISOString() : null,
                'check_in_ip' => $attendance->check_in_ip,
                'check_out_ip' => $attendance->check_out_ip,
                'work_time' => $workTime,
                'status' => $attendance->status,
                'notes' => $attendance->notes,
            ],
            'request_info' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ]
        ];
    }

    /**
     * Gửi HTTP request đến webhook
     */
    private function sendWebhook($data)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'laravel-admin-attendance',
                    'X-Webhook-Signature' => hash_hmac('sha256', json_encode($data), config('attendance.webhook_secret', 'default-secret'))
                ])
                ->post($this->webhookUrl, $data);

            // Log response để debug
            Log::info('Attendance webhook sent', [
                'url' => $this->webhookUrl,
                'status' => $response->status(),
                'response' => $response->body(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance webhook failed', [
                'url' => $this->webhookUrl,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Test webhook connection
     */
    public function testWebhook()
    {
        if (!$this->webhookUrl) {
            return ['success' => false, 'message' => 'Webhook URL not configured'];
        }

        try {
            $testData = [
                'event' => 'webhook_test',
                'timestamp' => now()->toISOString(),
                'message' => 'This is a test webhook from Laravel-Admin Attendance System'
            ];

            $response = Http::timeout(5)->post($this->webhookUrl, $testData);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}