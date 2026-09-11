<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class InscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s\.\'\-]+$/u'],
            'apellidos' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\'\-]+$/u'],
            'ci' => 'required|string|min:5|max:30',
            'email' => 'required|email',
            'celularPrefix' => 'nullable|string',
            'celular' => 'required|string',
            'lugarNacimiento' => 'required|string|min:2',
            'fechaNacimiento' => 'required|date',
            'anioBachiller' => 'required|numeric',
            'estadoCivil' => 'required|string',
            'grupoSanguineo' => 'required|string',
            'domicilio' => 'required|string',
            'nombrePadres' => 'nullable|string|max:255',
            'ciTutor' => 'nullable|string|max:30',
            'hermanosInscritos' => 'nullable|string|max:255',
            'contactoEmergencia' => 'required|string|max:255',
            'idioma' => 'required|string',
            'nivel' => 'required|string',
            'horario' => 'required|string',
            'tipoCurso' => 'required|string',
            'carnetCossmil' => 'nullable|string|max:50',
            'carnetMilitar' => 'nullable|string|max:50',
            'carnetMilitarSerie' => 'nullable|string|max:50',
        ];

        // Reglas condicionales de archivos:
        // Para usuario normal se exigen obligatoriamente los 4 requisitos
        if ($request->userType === 'normal') {
            $validationRules['carnet'] = 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
            $validationRules['titulo'] = 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
            $validationRules['nacimiento'] = 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
            $validationRules['foto'] = 'required|file|mimes:jpeg,jpg,png,webp|max:5120';
        } else {
            if ($request->hasFile('foto')) $validationRules['foto'] = 'file|mimes:jpeg,jpg,png,webp|max:5120';
            if ($request->hasFile('carnet')) $validationRules['carnet'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
            if ($request->hasFile('titulo')) $validationRules['titulo'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
            if ($request->hasFile('nacimiento')) $validationRules['nacimiento'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
        }

        if ($request->hasFile('deposito')) $validationRules['deposito'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
        if ($request->hasFile('credencialEmi')) $validationRules['credencialEmi'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
        if ($request->hasFile('carnetCossmilDoc')) $validationRules['carnetCossmilDoc'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';
        if ($request->hasFile('carnetMilitarDoc')) $validationRules['carnetMilitarDoc'] = 'file|mimes:pdf,jpeg,jpg,png,webp|max:5120';

        $request->validate($validationRules, [
            'nombres.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombres.regex' => 'El nombre solo debe contener letras (no se permiten números).',
            'apellidos.regex' => 'Los apellidos solo deben contener letras (no se permiten números).',
            'ci.min' => 'El carnet de identidad debe tener al menos 5 caracteres.',
            'lugarNacimiento.min' => 'El lugar de nacimiento debe tener al menos 2 caracteres.',
            'carnet.required' => 'El Carnet de Identidad es un requisito obligatorio.',
            'titulo.required' => 'El Título de Bachiller es un requisito obligatorio.',
            'nacimiento.required' => 'El Certificado de Nacimiento es un requisito obligatorio.',
            'foto.required' => 'La Fotografía Personal 4x4 con fondo rojo es un requisito obligatorio.',
            'foto.mimes' => 'La Fotografía Personal 4x4 debe ser una imagen (JPG o PNG con fondo rojo). No se permite formato PDF.',
            'carnet.mimes' => 'El Carnet de Identidad debe ser un archivo PDF o una imagen (JPG, PNG).',
            'titulo.mimes' => 'El Título de Bachiller debe ser un archivo PDF o una imagen (JPG, PNG).',
            'nacimiento.mimes' => 'El Certificado de Nacimiento debe ser un archivo PDF o una imagen (JPG, PNG).',
            'deposito.mimes' => 'La Boleta de Pago debe ser un archivo PDF o una imagen (JPG, PNG).',
            'credencialEmi.mimes' => 'La Credencial EMI debe ser un archivo PDF o una imagen (JPG, PNG).',
            'carnetCossmilDoc.mimes' => 'El Carnet COSSMIL debe ser un archivo PDF o una imagen (JPG, PNG).',
            'carnetMilitarDoc.mimes' => 'El Carnet Militar debe ser un archivo PDF o una imagen (JPG, PNG).',
        ]);

        // 0. Inspección estricta de Ciberseguridad de Archivos (Magic Bytes, Extensiones y Detección de Exploits en PDFs)
        if ($request->hasFile('foto')) {
            $this->validateSecureFile($request->file('foto'), true);
        }
        foreach (['carnet', 'titulo', 'nacimiento', 'deposito', 'credencialEmi', 'carnetCossmil', 'carnetCossmilDoc', 'carnetMilitarDoc'] as $key) {
            if ($request->hasFile($key)) {
                $this->validateSecureFile($request->file($key), false);
            }
        }

        try {
            DB::beginTransaction();

            $celularCompleto = ($request->celularPrefix ? ($request->celularPrefix . ' ') : '') . $request->celular;

            // 1. Buscar o reutilizar usuario existente por CI o Correo Electrónico
            $user = User::where('ci', $request->ci)
                        ->orWhere('correo_institucional', strtolower($request->email))
                        ->first();
            
            if ($user) {
                // Actualizar usuario existente
                $user->update([
                    'correo_institucional' => strtolower($request->email),
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'ci' => $request->ci,
                    'password' => Hash::make($request->ci),
                    'debe_cambiar_password' => true,
                ]);
            } else {
                // Crear nuevo usuario con rol Estudiante (id_rol = 2)
                $username = User::generateUsername($request->nombres, $request->apellidos);
                $user = User::create([
                    'id_rol' => 2, // 2 = estudiante en tabla roles
                    'correo_institucional' => strtolower($request->email),
                    'usuario' => $username,
                    'password' => Hash::make($request->ci),
                    'debe_cambiar_password' => true,
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'ci' => $request->ci,
                    'estado' => 'ACTIVO',
                ]);
            }

            // 2. Resolver grado académico mediante catálogo 'grados' (columna: nombre_grado)
            $idGrado = null;
            if ($request->filled('gradoAcademico') && trim($request->gradoAcademico) !== '') {
                $nombreGrado = trim($request->gradoAcademico);
                $grado = \App\Models\Grado::where('nombre_grado', $nombreGrado)->first();
                if (!$grado) {
                    $grado = \App\Models\Grado::create(['nombre_grado' => $nombreGrado]);
                }
                $idGrado = $grado->id_grado;
            }

            // 3. Resolver arma o especialidad mediante catálogo 'armas' (columna: nombre_arma)
            $idArma = null;
            if ($request->filled('armaEspecialidad') && trim($request->armaEspecialidad) !== '') {
                $nombreArma = trim($request->armaEspecialidad);
                $arma = \App\Models\Arma::where('nombre_arma', $nombreArma)->first();
                if (!$arma) {
                    $arma = \App\Models\Arma::create(['nombre_arma' => $nombreArma]);
                }
                $idArma = $arma->id_arma;
            }

            // 4. Resolver estado civil mediante catálogo 'estados_civil' (columna: nombre_estado_civil)
            $idEstadoCivil = null;
            // 4. Asignar estado civil y grupo sanguíneo directamente al Usuario (modelo User)
            if ($request->filled('estadoCivil') && trim($request->estadoCivil) !== '') {
                $user->estado_civil = trim($request->estadoCivil);
            }
            if ($request->filled('grupoSanguineo') && trim($request->grupoSanguineo) !== '') {
                $cleanGrupo = trim($request->grupoSanguineo);
                if (preg_match('/^([ABO\+\-]+)/i', $cleanGrupo, $m)) {
                    $cleanGrupo = trim($m[1]);
                }
                $user->grupo_sanguineo = $cleanGrupo;
            }
            $user->save();

            // 5. Normalizar carnet militar y cossmil (NULL para evitar colisiones UNIQUE en la BD)
            $carnetMilitar = ($request->filled('carnetMilitar') && trim($request->carnetMilitar) !== '') ? trim($request->carnetMilitar) : null;
            $carnetCossmil = ($request->filled('carnetCossmil') && trim($request->carnetCossmil) !== '') ? trim($request->carnetCossmil) : null;

            // 6. Buscar o crear perfil Estudiante
            $estudiante = Estudiante::where('id_usuario', $user->id_usuario)->first();

            // Verificar que los carnets no colisionen con otro estudiante
            if ($carnetMilitar && Estudiante::where('carnet_militar', $carnetMilitar)->when($estudiante, fn($q) => $q->where('id_estudiante', '!=', $estudiante->id_estudiante))->exists()) {
                $carnetMilitar = null;
            }
            if ($carnetCossmil && Estudiante::where('carnet_cossmil', $carnetCossmil)->when($estudiante, fn($q) => $q->where('id_estudiante', '!=', $estudiante->id_estudiante))->exists()) {
                $carnetCossmil = null;
            }

            $estudianteData = [
                'id_usuario' => $user->id_usuario,
                'id_grado' => $idGrado,
                'id_arma' => $idArma,
                'fecha_nacimiento' => $request->fechaNacimiento,
                'lugar_nacimiento' => $request->lugarNacimiento,
                'carnet_militar' => $carnetMilitar,
                'carnet_cossmil' => $carnetCossmil,
                'celular' => $celularCompleto,
                'domicilio' => $request->domicilio,
                'anio_egreso_bachiller' => $request->anioBachiller,
                'hermanos_inscritos' => is_numeric($request->hermanosInscritos) ? intval($request->hermanosInscritos) : 0,
                'tipo_usuario' => $request->userType ?? $request->tipo_usuario ?? 'normal',
                'estado' => 'Activo'
            ];

            if ($estudiante) {
                $estudiante->update($estudianteData);
            } else {
                $estudiante = Estudiante::create($estudianteData);
            }

            // 8. Procesar y almacenar archivos (Foto y Documentos de respaldo)
            try {
                if ($request->hasFile('foto')) {
                    $file = $request->file('foto');
                    $mime = $file->getClientMimeType() ?: 'image/jpeg';
                    $fileBinary = file_get_contents($file->getRealPath());
                    $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
                    $fileName = time() . '_' . \Illuminate\Support\Str::random(12) . '.' . $ext;
                    $remotePath = 'fotos/' . $request->ci . '/' . $fileName;

                    $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($fileBinary, $remotePath, $mime);
                    if ($supabaseUrl) {
                        $estudiante->foto_4x4_url = $supabaseUrl;
                    } else {
                        $path = $file->store('fotos/' . $request->ci, 'public');
                        $estudiante->foto_4x4_url = '/storage/' . $path;
                    }
                    $estudiante->save();
                }

                $filesToProcess = [
                    'carnet' => 'FOTOCOPIA CI',
                    'titulo' => 'TITULO DE BACHILLER',
                    'nacimiento' => 'CERTIFICADO DE NACIMIENTO',
                    'deposito' => 'COMPROBANTE DE PAGO',
                    'credencialEmi' => 'CREDENCIAL EMI',
                    'carnetCossmil' => 'CARNET COSSMIL',
                    'carnetCossmilDoc' => 'CARNET COSSMIL',
                    'carnetMilitarDoc' => 'CARNET MILITAR'
                ];

                foreach ($filesToProcess as $fileKey => $docTypeName) {
                    if ($request->hasFile($fileKey)) {
                        $file = $request->file($fileKey);
                        $mime = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/pdf';
                        $fileBinary = file_get_contents($file->getRealPath());
                        $ext = strtolower($file->getClientOriginalExtension()) ?: 'pdf';
                        $fileName = time() . '_' . \Illuminate\Support\Str::random(12) . '.' . $ext;
                        $remotePath = 'documentos/estudiantes/' . $estudiante->id_estudiante . '/' . $fileName;

                        $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($fileBinary, $remotePath, $mime);
                        $finalPath = $supabaseUrl ?: ('/storage/documentos/' . $file->storeAs('estudiantes/' . $estudiante->id_estudiante, $fileName, 'documentos'));

                        \App\Models\Documento::create([
                            'id_estudiante' => $estudiante->id_estudiante,
                            'tipo_documento' => $docTypeName,
                            'nombre_archivo' => $fileName,
                            'ruta_archivo' => $finalPath
                        ]);
                    }
                }
            } catch (\Throwable $fileEx) {
                \Log::warning("Aviso archivo inscripción: " . $fileEx->getMessage());
            }

            // 9. Guardar información de padres/tutores en la tabla 'responsables'
            if ($request->filled('nombrePadres')) {
                $nombreCompleto = trim($request->nombrePadres);
                $parts = explode(' ', $nombreCompleto);
                
                $nombres_resp = '';
                $paterno_resp = '';
                $materno_resp = '';
                
                if (count($parts) == 1) {
                    $nombres_resp = $parts[0];
                } elseif (count($parts) == 2) {
                    $nombres_resp = $parts[0];
                    $paterno_resp = $parts[1];
                } elseif (count($parts) == 3) {
                    $nombres_resp = $parts[0];
                    $paterno_resp = $parts[1];
                    $materno_resp = $parts[2];
                } else {
                    $materno_resp = array_pop($parts);
                    $paterno_resp = array_pop($parts);
                    $nombres_resp = implode(' ', $parts);
                }

                $ciTutor = ($request->filled('ciTutor') && trim($request->ciTutor) !== '') ? trim($request->ciTutor) : null;
                $id_responsable = null;

                if ($ciTutor) {
                    $responsable = \DB::table('responsables')->where('ci_responsable', $ciTutor)->first();
                    if ($responsable) {
                        $id_responsable = $responsable->id_responsable;
                        \DB::table('responsables')->where('id_responsable', $id_responsable)->update([
                            'nombres_responsable' => $nombres_resp,
                            'apellido_paterno_responsable' => $paterno_resp,
                            'apellido_materno_responsable' => $materno_resp,
                            'updated_at' => now()
                        ]);
                    }
                }

                if (!$id_responsable) {
                    $id_responsable = \DB::table('responsables')->insertGetId([
                        'nombres_responsable' => $nombres_resp,
                        'apellido_paterno_responsable' => $paterno_resp,
                        'apellido_materno_responsable' => $materno_resp,
                        'ci_responsable' => $ciTutor,
                        'celular_responsable' => '',
                        'direccion_responsable' => '',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                \DB::table('estudiante_responsable')->updateOrInsert(
                    ['id_estudiante' => $estudiante->id_estudiante, 'id_responsable' => $id_responsable],
                    ['parentesco' => 'Padre/Madre/Tutor', 'updated_at' => now()]
                );
            }

            // 10. Guardar información de contactos de emergencia
            if ($request->filled('contactoEmergencia')) {
                $contactoStr = trim($request->contactoEmergencia);
                $telefono = '';
                $nombre_cont = $contactoStr;
                
                if (preg_match('/(\+?\d[\d\s-]{6,12})/', $contactoStr, $matches)) {
                    $telefono = trim($matches[1]);
                    $nombre_cont = trim(str_replace($telefono, '', $contactoStr));
                    $nombre_cont = trim($nombre_cont, " \t\n\r\0\x0B-:,");
                }
                
                if (empty($nombre_cont)) {
                    $nombre_cont = $contactoStr;
                }

                \DB::table('contactos_emergencia')->updateOrInsert(
                    ['id_estudiante' => $estudiante->id_estudiante, 'es_principal' => 1],
                    [
                        'nombre_contacto' => $nombre_cont,
                        'telefono' => $telefono,
                        'relacion' => 'Familiar',
                        'updated_at' => now()
                    ]
                );
            }

            // 11. Normalizar y resolver Curso mediante catálogos (3NF)
            $idiomaNombre = $request->idioma ?: 'Inglés';
            $idioma = \App\Models\Idioma::firstOrCreate(['nombre_idioma' => $idiomaNombre]);

            $nivelNombre = $request->nivel ?: 'NIVEL I (BOOK 1-6)';
            $nivel = \App\Models\Nivel::firstOrCreate(['nombre_nivel' => $nivelNombre]);

            $modalidadNombre = !empty($request->tipoCurso) ? ucfirst(strtolower($request->tipoCurso)) : 'Presencial';
            $modalidad = \App\Models\Modalidad::firstOrCreate(['nombre_modalidad' => $modalidadNombre]);

            $curso = \App\Models\Curso::firstOrCreate([
                'id_idioma' => $idioma->id_idioma,
                'id_nivel' => $nivel->id_nivel,
                'id_modalidad' => $modalidad->id_modalidad,
            ], [
                'cupo_minimo' => 5,
                'cupo_maximo' => 30,
                'estado' => 'Activo'
            ]);

            // 12. Crear o actualizar Inscripción vinculando al estudiante y curso
            $inscripcion = Inscripcion::where('id_estudiante', $estudiante->id_estudiante)
                ->where('id_curso', $curso->id_curso)
                ->where('estado', 'pendiente')
                ->first();

            if (!$inscripcion) {
                $inscripcion = Inscripcion::create([
                    'id_estudiante' => $estudiante->id_estudiante,
                    'id_curso' => $curso->id_curso,
                    'id_paralelo' => null,
                    'fecha_registro' => now()->format('Y-m-d'),
                    'estado' => 'pendiente'
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Inscripción registrada con éxito',
                'id' => $inscripcion->id_inscripcion,
                'id_inscripcion' => $inscripcion->id_inscripcion
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error en Inscripción: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            
            return response()->json([
                'message' => 'Error crítico en el servidor',
                'detalle' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function index()
    {
        $inscripciones = Inscripcion::with([
            'estudiante.user',
            'estudiante.gradoRel',
            'estudiante.armaRel',
            'curso.idioma',
            'paralelo'
        ])->get()->map(function($ins) {
            return [
                'id_inscripcion' => $ins->id_inscripcion,
                'id' => $ins->id_inscripcion, // fallback
                'estudiante_id' => $ins->id_estudiante,
                'curso_id' => $ins->id_curso,
                'paralelo_id' => $ins->id_paralelo,
                'fecha_registro' => $ins->fecha_registro,
                'estado' => $ins->estado,
                'estudiante' => $ins->estudiante ? [
                    'id' => $ins->estudiante->id_estudiante,
                    'id_estudiante' => $ins->estudiante->id_estudiante,
                    'nombres' => $ins->estudiante->user->nombres ?? '',
                    'apellidos' => $ins->estudiante->user->apellidos ?? '',
                    'ci' => $ins->estudiante->user->ci ?? '',
                    'correo_electronico' => $ins->estudiante->user->correo_institucional ?? $ins->estudiante->user->correo_electronico ?? $ins->estudiante->correo_electronico ?? '',
                    'celular' => $ins->estudiante->celular,
                    'fecha_nacimiento' => $ins->estudiante->fecha_nacimiento,
                    'lugar_nacimiento' => $ins->estudiante->lugar_nacimiento,
                    'anio_egreso_bachiller' => $ins->estudiante->anio_egreso_bachiller,
                    'estado_civil' => $ins->estudiante->estado_civil,
                    'grupo_sanguineo' => $ins->estudiante->grupo_sanguineo,
                    'domicilio' => $ins->estudiante->domicilio,
                    'carnet_militar' => $ins->estudiante->carnet_militar,
                    'carnet_cossmil' => $ins->estudiante->carnet_cossmil,
                    'nombre_padres' => $ins->estudiante->nombre_padres,
                    'ci_tutor' => $ins->estudiante->ci_tutor,
                    'hermanos_inscritos' => $ins->estudiante->hermanos_inscritos,
                    'contacto_emergencia' => $ins->estudiante->contacto_emergencia,
                    'foto_4x4_url' => $ins->estudiante->foto_4x4_url,
                    'grado_academico' => $ins->estudiante->grado_academico ?? '',
                    'arma_especialidad' => $ins->estudiante->arma_especialidad ?? '',
                ] : null,
                'curso' => $ins->curso ? [
                    'id' => $ins->curso->id_curso,
                    'id_curso' => $ins->curso->id_curso,
                    'idioma' => $ins->curso->idioma->nombre_idioma ?? '',
                    'nivel' => $ins->curso->nivel ?? '',
                    'modalidad' => $ins->curso->modalidad ?? '',
                ] : null,
                'paralelo' => $ins->paralelo ? [
                    'id' => $ins->paralelo->id_paralelo,
                    'id_paralelo' => $ins->paralelo->id_paralelo,
                    'nombre' => $ins->paralelo->nombre_paralelo,
                ] : null,
            ];
        });

        return response()->json($inscripciones);
    }

    /**
     * Actualizar una inscripción específica
     */
    public function update(Request $request, $id)
    {
        try {
            $inscripcion = Inscripcion::findOrFail($id);
            
            $cursoId = $request->input('curso_id', $request->input('id_curso'));
            $paraleloId = $request->input('paralelo_id', $request->input('id_paralelo'));
            $estado = $request->input('estado');

            if ($paraleloId === '' || $paraleloId === 0 || $paraleloId === '0') {
                $paraleloId = null;
            }

            if ($estado) {
                $estadoLower = strtolower(trim($estado));
                if ($estadoLower === 'habilitado') {
                    $estadoLower = 'activo';
                }
                $inscripcion->estado = $estadoLower;
            }

            if ($cursoId) {
                $inscripcion->id_curso = $cursoId;
            }

            if ($request->has('paralelo_id') || $request->has('id_paralelo')) {
                $inscripcion->id_paralelo = $paraleloId;
            }

            if ($request->has('fecha_registro')) {
                $inscripcion->fecha_registro = $request->input('fecha_registro');
            }

            $inscripcion->save();

            return response()->json([
                'message' => 'Inscripción actualizada con éxito',
                'inscripcion' => [
                    'id_inscripcion' => $inscripcion->id_inscripcion,
                    'id' => $inscripcion->id_inscripcion,
                    'estado' => $inscripcion->estado,
                    'paralelo_id' => $inscripcion->id_paralelo,
                    'curso_id' => $inscripcion->id_curso
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una inscripción
     */
    public function destroy($id)
    {
        try {
            $inscripcion = Inscripcion::findOrFail($id);

            // Verificar si tiene notas asociadas
            if ($inscripcion->notes ?? $inscripcion->notas()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar la inscripción porque tiene notas asociadas.'
                ], 422);
            }

            $inscripcion->delete();

            return response()->json([
                'message' => 'Inscripción eliminada correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la inscripción.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignación directa de curso a un estudiante existente (desde panel admin)
     */
    public function adminAssign(Request $request)
    {
        $request->validate([
            'id_estudiante' => 'required|exists:estudiantes,id_estudiante',
            'id_curso' => 'required|exists:cursos,id_curso',
            'id_paralelo' => 'nullable|exists:paralelos,id_paralelo',
            'estado' => 'nullable|string',
        ]);

        try {
            $inscripcion = Inscripcion::create([
                'id_estudiante' => $request->id_estudiante,
                'id_curso' => $request->id_curso,
                'id_paralelo' => $request->id_paralelo,
                'estado' => $request->estado ?? 'activo',
                'fecha_registro' => $request->fecha_registro ?? now()->toDateString(),
            ]);

            return response()->json([
                'message' => 'Curso asignado al estudiante correctamente.',
                'inscripcion' => $inscripcion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar curso al estudiante.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inspecciona rigurosamente la seguridad de un archivo subido:
     * - Restricción de extensiones permitidas.
     * - Validación de firmas binarias reales (Magic Bytes).
     * - Detección de exploits y scripts maliciosos en PDFs (/JavaScript, /Launch, /EmbeddedFiles, PHP, etc.).
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param bool $isPhotoOnly
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateSecureFile($file, bool $isPhotoOnly = false)
    {
        if (!$file || !$file->isValid()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'archivo' => 'El archivo subido no es válido o está dañado.'
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = $isPhotoOnly ? ['jpg', 'jpeg', 'png', 'webp'] : ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowedExtensions)) {
            $msg = $isPhotoOnly
                ? "La Fotografía Personal 4x4 debe ser una imagen (JPG, PNG o WEBP), no se permite formato .{$extension}."
                : "Extensión no permitida (.{$extension}). Solo se admiten documentos PDF o imágenes (JPG, PNG).";
            throw \Illuminate\Validation\ValidationException::withMessages(['archivo' => $msg]);
        }

        // Inspección binaria de Magic Bytes
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        if ($isPhotoOnly) {
            $isJpg = str_starts_with($header, "\xFF\xD8\xFF");
            $isPng = str_starts_with($header, "\x89PNG");
            $isWebp = str_starts_with($header, "RIFF") && strpos($header, "WEBP") !== false;
            if (!$isJpg && !$isPng && !$isWebp) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'foto' => 'La fotografía 4x4 no corresponde a una imagen auténtica (cabecera binaria corrupta o formato falso).'
                ]);
            }
        } elseif ($extension === 'pdf') {
            if (!str_starts_with($header, "%PDF-")) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'archivo' => 'El documento no es un PDF auténtico (cabecera binaria inválida).'
                ]);
            }

            // Análisis de seguridad de PDF (Detección de exploits y scripts incrustados)
            $content = file_get_contents($file->getRealPath(), false, null, 0, 4 * 1024 * 1024);
            $maliciousPatterns = [
                '/\/JavaScript/i' => 'scripts ejecutables (/JavaScript)',
                '/\/JS\s*[\(\[<]/i' => 'scripts embebidos (/JS)',
                '/\/Launch/i' => 'comandos del sistema (/Launch)',
                '/\/EmbeddedFiles/i' => 'archivos ejecutables incrustados (/EmbeddedFiles)',
                '/<\?php/i' => 'código PHP incrustado',
                '/<\?=/i' => 'código PHP abreviado',
                '/<script/i' => 'etiquetas de script web (<script)',
                '/eval\s*\(/i' => 'ejecución dinámica eval()'
            ];

            foreach ($maliciousPatterns as $pattern => $description) {
                if (preg_match($pattern, $content)) {
                    \Log::warning("Bloqueo de PDF malicioso detectado ({$description}) en archivo: " . $file->getClientOriginalName());
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'archivo' => "¡Alerta de Seguridad! El archivo PDF contiene {$description}. Por seguridad de la Escuela de Idiomas del Ejército (EIE), este archivo ha sido bloqueado."
                    ]);
                }
            }
        } else {
            // Documento en formato imagen
            $isJpg = str_starts_with($header, "\xFF\xD8\xFF");
            $isPng = str_starts_with($header, "\x89PNG");
            $isWebp = str_starts_with($header, "RIFF") && strpos($header, "WEBP") !== false;
            if (!$isJpg && !$isPng && !$isWebp) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'archivo' => 'El archivo no corresponde a un documento de imagen válido o está corrupto.'
                ]);
            }
        }
    }
}
