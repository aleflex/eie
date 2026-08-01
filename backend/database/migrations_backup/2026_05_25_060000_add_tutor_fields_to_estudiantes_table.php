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
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('nombre_padres')->nullable()->after('domicilio');
            $table->string('ci_tutor')->nullable()->after('nombre_padres');
            $table->string('hermanos_inscritos')->nullable()->after('ci_tutor');
            $table->string('contacto_emergencia')->nullable()->after('hermanos_inscritos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['nombre_padres', 'ci_tutor', 'hermanos_inscritos', 'contacto_emergencia']);
        });
    }
};
