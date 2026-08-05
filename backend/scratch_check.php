<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$estudiantes = \App\Models\Estudiante::with(['user', 'inscripciones'])->get();
foreach ($estudiantes as $e) {
    $nombres = $e->user->nombres ?? $e->nombres ?? '';
    $apellidos = $e->user->apellidos ?? $e->apellidos ?? '';
    $name = $nombres . ' ' . $apellidos;
    if (stripos($name, 'Rodrigo') !== false || stripos($name, 'Alarcon') !== false || stripos($name, 'Mateo') !== false) {
        echo "========================================\n";
        echo "ID Estudiante: " . $e->id_estudiante . "\n";
        echo "Nombre: " . $name . "\n";
        echo "CI User: " . json_encode($e->user->ci ?? null) . "\n";
        echo "Expedido User: " . json_encode($e->user->expedido ?? null) . "\n";
        echo "CI Tutor: " . json_encode($e->ci_tutor) . "\n";
        echo "Carnet Militar: " . json_encode($e->carnet_militar) . "\n";
        echo "Carnet Cossmil: " . json_encode($e->carnet_cossmil) . "\n";
        echo "Inscripciones Count: " . $e->inscripciones->count() . "\n";
        foreach ($e->inscripciones as $ins) {
            echo "  - Inscription ID: " . $ins->id_inscripcion . " | Curso ID: " . $ins->id_curso . " | Paralelo ID: " . $ins->id_paralelo . " | Estado: " . $ins->estado . "\n";
        }
    }
}
