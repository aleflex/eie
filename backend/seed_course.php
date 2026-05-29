<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Curso;

if (!Curso::find(1)) {
    Curso::create([
        'id' => 1,
        'nombre_curso' => 'Curso de Idiomas - Nivel Inicial',
        'descripcion' => 'Curso por defecto para nuevas inscripciones',
        'estado' => 'Activo'
    ]);
    echo "Curso ID 1 creado correctamente.\n";
} else {
    echo "El curso ID 1 ya existe.\n";
}
