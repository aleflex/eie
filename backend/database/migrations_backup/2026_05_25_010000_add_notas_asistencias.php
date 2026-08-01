<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expandir tabla notas con periodo y observación
        Schema::table('notas', function (Blueprint $table) {
            $table->string('periodo')->default('Parcial 1')->after('nota');
            $table->text('observacion')->nullable()->after('periodo');
        });

        // Crear tabla asistencias
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->date('fecha');
            $table->enum('estado', ['presente', 'ausente', 'tardanza', 'justificado'])->default('presente');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('inscripcion_id')->references('id')->on('inscripciones')->onDelete('cascade');
            $table->unique(['inscripcion_id', 'fecha']); // Un registro por estudiante por día
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn(['periodo', 'observacion']);
        });
    }
};
