<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// التوجيه المباشر لصفحة المحلل بعد تسجيل الدخول
Route::get('/dashboard', function () {
    return redirect()->route('website.analyzer');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Website Analyzer Routes
    Route::get('/analyzer', [App\Http\Controllers\WebsiteAnalyzerController::class, 'index'])->name('website.analyzer');
    Route::post('/analyzer/analyze', [App\Http\Controllers\WebsiteAnalyzerController::class, 'analyze'])->name('website.analyze');
    Route::get('/analyzer/history', [App\Http\Controllers\WebsiteAnalyzerController::class, 'history'])->name('website.history');
    Route::get('/analyzer/{id}', [App\Http\Controllers\WebsiteAnalyzerController::class, 'show'])->name('website.show');
    Route::get('/analyzer/{id}/pdf', [App\Http\Controllers\WebsiteAnalyzerController::class, 'downloadPDF'])->name('website.report.pdf');
    
    // AI API Settings Routes
    Route::get('/ai-settings', [App\Http\Controllers\AiApiSettingController::class, 'index'])->name('ai-api-settings.index');
    Route::post('/ai-settings', [App\Http\Controllers\AiApiSettingController::class, 'store'])->name('ai-api-settings.store');
    Route::post('/ai-settings/test', [App\Http\Controllers\AiApiSettingController::class, 'testConnection'])->name('ai-api-settings.test');
    Route::patch('/ai-settings/{apiSetting}/toggle', [App\Http\Controllers\AiApiSettingController::class, 'toggleActive'])->name('ai-api-settings.toggle');
    Route::delete('/ai-settings/{apiSetting}', [App\Http\Controllers\AiApiSettingController::class, 'destroy'])->name('ai-api-settings.destroy');
});

require __DIR__.'/auth.php';
