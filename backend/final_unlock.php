<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Crear Idioma 1
    DB::table('idiomas')->insertOrIgnore(['id' => 1, 'nombre_idioma' => 'General']);
    
    // Crear Nivel 1
    DB::table('niveles')->insertOrIgnore(['id' => 1, 'nombre_nivel' => 'Nivel 1']);
    
    // Crear Docente 1 (solo nombres y apellidos para evitar errores de columnas desconocidas)
    DB::table('docentes')->insertOrIgnore(['id' => 1, 'nombres' => 'Admin', 'apellidos' => 'EIE']);
    
    // Crear Curso 1
    DB::table('cursos')->insertOrIgnore([
        'id' => 1,
        'docente_id' => 1,
        'idioma_id' => 1,
        'nivel_id' => 1,
        'modalidad' => 'Presencial',
        'horario' => 'General'
    ]);
    
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "¡DESBLOQUEO ACADEMICO EXITOSO! Curso 1 creado.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
