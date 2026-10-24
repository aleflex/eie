<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Obtiene una lista de todos los estudiantes registrados en la base de datos.
     * Incluye información de su cuenta de usuario y sus inscripciones.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo.curso', 'inscripciones.notas', 'gradoRel', 'armaRel'])->get());
    }

    /**
     * Busca estudiantes usando su Carnet de Identidad (CI) o su Nombre/Apellido.
     * 
     * @param Request $request Contiene los parámetros de búsqueda ('ci' o 'nombre').
     * @return \Illuminate\Http\JsonResponse Resultados de la búsqueda.
     */
    public function search(Request $request)
    {
        $query = Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo.curso', 'inscripciones.notas', 'gradoRel', 'armaRel']);

        if ($request->has('ci') && !empty($request->ci)) {
            $ci = trim($request->ci);
            $query->whereHas('user', function($qu) use ($ci) {
                $qu->where('ci', 'LIKE', '%' . $ci . '%');
            });
        }

        if ($request->has('nombre') && !empty($request->nombre)) {
            $nombre = trim($request->nombre);
            $query->whereHas('user', function($qu) use ($nombre) {
                $qu->where('nombres', 'LIKE', $nombre . '%')
                  ->orWhere('nombres', 'LIKE', '% ' . $nombre . '%')
                  ->orWhere('apellidos', 'LIKE', $nombre . '%')
                  ->orWhere('apellidos', 'LIKE', '% ' . $nombre . '%');
            });
        }

        return response()->json($query->get());
    }

    /**
     * Devuelve el historial académico completo de un estudiante específico.
     * Esto incluye todos los cursos en los que se ha inscrito y las notas obtenidas.
     * 
     * @param int $id El ID del estudiante.
     * @return \Illuminate\Http\JsonResponse
     */
    public function history($id)
    {
        $estudiante = Estudiante::with([
            'user',
            'inscripciones.curso.idioma',
            'inscripciones.curso.nivelRel',
            'inscripciones.paralelo.curso',
            'inscripciones.paralelo.docentes.user',
            'inscripciones.notas'
        ])->find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $user = $estudiante->user;
        $historial = $estudiante->inscripciones->map(function ($ins) {
            $notas = $ins->notas ?? collect();
            $avg = $notas->count() > 0 ? round($notas->avg('nota'), 1) : null;
            $ins->promedio = $avg;
            $ins->rendimiento = $avg !== null ? ($avg >= 71 ? 'Aprobado' : 'Reprobado') : 'Sin Notas';
            return $ins;
        });

        return response()->json([
            'estudiante' => [
                'id_estudiante' => $estudiante->id_estudiante,
                'nombres' => $user->nombres ?? ($estudiante->nombres ?? ''),
                'apellidos' => $user->apellidos ?? ($estudiante->apellidos ?? ''),
                'ci' => $user->ci ?? ($estudiante->ci ?? ''),
                'grado' => $estudiante->grado_academico ?: (is_object($estudiante->grado ?? null) ? ($estudiante->grado->nombre ?? 'Civil') : 'Civil')
            ],
            'historial' => $historial
        ]);
    }

    /**
     * Obtiene todos los detalles de un único estudiante usando su ID.
     * 
     * @param int $id El ID del estudiante.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $estudiante = Estudiante::with([
            'user', 
            'inscripciones.curso.idioma', 
            'inscripciones.curso.nivelRel', 
            'inscripciones.curso.modalidadRel',
            'inscripciones.paralelo.horarios', 
            'inscripciones.paralelo.curso', 
            'inscripciones.notas', 
            'gradoRel', 
            'armaRel'
        ])->find($id);
        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }
        return response()->json($estudiante);
    }

    /**
     * Actualiza la información personal de un estudiante existente.
     * También permite actualizar su foto 4x4 si se envía un nuevo archivo.
     * 
     * @param Request $request Los nuevos datos del estudiante.
     * @param int $id El ID del estudiante a actualizar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $estudiante = Estudiante::with('user')->find($id);
            if (!$estudiante) {
                return response()->json(['message' => 'Estudiante no encontrado'], 404);
            }

            $data = $request->all();

            // 1. Actualizar usuario vinculado (nombres, apellidos, ci, correo_electronico, etc.)
            if ($estudiante->user) {
                $userData = [];
                if ($request->has('nombres')) {
                    $userData['nombres'] = trim((string)$request->input('nombres'));
                }
                if ($request->has('apellidos')) {
                    $userData['apellidos'] = trim((string)$request->input('apellidos'));
                }
                if ($request->has('ci')) {
                    $cleanCi = preg_replace('/[^0-9]/', '', (string)$request->input('ci')) ?: trim((string)$request->input('ci'));
                    if (!empty($cleanCi)) {
                        $ciExists = \App\Models\User::where('ci', $cleanCi)
                            ->where('id_usuario', '!=', $estudiante->user->id_usuario)
                            ->exists();
                        if ($ciExists) {
                            return response()->json(['message' => "La cédula de identidad {$cleanCi} ya se encuentra registrada para otro usuario."], 422);
                        }
                        $userData['ci'] = $cleanCi;
                    }
                }
                if ($request->has('expedido')) {
                    $userData['expedido'] = $request->input('expedido') ? trim((string)$request->input('expedido')) : null;
                    $estudiante->expedido = $userData['expedido'];
                }
                if ($request->has('correo_electronico')) {
                    $cleanEmail = trim((string)$request->input('correo_electronico'));
                    if (!empty($cleanEmail)) {
                        $emailExists = \App\Models\User::where('correo_institucional', $cleanEmail)
                            ->where('id_usuario', '!=', $estudiante->user->id_usuario)
                            ->exists();
                        if ($emailExists) {
                            return response()->json(['message' => "El correo institucional {$cleanEmail} ya se encuentra registrado para otro usuario."], 422);
                        }
                        $userData['correo_institucional'] = $cleanEmail;
                    }
                }
                if ($request->has('celular')) {
                    $userData['celular'] = $request->input('celular');
                }
                if ($request->has('domicilio')) {
                    $userData['direccion'] = $request->input('domicilio');
                }
                if ($request->has('password') && !empty($request->password)) {
                    $userData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
                }
                
                if (!empty($userData)) {
                    $estudiante->user->update($userData);
                }
            }

            // 2. Si hay un archivo o cadena de foto, guardarlo permanentemente en Supabase Storage
            $ci = $request->input('ci', $estudiante->ci ?? 'foto');
            $photoUrl = null;
            $photoFile = $request->file('foto') ?? $request->file('foto_url') ?? $request->file('foto_4x4_url');

            if ($photoFile) {
                $mime = $photoFile->getClientMimeType() ?: 'image/jpeg';
                $fileBinary = file_get_contents($photoFile->getRealPath());
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $photoFile->getClientOriginalName());
                $remotePath = 'fotos/' . $ci . '/' . $fileName;

                // Intentar subir a Supabase Storage con timeout amplio
                $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($fileBinary, $remotePath, $mime);

                if ($supabaseUrl) {
                    $photoUrl = $supabaseUrl;
                } else {
                    $path = $photoFile->store('fotos/' . $ci, 'public');
                    $photoUrl = '/storage/' . $path;
                }
            } else {
                $candidatePhoto = $request->input('foto') ?? $request->input('foto_url') ?? $request->input('foto_4x4_url');
                if (is_string($candidatePhoto) && !empty($candidatePhoto)) {
                    if (str_starts_with($candidatePhoto, 'data:')) {
                        $photoUrl = $this->storeBase64Photo($candidatePhoto, 'fotos/' . $ci);
                    } elseif (str_starts_with($candidatePhoto, 'http') || str_starts_with($candidatePhoto, '/storage/')) {
                        $photoUrl = $candidatePhoto;
                    }
                }
            }

            if (!empty($photoUrl)) {
                $estudiante->foto_4x4_url = $photoUrl;
                $data['foto_4x4_url'] = $photoUrl;
                if ($estudiante->user) {
                    $estudiante->user->update(['foto_url' => $photoUrl]);
                }
            }

            // 3. Mapear explícitamente mutadores
            if ($request->has('grado_academico')) {
                $estudiante->grado_academico = $request->input('grado_academico');
            }
            if ($request->has('arma_especialidad')) {
                $estudiante->arma_especialidad = $request->input('arma_especialidad');
            }
            
            if ($request->has('estado_civil')) {
                $estudiante->estado_civil = $request->input('estado_civil');
            }
            if ($request->has('grupo_sanguineo')) {
                $estudiante->grupo_sanguineo = $request->input('grupo_sanguineo');
            }
            if ($request->has('nombre_padres')) {
                $estudiante->nombre_padres = $request->input('nombre_padres');
            }
            if ($request->has('ci_tutor')) {
                $ciTutor = trim((string)$request->input('ci_tutor'));
                // Permitir formato puro (7-8 dígitos) o formato antiguo (dígitos + espacio + depto)
                if ($ciTutor !== '' && !preg_match('/^[0-9]{7,8}(\s*[A-Za-z]{2})?$/', $ciTutor)) {
                    return response()->json(['message' => 'El C.I. del tutor debe contener 7 u 8 dígitos numéricos.'], 422);
                }
                $estudiante->ci_tutor = $ciTutor;
            }
            if ($request->has('contacto_emergencia')) {
                $contEmergencia = trim((string)$request->input('contacto_emergencia'));
                if ($contEmergencia !== '' && !preg_match('/^\+?[0-9\s\-]{7,25}$/', $contEmergencia)) {
                    return response()->json(['message' => 'El formato del número de contacto de emergencia es inválido.'], 422);
                }
                $estudiante->contacto_emergencia = $contEmergencia;
            }
            if ($request->has('hermanos_inscritos')) {
                $estudiante->hermanos_inscritos = $request->input('hermanos_inscritos');
            }

            // 4. Filtrar campos que no corresponden a columnas de la tabla 'estudiantes'
            $nonDbFields = [
                'nombres', 'apellidos', 'ci', 'correo_electronico',
                'estado_civil', 'grupo_sanguineo', 'grado_academico',
                'arma_especialidad', 'nombre_padres', 'ci_tutor',
                'contacto_emergencia', 'estado_inscripcion', 'curso_id',
                'paralelo_id', 'inscripcion_id', 'foto', 'user', 'inscripciones',
                'grado', 'arma', 'gradoRel', 'armaRel', 'grado_rel', 'arma_rel',
                'id', 'id_estudiante', 'id_usuario', '_method', 'expedido',
                'created_at', 'updated_at', 'deleted_at', 'foto_4x4_url'
            ];
            $fillData = array_diff_key($data, array_flip($nonDbFields));

            // Manejo de valores vacíos para columnas UNIQUE
            if ($request->has('carnet_militar')) {
                $cleanCm = trim((string)$request->input('carnet_militar'));
                $fillData['carnet_militar'] = $cleanCm !== '' ? $cleanCm : null;
                if ($fillData['carnet_militar']) {
                    $cmExists = Estudiante::where('carnet_militar', $fillData['carnet_militar'])
                        ->where('id_estudiante', '!=', $estudiante->id_estudiante)
                        ->exists();
                    if ($cmExists) {
                        return response()->json(['message' => "El carnet militar {$fillData['carnet_militar']} ya pertenece a otro estudiante."], 422);
                    }
                }
            }
            if ($request->has('carnet_cossmil')) {
                $cleanCs = strtoupper(trim((string)$request->input('carnet_cossmil')));
                $fillData['carnet_cossmil'] = $cleanCs !== '' ? $cleanCs : null;
                if ($fillData['carnet_cossmil']) {
                    $csExists = Estudiante::where('carnet_cossmil', $fillData['carnet_cossmil'])
                        ->where('id_estudiante', '!=', $estudiante->id_estudiante)
                        ->exists();
                    if ($csExists) {
                        return response()->json(['message' => "El carnet COSSMIL {$fillData['carnet_cossmil']} ya pertenece a otro estudiante."], 422);
                    }
                }
            }

            // Eliminar también cualquier valor tipo array u objeto no relacional
            foreach ($fillData as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    unset($fillData[$k]);
                }
            }

            $estudiante->fill($fillData);
            $estudiante->save();

            // 5. Actualizar estado de inscripción si viene en la petición
            if ($request->has('estado_inscripcion')) {
                $estadoInsc = $request->input('estado_inscripcion');
                $estudiante->inscripciones()->update(['estado' => strtolower(trim($estadoInsc))]);
            }

            $freshEstudiante = $estudiante->fresh(['user', 'gradoRel', 'armaRel', 'inscripciones.curso', 'inscripciones.paralelo']);

            // Emitir evento en tiempo real vía WebSockets
            \App\Services\WebSocketService::broadcast('estudiantes', 'estudiante_actualizado', $freshEstudiante);

            return response()->json([
                'message' => 'Estudiante actualizado con éxito',
                'estudiante' => $freshEstudiante
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error("DB Error actualizando estudiante {$id}: " . $e->getMessage());
            $errorInfo = $e->errorInfo[2] ?? $e->getMessage();
            if (str_contains($errorInfo, 'Duplicate entry') || $e->getCode() == 23000) {
                if (str_contains($errorInfo, 'carnet_militar')) {
                    return response()->json(['message' => 'La matrícula o carnet militar ya se encuentra registrada para otro estudiante.'], 422);
                }
                if (str_contains($errorInfo, 'carnet_cossmil')) {
                    return response()->json(['message' => 'El código de carnet COSSMIL ya se encuentra registrado para otro estudiante.'], 422);
                }
                if (str_contains($errorInfo, 'ci_responsable')) {
                    return response()->json(['message' => 'El C.I. del tutor ya se encuentra registrado.'], 422);
                }
                if (str_contains($errorInfo, 'usuarios_ci_unique')) {
                    return response()->json(['message' => 'El C.I. ingresado ya se encuentra registrado para otro usuario.'], 422);
                }
                return response()->json(['message' => 'Dato duplicado: ' . $errorInfo], 422);
            }
            return response()->json(['message' => 'Error en base de datos: ' . $errorInfo], 500);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error general actualizando estudiante {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Error al actualizar estudiante: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Da de baja lógica a un estudiante sin eliminarlo de la base de datos.
     * Si el estudiante ya tiene notas registradas, exige motivo y prueba de falta grave.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $estudiante = Estudiante::with(['user', 'inscripciones.notas'])->findOrFail($id);

            // Verificar si el estudiante tiene notas registradas
            $tieneNotas = \App\Models\Nota::whereHas('inscripcion', function($q) use ($id) {
                $q->where('id_estudiante', $id);
            })->exists();

            $motivo = $request->input('motivo_baja');
            $forzarBajaConNotas = $request->boolean('forzar_con_pruebas', false);

            if ($tieneNotas && (empty($motivo) || !$forzarBajaConNotas)) {
                return response()->json([
                    'message' => 'El estudiante tiene notas registradas en el sistema. Para proceder con la baja por infracción grave al reglamento institucional, debe especificar el motivo y adjuntar la prueba de respaldo.',
                    'tiene_notas' => true,
                    'requiere_justificacion' => true
                ], 422);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($estudiante, $request, $motivo) {
                // 1. Cambiar estado de inscripciones a 'baja'
                $estudiante->inscripciones()->update(['estado' => 'baja']);

                // 2. Desactivar cuenta de usuario para que no pueda ingresar
                if ($estudiante->user) {
                    $estudiante->user->update(['estado' => 'INACTIVO']);
                }

                // 3. Si se subió un archivo de prueba/acta de baja
                if ($request->hasFile('archivo_prueba')) {
                    $file = $request->file('archivo_prueba');
                    $fileName = 'baja_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file->getClientOriginalName());
                    $path = $file->storeAs('estudiantes/' . $estudiante->id_estudiante, $fileName, 'documentos');
                    $ruta = '/storage/documentos/' . $path;

                    \App\Models\Documento::create([
                        'id_estudiante' => $estudiante->id_estudiante,
                        'tipo_documento' => 'Acta de Baja por Falta Grave',
                        'nombre_archivo' => $fileName,
                        'ruta_archivo' => $ruta,
                        'observacion' => $motivo ?: 'Baja institucional justificada'
                    ]);
                }
            });

            $freshEstudiante = $estudiante->fresh(['user', 'inscripciones.curso', 'inscripciones.paralelo']);
            \App\Services\WebSocketService::broadcast('estudiantes', 'estudiante_actualizado', $freshEstudiante);

            return response()->json([
                'message' => 'El estudiante ha sido dado de baja exitosamente. Su registro e historial permanecen protegidos.',
                'estudiante' => $freshEstudiante
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la baja del estudiante.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rehabilita a un estudiante previamente dado de baja, reactivando su inscripción y usuario.
     */
    public function rehabilitar($id)
    {
        try {
            $estudiante = Estudiante::with(['user', 'inscripciones'])->findOrFail($id);

            \Illuminate\Support\Facades\DB::transaction(function () use ($estudiante) {
                // 1. Reactivar sus inscripciones que estaban en baja
                $estudiante->inscripciones()->whereIn('estado', ['baja', 'retirado'])->update(['estado' => 'activo']);

                // 2. Si no tenía inscripciones en baja, poner la última activa
                if ($estudiante->inscripciones()->where('estado', 'activo')->count() === 0 && $estudiante->inscripciones()->count() > 0) {
                    $estudiante->inscripciones()->latest('id_inscripcion')->first()->update(['estado' => 'activo']);
                }

                // 3. Reactivar usuario en sistema
                if ($estudiante->user) {
                    $estudiante->user->update(['estado' => 'ACTIVO']);
                }
            });

            $freshEstudiante = $estudiante->fresh(['user', 'inscripciones.curso', 'inscripciones.paralelo']);
            \App\Services\WebSocketService::broadcast('estudiantes', 'estudiante_actualizado', $freshEstudiante);

            return response()->json([
                'message' => 'El estudiante ha sido rehabilitado exitosamente.',
                'estudiante' => $freshEstudiante
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rehabilitar al estudiante.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function storeBase64Photo(string $dataUri, string $storagePath, string $prefix = 'foto'): string
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUri, $matches)) {
            return '';
        }

        $mime = $matches[1];
        $base64 = $matches[2];
        $decoded = base64_decode($base64);
        if ($decoded === false) {
            return '';
        }

        $extension = explode('/', $mime)[1] ?? 'jpg';
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = $prefix . '_' . time() . '.' . $extension;
        $relativePath = trim($storagePath, '/') . '/' . $filename;

        // Intentar guardar primero en Supabase Storage para persistencia permanente
        $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($decoded, $relativePath, $mime);
        if ($supabaseUrl) {
            return $supabaseUrl;
        }

        Storage::disk('public')->put($relativePath, $decoded);

        return '/storage/' . $relativePath;
    }
}
