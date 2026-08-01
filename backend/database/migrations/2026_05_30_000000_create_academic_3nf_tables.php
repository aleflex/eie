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
        // 1. usuarios
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('correo_institucional')->unique();
            $table->string('password');
            $table->string('nombres')->nullable();
            $table->string('apellidos')->nullable();
            $table->string('ci')->nullable()->unique();
            $table->string('tipo_usuario'); // admin, estudiante, docente
            $table->string('estado', 20)->default('ACTIVO');
            $table->string('foto_url')->nullable();
            $table->timestamp('email_verificado_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. grados
        Schema::create('grados', function (Blueprint $table) {
            $table->id('id_grado');
            $table->string('nombre_grado');
            $table->timestamps();
        });

        // 3. armas
        Schema::create('armas', function (Blueprint $table) {
            $table->id('id_arma');
            $table->string('nombre_arma');
            $table->timestamps();
        });

        // 4. estudiantes (3NF - sin nombres, apellidos, correo ni ci redundantes)
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id('id_estudiante');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_grado')->nullable()->constrained('grados', 'id_grado')->onDelete('set null');
            $table->foreignId('id_arma')->nullable()->constrained('armas', 'id_arma')->onDelete('set null');
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento')->nullable();
            $table->string('carnet_militar')->nullable();
            $table->string('carnet_cossmil')->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('grupo_sanguineo')->nullable();
            $table->string('celular')->nullable();
            $table->integer('anio_egreso_bachiller')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('foto_4x4_url')->nullable();
            $table->string('nombre_padres')->nullable();
            $table->string('ci_tutor')->nullable();
            $table->integer('hermanos_inscritos')->default(0);
            $table->string('contacto_emergencia')->nullable();
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 5. docentes (3NF - con relación uno-a-uno con usuarios)
        Schema::create('docentes', function (Blueprint $table) {
            $table->id('id_docente');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->string('especialidad')->nullable();
            $table->string('telefono')->nullable();
            $table->string('estado')->default('Activo');
            $table->string('tipo_contrato')->nullable();
            $table->date('fecha_contrato')->nullable();
            $table->timestamps();
        });

        // 6. idiomas
        Schema::create('idiomas', function (Blueprint $table) {
            $table->id('id_idioma');
            $table->string('nombre_idioma')->unique();
            $table->timestamps();
        });

        // 7. niveles
        Schema::create('niveles', function (Blueprint $table) {
            $table->id('id_nivel');
            $table->string('nombre_nivel')->unique();
            $table->timestamps();
        });

        // 8. modalidades
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id('id_modalidad');
            $table->string('nombre_modalidad')->unique();
            $table->timestamps();
        });

        // 9. cursos (3NF - referenciando catálogos de idioma, nivel y modalidad)
        Schema::create('cursos', function (Blueprint $table) {
            $table->id('id_curso');
            $table->foreignId('id_idioma')->constrained('idiomas', 'id_idioma')->onDelete('cascade');
            $table->foreignId('id_nivel')->constrained('niveles', 'id_nivel')->onDelete('cascade');
            $table->foreignId('id_modalidad')->constrained('modalidades', 'id_modalidad')->onDelete('cascade');
            $table->integer('cupo_minimo')->default(0);
            $table->integer('cupo_maximo')->default(30);
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 10. aulas
        Schema::create('aulas', function (Blueprint $table) {
            $table->id('id_aula');
            $table->string('nombre_aula');
            $table->integer('capacidad')->nullable();
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 11. horarios
        Schema::create('horarios', function (Blueprint $table) {
            $table->id('id_horario');
            $table->string('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 12. paralelos
        Schema::create('paralelos', function (Blueprint $table) {
            $table->id('id_paralelo');
            $table->foreignId('id_curso')->constrained('cursos', 'id_curso')->onDelete('cascade');
            $table->foreignId('id_aula')->nullable()->constrained('aulas', 'id_aula')->onDelete('set null');
            $table->string('nombre_paralelo');
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 13. horario_paralelo (Muchos a Muchos)
        Schema::create('horario_paralelo', function (Blueprint $table) {
            $table->id('id_horario_paralelo');
            $table->foreignId('id_paralelo')->constrained('paralelos', 'id_paralelo')->onDelete('cascade');
            $table->foreignId('id_horario')->constrained('horarios', 'id_horario')->onDelete('cascade');
            $table->timestamps();
        });

        // 14. docente_paralelo (Muchos a Muchos)
        Schema::create('docente_paralelo', function (Blueprint $table) {
            $table->id('id_docente_paralelo');
            $table->foreignId('id_docente')->constrained('docentes', 'id_docente')->onDelete('cascade');
            $table->foreignId('id_paralelo')->constrained('paralelos', 'id_paralelo')->onDelete('cascade');
            $table->timestamps();
        });

        // 15. inscripciones
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('id_inscripcion');
            $table->foreignId('id_estudiante')->constrained('estudiantes', 'id_estudiante')->onDelete('cascade');
            $table->foreignId('id_curso')->constrained('cursos', 'id_curso')->onDelete('cascade');
            $table->foreignId('id_paralelo')->nullable()->constrained('paralelos', 'id_paralelo')->onDelete('set null');
            $table->date('fecha_registro')->nullable();
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // 16. notas
        Schema::create('notas', function (Blueprint $table) {
            $table->id('id_nota');
            $table->foreignId('id_inscripcion')->constrained('inscripciones', 'id_inscripcion')->onDelete('cascade');
            $table->decimal('nota', 5, 2)->nullable();
            $table->string('periodo')->default('Parcial 1');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        // 17. asistencias
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id('id_asistencia');
            $table->foreignId('id_inscripcion')->constrained('inscripciones', 'id_inscripcion')->onDelete('cascade');
            $table->date('fecha');
            $table->string('estado'); // presente, ausente, tardanza, justificado
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->unique(['id_inscripcion', 'fecha']);
        });

        // 18. documentos
        Schema::create('documentos', function (Blueprint $table) {
            $table->id('id_documento');
            $table->foreignId('id_estudiante')->constrained('estudiantes', 'id_estudiante')->onDelete('cascade');
            $table->string('tipo_documento');
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->timestamps();
        });

        // 19. configuraciones (Reemplaza settings)
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id('id_configuracion');
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('tipo')->default('text');
            $table->string('grupo')->default('general');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('notas');
        Schema::dropIfExists('inscripciones');
        Schema::dropIfExists('docente_paralelo');
        Schema::dropIfExists('horario_paralelo');
        Schema::dropIfExists('paralelos');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('aulas');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('modalidades');
        Schema::dropIfExists('niveles');
        Schema::dropIfExists('idiomas');
        Schema::dropIfExists('docentes');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('armas');
        Schema::dropIfExists('grados');
        Schema::dropIfExists('usuarios');
    }
};
