<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('objective_question_id')
                ->constrained('bank_questions')
                ->cascadeOnDelete();
            $table->decimal('marks', 8, 2)->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_id', 'objective_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
