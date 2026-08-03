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

Route::middleware(['web', 'auth'])->post('/admin/categories/reorder', [CategoryReorderController::class, 'reorder'])->name('admin.categories.reorder');
Route::middleware(['web', 'auth'])->post('/admin/menu-items/reorder', [MenuItemReorderController::class, 'reorder'])->name('admin.menu-items.reorder');

// Statik sayfalar (İletişim, Hakkımızda, Yayın İlkeleri vb.) - en sonda, catch-all olarak
Route::get('/{page:slug}', [PageController::class, 'show'])->name('pages.show');
