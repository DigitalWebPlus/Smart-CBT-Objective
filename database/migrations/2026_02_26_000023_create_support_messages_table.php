<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
