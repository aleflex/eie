<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('docentes')) {
            Schema::table('docentes', function (Blueprint $table) {
                if (!Schema::hasColumn('docentes', 'motivo_baja')) {
                    $table->text('motivo_baja')->nullable()->after('estado');
                }
                if (!Schema::hasColumn('docentes', 'fecha_baja')) {
                    $table->timestamp('fecha_baja')->nullable()->after('motivo_baja');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('docentes')) {
            Schema::table('docentes', function (Blueprint $table) {
                if (Schema::hasColumn('docentes', 'motivo_baja')) {
                    $table->dropColumn('motivo_baja');
                }
                if (Schema::hasColumn('docentes', 'fecha_baja')) {
                    $table->dropColumn('fecha_baja');
                }
            });
        }
    }
};
