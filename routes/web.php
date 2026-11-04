<?php

// [Thành viên 1 - Owner] routes/web.php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QuizController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// --- KHU VỰC CỦA THÀNH VIÊN 1 (OWNER): LANDING PAGE, LEADERBOARD & AUTH ---
Route::get('/', [HomeController::class, 'welcome'])->name('welcome');
Route::get('/leaderboard', [HomeController::class, 'leaderboard'])->name('leaderboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- KHU VỰC CỦA THÀNH VIÊN 1 (OWNER): USER BENTO DASHBOARD ---
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// --- KHU VỰC CỦA THÀNH VIÊN 2 (CONTRIBUTOR A): QUIZ WORKFLOW, KAHOOT UI & REVIEW MISTAKES ---
Route::middleware('auth')->prefix('quiz')->name('quiz.')->group(function () {
    Route::get('/play', [QuizController::class, 'play'])->name('play');
    Route::get('/attempt/{attempt}', [QuizController::class, 'show'])->name('attempt');
    Route::post('/attempt/{attempt}/start', [QuizController::class, 'start'])->name('start');
    Route::post('/attempt/{attempt}/answer', [QuizController::class, 'answer'])->name('answer');
    Route::get('/attempt/{attempt}/answer/{answer}', [QuizController::class, 'answerResult'])->name('answer-result');
    Route::get('/attempt/{attempt}/result', [QuizController::class, 'result'])->name('result');
    Route::get('/mistakes', [QuizController::class, 'mistakes'])->name('mistakes');
});

// --- KHU VỰC CỦA THÀNH VIÊN 3 (CONTRIBUTOR B): ADMIN DASHBOARD, WIKIPEDIA API & GEMINI AI ---
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
});

require __DIR__.'/admin-articles.php';
require __DIR__.'/admin-question-sets.php';
