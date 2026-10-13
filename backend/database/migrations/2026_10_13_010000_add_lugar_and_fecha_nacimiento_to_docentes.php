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
                if (!Schema::hasColumn('docentes', 'fecha_nacimiento')) {
                    $table->date('fecha_nacimiento')->nullable()->after('especialidad');
                }
                if (!Schema::hasColumn('docentes', 'lugar_nacimiento')) {
                    $table->string('lugar_nacimiento', 255)->nullable()->after('fecha_nacimiento');
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
                if (Schema::hasColumn('docentes', 'lugar_nacimiento')) {
                    $table->dropColumn('lugar_nacimiento');
                }
                if (Schema::hasColumn('docentes', 'fecha_nacimiento')) {
                    $table->dropColumn('fecha_nacimiento');
                }
            });
        }
    }
};
