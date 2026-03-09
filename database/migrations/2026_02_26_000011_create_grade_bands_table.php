<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_bands', function (Blueprint $table): void {
            $table->id();
            $table->string('letter', 2)->unique();
            $table->unsignedTinyInteger('min_percentage');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_bands');
    }
};
