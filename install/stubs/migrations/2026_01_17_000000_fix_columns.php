<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (['active_user_locks', 'active_user_sessions', 'active_users'] as $tbl) {
            if (!Schema::hasTable($tbl)) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('sid', 128)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (['active_user_locks', 'active_user_sessions', 'active_users'] as $tbl) {
            if (!Schema::hasTable($tbl)) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('sid', 32)->change();
            });
        }
    }
};
