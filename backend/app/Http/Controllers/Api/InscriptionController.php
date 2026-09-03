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
            'nombres' => 'required|string|min:2|max:255',
            'apellidos' => 'required|string|max:255',
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
        ], [
            'nombres.min' => 'El nombre debe tener al menos 2 caracteres.',
            'ci.min' => 'El carnet de identidad debe tener al menos 5 caracteres.',
            'lugarNacimiento.min' => 'El lugar de nacimiento debe tener al menos 2 caracteres.',
        ]);

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
            if ($request->filled('estadoCivil') && trim($request->estadoCivil) !== '') {
                $nombreEstado = trim($request->estadoCivil);
                $ec = \DB::table('estados_civil')->where('nombre_estado_civil', $nombreEstado)->first();
                if (!$ec) {
                    $ec = \DB::table('estados_civil')->where('nombre_estado_civil', 'LIKE', '%' . strtok($nombreEstado, '/') . '%')->first();
                }
                if ($ec) {
                    $idEstadoCivil = $ec->id_estado_civil;
                } else {
                    $idEstadoCivil = \DB::table('estados_civil')->insertGetId([
                        'nombre_estado_civil' => $nombreEstado,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // 5. Resolver grupo sanguíneo mediante catálogo 'grupos_sanguineo' (columna: nombre_grupo_sanguineo)
            $idGrupoSanguineo = null;
            if ($request->filled('grupoSanguineo') && trim($request->grupoSanguineo) !== '') {
                $nombreGrupo = trim($request->grupoSanguineo);
                if (preg_match('/^([ABO\+\-]+)/i', $nombreGrupo, $m)) {
                    $cleanGrupo = trim($m[1]);
                } else {
                    $cleanGrupo = $nombreGrupo;
                }
                $gs = \DB::table('grupos_sanguineo')->where('nombre_grupo_sanguineo', $cleanGrupo)->first();
                if (!$gs) {
                    $gs = \DB::table('grupos_sanguineo')->where('nombre_grupo_sanguineo', $nombreGrupo)->first();
                }
                if ($gs) {
                    $idGrupoSanguineo = $gs->id_grupo_sanguineo;
                } else {
                    $idGrupoSanguineo = \DB::table('grupos_sanguineo')->insertGetId([
                        'nombre_grupo_sanguineo' => $cleanGrupo,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // 6. Normalizar carnet militar y cossmil (NULL para evitar colisiones UNIQUE en la BD)
            $carnetMilitar = ($request->filled('carnetMilitar') && trim($request->carnetMilitar) !== '') ? trim($request->carnetMilitar) : null;
            $carnetCossmil = ($request->filled('carnetCossmil') && trim($request->carnetCossmil) !== '') ? trim($request->carnetCossmil) : null;

            // 7. Buscar o crear perfil Estudiante
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
                'id_estado_civil' => $idEstadoCivil,
                'id_grupo_sanguineo' => $idGrupoSanguineo,
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
                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file->getClientOriginalName());
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
                    'deposito' => 'COMPROBANTE DE PAGO'
                ];

                if ($request->userType === 'emi') {
                    $filesToProcess['credencialEmi'] = 'CREDENCIAL EMI';
                }

                foreach ($filesToProcess as $fileKey => $docTypeName) {
                    if ($request->hasFile($fileKey)) {
                        $file = $request->file($fileKey);
                        $mime = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/pdf';
                        $fileBinary = file_get_contents($file->getRealPath());
                        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file->getClientOriginalName());
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
                    'correo_electronico' => $ins->estudiante->user->correo_electronico ?? '',
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
}
