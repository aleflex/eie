<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Curso;

try {
    echo "Intentando crear curso de Chino...\n";
    $curso = Curso::create([
        'idioma' => 'Chino',
        'nivel' => 'NIVEL I (BOOK 1-6)',
        'modalidad' => 'Presencial',
        'horario' => '10:00 - 12:00',
        'cupo_minimo' => 10,
        'cupo_maximo' => 30
    ]);
    echo "¡ÉXITO! Curso ID {$curso->id} creado.\n";
} catch (\Exception $e) {
    echo "FALLÓ LA CREACIÓN:\n";
    echo $e->getMessage() . "\n";
}
