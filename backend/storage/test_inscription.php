<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\InscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    echo "Running transaction test...\n";

    // Delete existing test student if exists
    $ci = '99999999';
    $existingUser = DB::table('usuarios')->where('ci', $ci)->first();
    if ($existingUser) {
        $existing = DB::table('estudiantes')->where('id_usuario', $existingUser->id_usuario)->first();
        if ($existing) {
            DB::table('inscripciones')->where('id_estudiante', $existing->id_estudiante)->delete();
            DB::table('contactos_emergencia')->where('id_estudiante', $existing->id_estudiante)->delete();
            DB::table('estudiante_responsable')->where('id_estudiante', $existing->id_estudiante)->delete();
            DB::table('estudiantes')->where('id_estudiante', $existing->id_estudiante)->delete();
        }
        DB::table('usuarios')->where('id_usuario', $existingUser->id_usuario)->delete();
    }

    $request = Request::create('/api/inscripciones', 'POST', [
        'nombres' => 'Juan Test',
        'apellidos' => 'Perez Gomez',
        'ci' => $ci,
        'email' => 'juan.test@domain.com',
        'celularPrefix' => '+591',
        'celular' => '71234567',
        'lugarNacimiento' => 'Cochabamba',
        'fechaNacimiento' => '2000-05-15',
        'anioBachiller' => 2018,
        'estadoCivil' => 'Soltero',
        'grupoSanguineo' => 'O+',
        'domicilio' => 'Calle Principal 123',
        'nombrePadres' => 'Pedro Perez',
        'ciTutor' => '88888888',
        'contactoEmergencia' => 'Maria Perez - 72222222',
        'idioma' => 'Inglés',
        'nivel' => 'Básico I',
        'tipoCurso' => 'Regular',
        'userType' => 'normal',
        'horario' => '18:00 - 19:30'
    ]);

    $controller = new InscriptionController();
    $response = $controller->store($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";

    if ($response->getStatusCode() == 201) {
        echo "Test PASSED! Checking inserted records:\n";
        $user = DB::table('usuarios')->where('ci', $ci)->first();
        print_r($user);

        $stud = DB::table('estudiantes')->where('id_usuario', $user->id_usuario)->first();
        print_r($stud);

        $resp = DB::table('responsables')->where('ci_responsable', '88888888')->first();
        print_r($resp);

        $cont = DB::table('contactos_emergencia')->where('id_estudiante', $stud->id_estudiante)->first();
        print_r($cont);

        $ins = DB::table('inscripciones')->where('id_estudiante', $stud->id_estudiante)->first();
        print_r($ins);
    } else {
        echo "Test FAILED!\n";
    }

    DB::rollBack();
    echo "Transaction rolled back successfully.\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    DB::rollBack();
}
