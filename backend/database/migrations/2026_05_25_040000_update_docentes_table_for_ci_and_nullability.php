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
        Schema::table('docentes', function (Blueprint $table) {
            // Añadir columna ci
            $table->string('ci')->nullable()->after('user_id');
            // Hacer user_id nullable para permitir desvincular sin eliminar el docente
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn('ci');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
