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
        // 1. Quitar la relación 1-a-Muchos de la tabla paralelos
        Schema::table('paralelos', function (Blueprint $table) {
            $table->dropForeign(['docente_id']);
            $table->dropColumn('docente_id');
        });

        // 2. Crear la tabla Pivot para relación Muchos-a-Muchos
        Schema::create('docente_paralelo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('paralelo_id')->constrained('paralelos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_paralelo');
        
        Schema::table('paralelos', function (Blueprint $table) {
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->onDelete('set null');
        });
    }
};
