<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columnas estado_civil y grupo_sanguineo a tabla usuarios
        if (!Schema::hasColumn('usuarios', 'estado_civil')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('estado_civil', 50)->nullable()->after('ci');
            });
        }

        if (!Schema::hasColumn('usuarios', 'grupo_sanguineo')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('grupo_sanguineo', 10)->nullable()->after('estado_civil');
            });
        }

        // 2. Poblar los valores de texto en usuarios desde las tablas catálogo si existen
        $hasUserEstCivil = Schema::hasColumn('usuarios', 'id_estado_civil');
        $hasEstEstCivil = Schema::hasTable('estudiantes') && Schema::hasColumn('estudiantes', 'id_estado_civil');

        try {
            if (Schema::hasTable('estados_civil')) {
                $joinConds = [];
                if ($hasUserEstCivil) {
                    $joinConds[] = "u.id_estado_civil = ec.id_estado_civil";
                }
                if ($hasEstEstCivil) {
                    $joinConds[] = "e.id_estado_civil = ec.id_estado_civil";
                }
                $joinOn = !empty($joinConds) ? implode(' OR ', $joinConds) : '1=0';

                DB::statement("
                    UPDATE usuarios u
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    LEFT JOIN estados_civil ec ON ({$joinOn})
                    SET u.estado_civil = COALESCE(ec.nombre_estado_civil, u.estado_civil, 'Soltero(a)')
                    WHERE u.estado_civil IS NULL OR u.estado_civil = ''
                ");
            }
        } catch (\Exception $e) {}

        $hasUserGrupoSang = Schema::hasColumn('usuarios', 'id_grupo_sanguineo');
        $hasEstGrupoSang = Schema::hasTable('estudiantes') && Schema::hasColumn('estudiantes', 'id_grupo_sanguineo');

        try {
            if (Schema::hasTable('grupos_sanguineo')) {
                $joinConds = [];
                if ($hasUserGrupoSang) {
                    $joinConds[] = "u.id_grupo_sanguineo = gs.id_grupo_sanguineo";
                }
                if ($hasEstGrupoSang) {
                    $joinConds[] = "e.id_grupo_sanguineo = gs.id_grupo_sanguineo";
                }
                $joinOn = !empty($joinConds) ? implode(' OR ', $joinConds) : '1=0';

                DB::statement("
                    UPDATE usuarios u
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    LEFT JOIN grupos_sanguineo gs ON ({$joinOn})
                    SET u.grupo_sanguineo = COALESCE(gs.nombre_grupo_sanguineo, u.grupo_sanguineo, 'O+')
                    WHERE u.grupo_sanguineo IS NULL OR u.grupo_sanguineo = ''
                ");
            }
        } catch (\Exception $e) {}

        // 3. Eliminar llaves foráneas y columnas de estudiantes
        if (Schema::hasTable('estudiantes')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                if (Schema::hasColumn('estudiantes', 'id_estado_civil')) {
                    try {
                        $table->dropForeign(['id_estado_civil']);
                    } catch (\Exception $e) {}
                    $table->dropColumn('id_estado_civil');
                }
                if (Schema::hasColumn('estudiantes', 'id_grupo_sanguineo')) {
                    try {
                        $table->dropForeign(['id_grupo_sanguineo']);
                    } catch (\Exception $e) {}
                    $table->dropColumn('id_grupo_sanguineo');
                }
            });
        }

        // 4. Eliminar columnas numéricas de usuarios si existían
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'id_estado_civil')) {
                try {
                    $table->dropForeign(['id_estado_civil']);
                } catch (\Exception $e) {}
                $table->dropColumn('id_estado_civil');
            }
            if (Schema::hasColumn('usuarios', 'id_grupo_sanguineo')) {
                try {
                    $table->dropForeign(['id_grupo_sanguineo']);
                } catch (\Exception $e) {}
                $table->dropColumn('id_grupo_sanguineo');
            }
        });

        // 5. Eliminar las tablas satélite/catálogo
        Schema::dropIfExists('estados_civil');
        Schema::dropIfExists('grupos_sanguineo');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'grupo_sanguineo')) {
                $table->dropColumn('grupo_sanguineo');
            }
            if (Schema::hasColumn('usuarios', 'estado_civil')) {
                $table->dropColumn('estado_civil');
            }
        });
    }
};
