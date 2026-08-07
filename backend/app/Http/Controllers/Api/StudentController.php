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
        $estudiante = Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo.curso', 'inscripciones.notas'])
            ->find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        return response()->json([
            'estudiante' => [
                'id_estudiante' => $estudiante->id_estudiante,
                'nombres' => $estudiante->nombres ?? '',
                'apellidos' => $estudiante->apellidos ?? '',
                'ci' => $estudiante->ci ?? '',
            ],
            'historial' => $estudiante->inscripciones
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
        $estudiante = Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo.curso', 'inscripciones.notas', 'gradoRel', 'armaRel'])->find($id);
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
        $estudiante = Estudiante::with('user')->find($id);
        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $data = $request->all();

        // 1. Actualizar usuario vinculado (nombres, apellidos, ci, correo_electronico, etc.)
        if ($estudiante->user) {
            $userData = [];
            if ($request->has('nombres')) {
                $userData['nombres'] = $request->input('nombres');
            }
            if ($request->has('apellidos')) {
                $userData['apellidos'] = $request->input('apellidos');
            }
            if ($request->has('ci')) {
                $userData['ci'] = $request->input('ci');
            }
            if ($request->has('expedido')) {
                $userData['expedido'] = $request->input('expedido');
                $estudiante->expedido = $request->input('expedido');
            }
            if ($request->has('correo_electronico')) {
                $userData['correo_institucional'] = $request->input('correo_electronico');
            }
            if ($request->has('password') && !empty($request->password)) {
                $userData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
            }
            
            if (!empty($userData)) {
                $estudiante->user->update($userData);
            }
        }

        // 2. Si hay un archivo de foto, guardarlo en Supabase Storage
        $ci = $request->input('ci', $estudiante->ci ?? 'foto');
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $fileBinary = file_get_contents($file->getRealPath());
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file->getClientOriginalName());
            $remotePath = 'fotos/' . $ci . '/' . $fileName;

            // Intentar subir a Supabase Storage
            $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($fileBinary, $remotePath, $mime);

            if ($supabaseUrl) {
                $photoUrl = $supabaseUrl;
            } else {
                // Respaldar foto como Data URI en MySQL para que NUNCA se borre
                $photoUrl = 'data:' . $mime . ';base64,' . base64_encode($fileBinary);
            }

            $estudiante->foto_4x4_url = $photoUrl;
            $data['foto_4x4_url'] = $photoUrl;
        } elseif ($request->has('foto') && is_string($request->input('foto')) && (str_starts_with($request->input('foto'), 'http') || str_starts_with($request->input('foto'), 'data:'))) {
            $estudiante->foto_4x4_url = $request->input('foto');
            $data['foto_4x4_url'] = $request->input('foto');
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
            $ciTutor = trim($request->input('ci_tutor'));
            // Permitir formato puro (7-8 dígitos) o formato antiguo (dígitos + espacio + depto)
            if ($ciTutor !== '' && !preg_match('/^[0-9]{7,8}(\s*[A-Za-z]{2})?$/', $ciTutor)) {
                return response()->json(['message' => 'El C.I. del tutor debe contener 7 u 8 dígitos numéricos.'], 422);
            }
            $estudiante->ci_tutor = $ciTutor;
        }
        if ($request->has('contacto_emergencia')) {
            $estudiante->contacto_emergencia = $request->input('contacto_emergencia');
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
            'grado', 'arma', 'gradoRel', 'armaRel', 'id', '_method', 'expedido'
        ];
        $fillData = array_diff_key($data, array_flip($nonDbFields));

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
            $estudiante->inscripciones()->update(['estado' => ucfirst(strtolower($estadoInsc))]);
        }

        return response()->json([
            'message' => 'Estudiante actualizado con éxito',
            'estudiante' => $estudiante->fresh(['user', 'gradoRel', 'armaRel', 'inscripciones.curso', 'inscripciones.paralelo'])
        ]);
    }

    public function destroy($id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);
            $user = $estudiante->user;

            \Illuminate\Support\Facades\DB::transaction(function () use ($estudiante, $user) {
                // Eliminar inscripciones, documentos, contactos y responsables vinculados
                $estudiante->inscripciones()->delete();
                $estudiante->documentos()->delete();
                \Illuminate\Support\Facades\DB::table('contactos_emergencia')->where('id_estudiante', $estudiante->id_estudiante)->delete();
                \Illuminate\Support\Facades\DB::table('estudiante_responsable')->where('id_estudiante', $estudiante->id_estudiante)->delete();
                
                // Eliminar estudiante
                $estudiante->delete();

                // Eliminar usuario si existe
                if ($user) {
                    $user->delete();
                }
            });

            return response()->json(['message' => 'Estudiante y usuario eliminados permanentemente con éxito.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el estudiante.',
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

        Storage::disk('public')->put($relativePath, $decoded);

        return '/storage/' . $relativePath;
    }
}
