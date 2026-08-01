<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Curso;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $curso = Curso::first();
    if (!$curso) {
        throw new Exception("No hay ningún curso en la base de datos.");
    }

    $studentsData = [
        [
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Pérez Militar',
            'ci' => '1000001',
            'email' => 'juan.perez@est.eie.edu.bo',
            'tipo_usuario' => 'militar',
            'grado_academico' => 'Sargento',
            'arma_especialidad' => 'Caballería',
            'carnet_militar' => 'CM-887766',
            'celular' => '71234567',
            'lugar_nacimiento' => 'La Paz',
            'fecha_nacimiento' => '1990-05-15',
            'domicilio' => 'Av. Arce 1234',
            'anio_egreso_bachiller' => 2008,
        ],
        [
            'nombres' => 'María Belén',
            'apellidos' => 'Flores EMI',
            'ci' => '1000002',
            'email' => 'maria.flores@est.eie.edu.bo',
            'tipo_usuario' => 'emi',
            'celular' => '60555444',
            'lugar_nacimiento' => 'Cochabamba',
            'fecha_nacimiento' => '2001-10-20',
            'domicilio' => 'Calle Jordan 456',
            'anio_egreso_bachiller' => 2019,
        ],
        [
            'nombres' => 'Carlos Daniel',
            'apellidos' => 'López Hijo',
            'ci' => '1000003',
            'email' => 'carlos.lopez@est.eie.edu.bo',
            'tipo_usuario' => 'hijo_militar',
            'carnet_cossmil' => 'COS-112233',
            'celular' => '79998888',
            'lugar_nacimiento' => 'Santa Cruz',
            'fecha_nacimiento' => '2004-03-12',
            'domicilio' => 'Av. Beni 789',
            'anio_egreso_bachiller' => 2021,
        ],
        [
            'nombres' => 'Ana Sofía',
            'apellidos' => 'Guzmán Civil',
            'ci' => '1000004',
            'email' => 'ana.guzman@gmail.com',
            'tipo_usuario' => 'normal',
            'celular' => '68887777',
            'lugar_nacimiento' => 'Oruro',
            'fecha_nacimiento' => '1999-07-30',
            'domicilio' => 'Calle Junin 101',
            'anio_egreso_bachiller' => 2017,
        ],
    ];

    foreach ($studentsData as $data) {
        // 1. Crear usuario
        $user = User::updateOrCreate(
            ['correo_institucional' => $data['email']],
            [
                'id_rol' => 3, // ESTUDIANTE
                'password' => Hash::make($data['ci']),
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'ci' => $data['ci'],
                'estado' => 'ACTIVO',
            ]
        );

        // 2. Grado y Arma
        $idGrado = null;
        if (isset($data['grado_academico'])) {
            $grado = \App\Models\Grado::firstOrCreate(['nombre_grado' => $data['grado_academico']]);
            $idGrado = $grado->id_grado;
        }

        $idArma = null;
        if (isset($data['arma_especialidad'])) {
            $arma = \App\Models\Arma::firstOrCreate(['nombre_arma' => $data['arma_especialidad']]);
            $idArma = $arma->id_arma;
        }

        // 3. Crear estudiante
        $estudiante = Estudiante::updateOrCreate(
            ['id_usuario' => $user->id_usuario],
            [
                'id_grado' => $idGrado,
                'id_arma' => $idArma,
                'id_estado_civil' => 1,
                'id_grupo_sanguineo' => 1,
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'lugar_nacimiento' => $data['lugar_nacimiento'],
                'carnet_militar' => $data['carnet_militar'] ?? null,
                'carnet_cossmil' => $data['carnet_cossmil'] ?? null,
                'celular' => $data['celular'],
                'domicilio' => $data['domicilio'],
                'anio_egreso_bachiller' => $data['anio_egreso_bachiller'],
                'hermanos_inscritos' => 0,
                'tipo_usuario' => $data['tipo_usuario']
            ]
        );

        // 4. Crear Inscripción
        Inscripcion::updateOrCreate(
            ['id_estudiante' => $estudiante->id_estudiante, 'id_curso' => $curso->id_curso],
            [
                'id_paralelo' => null,
                'fecha_registro' => now()->format('Y-m-d'),
                'estado' => 'activo'
            ]
        );

        echo "Estudiante creado e inscrito: {$data['nombres']} {$data['apellidos']} (CI: {$data['ci']})\n";
    }

    DB::commit();
    echo "Seeding completado con éxito!\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
