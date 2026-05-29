<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "--- DESBLOQUEO ACADEMICO TOTAL ---\n";
    
    // 1. Desactivar revisión de llaves foráneas temporalmente
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // 2. Crear Idioma por defecto
    DB::table('idiomas')->insertOrIgnore(['id' => 1, 'nombre_idioma' => 'Español/General']);
    echo "✔ Idioma base listo.\n";

    // 3. Crear Nivel por defecto
    DB::table('niveles')->insertOrIgnore(['id' => 1, 'nombre_nivel' => 'Nivel 1']);
    echo "✔ Nivel base listo.\n";

    // 4. Crear Docente por defecto
    DB::table('docentes')->insertOrIgnore(['id' => 1, 'nombres' => 'Docente', 'apellidos' => 'General', 'ci' => '0000000']);
    echo "✔ Docente base listo.\n";

    // 5. Crear el Curso 1 conectado a todo lo anterior
    DB::table('cursos')->insertOrIgnore([
        'id' => 1,
        'docente_id' => 1,
        'idioma_id' => 1,
        'nivel_id' => 1,
        'modalidad' => 'Presencial',
        'horario' => '08:00 - 10:00',
        'cupo_maximo' => 30
    ]);
    echo "✔ Curso ID 1 ACTIVADO.\n";

    // 6. Reactivar revisión de llaves
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "\n¡TODO LISTO! Intenta la inscripción ahora, ya no hay nada que la bloquee.\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
