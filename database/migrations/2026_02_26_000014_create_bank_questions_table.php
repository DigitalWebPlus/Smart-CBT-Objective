<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type')->default('msa');
            $table->string('image_path')->nullable();
            $table->decimal('marks', 5, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->text('explanation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'question_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
    }
};
