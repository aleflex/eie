<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estudiante;

echo "--- LISTA DE ESTUDIANTES REGISTRADOS ---\n";
$estudiantes = Estudiante::all();
foreach($estudiantes as $e) {
    echo "ID: {$e->id} | Tipo: {$e->tipo_usuario} | CI: {$e->ci} | Nombre: {$e->nombres} {$e->apellidos}\n";
}
echo "----------------------------------------\n";
