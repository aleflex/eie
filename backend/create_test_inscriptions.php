<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estudiante;
use App\Models\Inscripcion;

try {
    echo "--- GENERANDO INSCRIPCIONES PARA ESTUDIANTES EXISTENTES ---\n";
    $estudiantes = Estudiante::all();
    
    foreach ($estudiantes as $e) {
        // Verificar si ya tiene inscripción
        $existe = Inscripcion::where('estudiante_id', $e->id)->exists();
        
        if (!$existe) {
            Inscripcion::create([
                'estudiante_id' => $e->id,
                'curso_id' => 1,
                'fecha_registro' => now(),
                'estado' => 'pendiente'
            ]);
            echo " - Inscripción creada para ID {$e->id} ({$e->nombres})\n";
        } else {
            echo " - El estudiante ID {$e->id} ya estaba inscrito.\n";
        }
    }
    echo "--- PROCESO COMPLETADO ---\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
