<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
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
    if (auth()->check()) {
        return Redirect::route('website.analyzer');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// التوجيه المباشر لصفحة المحلل بعد تسجيل الدخول
Route::get('/dashboard', function () {
    return Redirect::route('website.analyzer');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\WebsiteAnalyzerController;
use App\Http\Controllers\AiApiSettingController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Website Analyzer Routes - النظام الشامل المحدث
    Route::get('/analyzer', function() { 
        return Inertia::render('BusinessAnalyzer'); 
    })->name('website.analyzer');
    Route::post('/analyzer/analyze', [WebsiteAnalyzerController::class, 'analyze'])->name('website.analyze');
    Route::get('/analyzer/search-business', [WebsiteAnalyzerController::class, 'searchBusiness'])->name('website.search.business');
    Route::post('/analyzer/analyze-business', [WebsiteAnalyzerController::class, 'analyzeBusiness'])->name('website.analyze.business');
    Route::get('/analyzer/history', [WebsiteAnalyzerController::class, 'history'])->name('website.history');
    Route::get('/analyzer/report/{id}', [WebsiteAnalyzerController::class, 'show'])->name('website.show')->where('id', '[0-9]+');
    Route::get('/analyzer/report/{id}/pdf', [WebsiteAnalyzerController::class, 'downloadPDF'])->name('website.report.pdf')->where('id', '[0-9]+');
    
    // AI API Settings Routes
    Route::get('/ai-settings', [AiApiSettingController::class, 'index'])->name('ai-api-settings.index');
    Route::post('/ai-settings', [AiApiSettingController::class, 'store'])->name('ai-api-settings.store');
    Route::post('/ai-settings/test', [AiApiSettingController::class, 'testConnection'])->name('ai-api-settings.test');
    Route::patch('/ai-settings/{apiSetting}/toggle', [AiApiSettingController::class, 'toggleActive'])->name('ai-api-settings.toggle');
    Route::delete('/ai-settings/{apiSetting}', [AiApiSettingController::class, 'destroy'])->name('ai-api-settings.destroy');
});

require __DIR__.'/auth.php';
