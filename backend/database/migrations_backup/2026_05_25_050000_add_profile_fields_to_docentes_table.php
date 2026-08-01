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
        // 1. Agregar columnas de perfil a docentes
        Schema::table('docentes', function (Blueprint $table) {
            $table->string('nombres')->nullable()->after('user_id');
            $table->string('apellidos')->nullable()->after('nombres');
            $table->string('correo_electronico')->nullable()->after('apellidos');
        });

        // 2. Migrar los datos de docentes existentes
        try {
            $docentes = DB::table('docentes')->get();
            foreach ($docentes as $docente) {
                if ($docente->user_id) {
                    $user = DB::table('users')->where('id', $docente->user_id)->first();
                    if ($user) {
                        // Dividir el nombre completo para aproximar nombres y apellidos
                        $parts = explode(' ', trim($user->name), 2);
                        $nombres = $parts[0];
                        $apellidos = isset($parts[1]) ? $parts[1] : '';

                        DB::table('docentes')->where('id', $docente->id)->update([
                            'nombres' => $nombres,
                            'apellidos' => $apellidos,
                            'correo_electronico' => $user->email
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Prevenir fallos en la migración si hay problemas con los datos
            Log::warning("Error al migrar nombres de docentes: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn(['nombres', 'apellidos', 'correo_electronico']);
        });
    }
};
