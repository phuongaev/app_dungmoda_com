<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS API Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình kết nối API POS Pages.fm
    |
    */

    'base_url' => env('POS_API_BASE_URL', 'https://pos.pages.fm/api/v1'),
    
    'shop_id' => env('POS_SHOP_ID', '1434896'),
    
    'api_key' => env('POS_API_KEY', 'e7dbf89831cf426f9ceec3698e42301d'),
    
    'timeout' => env('POS_API_TIMEOUT', 30),
    
    'sync' => [
        'page_size' => env('POS_SYNC_PAGE_SIZE', 50),
        'max_pages_per_run' => env('POS_SYNC_MAX_PAGES', null),
        'sleep_between_requests' => env('POS_SYNC_SLEEP', 1), // seconds
    ],
];