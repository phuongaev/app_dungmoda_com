<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\DailyTaskReminderController;

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