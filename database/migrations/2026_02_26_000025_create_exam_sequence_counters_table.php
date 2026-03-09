<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sequence_counters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->unsignedBigInteger('next_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sequence_counters');
    }
};
