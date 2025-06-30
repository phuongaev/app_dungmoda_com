<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình webhook cho hệ thống chấm công
    |
    */

    // URL webhook sẽ nhận dữ liệu chấm công
    'webhook_url' => env('ATTENDANCE_WEBHOOK_URL'),

    // Secret key để ký request (tăng bảo mật)
    'webhook_secret' => env('ATTENDANCE_WEBHOOK_SECRET', 'your-secret-key'),

    // Timeout cho HTTP request (giây)
    'webhook_timeout' => env('ATTENDANCE_WEBHOOK_TIMEOUT', 10),

    // Bật/tắt webhook
    'webhook_enabled' => env('ATTENDANCE_WEBHOOK_ENABLED', true),

    // Retry settings
    'webhook_retry_times' => env('ATTENDANCE_WEBHOOK_RETRY', 3),
    'webhook_retry_delay' => env('ATTENDANCE_WEBHOOK_RETRY_DELAY', 1000), // milliseconds

    // Log level cho webhook
    'webhook_log_level' => env('ATTENDANCE_WEBHOOK_LOG_LEVEL', 'info'), // debug, info, warning, error
];