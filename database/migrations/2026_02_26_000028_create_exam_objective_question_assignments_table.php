<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_objective_question_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedBigInteger('objective_question_id');
            $table->decimal('marks', 8, 2)->default(0);
            $table->unsignedInteger('display_order')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'objective_question_id'], 'exam_obj_q_assign_unique');

            $table->foreign('objective_question_id', 'exam_obj_q_assign_bank_q_fk')
                ->references('id')
                ->on('bank_questions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_objective_question_assignments');
    }
};
