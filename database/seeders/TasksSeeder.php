<?php
// database/seeders/TasksSeeder.php

namespace Database\Seeders;

use App\Models\TaskCategory;
use App\Models\DailyTask;
use Illuminate\Database\Seeder;

class TasksSeeder extends Seeder
{
    public function run()
    {
        // Create Task Categories
        $categories = [
            [
                'name' => 'Công việc hành chính',
                'color' => '#007bff',
                'icon' => 'fa-clipboard',
                'sort_order' => 1
            ],
            [
                'name' => 'Kiểm tra hệ thống',
                'color' => '#28a745', 
                'icon' => 'fa-server',
                'sort_order' => 2
            ],
            [
                'name' => 'Báo cáo',
                'color' => '#ffc107',
                'icon' => 'fa-chart-bar',
                'sort_order' => 3
            ],
            [
                'name' => 'Liên hệ khách hàng',
                'color' => '#17a2b8',
                'icon' => 'fa-phone',
                'sort_order' => 4
            ],
            [
                'name' => 'Vệ sinh - An toàn',
                'color' => '#fd7e14',
                'icon' => 'fa-shield',
                'sort_order' => 5
            ]
        ];

        foreach ($categories as $category) {
            TaskCategory::create($category);
        }

        // Create Daily Tasks
        $tasks = [
            // Công việc hành chính
            [
                'title' => 'Kiểm tra email và phản hồi khách hàng',
                'description' => 'Đọc và trả lời tất cả email từ khách hàng trong vòng 2 giờ',
                'category_id' => 1,
                'priority' => 'high',
                'suggested_time' => '08:00',
                'estimated_minutes' => 30,
                'frequency' => 'daily',
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1
            ],
            [
                'title' => 'Cập nhật bảng chấm công',
                'description' => 'Điền thông tin chấm công và các hoạt động trong ngày',
                'category_id' => 1,
                'priority' => 'medium',
                'suggested_time' => '17:30',
                'estimated_minutes' => 15,
                'frequency' => 'weekdays',
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1
            ],

            // Kiểm tra hệ thống
            [
                'title' => 'Kiểm tra server và database',
                'description' => 'Kiểm tra tình trạng hoạt động của server, database và các service quan trọng',
                'category_id' => 2,
                'priority' => 'urgent',
                'suggested_time' => '08:30',
                'estimated_minutes' => 20,
                'frequency' => 'daily',
                'assigned_roles' => ['administrator', 'manager'],
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1
            ],
            [
                'title' => 'Backup dữ liệu',
                'description' => 'Thực hiện backup database và files quan trọng',
                'category_id' => 2,
                'priority' => 'high',
                'suggested_time' => '23:00',
                'estimated_minutes' => 45,
                'frequency' => 'daily',
                'assigned_roles' => ['administrator'],
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1
            ],

            // Báo cáo
            [
                'title' => 'Báo cáo doanh thu ngày',
                'description' => 'Tổng hợp và gửi báo cáo doanh thu, đơn hàng trong ngày',
                'category_id' => 3,
                'priority' => 'high',
                'suggested_time' => '18:00',
                'estimated_minutes' => 25,
                'frequency' => 'weekdays',
                'assigned_roles' => ['manager'],
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1
            ],
            [
                'title' => 'Báo cáo tuần',
                'description' => 'Tổng hợp báo cáo hoạt động và kết quả kinh doanh tuần',
                'category_id' => 3,
                'priority' => 'medium',
                'suggested_time' => '16:00',
                'estimated_minutes' => 60,
                'frequency' => 'friday',
                'assigned_roles' => ['manager'],
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1
            ],

            // Liên hệ khách hàng
            [
                'title' => 'Gọi điện cho khách hàng VIP',
                'description' => 'Liên hệ với top 5 khách hàng VIP để chăm sóc và tư vấn',
                'category_id' => 4,
                'priority' => 'medium',
                'suggested_time' => '14:00',
                'estimated_minutes' => 40,
                'frequency' => 'weekdays',
                'assigned_roles' => ['staff'],
                'is_required' => false,
                'sort_order' => 1,
                'created_by' => 1
            ],
            [
                'title' => 'Phản hồi reviews và feedback',
                'description' => 'Trả lời các đánh giá và phản hồi từ khách hàng trên website và social media',
                'category_id' => 4,
                'priority' => 'medium',
                'suggested_time' => '15:30',
                'estimated_minutes' => 30,
                'frequency' => 'daily',
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1
            ],

            // Vệ sinh - An toàn
            [
                'title' => 'Kiểm tra an toàn văn phòng',
                'description' => 'Kiểm tra các thiết bị điện, cửa ra vào, hệ thống báo cháy',
                'category_id' => 5,
                'priority' => 'low',
                'suggested_time' => '09:00',
                'estimated_minutes' => 15,
                'frequency' => 'daily',
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1
            ],
            [
                'title' => 'Dọn dẹp khu vực làm việc',
                'description' => 'Vệ sinh bàn làm việc, sắp xếp tài liệu và đồ dùng',
                'category_id' => 5,
                'priority' => 'low',
                'suggested_time' => '17:45',
                'estimated_minutes' => 10,
                'frequency' => 'weekdays',
                'is_required' => false,
                'sort_order' => 2,
                'created_by' => 1
            ]
        ];

        foreach ($tasks as $task) {
            DailyTask::create($task);
        }
    }
}