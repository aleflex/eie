<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estudiante;

try {
    Estudiante::create([
        'tipo_usuario' => 'militar',
        'nombres' => 'Juan',
        'apellidos' => 'Perez Militar',
        'ci' => '888888',
        'grado_academico' => 'Sargento',
        'arma_especialidad' => 'Comunicaciones',
        'celular' => '71234567',
        'lugar_nacimiento' => 'La Paz',
        'fecha_nacimiento' => '1985-05-20',
        'correo_electronico' => 'juan.militar@test.com'
    ]);
    echo "¡Militar de prueba (Juan Perez) creado con éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
