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
        Schema::table('docentes', function (Blueprint $table) {
            $table->string('foto_url')->nullable()->after('correo_electronico');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_url')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn('foto_url');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('foto_url');
        });
    }
};
