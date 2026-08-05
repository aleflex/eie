<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'expedido')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('expedido', 10)->nullable();
            });
        }
        if (Schema::hasTable('estudiantes') && !Schema::hasColumn('estudiantes', 'expedido')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->string('expedido', 10)->nullable();
            });
        }
        if (Schema::hasTable('docentes') && !Schema::hasColumn('docentes', 'expedido')) {
            Schema::table('docentes', function (Blueprint $table) {
                $table->string('expedido', 10)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'expedido')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('expedido');
            });
        }
        if (Schema::hasTable('estudiantes') && Schema::hasColumn('estudiantes', 'expedido')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->dropColumn('expedido');
            });
        }
        if (Schema::hasTable('docentes') && Schema::hasColumn('docentes', 'expedido')) {
            Schema::table('docentes', function (Blueprint $table) {
                $table->dropColumn('expedido');
            });
        }
    }
};
