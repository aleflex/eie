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
        // 1. Rename column 'nombre' in 'grados' to 'nombre_grado'
        if (Schema::hasColumn('grados', 'nombre')) {
            Schema::table('grados', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_grado');
            });
        }

        // 2. Rename column 'nombre' in 'armas' to 'nombre_arma'
        if (Schema::hasColumn('armas', 'nombre')) {
            Schema::table('armas', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_arma');
            });
        }

        // 3. Rename column 'nombre' in 'paralelos' to 'nombre_paralelo'
        if (Schema::hasColumn('paralelos', 'nombre')) {
            Schema::table('paralelos', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_paralelo');
            });
        }

        // 4. Rename column 'nombre' in 'estados_civil' to 'nombre_estado_civil'
        if (Schema::hasColumn('estados_civil', 'nombre')) {
            Schema::table('estados_civil', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_estado_civil');
            });
        }

        // 5. Rename column 'nombre' in 'grupos_sanguineo' to 'nombre_grupo_sanguineo'
        if (Schema::hasColumn('grupos_sanguineo', 'nombre')) {
            Schema::table('grupos_sanguineo', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_grupo_sanguineo');
            });
        }

        // 6. Rename table 'tipos_documento' to 'tipos_documentos' and column 'nombre' to 'nombre_tipo_documento'
        if (Schema::hasTable('tipos_documento')) {
            Schema::rename('tipos_documento', 'tipos_documentos');
        }
        if (Schema::hasColumn('tipos_documentos', 'nombre')) {
            Schema::table('tipos_documentos', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_tipo_documento');
            });
        }

        // 7. Rename column 'nombre' in 'tipos_contrato_docente' to 'nombre_tipo_contrato'
        if (Schema::hasColumn('tipos_contrato_docente', 'nombre')) {
            Schema::table('tipos_contrato_docente', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_tipo_contrato');
            });
        }

        // 8. Rename column 'nombre' in 'contactos_emergencia' to 'nombre_contacto'
        if (Schema::hasColumn('contactos_emergencia', 'nombre')) {
            Schema::table('contactos_emergencia', function (Blueprint $table) {
                $table->renameColumn('nombre', 'nombre_contacto');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contactos_emergencia', function (Blueprint $table) {
            $table->renameColumn('nombre_contacto', 'nombre');
        });

        Schema::table('tipos_contrato_docente', function (Blueprint $table) {
            $table->renameColumn('nombre_tipo_contrato', 'nombre');
        });

        Schema::table('tipos_documentos', function (Blueprint $table) {
            $table->renameColumn('nombre_tipo_documento', 'nombre');
        });
        if (Schema::hasTable('tipos_documentos')) {
            Schema::rename('tipos_documentos', 'tipos_documento');
        }

        Schema::table('grupos_sanguineo', function (Blueprint $table) {
            $table->renameColumn('nombre_grupo_sanguineo', 'nombre');
        });

        Schema::table('estados_civil', function (Blueprint $table) {
            $table->renameColumn('nombre_estado_civil', 'nombre');
        });

        Schema::table('paralelos', function (Blueprint $table) {
            $table->renameColumn('nombre_paralelo', 'nombre');
        });

        Schema::table('armas', function (Blueprint $table) {
            $table->renameColumn('nombre_arma', 'nombre');
        });

        Schema::table('grados', function (Blueprint $table) {
            $table->renameColumn('nombre_grado', 'nombre');
        });
    }
};
