<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Añadiendo campos militares a ESTUDIANTES...\n";
    
    // Añadir grado_academico si no existe
    try {
        DB::statement('ALTER TABLE estudiantes ADD COLUMN grado_academico VARCHAR(100) NULL AFTER apellidos');
        echo " - Columna grado_academico añadida.\n";
    } catch (\Exception $e) { echo " - grado_academico ya existe.\n"; }

    // Añadir arma_especialidad si no existe
    try {
        DB::statement('ALTER TABLE estudiantes ADD COLUMN arma_especialidad VARCHAR(100) NULL AFTER grado_academico');
        echo " - Columna arma_especialidad añadida.\n";
    } catch (\Exception $e) { echo " - arma_especialidad ya existe.\n"; }

    echo "\n¡Tabla actualizada con éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
