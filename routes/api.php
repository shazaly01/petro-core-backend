<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- استيراد المتحكمات (Controllers) ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FuelTypeController;
use App\Http\Controllers\Api\IslandController;
use App\Http\Controllers\Api\TankController;
use App\Http\Controllers\Api\PumpController;
use App\Http\Controllers\Api\NozzleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SupplyLogController;
use App\Http\Controllers\Api\DashboardController; // تأكد من وجوده أو قم بإنشائه لاحقاً
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- 1. المسارات العامة (Public Routes) ---
Route::post('/login', [AuthController::class, 'login']);

// --- 2. المسارات المحمية (Protected Routes) ---
Route::middleware('auth:sanctum')->group(function () {

    // --- أ. إدارة المصادقة والمستخدمين ---
    Route::post('/logout', [AuthController::class, 'logout']);

    // جلب بيانات المستخدم الحالي
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\Api\UserResource($request->user()->load('roles'));
    });

    // إدارة المستخدمين والصلاحيات
    Route::apiResource('users', UserController::class);
    Route::get('roles/permissions', [RoleController::class, 'getAllPermissions']);
    Route::apiResource('roles', RoleController::class);


    // --- ب. البنية التحتية للمحطة (Infrastructure) ---
    // هذه المسارات محمية تلقائياً بـ Policies داخل الـ Controllers

    // أنواع الوقود
    Route::apiResource('fuel-types', FuelTypeController::class);

    // الجزر
    Route::apiResource('islands', IslandController::class);

    // الخزانات
    Route::apiResource('tanks', TankController::class);

    // المضخات
    Route::apiResource('pumps', PumpController::class);

    // المسدسات
    Route::apiResource('nozzles', NozzleController::class);


    // --- ج. العمليات اليومية (Daily Operations) ---

    // الورديات (فتح، إغلاق، تقارير)
    Route::apiResource('shifts', ShiftController::class);

    // التكليفات (توزيع العمال ومحاسبتهم)
    Route::apiResource('assignments', AssignmentController::class);

    // المعاملات المالية (دفع إلكتروني، كاش)
    Route::apiResource('transactions', TransactionController::class);

    // سجل التوريدات (تعبئة الخزانات)
    Route::apiResource('supply-logs', SupplyLogController::class);


    // --- د. التقارير والإحصائيات (Reports & Dashboard) ---
    // إذا لم نقم بإنشاء DashboardController بعد، يمكنك تعليق هذا السطر
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/reports/daily-movement', [ReportController::class, 'dailyMovement']);

});
