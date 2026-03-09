<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_action_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->after('admin_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('admin_action_logs', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
