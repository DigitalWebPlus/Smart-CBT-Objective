<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_objective_exams', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->string('status')->default('draft');
            $table->decimal('total_marks', 10, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_objective_exams');
    }
};
