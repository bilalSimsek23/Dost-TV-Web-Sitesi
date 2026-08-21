<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/programlar', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programlar/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');

Route::get('/yayin-akisi', [ScheduleController::class, 'index'])->name('schedule.index');

Route::get('/canli-tv', [LiveController::class, 'tv'])->name('live.tv');
Route::get('/canli-radyo', [LiveController::class, 'radio'])->name('live.radio');

use App\Http\Controllers\Admin\CategoryReorderController;
use App\Http\Controllers\Admin\MenuItemReorderController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/admin/categories/reorder', [CategoryReorderController::class, 'reorder'])->name('admin.categories.reorder');
    Route::post('/admin/menu-items/reorder', [MenuItemReorderController::class, 'reorder'])->name('admin.menu-items.reorder');

    Route::get('/admin/schedule/download-template', function (\App\Services\Schedule\ScheduleExcelImportService $service) {
        $path = $service->generateSampleTemplate();
        return response()->download($path, 'Yayın_Akışı_Excel_Şablonu.xlsx')->deleteFileAfterSend(true);
    })->name('admin.schedule.download-template');

    Route::get('/admin/schedule/download-errors', function (\Illuminate\Http\Request $request, \App\Services\Schedule\ScheduleExcelImportService $service) {
        $key = $request->query('key');
        $errors = [];
        if ($key) {
            $errors = json_decode(base64_decode($key), true) ?: [];
        }
        $path = $service->generateErrorExport($errors);
        return response()->download($path, 'Yayın_Akışı_İçe_Aktarma_Hataları.xlsx')->deleteFileAfterSend(true);
    })->name('schedule.excel.errors');
});

use App\Http\Controllers\Auth\InvitationController;

Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::get('/davet/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
    Route::post('/davet/{token}', [InvitationController::class, 'accept'])->name('invitation.accept.post');
});

// Statik sayfalar (İletişim, Hakkımızda, Yayın İlkeleri vb.) - en sonda, catch-all olarak
Route::get('/{page:slug}', [PageController::class, 'show'])->name('pages.show');
