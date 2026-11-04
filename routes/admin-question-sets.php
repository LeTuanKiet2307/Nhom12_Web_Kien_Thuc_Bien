<?php

use App\Http\Controllers\Admin\AiGenerationController;
use App\Http\Controllers\Admin\QuestionSetController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('ai', [AiGenerationController::class, 'index'])->name('ai.index');
    Route::post('ai', [AiGenerationController::class, 'store'])->middleware('throttle:5,1')->name('ai.store');
    Route::resource('question-sets', QuestionSetController::class)->except('show');
    Route::post('question-sets/{questionSet}/publish', [QuestionSetController::class, 'publish'])->name('question-sets.publish');
    Route::post('question-sets/{questionSet}/unpublish', [QuestionSetController::class, 'unpublish'])->name('question-sets.unpublish');
});
