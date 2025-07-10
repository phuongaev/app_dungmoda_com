<?php

use Illuminate\Routing\Router;
use App\Admin\Controllers\CashFlowController;
use App\Admin\Controllers\ShipmentController;
use App\Admin\Controllers\AttendanceController;
use App\Admin\Controllers\DailyTaskProgressController;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->resource('auth/users', UserController::class)->names('auth.users');

    $router->get('/', 'HomeController@index')->name('home');

    $router->get('dashboard/packages', 'DashboardController@packages')->name('dashboard.packages');
    // $router->get('dashboard/reports', 'DashboardController@reports')->name('dashboard.reports');
    // $router->get('dashboard/sales', 'DashboardController@sales')->name('dashboard.sales');


    $router->resource('cash', 'CashFlowController');
    $router->post('cash/update/{id}','CashFlowController@custom_update');
    $router->resource('labels', 'LabelController');

    $router->get('cashflow-statistics', 'CashFlowStatisticsController@index')->name('admin.cashflow.statistics');


    ############# BaseStatusController #############
    $router->resource('base-status', 'BaseStatusController');

    ############# CustomerController #############
    $router->resource('customers', 'CustomerController');

    ############# DatasetController #############
    $router->resource('datasets', 'DatasetController');

    ############# FanPageController #############
    $router->resource('fanpages', 'FanPageController');

    ############# MediaListController #############
    $router->resource('media-lists', 'MediaListController');

    ############# MediaSourceController #############
    $router->resource('media-sources', 'MediaSourceController');

    ############# OmniProfileController #############
    $router->resource('omni-profiles', 'OmniProfileController');

    ############# ProductController #############
    $router->resource('products', 'ProductController');
    
    ############# ZaloTaskController #############
    $router->resource('zalo-tasks', 'ZaloTaskController');

    ############# ImportOrderController #############
    $router->resource('import-orders', 'ImportOrderController');

    ############# PackageController #############
    $router->resource('packages', 'PackageController');
    $router->get('packages/{id}/update-status', 'PackageController@updateStatus')->name('packages.update-status');
    // $router->get('packages-dashboard', 'PackageController@packagesDashboard')->name('packages.dashboard');

    ############# ShipmentController #############
    $router->resource('shipments', 'ShipmentController');

    ############# Hệ thống chấm công #############
    $router->resource('attendances', 'AttendanceController');
    $router->post('attendance/check-in', 'AttendanceController@checkIn');
    $router->post('attendance/check-out', 'AttendanceController@checkOut');
    $router->get('attendance/today-status', 'AttendanceController@todayStatus');
    $router->get('attendance/today-history', 'AttendanceController@todayHistory');


    // Task Categories
    $router->resource('task-categories', TaskCategoryController::class);
    
    // Daily Tasks
    $router->resource('daily-tasks', DailyTaskController::class);

    // Routes cho quản lý tiến độ công việc nhân viên
    $router->group([
        'prefix' => 'daily-task-progress',
        'as' => 'daily-task-progress.'
    ], function ($router) {
        // Dashboard tổng quan
        $router->get('/', [DailyTaskProgressController::class, 'index'])->name('index');
        // Tiến độ theo ngày
        $router->get('/daily', [DailyTaskProgressController::class, 'daily'])->name('daily');
        // Tiến độ theo tuần
        $router->get('/weekly', [DailyTaskProgressController::class, 'weekly'])->name('weekly');
        // Chi tiết tiến độ của một nhân viên
        $router->get('/{user}/detail', [DailyTaskProgressController::class, 'userDetail'])->name('user-detail');
    });
    
    // AJAX Routes for task completion
    $router->post('daily-tasks/ajax/toggle-completion', 'DailyTaskAjaxController@toggleCompletion');
    $router->post('daily-tasks/ajax/add-note', 'DailyTaskAjaxController@addNote');
    $router->get('daily-tasks/ajax/stats', 'DailyTaskAjaxController@getStats');


    ############# OrderController #############
    $router->resource('pos-orders', 'PosOrderController');
    
    // Additional routes for POS Orders
    $router->post('pos-orders/import', 'PosOrderController@import')->name('pos-orders.import');
    $router->get('pos-orders/export', 'PosOrderController@export')->name('pos-orders.export');
    $router->post('pos-orders/bulk-update-status', 'PosOrderController@bulkUpdateStatus')->name('pos-orders.bulk-update-status');

    // POS Order Statuses routes
    $router->resource('pos-order-statuses', 'PosOrderStatusController');
    
    // Additional routes for statuses
    $router->get('pos-order-statuses/refresh-cache', 'PosOrderStatusController@refreshCache')->name('pos-order-statuses.refresh-cache');

    // ===== Workflow History Management =====
    $router->resource('workflow-histories', 'WorkflowHistoryController', [
        'except' => ['create', 'store'] // Chỉ cho phép xem và xóa, không tạo mới từ admin
    ]);
    // Additional workflow history routes
    $router->get('workflow-histories/by-order/{orderId}', 'WorkflowHistoryController@byOrder')->name('workflow-histories.by-order');
    $router->get('workflow-histories/statistics', 'WorkflowHistoryController@statistics')->name('workflow-histories.statistics');
    $router->delete('workflow-histories/bulk-delete', 'WorkflowHistoryController@bulkDelete')->name('workflow-histories.bulk-delete');


});
