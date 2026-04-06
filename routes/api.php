<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\MedicController;
use App\Http\Controllers\Api\MentalStatusController;
use App\Http\Controllers\Api\MoodJournalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ScreeningController;
use App\Http\Controllers\Api\SleepController;
use App\Http\Controllers\Api\VitalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------------------
    // Auth — unauthenticated
    // -------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
        Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    });

    // -------------------------------------------------------------------------
    // Authenticated API routes (Sanctum token)
    // -------------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
            Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
            Route::put('profile', [AuthController::class, 'updateProfile'])->name('api.auth.profile.update');
        });

        // Vitals & Wearable
        Route::prefix('vitals')->group(function () {
            Route::post('sync', [VitalController::class, 'sync'])->name('api.vitals.sync');
            Route::get('latest', [VitalController::class, 'latest'])->name('api.vitals.latest');
            Route::get('history', [VitalController::class, 'history'])->name('api.vitals.history');
        });

        // Sleep Logs
        Route::prefix('sleep')->group(function () {
            Route::post('/', [SleepController::class, 'store'])->name('api.sleep.store');
            Route::get('history', [SleepController::class, 'history'])->name('api.sleep.history');
        });

        // Health Screening
        Route::prefix('screening')->group(function () {
            Route::put('/', [ScreeningController::class, 'upsert'])->name('api.screening.upsert');
            Route::get('latest', [ScreeningController::class, 'latest'])->name('api.screening.latest');
        });

        // Chat
        Route::prefix('chat')->group(function () {
            Route::post('/', [ChatController::class, 'store'])->name('api.chat.store');
            Route::get('history', [ChatController::class, 'history'])->name('api.chat.history');
        });

        // Mood Journal
        Route::prefix('mood')->group(function () {
            Route::post('/', [MoodJournalController::class, 'store'])->name('api.mood.store');
            Route::get('/', [MoodJournalController::class, 'index'])->name('api.mood.index');
        });

        // Mental Health Status
        Route::prefix('mental-status')->group(function () {
            Route::get('/', [MentalStatusController::class, 'index'])->name('api.mental-status.index');
            Route::get('latest', [MentalStatusController::class, 'latest'])->name('api.mental-status.latest');
        });

        // Medics
        Route::get('medics', [MedicController::class, 'index'])->name('api.medics.index');

        // Consultations
        Route::prefix('consultations')->group(function () {
            Route::get('/', [ConsultationController::class, 'index'])->name('api.consultations.index');
            Route::post('/', [ConsultationController::class, 'store'])->name('api.consultations.store');
            Route::get('{consultation}', [ConsultationController::class, 'show'])->name('api.consultations.show');
            Route::patch('{consultation}/cancel', [ConsultationController::class, 'cancel'])->name('api.consultations.cancel');
            Route::patch('{consultation}/start', [ConsultationController::class, 'start'])->name('api.consultations.start');
            Route::patch('{consultation}/complete', [ConsultationController::class, 'complete'])->name('api.consultations.complete');
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
            Route::put('read-all', [NotificationController::class, 'readAll'])->name('api.notifications.read-all');
            Route::put('{notification:uuid}/read', [NotificationController::class, 'markRead'])->name('api.notifications.read');
        });
    });
});
