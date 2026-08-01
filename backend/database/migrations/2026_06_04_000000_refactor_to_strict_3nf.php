<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refactorización completa a 3NF estricta
     * - Elimina datos redundantes
     * - Normaliza todas las relaciones
     * - Agrega soft deletes para auditoría
     * - Elimina campos denormalizados
     */
    public function up(): void
    {
        // PASO 1: Crear tablas de referencia (catálogos) - SIN DATOS AÚN
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre_rol')->unique(); // admin, estudiante, docente
            $table->text('descripcion')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Llenar roles primero
        \DB::table('roles')->insert([
            ['nombre_rol' => 'admin', 'descripcion' => 'Administrador del sistema', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_rol' => 'estudiante', 'descripcion' => 'Estudiante de la institución', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_rol' => 'docente', 'descripcion' => 'Docente/Profesor', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_rol' => 'directivo', 'descripcion' => 'Directivo/Rector', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_rol' => 'secretaria', 'descripcion' => 'Personal administrativo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('estados_civil', function (Blueprint $table) {
            $table->id('id_estado_civil');
            $table->string('nombre_estado_civil')->unique(); // Soltero, Casado, Divorciado, Viudo
            $table->softDeletes();
            $table->timestamps();
        });

        // Llenar estados civiles
        \DB::table('estados_civil')->insert([
            ['nombre_estado_civil' => 'Soltero', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_estado_civil' => 'Casado', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_estado_civil' => 'Divorciado', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_estado_civil' => 'Viudo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_estado_civil' => 'Unión Libre', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_estado_civil' => 'Separado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('grupos_sanguineo', function (Blueprint $table) {
            $table->id('id_grupo_sanguineo');
            $table->string('nombre_grupo_sanguineo')->unique(); // O+, O-, A+, A-, B+, B-, AB+, AB-
            $table->softDeletes();
            $table->timestamps();
        });

        // Llenar grupos sanguíneos
        \DB::table('grupos_sanguineo')->insert([
            ['nombre_grupo_sanguineo' => 'O+', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'O-', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'A+', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'A-', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'B+', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'B-', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'AB+', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo_sanguineo' => 'AB-', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('tipos_documentos', function (Blueprint $table) {
            $table->id('id_tipo_documento');
            $table->string('nombre_tipo_documento')->unique(); // Cédula, Pasaporte, Carnet, etc
            $table->string('codigo')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        // Llenar tipos de documento
        \DB::table('tipos_documentos')->insert([
            ['nombre_tipo_documento' => 'Cédula de Identidad', 'codigo' => 'CI', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Pasaporte', 'codigo' => 'PASS', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Licencia de Conducir', 'codigo' => 'LDER', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Carnet de Extranjería', 'codigo' => 'CE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Documento de Identidad', 'codigo' => 'DI', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Carnet Militar', 'codigo' => 'CM', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_documento' => 'Certificado de Nacimiento', 'codigo' => 'CN', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('tipos_contrato_docente', function (Blueprint $table) {
            $table->id('id_tipo_contrato');
            $table->string('nombre_tipo_contrato')->unique(); // Contratado, Titular, Interino
            $table->text('descripcion')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Llenar tipos de contrato
        \DB::table('tipos_contrato_docente')->insert([
            ['nombre_tipo_contrato' => 'Contratado', 'descripcion' => 'Docente contratado por período', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_contrato' => 'Titular', 'descripcion' => 'Docente en plantilla permanente', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_contrato' => 'Interino', 'descripcion' => 'Docente interino/temporal', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_contrato' => 'Practicante', 'descripcion' => 'Docente en período de prácticas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_contrato' => 'Becario', 'descripcion' => 'Docente becario', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // PASO 2: Modificar tabla usuarios para usar FK a roles
        // Primero: agregar columna nullable
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'id_rol')) {
                $table->unsignedBigInteger('id_rol')->nullable()->after('ci');
            }
            if (!Schema::hasColumn('usuarios', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Actualizar valores existentes (mapear tipo_usuario string a id_rol)
        \DB::statement("UPDATE usuarios SET id_rol = 1 WHERE tipo_usuario = 'admin'");
        \DB::statement("UPDATE usuarios SET id_rol = 2 WHERE tipo_usuario = 'estudiante'");
        \DB::statement("UPDATE usuarios SET id_rol = 3 WHERE tipo_usuario = 'docente'");
        \DB::statement("UPDATE usuarios SET id_rol = 4 WHERE tipo_usuario = 'directivo'");
        \DB::statement("UPDATE usuarios SET id_rol = 5 WHERE tipo_usuario = 'secretaria'");
        // Valores por defecto para los que no matched
        \DB::statement("UPDATE usuarios SET id_rol = 2 WHERE id_rol IS NULL");

        // Ahora sí: eliminar columna vieja y crear FK
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('tipo_usuario');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('id_rol')
                ->change()
                ->constrained('roles', 'id_rol')
                ->onDelete('restrict');
        });

        // PASO 3: Refactorizar tabla estudiantes (eliminar redundancias)
        Schema::create('estudiantes_nueva', function (Blueprint $table) {
            $table->id('id_estudiante');
            $table->foreignId('id_usuario')
                ->constrained('usuarios', 'id_usuario')
                ->onDelete('cascade');
            $table->foreignId('id_grado')
                ->nullable()
                ->constrained('grados', 'id_grado')
                ->onDelete('set null');
            $table->foreignId('id_arma')
                ->nullable()
                ->constrained('armas', 'id_arma')
                ->onDelete('set null');
            $table->foreignId('id_estado_civil')
                ->nullable()
                ->constrained('estados_civil', 'id_estado_civil')
                ->onDelete('set null');
            $table->foreignId('id_grupo_sanguineo')
                ->nullable()
                ->constrained('grupos_sanguineo', 'id_grupo_sanguineo')
                ->onDelete('set null');

            // Información personal específica del estudiante
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento', 255)->nullable();
            $table->string('carnet_militar', 50)->nullable()->unique();
            $table->string('carnet_cossmil', 50)->nullable()->unique();
            $table->string('celular', 20)->nullable();
            $table->smallInteger('anio_egreso_bachiller')->nullable();
            $table->string('domicilio', 255)->nullable();
            $table->string('foto_4x4_url', 500)->nullable();
            $table->integer('hermanos_inscritos')->default(0); // Denormalizado para consultas rápidas

            // Estados y auditoría
            $table->string('tipo_usuario', 50)->default('normal');
            $table->string('estado', 50)->default('Activo');
            $table->softDeletes();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('id_usuario');
            $table->index('estado');
            $table->index('carnet_militar');
            $table->index('tipo_usuario');
        });

        // Copiar datos válidos de estudiantes a estudiantes_nueva
        // Mapear estado_civil string a id_estado_civil
        \DB::statement("
            INSERT INTO estudiantes_nueva
            (id_estudiante, id_usuario, id_grado, id_arma, id_estado_civil, id_grupo_sanguineo,
             fecha_nacimiento, lugar_nacimiento, carnet_militar, carnet_cossmil, celular,
             anio_egreso_bachiller, domicilio, foto_4x4_url, hermanos_inscritos,
             tipo_usuario, estado, created_at, updated_at)
            SELECT
                e.id_estudiante,
                e.id_usuario,
                e.id_grado,
                e.id_arma,
                COALESCE(ec.id_estado_civil, 1) as id_estado_civil,
                COALESCE(gs.id_grupo_sanguineo, NULL) as id_grupo_sanguineo,
                e.fecha_nacimiento,
                e.lugar_nacimiento,
                e.carnet_militar,
                e.carnet_cossmil,
                e.celular,
                e.anio_egreso_bachiller,
                e.domicilio,
                e.foto_4x4_url,
                e.hermanos_inscritos,
                'normal',
                e.estado,
                e.created_at,
                e.updated_at
            FROM estudiantes e
            LEFT JOIN estados_civil ec ON e.estado_civil = ec.nombre_estado_civil
            LEFT JOIN grupos_sanguineo gs ON e.grupo_sanguineo = gs.nombre_grupo_sanguineo
            WHERE e.id_usuario IS NOT NULL
        ");
        // PASO 4: Crear tabla de contactos de emergencia (normalizar datos dispersos)
        Schema::create('contactos_emergencia', function (Blueprint $table) {
            $table->id('id_contacto_emergencia');
            $table->foreignId('id_estudiante')
                ->constrained('estudiantes_nueva', 'id_estudiante')
                ->onDelete('cascade');
            $table->string('nombre_contacto', 255);
            $table->string('ci', 50)->nullable();
            $table->string('relacion', 100); // Padre, Madre, Tutor, Hermano
            $table->string('telefono', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('id_estudiante');
            $table->unique(['id_estudiante', 'es_principal']);
        });

        // PASO 4b: Crear tablas de responsables y su relación (3NF)
        Schema::create('responsables', function (Blueprint $table) {
            $table->id('id_responsable');
            $table->string('nombres_responsable', 255);
            $table->string('apellido_paterno_responsable', 100)->nullable();
            $table->string('apellido_materno_responsable', 100)->nullable();
            $table->string('ci_responsable', 50)->nullable()->unique();
            $table->string('celular_responsable', 50)->nullable();
            $table->string('direccion_responsable', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('estudiante_responsable', function (Blueprint $table) {
            $table->id('id_estudiante_responsable');
            $table->foreignId('id_estudiante')
                ->constrained('estudiantes_nueva', 'id_estudiante')
                ->onDelete('cascade');
            $table->foreignId('id_responsable')
                ->constrained('responsables', 'id_responsable')
                ->onDelete('cascade');
            $table->string('parentesco', 100)->nullable();
            $table->timestamps();

            $table->unique(['id_estudiante', 'id_responsable']);
        });

        // PASO 5: Refactorizar docentes
        // Primero: agregar id_tipo_contrato nullable
        Schema::table('docentes', function (Blueprint $table) {
            if (!Schema::hasColumn('docentes', 'id_tipo_contrato')) {
                $table->unsignedBigInteger('id_tipo_contrato')->nullable()->after('especialidad');
            }
            if (!Schema::hasColumn('docentes', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Mapear tipo_contrato string a id_tipo_contrato
        \DB::statement("UPDATE docentes SET id_tipo_contrato = 1 WHERE tipo_contrato = 'Contratado'");
        \DB::statement("UPDATE docentes SET id_tipo_contrato = 2 WHERE tipo_contrato = 'Titular'");
        \DB::statement("UPDATE docentes SET id_tipo_contrato = 3 WHERE tipo_contrato = 'Interino'");
        \DB::statement("UPDATE docentes SET id_tipo_contrato = 4 WHERE tipo_contrato = 'Practicante'");
        \DB::statement("UPDATE docentes SET id_tipo_contrato = 5 WHERE tipo_contrato = 'Becario'");

        // Ahora sí: eliminar columna vieja
        Schema::table('docentes', function (Blueprint $table) {
            if (Schema::hasColumn('docentes', 'tipo_contrato')) {
                $table->dropColumn('tipo_contrato');
            }
        });

        // Crear la FK
        Schema::table('docentes', function (Blueprint $table) {
            $table->foreignId('id_tipo_contrato')
                ->change()
                ->nullable()
                ->constrained('tipos_contrato_docente', 'id_tipo_contrato')
                ->onDelete('set null');
        });

        // PASO 6: Limpiar tabla cursos (naming inconsistente)
        Schema::table('cursos', function (Blueprint $table) {
            if (!Schema::hasColumn('cursos', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // PASO 7: Agregar soft deletes a tablas importantes
        Schema::table('paralelos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('aulas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('idiomas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('niveles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('modalidades', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('grados', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('armas', function (Blueprint $table) {
            $table->softDeletes();
        });

        // PASO 8: Reemplazar tabla estudiantes por la nueva
        // Desactivar checks temporalmente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::drop('estudiantes');
        Schema::rename('estudiantes_nueva', 'estudiantes');
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // PASO 9: Agregar índices de rendimiento
        Schema::table('usuarios', function (Blueprint $table) {
            $table->index('correo_institucional');
            $table->index('ci');
            $table->index('id_rol');
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->index('id_estudiante');
            $table->index('id_curso');
            $table->index('estado');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->index('id_inscripcion');
            $table->index('periodo');
        });

        // PASO 10: Crear tabla de auditoría (optional pero recomendado para 3NF)
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->string('tabla', 100);
            $table->unsignedBigInteger('registro_id');
            $table->string('accion', 50); // CREATE, UPDATE, DELETE
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['tabla', 'registro_id']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('estudiante_responsable');
        Schema::dropIfExists('responsables');
        Schema::dropIfExists('contactos_emergencia');

        // Revertir cambios en tablas existentes
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeignIdFor('roles');
            $table->dropIndex('usuarios_correo_institucional_index');
            $table->dropIndex('usuarios_ci_index');
        });

        Schema::table('docentes', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeignIdFor('tipos_contrato_docente');
        });

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeignIdFor('modalidades');
            $table->dropColumn('id_modalidad');
        });

        Schema::table('paralelos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('aulas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('idiomas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('niveles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('modalidades', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('grados', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('armas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Dropear tablas de catálogos
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tipos_contrato_docente');
        Schema::dropIfExists('tipos_documentos');
        Schema::dropIfExists('grupos_sanguineo');
        Schema::dropIfExists('estados_civil');
    }
};
