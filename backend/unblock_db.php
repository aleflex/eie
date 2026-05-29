<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Curso;

try {
    echo "Ajustando tabla CURSOS...\n";
    $columns = ['idioma_id', 'nivel_id', 'modalidad_id', 'horario_id', 'descripcion', 'estado'];
    
    foreach ($columns as $col) {
        try {
            DB::statement("ALTER TABLE cursos MODIFY $col varchar(255) NULL");
            echo " - Columna $col ahora es NULLable.\n";
        } catch (\Exception $e) {
            echo " - No se pudo modificar $col (tal vez no existe o ya es null).\n";
        }
    }
    
    // Intentar crear el curso 1 otra vez
    if (!Curso::find(1)) {
        DB::table('cursos')->insert([
            'id' => 1,
            'nombre_curso' => 'Curso Inicial EIE',
            'estado' => 'Activo'
        ]);
        echo "Curso ID 1 creado con éxito.\n";
    } else {
        echo "Curso ID 1 ya existía.\n";
    }

    echo "\n¡Base de datos lista para recibir inscripciones!\n";
    
} catch (\Exception $e) {
    echo "ERROR CRITICO: " . $e->getMessage();
}
