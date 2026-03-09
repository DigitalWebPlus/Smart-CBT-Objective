<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('action', 100);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
