<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_objective_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_attempt_id')
                ->constrained('exam_attempts')
                ->cascadeOnDelete();
            $table->foreignId('exam_question_id')
                ->constrained('exam_questions')
                ->cascadeOnDelete();
            $table->foreignId('objective_question_id')
                ->constrained('bank_questions')
                ->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('awarded_marks', 8, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'exam_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_objective_responses');
    }
};
