<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->unsignedBigInteger('paralelo_id')->nullable()->after('curso_id');
            $table->foreign('paralelo_id')->references('id')->on('paralelos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['paralelo_id']);
            $table->dropColumn('paralelo_id');
        });
    }
};
