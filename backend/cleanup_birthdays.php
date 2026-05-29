<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estudiante;
use Illuminate\Support\Facades\DB;

try {
    echo "--- INICIANDO LIMPIEZA DE DATOS DE NACIMIENTO ---\n";
    $estudiantes = Estudiante::all();
    
    foreach ($estudiantes as $e) {
        // Si el lugar contiene algo que parece una fecha (ej: "Lp 17/09/2001")
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})/', $e->lugar_nacimiento, $matches)) {
            $fecha_encontrada = $matches[1];
            // Convertir 17/09/2001 a 2001-09-17 para la base de datos
            $partes = explode('/', $fecha_encontrada);
            $fecha_db = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
            
            // Limpiar el lugar (quitar la fecha del texto)
            $lugar_limpio = trim(str_replace($fecha_encontrada, '', $e->lugar_nacimiento));
            
            echo "ID {$e->id}: Corrigiendo '{$e->lugar_nacimiento}' -> Lugar: '{$lugar_limpio}', Fecha: '{$fecha_db}'\n";
            
            $e->lugar_nacimiento = $lugar_limpio;
            $e->fecha_nacimiento = $fecha_db;
            $e->save();
        }
    }
    echo "--- LIMPIEZA COMPLETADA ---\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
