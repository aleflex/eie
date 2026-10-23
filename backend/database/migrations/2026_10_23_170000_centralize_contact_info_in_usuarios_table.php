<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Centraliza la información de contacto (celular, teléfono, dirección) en la tabla 'usuarios'
     * dando consistencia al modelo entidad-relación (Figura 5).
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'celular')) {
                $table->string('celular', 50)->nullable()->after('expedido');
            }
            if (!Schema::hasColumn('usuarios', 'telefono')) {
                $table->string('telefono', 50)->nullable()->after('celular');
            }
            if (!Schema::hasColumn('usuarios', 'direccion')) {
                $table->string('direccion', 255)->nullable()->after('telefono');
            }
        });

        // Sincronizar datos históricos de estudiantes hacia usuarios
        DB::statement("
            UPDATE usuarios u
            INNER JOIN estudiantes e ON u.id_usuario = e.id_usuario
            SET u.celular = COALESCE(e.celular, u.celular),
                u.direccion = COALESCE(e.domicilio, u.direccion)
        ");

        // Sincronizar datos históricos de docentes hacia usuarios
        DB::statement("
            UPDATE usuarios u
            INNER JOIN docentes d ON u.id_usuario = d.id_usuario
            SET u.telefono = COALESCE(d.telefono, u.telefono),
                u.celular = COALESCE(u.celular, d.telefono)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'direccion')) {
                $table->dropColumn('direccion');
            }
            if (Schema::hasColumn('usuarios', 'telefono')) {
                $table->dropColumn('telefono');
            }
            if (Schema::hasColumn('usuarios', 'celular')) {
                $table->dropColumn('celular');
            }
        });
    }
};
