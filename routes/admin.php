<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminActionLogController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminLoginController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ExamAttemptController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExamSettingController;
use App\Http\Controllers\Admin\ExamMonitorController;
use App\Http\Controllers\Admin\ObjectiveAttemptAnswerController;
use App\Http\Controllers\Admin\ObjectiveQuestionController;
use App\Http\Controllers\Admin\ResultManagementController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:admin', 'log.admin.activity'])->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [AdminActionLogController::class, 'index'])->name('index');
            Route::get('{log}', [AdminActionLogController::class, 'show'])->name('show');
            Route::post('reset', [AdminActionLogController::class, 'reset'])->name('reset');
            Route::post('toggle', [AdminActionLogController::class, 'toggle'])->name('toggle');
        });

        Route::get('candidates/upload', [CandidateController::class, 'upload'])->name('candidates.upload');
        Route::post('candidates/import', [CandidateController::class, 'import'])->name('candidates.import');
        Route::resource('candidates', CandidateController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class);
        Route::resource('subjects', SubjectController::class)->except(['show']);

        Route::prefix('question-banks')->name('question-banks.')->group(function () {
            Route::get('/', [ObjectiveQuestionController::class, 'index'])->name('index');
            Route::get('upload', [ObjectiveQuestionController::class, 'upload'])->name('upload');
            Route::get('create', [ObjectiveQuestionController::class, 'create'])->name('create');
            Route::post('/', [ObjectiveQuestionController::class, 'store'])->name('store');
            Route::post('import', [ObjectiveQuestionController::class, 'import'])->name('import');
            Route::get('export', [ObjectiveQuestionController::class, 'export'])->name('export');
            Route::get('{objective}/edit', [ObjectiveQuestionController::class, 'edit'])->name('edit');
            Route::put('{objective}', [ObjectiveQuestionController::class, 'update'])->name('update');
            Route::delete('{objective}', [ObjectiveQuestionController::class, 'destroy'])->name('destroy');
        });

        Route::resource('exams', ExamController::class)->except(['show']);

        Route::prefix('exams/{exam}')->name('exams.')->group(function () {
            Route::get('attempts', [ExamAttemptController::class, 'index'])->name('attempts.index');
            Route::get('attempts/{attempt}', function (Exam $exam, ExamAttempt $attempt) {
                return redirect()->route('admin.monitor-exams.attempts.show', [$exam, $attempt]);
            })->name('attempts.show');
            Route::delete('attempts/{attempt}', [ExamAttemptController::class, 'destroy'])->name('attempts.destroy');
        });

        Route::get('monitor-exams', [ExamMonitorController::class, 'index'])->name('monitor-exams.index');
        Route::get('monitor-exams/{exam}/attempts/{attempt}', [ExamMonitorController::class, 'show'])
            ->name('monitor-exams.attempts.show');

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultManagementController::class, 'index'])->name('index');
            Route::get('{attempt}/print', [ResultManagementController::class, 'print'])->name('print');
        });

        Route::get('attempts', [ObjectiveAttemptAnswerController::class, 'index'])->name('attempts.index');
        Route::get('attempts/exam/{exam}', [ObjectiveAttemptAnswerController::class, 'show'])->name('attempts.show');
        Route::get('attempts/exam/{exam}/answers/{attempt}', [ObjectiveAttemptAnswerController::class, 'showAttempt'])
            ->name('attempts.answers.show');
        Route::get('attempts/answers/{exam}/{attempt}', function (Exam $exam, ExamAttempt $attempt) {
            return redirect()->route('admin.attempts.answers.show', [$exam, $attempt]);
        })->name('attempts.answers.show-legacy-uri');
        Route::get('attempts/{attempt}/answers', [ObjectiveAttemptAnswerController::class, 'showAttempt'])
            ->name('attempts.answers.show-legacy');
        Route::put('attempts/{attempt}/status', [ObjectiveAttemptAnswerController::class, 'updateStatus'])
            ->name('attempts.answers.status-legacy');
        Route::put('attempts/{exam}/{attempt}/status', [ObjectiveAttemptAnswerController::class, 'updateStatus'])
            ->name('attempts.answers.status');

        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [AdminSupportTicketController::class, 'index'])->name('index');
            Route::get('{ticket}', [AdminSupportTicketController::class, 'show'])->name('show');
            Route::post('{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('reply');
            Route::post('{ticket}/close', [AdminSupportTicketController::class, 'close'])->name('close');
            Route::post('{ticket}/reopen', [AdminSupportTicketController::class, 'reopen'])->name('reopen');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SiteSettingController::class, 'edit'])->name('edit');
            Route::put('/', [SiteSettingController::class, 'update'])->name('update');
        });

        Route::prefix('exam-settings')->name('exam-settings.')->group(function () {
            Route::get('/', [ExamSettingController::class, 'edit'])->name('edit');
            Route::put('/', [ExamSettingController::class, 'update'])->name('update');
        });

        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    });
});
