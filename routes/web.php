<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\ExamAttemptController as CandidateExamAttemptController;
use App\Http\Controllers\Candidate\ExamController as CandidateExamController;
use App\Http\Controllers\Candidate\ProfileDashboardController as CandidateProfileDashboardController;
use App\Http\Controllers\Candidate\ResultController as CandidateResultController;
use App\Http\Controllers\Candidate\SupportTicketController as CandidateSupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'log.candidate.activity'])->prefix('candidate')->group(function () {
    Route::get('dashboard', CandidateDashboardController::class)
        ->middleware('verified')
        ->name('dashboard');

    Route::get('settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('profile', CandidateProfileDashboardController::class)->name('candidate.profile.show');

    Route::prefix('exams')->name('candidate.exams.')->group(function () {
        Route::get('/', [CandidateExamController::class, 'index'])->name('index');
        Route::get('{exam}', [CandidateExamController::class, 'show'])->name('show');
        Route::post('{exam}/attempts', [CandidateExamAttemptController::class, 'store'])->name('attempts.start');
    });

    Route::prefix('exam-attempts')->name('candidate.exam-attempts.')->group(function () {
        Route::get('{attempt}', [CandidateExamAttemptController::class, 'show'])->name('show');
        Route::post('{attempt}/submit', [CandidateExamAttemptController::class, 'submit'])->name('submit');
        Route::post('{attempt}/autosave', [CandidateExamAttemptController::class, 'autosave'])->name('autosave');
    });

    Route::prefix('support-tickets')->name('candidate.support-tickets.')->group(function () {
        Route::get('/', [CandidateSupportTicketController::class, 'index'])->name('index');
        Route::get('create', [CandidateSupportTicketController::class, 'create'])->name('create');
        Route::post('/', [CandidateSupportTicketController::class, 'store'])->name('store');
        Route::get('{ticket}', [CandidateSupportTicketController::class, 'show'])->name('show');
        Route::post('{ticket}/reply', [CandidateSupportTicketController::class, 'reply'])->name('reply');
        Route::post('{ticket}/close', [CandidateSupportTicketController::class, 'close'])->name('close');
    });

    Route::prefix('results')->name('candidate.results.')->group(function () {
        Route::get('{attempt}/print', [CandidateResultController::class, 'print'])->name('print');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
