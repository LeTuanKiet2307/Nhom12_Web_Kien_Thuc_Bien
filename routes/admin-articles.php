<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\WikipediaController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('articles', ArticleController::class)->except('show');
    Route::get('wikipedia', [WikipediaController::class, 'index'])->name('wikipedia.index');
    Route::post('wikipedia', [WikipediaController::class, 'store'])->middleware('throttle:10,1')->name('wikipedia.store');
});
