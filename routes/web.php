<?php

use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Web\Admin\StatisticsController;
use App\Http\Controllers\Web\Admin\UserManagementController;
use App\Http\Controllers\Web\ConsultationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PatientController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RiskController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

// HeaLink Medical Dashboard Routes
Route::middleware(['auth', 'verified', 'role:medic,admin'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{user:uuid}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{user:uuid}/chat-log', [PatientController::class, 'chatLog'])->name('patients.chat-log');

    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/{consultation:uuid}/room', [ConsultationController::class, 'room'])->name('consultations.room');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification:uuid}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/risk', [RiskController::class, 'index'])->name('risk.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users');
    Route::get('/admin/statistics', [StatisticsController::class, 'index'])->name('admin.statistics');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
