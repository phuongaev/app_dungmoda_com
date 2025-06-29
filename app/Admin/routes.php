<?php

use Illuminate\Routing\Router;
use App\Admin\Controllers\CashFlowController;
use App\Admin\Controllers\ShipmentController;
use App\Admin\Controllers\AttendanceController;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');

    $router->get('dashboard/packages', 'DashboardController@packages')->name('dashboard.packages');
    // $router->get('dashboard/reports', 'DashboardController@reports')->name('dashboard.reports');
    // $router->get('dashboard/sales', 'DashboardController@sales')->name('dashboard.sales');


    $router->resource('cash', 'CashFlowController');
    $router->post('cash/update/{id}','CashFlowController@custom_update');
    $router->resource('labels', 'LabelController');


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
    
    // AJAX Routes for task completion
    $router->post('daily-tasks/ajax/toggle-completion', 'DailyTaskAjaxController@toggleCompletion');
    $router->post('daily-tasks/ajax/add-note', 'DailyTaskAjaxController@addNote');
    $router->get('daily-tasks/ajax/stats', 'DailyTaskAjaxController@getStats');
    $router->get('daily-tasks/ajax/weekly-report', 'DailyTaskAjaxController@getWeeklyReport');


});
