<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estudiante;

try {
    // 1. Hijo de Militar
    Estudiante::updateOrCreate(
        ['ci' => '111222'],
        [
            'tipo_usuario' => 'hijo_militar',
            'nombres' => 'Carlos',
            'apellidos' => 'Hijo de Militar',
            'carnet_cossmil' => 'COS-9988',
            'celular' => '60001111',
            'lugar_nacimiento' => 'Cochabamba',
            'fecha_nacimiento' => '2005-10-10',
            'correo_electronico' => 'carlos@test.com'
        ]
    );

    // 2. Estudiante EMI
    Estudiante::updateOrCreate(
        ['ci' => '333444'],
        [
            'tipo_usuario' => 'emi',
            'nombres' => 'Maria',
            'apellidos' => 'Estudiante EMI',
            'celular' => '70002222',
            'lugar_nacimiento' => 'Oruro',
            'fecha_nacimiento' => '2002-12-15',
            'correo_electronico' => 'maria@emi.edu.bo'
        ]
    );

    // 3. Civil Normal
    Estudiante::updateOrCreate(
        ['ci' => '555666'],
        [
            'tipo_usuario' => 'normal',
            'nombres' => 'Pedro',
            'apellidos' => 'Civil',
            'celular' => '78883333',
            'lugar_nacimiento' => 'Potosí',
            'fecha_nacimiento' => '1998-03-25',
            'correo_electronico' => 'pedro@gmail.com'
        ]
    );

    echo "¡Estudiantes de prueba (Hijo Militar, EMI y Civil) creados con éxito!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
