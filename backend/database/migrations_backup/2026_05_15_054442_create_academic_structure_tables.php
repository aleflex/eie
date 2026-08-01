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
        // 1. Aulas
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('capacidad')->nullable();
            $table->timestamps();
        });

        // 2. Horarios
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->string('dia_semana'); // Lunes, Martes, etc.
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        // 3. Paralelos (Vínculo entre Curso, Aula y Docente)
        Schema::create('paralelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->onDelete('set null');
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->onDelete('set null');
            $table->string('nombre'); // Ej: "Paralelo A", "Grupo 1"
            $table->timestamps();
        });

        // 4. Tabla pivote Horario-Paralelo (Muchos a Muchos)
        Schema::create('horario_paralelo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paralelo_id')->constrained('paralelos')->onDelete('cascade');
            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_paralelo');
        Schema::dropIfExists('paralelos');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('aulas');
    }
};
