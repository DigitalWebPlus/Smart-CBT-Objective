<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('objective_question_id')
                ->constrained('bank_questions')
                ->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('display_order')->default(1);
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['objective_question_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_options');
    }
};
