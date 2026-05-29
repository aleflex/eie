<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Curso;

try {
    // 1. Hacer idioma_id opcional en la tabla cursos
    DB::statement('ALTER TABLE cursos MODIFY idioma_id int(11) NULL');
    echo "Columna idioma_id actualizada a NULLable.\n";
    
    // 2. Crear el curso 1 si no existe
    if (!Curso::find(1)) {
        Curso::create([
            'id' => 1,
            'nombre_curso' => 'Curso de Idiomas - Nivel Inicial',
            'descripcion' => 'Inscripción automática',
            'estado' => 'Activo'
        ]);
        echo "Curso ID 1 creado con éxito.\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
