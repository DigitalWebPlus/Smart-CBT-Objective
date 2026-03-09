<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('exam_type')->default('objective');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
