<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_objective_attempts', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->json('login_ips')->nullable();
            $table->decimal('auto_score', 8, 2)->default(0);
            $table->decimal('manual_score', 8, 2)->default(0);
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('percentage', 6, 2)->default(0);
            $table->json('subject_scores')->nullable();
            $table->string('grade_letter')->nullable();
            $table->string('grade_remark')->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_objective_attempts');
    }
};
