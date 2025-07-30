<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\DailyTaskReminderController;
use App\Http\Controllers\Api\N8nWebhookController;
use App\Http\Controllers\Api\PosOrderApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'webhook'], function () {
   Route::post('/telegram', [WebhookController::class,'telegram']);
   Route::get('/admin-config/{key}', [WebhookController::class,'getAdminConfig']);

   Route::post('/pos-pancake', [WebhookController::class,'handlePosPancake']);

});

// Media API Routes
Route::prefix('media')->group(function () {
   Route::get('/code/{variationsCode}', [MediaController::class, 'getMediaByVariationsCodeParam']);
   Route::get('/source-name/{mediaId}', [MediaController::class, 'getSourceNameByMediaId']);
});


// Api cho Daily Tasks
Route::group(['prefix' => 'daily-tasks'], function () {
   Route::get('/reminders', [DailyTaskReminderController::class, 'index']);
});


Route::prefix('n8n')->group(function () {
    Route::get('/health', [N8nWebhookController::class, 'healthCheck']);
    Route::post('/workflow-history', [N8nWebhookController::class, 'createWorkflowHistory']);
    Route::post('/workflow-history/batch', [N8nWebhookController::class, 'batchCreateWorkflowHistory']);
    Route::get('/order-status', [N8nWebhookController::class, 'checkOrderStatus']);
});

// =================== POS ORDER WORKFLOW API ===================
Route::group(['prefix' => 'pos-orders'], function () {
   Route::get('/test', [PosOrderApiController::class, 'test']);
   Route::get('/filter', [PosOrderApiController::class, 'filterOrders']);
   Route::post('/before-date-not-run-workflow', [PosOrderApiController::class, 'getOrdersBeforeDateNotRunWorkflow']);
   Route::get('/workflow-statistics', [PosOrderApiController::class, 'getWorkflowStatistics']);
   Route::get('/not-run-workflow', [PosOrderApiController::class, 'getOrdersNotRunWorkflow']);
});