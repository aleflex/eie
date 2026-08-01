<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Idioma;
use App\Models\Nivel;
use App\Models\Modalidad;
use App\Models\Curso;
use App\Models\Aula;
use App\Models\Horario;
use App\Models\Paralelo;
use App\Models\Inscripcion;
use App\Models\Documento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with complete, realistic data.
     */
    public function run(): void
    {
        // 1. Catálogos base (Grados, Armas, Idiomas, Niveles, Modalidades, Aulas, Horarios)
        
        // Grados Militares y Civiles
        $grados = ['Subteniente', 'Teniente', 'Capitán', 'Mayor', 'Teniente Coronel', 'Coronel', 'Premilitar', 'Civil'];
        foreach ($grados as $g) {
            if (!DB::table('grados')->where('nombre_grado', $g)->exists()) {
                DB::table('grados')->insert(['nombre_grado' => $g, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Armas del Ejército
        $armas = ['Infantería', 'Caballería', 'Artallería', 'Ingeniería', 'Comunicaciones', 'Blindados', 'Servicios'];
        foreach ($armas as $a) {
            if (!DB::table('armas')->where('nombre_arma', $a)->exists()) {
                DB::table('armas')->insert(['nombre_arma' => $a, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Idiomas
        $idiomas = ['Inglés', 'Francés', 'Chino Mandarín', 'Alemán', 'Quechua', 'Aymara'];
        foreach ($idiomas as $idm) {
            if (!Idioma::where('nombre_idioma', $idm)->exists()) {
                Idioma::create(['nombre_idioma' => $idm]);
            }
        }

        // Niveles
        $niveles = ['NIVEL I (BOOK 1-6)', 'NIVEL II (BOOK 7-12)', 'NIVEL III (BOOK 13-18)', 'AVANZADO I', 'AVANZADO II'];
        foreach ($niveles as $niv) {
            if (!Nivel::where('nombre_nivel', $niv)->exists()) {
                Nivel::create(['nombre_nivel' => $niv]);
            }
        }

        // Modalidades
        $modalidades = ['Presencial', 'Virtual', 'Semipresencial'];
        foreach ($modalidades as $mod) {
            if (!Modalidad::where('nombre_modalidad', $mod)->exists()) {
                Modalidad::create(['nombre_modalidad' => $mod]);
            }
        }

        // Aulas
        if (Aula::count() === 0) {
            Aula::create(['nombre_aula' => 'Aula 101 - Bloque A', 'capacidad' => 30, 'estado' => 'Activo']);
            Aula::create(['nombre_aula' => 'Aula 102 - Bloque A', 'capacidad' => 30, 'estado' => 'Activo']);
            Aula::create(['nombre_aula' => 'Aula 201 - Bloque B', 'capacidad' => 35, 'estado' => 'Activo']);
            Aula::create(['nombre_aula' => 'Laboratorio de Idiomas 1', 'capacidad' => 25, 'estado' => 'Activo']);
            Aula::create(['nombre_aula' => 'Aula Virtual Zoom 1', 'capacidad' => 100, 'estado' => 'Activo']);
        }

        // Horarios
        if (Horario::count() === 0) {
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:00:00', 'estado' => 'Activo']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '14:30:00', 'hora_fin' => '16:30:00', 'estado' => 'Activo']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00', 'estado' => 'Activo']);
            Horario::create(['dia_semana' => 'Sábados', 'hora_inicio' => '08:30:00', 'hora_fin' => '12:30:00', 'estado' => 'Activo']);
        }

        // 2. Usuarios del Sistema (Admin, Rector, Secretaria, Docentes, Estudiantes)
        
        // ADMIN
        $admin = User::firstOrCreate(
            ['correo_institucional' => 'admin@eie.edu.bo'],
            [
                'id_rol' => 1,
                'password' => Hash::make('password123'),
                'nombres' => 'Carlos Mario',
                'apellidos' => 'Mendoza Claros',
                'ci' => '4589123 LP',
                'estado' => 'ACTIVO',
            ]
        );

        // RECTOR / DIRECTIVO
        $rector = User::firstOrCreate(
            ['correo_institucional' => 'rector@eie.edu.bo'],
            [
                'id_rol' => 4,
                'password' => Hash::make('password123'),
                'nombres' => 'Fernando',
                'apellidos' => 'Gutierrez DAEN',
                'ci' => '3412980 CB',
                'estado' => 'ACTIVO',
            ]
        );

        // SECRETARIA
        $secretaria = User::firstOrCreate(
            ['correo_institucional' => 'secretaria@eie.edu.bo'],
            [
                'id_rol' => 5,
                'password' => Hash::make('password123'),
                'nombres' => 'Maria Elena',
                'apellidos' => 'Paredes Rojas',
                'ci' => '5123908 LP',
                'estado' => 'ACTIVO',
            ]
        );

        // DOCENTE 1 - INGLÉS
        $userDoc1 = User::firstOrCreate(
            ['correo_institucional' => 'docente.ingles@eie.edu.bo'],
            [
                'id_rol' => 3,
                'password' => Hash::make('password123'),
                'nombres' => 'Roberto',
                'apellidos' => 'Valenzuela Solares',
                'ci' => '4892105 CB',
                'estado' => 'ACTIVO',
            ]
        );

        $docente1 = Docente::firstOrCreate(
            ['id_usuario' => $userDoc1->id_usuario],
            [
                'especialidad' => 'Lingüística e Inglés Técnico',
                'telefono' => '71728394',
                'estado' => 'Activo',
                'id_tipo_contrato' => 2, // Titular
                'fecha_contrato' => '2025-01-15',
            ]
        );

        // DOCENTE 2 - FRANCÉS
        $userDoc2 = User::firstOrCreate(
            ['correo_institucional' => 'docente.frances@eie.edu.bo'],
            [
                'id_rol' => 3,
                'password' => Hash::make('password123'),
                'nombres' => 'Patricia',
                'apellidos' => 'Morales Villarroel',
                'ci' => '3981204 LP',
                'estado' => 'ACTIVO',
            ]
        );

        $docente2 = Docente::firstOrCreate(
            ['id_usuario' => $userDoc2->id_usuario],
            [
                'especialidad' => 'Lengua y Cultura Francesa',
                'telefono' => '72839405',
                'estado' => 'Activo',
                'id_tipo_contrato' => 1, // Contratado
                'fecha_contrato' => '2026-02-01',
            ]
        );

        // ESTUDIANTE 1 - Subteniente Militar
        $userEst1 = User::firstOrCreate(
            ['correo_institucional' => 'estudiante.juan@eie.edu.bo'],
            [
                'id_rol' => 2,
                'password' => Hash::make('password123'),
                'nombres' => 'Juan Pablo',
                'apellidos' => 'Mamani Claros',
                'ci' => '8421093 CB',
                'estado' => 'ACTIVO',
            ]
        );

        $idGradoSubt = DB::table('grados')->where('nombre_grado', 'Subteniente')->value('id_grado');
        $idArmaInf = DB::table('armas')->where('nombre_arma', 'Infantería')->value('id_arma');

        $estudiante1 = Estudiante::firstOrCreate(
            ['id_usuario' => $userEst1->id_usuario],
            [
                'id_grado' => $idGradoSubt,
                'id_arma' => $idArmaInf,
                'id_estado_civil' => 1, // Soltero
                'id_grupo_sanguineo' => 1, // O+
                'fecha_nacimiento' => '1998-05-14',
                'lugar_nacimiento' => 'Cochabamba',
                'carnet_militar' => 'CM-849201',
                'carnet_cossmil' => 'CS-48921',
                'celular' => '71234567',
                'anio_egreso_bachiller' => 2016,
                'domicilio' => 'Av. Ejército Nro. 450, Zona Muyurina, Cochabamba',
                'hermanos_inscritos' => 1,
                'tipo_usuario' => 'militar',
                'estado' => 'Activo',
                'documentos_habilitados_hasta' => '2027-12-31 23:59:59',
            ]
        );
        $estudiante1->nombre_padres = 'Sgt. Mario Mamani Perez y Sra. Rosa Claros';
        $estudiante1->ci_tutor = '3920194 CB';
        $estudiante1->contacto_emergencia = '71239081';
        $estudiante1->save();

        // ESTUDIANTE 2 - Civil
        $userEst2 = User::firstOrCreate(
            ['correo_institucional' => 'estudiante.ana@eie.edu.bo'],
            [
                'id_rol' => 2,
                'password' => Hash::make('password123'),
                'nombres' => 'Ana Isabel',
                'apellidos' => 'Vargas Rios',
                'ci' => '9120485 LP',
                'estado' => 'ACTIVO',
            ]
        );

        $idGradoCivil = DB::table('grados')->where('nombre_grado', 'Civil')->value('id_grado');

        $estudiante2 = Estudiante::firstOrCreate(
            ['id_usuario' => $userEst2->id_usuario],
            [
                'id_grado' => $idGradoCivil,
                'id_arma' => null,
                'id_estado_civil' => 1, // Soltero
                'id_grupo_sanguineo' => 3, // A+
                'fecha_nacimiento' => '2001-09-22',
                'lugar_nacimiento' => 'La Paz',
                'carnet_militar' => null,
                'carnet_cossmil' => null,
                'celular' => '76543210',
                'anio_egreso_bachiller' => 2019,
                'domicilio' => 'Calle España Nro. 120, Zona Central, Cochabamba',
                'hermanos_inscritos' => 0,
                'tipo_usuario' => 'normal',
                'estado' => 'Activo',
                'documentos_habilitados_hasta' => '2027-12-31 23:59:59',
            ]
        );
        $estudiante2->nombre_padres = 'Sr. Carlos Vargas y Sra. Elena Rios de Vargas';
        $estudiante2->ci_tutor = '2981049 LP';
        $estudiante2->contacto_emergencia = '76510928';
        $estudiante2->save();

        // ESTUDIANTE 3 - Teniente Militar
        $userEst3 = User::firstOrCreate(
            ['correo_institucional' => 'estudiante.carlos@eie.edu.bo'],
            [
                'id_rol' => 2,
                'password' => Hash::make('password123'),
                'nombres' => 'Carlos Eduardo',
                'apellidos' => 'Siles Torrez',
                'ci' => '7410928 SC',
                'estado' => 'ACTIVO',
            ]
        );

        $idGradoTte = DB::table('grados')->where('nombre_grado', 'Teniente')->value('id_grado');
        $idArmaCab = DB::table('armas')->where('nombre_arma', 'Caballería')->value('id_arma');

        $estudiante3 = Estudiante::firstOrCreate(
            ['id_usuario' => $userEst3->id_usuario],
            [
                'id_grado' => $idGradoTte,
                'id_arma' => $idArmaCab,
                'id_estado_civil' => 2, // Casado
                'id_grupo_sanguineo' => 1, // O+
                'fecha_nacimiento' => '1995-11-03',
                'lugar_nacimiento' => 'Santa Cruz',
                'carnet_militar' => 'CM-901248',
                'carnet_cossmil' => 'CS-51029',
                'celular' => '79812345',
                'anio_egreso_bachiller' => 2013,
                'domicilio' => 'Av. Heroínas Nro. 890, Cochabamba',
                'hermanos_inscritos' => 0,
                'tipo_usuario' => 'militar',
                'estado' => 'Activo',
                'documentos_habilitados_hasta' => '2027-12-31 23:59:59',
            ]
        );
        $estudiante3->nombre_padres = 'My. Eduardo Siles P. y Sra. Maria Torrez';
        $estudiante3->ci_tutor = '3109284 SC';
        $estudiante3->contacto_emergencia = '79812340';
        $estudiante3->save();

        // ESTUDIANTE 4 - Estudiante EMI
        $userEst4 = User::firstOrCreate(
            ['correo_institucional' => 'estudiante.emi@eie.edu.bo'],
            [
                'id_rol' => 2,
                'password' => Hash::make('password123'),
                'nombres' => 'Rodrigo',
                'apellidos' => 'Alarcon Peñaranda',
                'ci' => '6190284 LP',
                'estado' => 'ACTIVO',
            ]
        );

        $idArmaIng = DB::table('armas')->where('nombre_arma', 'Ingeniería')->value('id_arma');

        $estudiante4 = Estudiante::firstOrCreate(
            ['id_usuario' => $userEst4->id_usuario],
            [
                'id_grado' => $idGradoSubt,
                'id_arma' => $idArmaIng,
                'id_estado_civil' => 1,
                'id_grupo_sanguineo' => 1,
                'fecha_nacimiento' => '1999-03-18',
                'lugar_nacimiento' => 'La Paz',
                'carnet_militar' => 'CM-994012',
                'carnet_cossmil' => 'CS-88123',
                'celular' => '71209845',
                'anio_egreso_bachiller' => 2017,
                'domicilio' => 'Av. Irpavi Nro. 300, La Paz',
                'hermanos_inscritos' => 1,
                'estado' => 'Activo',
                'tipo_usuario' => 'emi',
                'documentos_habilitados_hasta' => '2027-12-31 23:59:59',
            ]
        );
        $estudiante4->nombre_padres = 'Gral. Rodrigo Alarcon V. y Sra. Carmen Peñaranda';
        $estudiante4->ci_tutor = '2981048 LP';
        $estudiante4->contacto_emergencia = '71209845';
        $estudiante4->save();

        // ESTUDIANTE 5 - Hijo de Militar
        $userEst5 = User::firstOrCreate(
            ['correo_institucional' => 'estudiante.hijo@eie.edu.bo'],
            [
                'id_rol' => 2,
                'password' => Hash::make('password123'),
                'nombres' => 'Mateo Fernando',
                'apellidos' => 'Gutierrez Morales',
                'ci' => '9840192 CB',
                'estado' => 'ACTIVO',
            ]
        );

        $estudiante5 = Estudiante::firstOrCreate(
            ['id_usuario' => $userEst5->id_usuario],
            [
                'id_grado' => $idGradoCivil,
                'id_arma' => null,
                'id_estado_civil' => 1,
                'id_grupo_sanguineo' => 1,
                'fecha_nacimiento' => '2004-12-10',
                'lugar_nacimiento' => 'Cochabamba',
                'carnet_militar' => null,
                'carnet_cossmil' => 'CS-99120',
                'celular' => '76590124',
                'anio_egreso_bachiller' => 2022,
                'domicilio' => 'Av. Ballivián Nro. 500, Cochabamba',
                'hermanos_inscritos' => 2,
                'estado' => 'Activo',
                'tipo_usuario' => 'hijo_militar',
                'documentos_habilitados_hasta' => '2027-12-31 23:59:59',
            ]
        );
        $estudiante5->nombre_padres = 'Cnl. DAEN Fernando Gutierrez y Sra. Sofia Morales';
        $estudiante5->ci_tutor = '3412980 CB';
        $estudiante5->contacto_emergencia = '76590124';
        $estudiante5->save();

        // 3. Cursos, Paralelos e Inscripciones
        $idiomaIngles = Idioma::where('nombre_idioma', 'Inglés')->first()->id_idioma;
        $idiomaFrances = Idioma::where('nombre_idioma', 'Francés')->first()->id_idioma;

        $nivel1 = Nivel::first()->id_nivel;
        $modPresencial = Modalidad::where('nombre_modalidad', 'Presencial')->first()->id_modalidad;
        $modVirtual = Modalidad::where('nombre_modalidad', 'Virtual')->first()->id_modalidad;

        // Curso 1: Inglés I Presencial
        $cursoIngles = Curso::firstOrCreate(
            ['id_idioma' => $idiomaIngles, 'id_nivel' => $nivel1, 'id_modalidad' => $modPresencial],
            ['cupo_minimo' => 5, 'cupo_maximo' => 30, 'estado' => 'Activo']
        );

        // Curso 2: Francés I Virtual
        $cursoFrances = Curso::firstOrCreate(
            ['id_idioma' => $idiomaFrances, 'id_nivel' => $nivel1, 'id_modalidad' => $modVirtual],
            ['cupo_minimo' => 5, 'cupo_maximo' => 25, 'estado' => 'Activo']
        );

        // Paralelos
        $aula101 = Aula::first()->id_aula;
        $paraleloA = Paralelo::firstOrCreate(
            ['id_curso' => $cursoIngles->id_curso, 'nombre_paralelo' => 'Paralelo A'],
            ['id_aula' => $aula101, 'estado' => 'Activo']
        );

        $paraleloB = Paralelo::firstOrCreate(
            ['id_curso' => $cursoFrances->id_curso, 'nombre_paralelo' => 'Paralelo A-Virtual'],
            ['id_aula' => null, 'estado' => 'Activo']
        );

        // Asignación de Docente a Paralelo
        DB::table('docente_paralelo')->insertOrIgnore([
            ['id_docente' => $docente1->id_docente, 'id_paralelo' => $paraleloA->id_paralelo, 'created_at' => now(), 'updated_at' => now()],
            ['id_docente' => $docente2->id_docente, 'id_paralelo' => $paraleloB->id_paralelo, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Inscripciones
        $insc1 = Inscripcion::firstOrCreate(
            ['id_estudiante' => $estudiante1->id_estudiante, 'id_curso' => $cursoIngles->id_curso],
            ['id_paralelo' => $paraleloA->id_paralelo, 'fecha_registro' => '2026-02-10', 'estado' => 'Activo']
        );

        $insc2 = Inscripcion::firstOrCreate(
            ['id_estudiante' => $estudiante2->id_estudiante, 'id_curso' => $cursoIngles->id_curso],
            ['id_paralelo' => $paraleloA->id_paralelo, 'fecha_registro' => '2026-02-11', 'estado' => 'Activo']
        );

        $insc3 = Inscripcion::firstOrCreate(
            ['id_estudiante' => $estudiante3->id_estudiante, 'id_curso' => $cursoFrances->id_curso],
            ['id_paralelo' => $paraleloB->id_paralelo, 'fecha_registro' => '2026-02-12', 'estado' => 'Activo']
        );

        // 4. Documentos Digitales Reales (Asociados a Estudiantes)
        $docsJuan = [
            ['tipo' => 'Cédula de Identidad', 'nombre' => 'ci_juan_mamani.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante1->id_estudiante . '/ci_juan_mamani.pdf'],
            ['tipo' => 'Certificado de Nacimiento', 'nombre' => 'certificado_nacimiento_juan.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante1->id_estudiante . '/certificado_nacimiento_juan.pdf'],
            ['tipo' => 'Carnet Militar', 'nombre' => 'carnet_militar_juan.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante1->id_estudiante . '/carnet_militar_juan.pdf'],
            ['tipo' => 'Comprobante de Depósito', 'nombre' => 'comprobante_deposito_juan.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante1->id_estudiante . '/comprobante_deposito_juan.pdf'],
        ];

        foreach ($docsJuan as $d) {
            Documento::firstOrCreate(
                ['id_estudiante' => $estudiante1->id_estudiante, 'nombre_archivo' => $d['nombre']],
                ['tipo_documento' => $d['tipo'], 'ruta_archivo' => $d['ruta']]
            );
        }

        $docsAna = [
            ['tipo' => 'Cédula de Identidad', 'nombre' => 'ci_ana_vargas.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante2->id_estudiante . '/ci_ana_vargas.pdf'],
            ['tipo' => 'Título de Bachiller', 'nombre' => 'titulo_bachiller_ana.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante2->id_estudiante . '/titulo_bachiller_ana.pdf'],
            ['tipo' => 'Comprobante de Depósito', 'nombre' => 'deposito_bancario_ana.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante2->id_estudiante . '/deposito_bancario_ana.pdf'],
        ];

        foreach ($docsAna as $d) {
            Documento::firstOrCreate(
                ['id_estudiante' => $estudiante2->id_estudiante, 'nombre_archivo' => $d['nombre']],
                ['tipo_documento' => $d['tipo'], 'ruta_archivo' => $d['ruta']]
            );
        }

        $docsCarlos = [
            ['tipo' => 'Cédula de Identidad', 'nombre' => 'ci_carlos_siles.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante3->id_estudiante . '/ci_carlos_siles.pdf'],
            ['tipo' => 'Carnet Militar', 'nombre' => 'carnet_militar_carlos.pdf', 'ruta' => '/storage/documentos/estudiantes/' . $estudiante3->id_estudiante . '/carnet_militar_carlos.pdf'],
        ];

        foreach ($docsCarlos as $d) {
            Documento::firstOrCreate(
                ['id_estudiante' => $estudiante3->id_estudiante, 'nombre_archivo' => $d['nombre']],
                ['tipo_documento' => $d['tipo'], 'ruta_archivo' => $d['ruta']]
            );
        }

        // 5. Contactos de Emergencia
        if (DB::table('contactos_emergencia')->count() === 0) {
            DB::table('contactos_emergencia')->insert([
                [
                    'id_estudiante' => $estudiante1->id_estudiante,
                    'nombre_contacto' => 'Sgt. Mario Mamani Perez',
                    'ci' => '3920194 CB',
                    'relacion' => 'Padre',
                    'telefono' => '71239081',
                    'email' => 'mario.mamani@gmail.com',
                    'direccion' => 'Av. Ejército Nro. 450, Cochabamba',
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id_estudiante' => $estudiante2->id_estudiante,
                    'nombre_contacto' => 'Sra. Elena Rios de Vargas',
                    'ci' => '2981049 LP',
                    'relacion' => 'Madre',
                    'telefono' => '76510928',
                    'email' => 'elena.vargas@gmail.com',
                    'direccion' => 'Calle España Nro. 120, Cochabamba',
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 6. Sembrar Configuraciones del Sistema
        $this->call(SettingsTableSeeder::class);
    }
}
