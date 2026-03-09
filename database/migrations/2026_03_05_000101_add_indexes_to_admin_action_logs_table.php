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
            $table->index('admin_id');
            $table->index('action');
            $table->index('created_at');

            if (Schema::hasColumn('admin_action_logs', 'user_id')) {
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_action_logs', function (Blueprint $table): void {
            $table->dropIndex(['admin_id']);
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);

            if (Schema::hasColumn('admin_action_logs', 'user_id')) {
                $table->dropIndex(['user_id']);
            }
        });
    }
};
