<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('idioma')->nullable();
            $table->string('nivel')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('horario')->nullable();
            $table->integer('cupo_minimo')->default(0);
            $table->integer('cupo_maximo')->default(30);
            // No timestamps as per model
        });

        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_usuario')->nullable();
            $table->unsignedBigInteger('grado_id')->nullable();
            $table->unsignedBigInteger('arma_id')->nullable();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('grado_academico')->nullable();
            $table->string('arma_especialidad')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento')->nullable();
            $table->string('ci')->unique();
            $table->string('carnet_militar')->nullable();
            $table->string('carnet_cossmil')->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('grupo_sanguineo')->nullable();
            $table->string('celular')->nullable();
            $table->integer('anio_egreso_bachiller')->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('foto_4x4_url')->nullable();
            // No timestamps as per model
        });

        // Drop the empty inscripciones created by the other migration if it exists
        Schema::dropIfExists('inscripciones');

        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estudiante_id');
            $table->unsignedBigInteger('curso_id');
            $table->date('fecha_registro')->nullable();
            $table->string('estado')->default('Activo');

            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            // No timestamps
        });

        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->decimal('nota', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('inscripcion_id')->references('id')->on('inscripciones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
        Schema::dropIfExists('inscripciones');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('cursos');
    }
};
