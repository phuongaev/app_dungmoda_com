<?php

use Illuminate\Routing\Router;
use App\Admin\Controllers\CashFlowController;
use App\Admin\Controllers\ShipmentController;
use App\Admin\Controllers\AttendanceController;
use App\Admin\Controllers\DailyTaskProgressController;
use App\Admin\Controllers\ShiftCalendarController;

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
    $router->get('attendance-reports', 'AttendanceReportController@index');
    


    // Task Categories
    $router->resource('task-categories', TaskCategoryController::class);
    // Daily Tasks
    $router->resource('daily-tasks', DailyTaskController::class);
    // Routes cho quản lý tiến độ công việc nhân viên
    $router->group([
        'prefix' => 'daily-task-progress',
        'as' => 'daily-task-progress.'
    ], function ($router) {
        $router->get('/', [DailyTaskProgressController::class, 'index'])->name('index');
        $router->get('/daily', [DailyTaskProgressController::class, 'daily'])->name('daily');
        $router->get('/weekly', [DailyTaskProgressController::class, 'weekly'])->name('weekly');
        $router->get('/{user}/detail', [DailyTaskProgressController::class, 'userDetail'])->name('user-detail');

        // Progress controller endpoints
        $router->get('/quick-stats', 'DailyTaskProgressController@getQuickStats');
        $router->get('/tasks-needing-review', 'DailyTaskProgressController@getTasksNeedingReview');
        $router->get('/overdue-tasks', 'DailyTaskProgressController@getOverdueTasks');
    });
    // AJAX Routes for task completion
    $router->post('daily-tasks/ajax/toggle-completion', 'DailyTaskAjaxController@toggleCompletion');
    $router->post('daily-tasks/ajax/add-note', 'DailyTaskAjaxController@addNote');
    $router->get('daily-tasks/ajax/stats', 'DailyTaskAjaxController@getStats');
    $router->get('daily-tasks/toggle-review/{completion}/{status}', 'DailyTaskController@toggleReview');
    $router->post('daily-tasks/update-completion-note/{completion}', 'DailyTaskController@updateCompletionNote');
    // API endpoints mới cho AJAX
    $router->get('daily-tasks/ajax/quick-stats', 'DailyTaskAjaxController@getQuickStats');
    $router->get('daily-tasks/ajax/tasks', 'DailyTaskAjaxController@getTasks');
    $router->post('daily-tasks/ajax/batch-update', 'DailyTaskAjaxController@batchUpdate');
    $router->get('daily-tasks/ajax/completion-history', 'DailyTaskAjaxController@getCompletionHistory');
    $router->post('daily-tasks/ajax/mark-all-completed', 'DailyTaskAjaxController@markAllCompleted');
    $router->post('daily-tasks/ajax/reset-all-tasks', 'DailyTaskAjaxController@resetAllTasks');

    


    ############# OrderController #############
    $router->resource('pos-orders', 'PosOrderController');
    // Additional routes for POS Orders
    $router->post('pos-orders/import', 'PosOrderController@import')->name('pos-orders.import');
    $router->get('pos-orders/export', 'PosOrderController@export')->name('pos-orders.export');
    $router->post('pos-orders/bulk-update-status', 'PosOrderController@bulkUpdateStatus')->name('pos-orders.bulk-update-status');

    // Get contacts
    $router->group(['prefix' => 'delivery'], function ($router) {
        $router->get('contacts', 'PosOrderController@getDeliveryContacts')->name('delivery.contacts');
    });

    

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


    ############# Existing Shift Calendar Routes #############
    $router->get('shift-calendar', 'ShiftCalendarController@index')->name('shift-calendar.index');
    $router->get('shifts/events', 'ShiftCalendarController@events')->name('shifts.events');
    $router->post('shifts/update', 'ShiftCalendarController@updateShift')->name('shifts.update');
    $router->post('shifts/swap', 'ShiftCalendarController@swapShifts')->name('shifts.swap');
    $router->post('shifts/change-person', 'ShiftCalendarController@changePerson')->name('shifts.change_person');
    $router->post('shifts/create', 'ShiftCalendarController@createShift')->name('shifts.create');
    $router->delete('shifts/delete', 'ShiftCalendarController@deleteShift')->name('shifts.delete');
    $router->get('shifts/available-users', 'ShiftCalendarController@getAvailableUsers')->name('shifts.available_users');
    $router->get('shifts/available', 'ShiftCalendarController@getAvailableShifts')->name('shifts.available');
    $router->post('shifts/create-leave', 'ShiftCalendarController@createLeaveForEmployee')->name('shifts.create_leave');
    $router->get('shifts/leave-events', 'ShiftCalendarController@leaveEvents')->name('shifts.leave_events');


    ############# NEW: Admin Leave Request Management #############
    $router->resource('leave-requests', 'LeaveRequestController')->except(['create', 'store', 'edit', 'update']);
    $router->post('leave-requests/{id}/approve', 'LeaveRequestController@approve')->name('leave-requests.approve');
    $router->post('leave-requests/{id}/reject', 'LeaveRequestController@reject')->name('leave-requests.reject');
    $router->post('leave-requests/{id}/cancel', 'LeaveRequestController@cancel')->name('leave-requests.cancel');
    $router->post('leave-requests/change-person', 'LeaveRequestController@changePerson')->name('leave-requests.change_person');

    ############# NEW: Admin Shift Swap Request Management #############
    $router->resource('shift-swap-requests', 'ShiftSwapRequestController')->except(['create', 'store', 'edit', 'update']);
    $router->post('shift-swap-requests/{id}/approve', 'ShiftSwapRequestController@approve')->name('shift-swap-requests.approve');
    $router->post('shift-swap-requests/{id}/reject', 'ShiftSwapRequestController@reject')->name('shift-swap-requests.reject');
    $router->post('shift-swap-requests/{id}/cancel', 'ShiftSwapRequestController@cancel')->name('shift-swap-requests.cancel');

    ############# NEW: Employee Leave Request Management #############
    $router->resource('employee-leave-requests', 'EmployeeLeaveRequestController')->except(['edit', 'update', 'destroy']);
    $router->post('employee-leave-requests/{id}/cancel', 'EmployeeLeaveRequestController@cancel')->name('employee-leave-requests.cancel');

    ############# NEW: Employee Shift Swap Management #############
    $router->resource('employee-shift-swaps', 'EmployeeShiftSwapController')->except(['edit', 'update', 'destroy']);
    $router->post('employee-shift-swaps/{id}/cancel', 'EmployeeShiftSwapController@cancel')->name('employee-shift-swaps.cancel');
    $router->get('employee-shift-swaps/get-user-shifts', 'EmployeeShiftSwapController@getUserShifts')->name('employee-shift-swaps.get-user-shifts');


    // ############# WORKFLOW MANAGEMENT #############
    $router->resource('workflows', 'WorkflowController');
    
    // Additional workflow routes
    $router->get('workflows/{id}/statistics', 'WorkflowController@statistics')->name('workflows.statistics');
    $router->post('workflows/bulk-update-status', 'WorkflowController@bulkUpdateStatus')->name('workflows.bulk-update-status');


    // ===== Workflow History Management =====
    $router->resource('workflow-histories', 'WorkflowHistoryController', [
        'except' => ['create', 'store'] // Chỉ cho phép xem và xóa, không tạo mới từ admin
    ]);
    // Additional workflow history routes
    $router->get('workflow-histories/by-order/{orderId}', 'WorkflowHistoryController@byOrder')->name('workflow-histories.by-order');
    $router->get('workflow-histories/by-workflow/{workflowId}', 'WorkflowHistoryController@byWorkflow')->name('workflow-histories.by-workflow');
    $router->get('workflow-histories/statistics', 'WorkflowHistoryController@statistics')->name('workflow-histories.statistics');
    $router->delete('workflow-histories/bulk-delete', 'WorkflowHistoryController@bulkDelete')->name('workflow-histories.bulk-delete');
    // ===== Workflow API Dashboard =====
    $router->get('workflow-dashboard', 'WorkflowDashboardController@index')->name('workflow-dashboard');
    $router->get('workflow-dashboard/analytics', 'WorkflowDashboardController@analytics')->name('workflow-dashboard.analytics');


    // ZALO PAGE
    $router->resource('zalo-pages', ZaloPageController::class);



});
