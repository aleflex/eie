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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->integer('estudiante_id');

            $table->string('tipo_documento'); // CI, Titulo, etc.
            $table->string('nombre_archivo'); // original name
            $table->string('ruta_archivo');   // path in storage
            $table->string('extension', 10);
            $table->bigInteger('peso');
            $table->timestamps();

            // $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
