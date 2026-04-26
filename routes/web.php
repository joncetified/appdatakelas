<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SystemToolController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\InitialAdminSetupController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InfrastructureReportController;
use App\Http\Controllers\InfrastructureVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportChatController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::post('/chatbot/message', SupportChatController::class)->name('chatbot.message');

Route::middleware('guest')->group(function (): void {
    Route::get('/setup/admin', [InitialAdminSetupController::class, 'create'])->name('setup.admin.create');
    Route::post('/setup/admin', [InitialAdminSetupController::class, 'store'])->name('setup.admin.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/login/google', [AuthenticatedSessionController::class, 'googleRedirect'])->name('login.google');
    Route::get('/auth-google-callback', [AuthenticatedSessionController::class, 'googleCallback'])->name('login.google.callback');
    Route::get('/login/google/callback', [AuthenticatedSessionController::class, 'googleCallback']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/login/otp', [AuthenticatedSessionController::class, 'createOtp'])->name('login.otp');
    Route::post('/login/otp', [AuthenticatedSessionController::class, 'verifyOtp'])->name('login.otp.verify');
    Route::post('/login/otp/resend', [AuthenticatedSessionController::class, 'resendOtp'])->name('login.otp.resend');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('success', 'Email berhasil diverifikasi. Akun sudah aktif.');
    })->middleware(['signed'])->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link verifikasi email berhasil dikirim ulang.');
    })->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/chat', [ChatbotController::class, 'index'])->name('chat.index');
        Route::post('/chat/message', [ChatbotController::class, 'sendMessage'])->name('chat.message');

        Route::get('/dashboard', DashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::get('/reports', [InfrastructureReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');

        Route::get('/reports/export/excel', [InfrastructureReportController::class, 'exportExcel'])
            ->middleware('permission:reports.view')
            ->name('reports.export.excel');
        Route::get('/reports/export/pdf', [InfrastructureReportController::class, 'exportPdf'])
            ->middleware('permission:reports.view')
            ->name('reports.export.pdf');
        Route::get('/reports/export/print', [InfrastructureReportController::class, 'exportPrint'])
            ->middleware('permission:reports.view')
            ->name('reports.export.print');

        Route::middleware(['permission:reports.create', 'role:ketua_kelas'])->group(function (): void {
            Route::get('/reports/create', [InfrastructureReportController::class, 'create'])->name('reports.create');
            Route::post('/reports', [InfrastructureReportController::class, 'store'])->name('reports.store');
        });

        Route::middleware(['permission:reports.edit', 'role:ketua_kelas'])->group(function (): void {
            Route::get('/reports/{report}/edit', [InfrastructureReportController::class, 'edit'])->name('reports.edit');
            Route::put('/reports/{report}', [InfrastructureReportController::class, 'update'])->name('reports.update');
        });

        Route::delete('/reports/{report}', [InfrastructureReportController::class, 'destroy'])
            ->middleware(['permission:reports.delete'])
            ->name('reports.destroy');

        Route::middleware(['permission:reports.verify', 'role:wali_kelas'])->group(function (): void {
            Route::post('/reports/{report}/verification', [InfrastructureVerificationController::class, 'update'])
                ->name('reports.verification.update');
        });

        Route::get('/reports/{report}/export/excel', [InfrastructureReportController::class, 'exportDetailExcel'])
            ->middleware('permission:reports.view')
            ->name('reports.export.detail.excel');
        Route::get('/reports/{report}/export/pdf', [InfrastructureReportController::class, 'exportDetailPdf'])
            ->middleware('permission:reports.view')
            ->name('reports.export.detail.pdf');
        Route::get('/reports/{report}/export/print', [InfrastructureReportController::class, 'exportDetailPrint'])
            ->middleware('permission:reports.view')
            ->name('reports.export.detail.print');

        Route::get('/reports/{report}', [InfrastructureReportController::class, 'show'])
            ->middleware('permission:reports.view')
            ->name('reports.show');

        Route::get('/income', [IncomeController::class, 'index'])
            ->middleware('permission:income.view')
            ->name('income.index');
        Route::middleware('permission:income.manage')->group(function (): void {
            Route::get('/income/create', [IncomeController::class, 'create'])->name('income.create');
            Route::post('/income', [IncomeController::class, 'store'])->name('income.store');
            Route::get('/income/{income}/edit', [IncomeController::class, 'edit'])->name('income.edit');
            Route::put('/income/{income}', [IncomeController::class, 'update'])->name('income.update');
            Route::delete('/income/{income}', [IncomeController::class, 'destroy'])->name('income.destroy');
        });

        Route::prefix('admin')
            ->name('admin.')
            ->middleware('role:super_admin,admin,manager,kepala_sekolah')
            ->group(function (): void {
                Route::middleware('permission:users.manage')->group(function (): void {
                    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
                    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
                    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
                    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
                    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
                    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
                });

                Route::middleware('permission:classrooms.manage')->group(function (): void {
                    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
                    Route::get('/classrooms/create', [ClassroomController::class, 'create'])->name('classrooms.create');
                    Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
                    Route::get('/classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classrooms.edit');
                    Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
                    Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
                });

                Route::middleware('permission:permissions.manage')->group(function (): void {
                    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
                    Route::get('/permissions/{user}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
                    Route::put('/permissions/{user}', [PermissionController::class, 'update'])->name('permissions.update');
                });

                Route::get('/settings', [SiteSettingController::class, 'edit'])
                    ->middleware('permission:settings.manage')
                    ->name('settings.edit');
                Route::put('/settings', [SiteSettingController::class, 'update'])
                    ->middleware('permission:settings.manage')
                    ->name('settings.update');

                Route::get('/activity', [ActivityLogController::class, 'index'])
                    ->middleware('permission:activity.view')
                    ->name('activity.index');

                Route::get('/trash', [TrashController::class, 'index'])
                    ->middleware('permission:trash.manage')
                    ->name('trash.index');
                Route::post('/trash/users/{userId}/restore', [TrashController::class, 'restoreUser'])
                    ->middleware('permission:trash.manage')
                    ->name('trash.users.restore');
                Route::post('/trash/classrooms/{classroomId}/restore', [TrashController::class, 'restoreClassroom'])
                    ->middleware('permission:trash.manage')
                    ->name('trash.classrooms.restore');
                Route::post('/trash/reports/{reportId}/restore', [TrashController::class, 'restoreReport'])
                    ->middleware('permission:trash.manage')
                    ->name('trash.reports.restore');
                Route::post('/trash/income/{incomeId}/restore', [TrashController::class, 'restoreIncome'])
                    ->middleware('permission:trash.manage')
                    ->name('trash.income.restore');

                Route::get('/tools', [SystemToolController::class, 'index'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.index');
                Route::post('/tools/backups', [SystemToolController::class, 'createBackup'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.backups.create');
                Route::get('/tools/backups/{filename}', [SystemToolController::class, 'downloadBackup'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.backups.download');
                Route::post('/tools/backups/restore', [SystemToolController::class, 'restoreBackup'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.backups.restore');
                Route::post('/tools/cache/clear', [SystemToolController::class, 'clearCaches'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.cache.clear');
                Route::post('/tools/database/reset', [SystemToolController::class, 'resetDatabase'])
                    ->middleware('permission:tools.manage')
                    ->name('tools.database.reset');

                Route::get('/exports/users', [SystemToolController::class, 'exportUsers'])
                    ->middleware('permission:exports.manage')
                    ->name('exports.users');
                Route::post('/imports/users', [SystemToolController::class, 'importUsers'])
                    ->middleware('permission:exports.manage')
                    ->name('imports.users');
                Route::get('/exports/items', [SystemToolController::class, 'exportItems'])
                    ->middleware('permission:exports.manage')
                    ->name('exports.items');
                Route::post('/imports/items', [SystemToolController::class, 'importItems'])
                    ->middleware('permission:exports.manage')
                    ->name('imports.items');
            });
    });
});
