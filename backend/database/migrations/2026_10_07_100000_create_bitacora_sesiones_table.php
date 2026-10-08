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
        if (!Schema::hasTable('bitacora_sesiones')) {
            Schema::create('bitacora_sesiones', function (Blueprint $table) {
                $table->id('id_bitacora');
                $table->unsignedBigInteger('id_usuario')->nullable();
                $table->string('usuario_nombre')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('mac_address', 128)->nullable();
                $table->string('modelo_dispositivo', 255)->nullable();
                $table->string('ubicacion_lugar', 255)->nullable();
                $table->string('plataforma', 64)->default('WEB_VERCEL');
                $table->timestamp('hora_ingreso')->useCurrent();
                $table->timestamp('hora_salida')->nullable();
                $table->boolean('terminos_aceptados')->default(true);
                $table->timestamps();

                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_sesiones');
    }
};
