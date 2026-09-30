<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use Illuminate\Http\Request;

class NotaAsistenciaController extends Controller
{
    // ==================== NOTAS ====================

    /**
     * Obtener todas las notas de una inscripción
     */
    public function getNotas($inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $notas = Nota::where('id_inscripcion', $inscripcionId)->get();
        
        $response = $notas->map(function($nota) {
            return [
                'id_nota' => $nota->id_nota,
                'id' => $nota->id_nota,
                'id_inscripcion' => $nota->id_inscripcion,
                'inscripcion_id' => $nota->id_inscripcion,
                'nota' => $nota->nota,
                'periodo' => $nota->periodo,
                'observacion' => $nota->observacion,
                'created_at' => $nota->created_at,
                'updated_at' => $nota->updated_at
            ];
        });
        
        return response()->json($response);
    }

    /**
     * Obtener el usuario autenticado (vía Sanctum, sesión o parámetro)
     */
    protected function getAuthenticatedUser(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }
        if (!$user && $request->filled('user_id')) {
            $user = \App\Models\User::find($request->input('user_id'));
        }
        return $user;
    }

    /**
     * Verificar si el usuario tiene rol de Administrador
     */
    protected function isUserAdmin($user): bool
    {
        if (!$user) return false;
        if (isset($user->id_rol) && (int)$user->id_rol === 1) return true;
        if (isset($user->tipo_usuario) && strtolower($user->tipo_usuario) === 'admin') return true;
        if (!$user->docente && !$user->estudiante) return true;
        return false;
    }

    /**
     * Verifica si las credenciales provistas corresponden a un Administrador activo.
     * Retorna el modelo User del Administrador que autorizó, o null.
     */
    protected function verifyAdminAuthorization(Request $request): ?\App\Models\User
    {
        $adminPassword = $request->input('admin_password');
        if (empty($adminPassword)) {
            return null;
        }

        $adminUser = trim($request->input('admin_user', ''));

        // Si se especificó el usuario o correo del administrador
        if (!empty($adminUser)) {
            $candidate = \App\Models\User::where(function($q) use ($adminUser) {
                $q->where('usuario', strtolower($adminUser))
                  ->orWhere('correo_institucional', strtolower($adminUser));
            })->first();

            if ($candidate && $this->isUserAdmin($candidate) && \Illuminate\Support\Facades\Hash::check($adminPassword, $candidate->password)) {
                return $candidate;
            }
            return null;
        }

        // Si solo se ingresó la contraseña del Administrador, verificar contra administradores del sistema
        $admins = \App\Models\User::where(function($q) {
            $q->where('id_rol', 1)
              ->orWhere(function($sub) {
                  $sub->whereDoesntHave('docente')->whereDoesntHave('estudiante');
              });
        })->get();

        foreach ($admins as $candidate) {
            if (\Illuminate\Support\Facades\Hash::check($adminPassword, $candidate->password)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Endpoint API para validar la autorización del Administrador en tiempo real
     */
    public function autorizarModificacion(Request $request)
    {
        $request->validate([
            'admin_password' => 'required|string',
            'justificativo'  => 'required|string|min:5|max:500',
            'admin_user'     => 'nullable|string',
            'tipo'           => 'nullable|string'
        ]);

        $admin = $this->verifyAdminAuthorization($request);

        if (!$admin) {
            return response()->json([
                'message' => 'Contraseña o credenciales de Administrador incorrectas. Solo un Administrador autorizado puede dar permiso para modificar.'
            ], 403);
        }

        return response()->json([
            'success'    => true,
            'message'    => "Autorización concedida por el Administrador {$admin->name}.",
            'admin_name' => $admin->name,
            'admin_id'   => $admin->id_usuario
        ]);
    }

    /**
     * Obtiene el estado de autorización académica (notas y asistencias) de un paralelo
     */
    public function getAutorizacionParalelo($paraleloId)
    {
        $claveNotas = "permiso_notas_paralelo_{$paraleloId}";
        $claveAsist = "permiso_asist_paralelo_{$paraleloId}";

        $confNotas = \App\Models\Configuracion::where('clave', $claveNotas)->first();
        $confAsist = \App\Models\Configuracion::where('clave', $claveAsist)->first();

        $dataNotas = $confNotas ? json_decode($confNotas->valor, true) : null;
        $dataAsist = $confAsist ? json_decode($confAsist->valor, true) : null;

        return response()->json([
            'notas' => [
                'autorizado'    => !empty($dataNotas['autorizado']),
                'justificativo' => $dataNotas['justificativo'] ?? null,
                'admin_nombre'  => $dataNotas['admin_nombre'] ?? null,
                'fecha'         => $dataNotas['fecha'] ?? null,
            ],
            'asistencias' => [
                'autorizado'    => !empty($dataAsist['autorizado']),
                'justificativo' => $dataAsist['justificativo'] ?? null,
                'admin_nombre'  => $dataAsist['admin_nombre'] ?? null,
                'fecha'         => $dataAsist['fecha'] ?? null,
            ]
        ]);
    }

    /**
     * El Administrador otorga o revoca autorización al docente de un paralelo
     */
    public function setAutorizacionParalelo(Request $request, $paraleloId)
    {
        $user = $this->getAuthenticatedUser($request);
        $isAdmin = $this->isUserAdmin($user);

        if (!$isAdmin) {
            $admin = $this->verifyAdminAuthorization($request);
            if (!$admin) {
                return response()->json([
                    'message' => 'Acceso denegado: Solo un Administrador puede otorgar o revocar autorizaciones a los docentes.'
                ], 403);
            }
            $adminName = $admin->name;
        } else {
            $adminName = $user ? $user->name : 'Administrador';
        }

        $request->validate([
            'tipo'          => 'required|in:notas,asistencias',
            'accion'        => 'required|in:autorizar,revocar',
            'justificativo' => 'nullable|string|max:500'
        ]);

        $tipo = $request->input('tipo');
        $accion = $request->input('accion');
        $justificativo = trim($request->input('justificativo', ''));

        if ($accion === 'autorizar' && (empty($justificativo) || strlen($justificativo) < 5)) {
            return response()->json([
                'message' => 'Debe ingresar un justificativo o motivo formal de autorización (mínimo 5 caracteres).'
            ], 422);
        }

        $clave = $tipo === 'notas' ? "permiso_notas_paralelo_{$paraleloId}" : "permiso_asist_paralelo_{$paraleloId}";

        if ($accion === 'autorizar') {
            $valor = json_encode([
                'autorizado'    => true,
                'justificativo' => $justificativo,
                'admin_nombre'  => $adminName,
                'fecha'         => now()->format('d/m/Y H:i')
            ]);
            \App\Models\Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor, 'tipo' => 'json', 'grupo' => 'permisos_academicos']
            );
            $msg = "Autorización otorgada al Docente para modificar " . ($tipo === 'notas' ? 'calificaciones' : 'asistencias') . " exitosamente.";
        } else {
            $valor = json_encode([
                'autorizado'     => false,
                'fecha_revocado' => now()->format('d/m/Y H:i'),
                'admin_nombre'   => $adminName
            ]);
            \App\Models\Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor, 'tipo' => 'json', 'grupo' => 'permisos_academicos']
            );
            $msg = "Autorización para el Docente revocada exitosamente. Las " . ($tipo === 'notas' ? 'calificaciones' : 'asistencias') . " quedan bloqueadas.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    /**
     * Guardar o actualizar una nota para una inscripción
     */
    public function saveNota(Request $request, $inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);

        if (empty($inscripcion->id_paralelo)) {
            return response()->json([
                'message' => 'No se pueden registrar calificaciones a un estudiante sin paralelo asignado.'
            ], 422);
        }

        $validated = $request->validate([
            'nota'          => 'required|numeric|min:0|max:100',
            'periodo'       => 'required|string|max:50',
            'observacion'   => 'nullable|string|max:500',
            'justificativo' => 'nullable|string|max:500',
        ]);

        $user = $this->getAuthenticatedUser($request);
        $isAdmin = $this->isUserAdmin($user);

        // Verificar si la nota para este periodo ya existe previamente
        $existingNota = Nota::where('id_inscripcion', $inscripcion->id_inscripcion)
            ->where('periodo', $validated['periodo'])
            ->first();

        if ($existingNota) {
            $justificativo = trim($request->input('justificativo', ''));

            // Verificar si el paralelo ya tiene autorización activa de Dirección/Administrador
            $paraleloAutorizado = false;
            $adminName = null;
            if (!empty($inscripcion->id_paralelo)) {
                $claveNotas = "permiso_notas_paralelo_{$inscripcion->id_paralelo}";
                $confNotas = \App\Models\Configuracion::where('clave', $claveNotas)->first();
                $dataNotas = $confNotas ? json_decode($confNotas->valor, true) : null;
                if (!empty($dataNotas['autorizado'])) {
                    $paraleloAutorizado = true;
                    $adminName = $dataNotas['admin_nombre'] ?? 'Administrador';
                    if (empty($justificativo)) {
                        $justificativo = $dataNotas['justificativo'] ?? 'Autorizado formalmente por Administración';
                    }
                }
            }

            // Si el usuario no es admin y el paralelo no está pre-autorizado, verificar contraseña de admin
            if (!$isAdmin && !$paraleloAutorizado) {
                if (empty($justificativo)) {
                    return response()->json([
                        'message' => "La calificación para '{$validated['periodo']}' ya fue registrada previamente. Solo el Administrador puede autorizar al docente para modificarla."
                    ], 422);
                }

                $adminAuthorizer = $this->verifyAdminAuthorization($request);
                if (!$adminAuthorizer) {
                    return response()->json([
                        'message' => 'Acceso Denegado: Solo el Administrador puede autorizar al docente para modificar calificaciones ya asentadas.'
                    ], 403);
                }
                $adminName = $adminAuthorizer->name;
            }

            $userName = $user ? $user->name : 'Docente';
            if ($isAdmin) {
                $auditLog = "[Rectificado por Admin ({$userName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            } else {
                $adminName = $adminName ?: 'Administrador';
                $auditLog = "[Rectificado por Docente ({$userName}) con Autorización de Admin ({$adminName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            }

            $nuevaObs = !empty($validated['observacion']) 
                ? $validated['observacion'] . " | " . $auditLog 
                : $auditLog;

            $existingNota->update([
                'nota' => $validated['nota'],
                'observacion' => $nuevaObs,
            ]);

            return response()->json([
                'message' => 'Calificación rectificada por el Docente con autorización de Administrador.',
                'nota'    => [
                    'id_nota' => $existingNota->id_nota,
                    'id' => $existingNota->id_nota,
                    'inscripcion_id' => $existingNota->id_inscripcion,
                    'nota' => $existingNota->nota,
                    'periodo' => $existingNota->periodo,
                    'observacion' => $existingNota->observacion
                ]
            ]);
        }

        // Si no existe, se crea por primera vez
        $nota = Nota::create([
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'periodo'        => $validated['periodo'],
            'nota'           => $validated['nota'],
            'observacion'    => $validated['observacion'] ?? null,
        ]);

        return response()->json([
            'message' => 'Nota guardada exitosamente y bloqueada para futuras modificaciones docentes.',
            'nota'    => [
                'id_nota' => $nota->id_nota,
                'id' => $nota->id_nota,
                'inscripcion_id' => $nota->id_inscripcion,
                'nota' => $nota->nota,
                'periodo' => $nota->periodo,
                'observacion' => $nota->observacion
            ]
        ]);
    }

    /**
     * Eliminar una nota (Solo Administrador)
     */
    public function deleteNota(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        $isAdmin = $this->isUserAdmin($user);

        if (!$isAdmin) {
            return response()->json([
                'message' => 'No tiene autorización. Solo un Administrador puede eliminar calificaciones.'
            ], 403);
        }

        $nota = Nota::findOrFail($id);
        $nota->delete();
        return response()->json(['message' => 'Nota eliminada correctamente por Administración']);
    }

    // ==================== ASISTENCIAS ====================

    /**
     * Obtener asistencias de una inscripción
     */
    public function getAsistencias($inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $asistencias = $inscripcion->asistencias()->orderBy('fecha')->get()->map(function($asistencia) {
            return [
                'id_asistencia' => $asistencia->id_asistencia,
                'id' => $asistencia->id_asistencia,
                'inscripcion_id' => $asistencia->id_inscripcion,
                'fecha' => $asistencia->fecha,
                'estado' => $asistencia->estado,
                'observacion' => $asistencia->observacion,
                'created_at' => $asistencia->created_at,
                'updated_at' => $asistencia->updated_at
            ];
        });
        return response()->json($asistencias);
    }

    /**
     * Registrar o actualizar asistencia de una fecha
     */
    public function saveAsistencia(Request $request, $inscripcionId)
    {
        $inscripcion = Inscripcion::findOrFail($inscripcionId);

        $validated = $request->validate([
            'fecha'         => 'required|date',
            'estado'        => 'required|in:presente,ausente,tardanza,justificado',
            'observacion'   => 'nullable|string|max:500',
            'justificativo' => 'nullable|string|max:500',
        ]);

        $user = $this->getAuthenticatedUser($request);
        $isAdmin = $this->isUserAdmin($user);

        $fechaRegistro = \Carbon\Carbon::parse($validated['fecha'])->startOfDay();
        $hoy = now()->startOfDay();

        // Verificar si ya existe un registro de asistencia para esta fecha
        $existingAsistencia = Asistencia::where('id_inscripcion', $inscripcion->id_inscripcion)
            ->where('fecha', $validated['fecha'])
            ->first();

        if ($existingAsistencia) {
            $justificativo = trim($request->input('justificativo', ''));

            // Verificar si el paralelo ya tiene autorización activa para asistencias
            $paraleloAutorizado = false;
            $adminName = null;
            if (!empty($inscripcion->id_paralelo)) {
                $claveAsist = "permiso_asist_paralelo_{$inscripcion->id_paralelo}";
                $confAsist = \App\Models\Configuracion::where('clave', $claveAsist)->first();
                $dataAsist = $confAsist ? json_decode($confAsist->valor, true) : null;
                if (!empty($dataAsist['autorizado'])) {
                    $paraleloAutorizado = true;
                    $adminName = $dataAsist['admin_nombre'] ?? 'Administrador';
                    if (empty($justificativo)) {
                        $justificativo = $dataAsist['justificativo'] ?? 'Autorizado formalmente por Administración';
                    }
                }
            }

            if (!$isAdmin && !$paraleloAutorizado) {
                if (empty($justificativo)) {
                    return response()->json([
                        'message' => "La asistencia del {$validated['fecha']} ya fue asentada. Solo el Administrador puede autorizar al docente para modificarla."
                    ], 422);
                }

                $adminAuthorizer = $this->verifyAdminAuthorization($request);
                if (!$adminAuthorizer) {
                    return response()->json([
                        'message' => 'Acceso Denegado: Solo el Administrador puede autorizar al docente para modificar asistencias ya asentadas.'
                    ], 403);
                }
                $adminName = $adminAuthorizer->name;
            }

            $userName = $user ? $user->name : 'Docente';
            if ($isAdmin) {
                $auditLog = "[Rectificado por Admin ({$userName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            } else {
                $adminName = $adminName ?: 'Administrador';
                $auditLog = "[Rectificado por Docente ({$userName}) con Autorización de Admin ({$adminName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            }

            $nuevaObs = !empty($validated['observacion']) 
                ? $validated['observacion'] . " | " . $auditLog 
                : $auditLog;

            $existingAsistencia->update([
                'estado' => $validated['estado'],
                'observacion' => $nuevaObs,
            ]);

            return response()->json([
                'message'    => 'Asistencia rectificada por el Docente con autorización de Administrador.',
                'asistencia' => [
                    'id_asistencia' => $existingAsistencia->id_asistencia,
                    'id' => $existingAsistencia->id_asistencia,
                    'inscripcion_id' => $existingAsistencia->id_inscripcion,
                    'fecha' => $existingAsistencia->fecha,
                    'estado' => $existingAsistencia->estado,
                    'observacion' => $existingAsistencia->observacion
                ]
            ]);
        }

        // Si no existe pero es fecha pasada, requerir justificativo y autorización de admin
        $justificativo = trim($request->input('justificativo', ''));
        if ($fechaRegistro->lessThan($hoy)) {
            // Verificar si el paralelo ya tiene autorización activa
            $paraleloAutorizado = false;
            $adminName = null;
            if (!empty($inscripcion->id_paralelo)) {
                $claveAsist = "permiso_asist_paralelo_{$inscripcion->id_paralelo}";
                $confAsist = \App\Models\Configuracion::where('clave', $claveAsist)->first();
                $dataAsist = $confAsist ? json_decode($confAsist->valor, true) : null;
                if (!empty($dataAsist['autorizado'])) {
                    $paraleloAutorizado = true;
                    $adminName = $dataAsist['admin_nombre'] ?? 'Administrador';
                    if (empty($justificativo)) {
                        $justificativo = $dataAsist['justificativo'] ?? 'Registro extemporáneo autorizado por Administración';
                    }
                }
            }

            if (!$isAdmin && !$paraleloAutorizado) {
                if (empty($justificativo)) {
                    return response()->json([
                        'message' => 'Para registrar asistencia extemporánea de una fecha pasada, se requiere obligatoriamente el justificativo y autorización del Administrador.'
                    ], 422);
                }

                $adminAuthorizer = $this->verifyAdminAuthorization($request);
                if (!$adminAuthorizer) {
                    return response()->json([
                        'message' => 'Acceso Denegado: Solo el Administrador puede autorizar el registro de asistencia en fechas pasadas.'
                    ], 403);
                }
                $adminName = $adminAuthorizer->name;
            }
        }

        $obsFinal = $validated['observacion'] ?? null;
        if (!empty($justificativo)) {
            $userName = $user ? $user->name : 'Docente';
            if ($isAdmin) {
                $auditLog = "[Extemporáneo por Admin ({$userName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            } else {
                $adminName = $adminName ?: ($adminAuthorizer ? $adminAuthorizer->name : 'Administrador');
                $auditLog = "[Extemporáneo por Docente ({$userName}) con Autorización de Admin ({$adminName}): {$justificativo} - " . now()->format('d/m/Y H:i') . "]";
            }
            $obsFinal = !empty($obsFinal) ? $obsFinal . " | " . $auditLog : $auditLog;
        }

        $asistencia = Asistencia::create([
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'fecha'          => $validated['fecha'],
            'estado'         => $validated['estado'],
            'observacion'    => $obsFinal,
        ]);

        return response()->json([
            'message'    => 'Asistencia registrada correctamente y bloqueada para modificaciones futuras.',
            'asistencia' => [
                'id_asistencia' => $asistencia->id_asistencia,
                'id' => $asistencia->id_asistencia,
                'inscripcion_id' => $asistencia->id_inscripcion,
                'fecha' => $asistencia->fecha,
                'estado' => $asistencia->estado,
                'observacion' => $asistencia->observacion
            ]
        ]);
    }

    /**
     * Obtener resumen de asistencia de todos los estudiantes de un paralelo
     */
    public function getAsistenciasParalelo($paraleloId)
    {
        $inscripciones = Inscripcion::where('id_paralelo', $paraleloId)
            ->with([
                'estudiante.user',
                'asistencias', 
                'notas'
            ])
            ->get()->map(function($ins) {
                $flatNotas = [];
                $nota = $ins->notas->first();
                if ($nota) {
                    if ($nota->nota_1 !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_1,
                            'periodo' => 'Parcial 1',
                            'observacion' => $nota->observacion
                        ];
                    }
                    if ($nota->nota_2 !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_2,
                            'periodo' => 'Parcial 2',
                            'observacion' => $nota->observacion
                        ];
                    }
                    if ($nota->nota_final !== null) {
                        $flatNotas[] = [
                            'id_nota' => $nota->id_nota,
                            'id_inscripcion' => $nota->id_inscripcion,
                            'nota' => $nota->nota_final,
                            'periodo' => 'Examen Final',
                            'observacion' => $nota->observacion
                        ];
                    }
                }

                return [
                    'id_inscripcion' => $ins->id_inscripcion,
                    'id' => $ins->id_inscripcion,
                    'estudiante_id' => $ins->id_estudiante,
                    'paralelo_id' => $ins->id_paralelo,
                    'estado' => $ins->estado,
                    'estudiante' => $ins->estudiante ? [
                        'id' => $ins->estudiante->id_estudiante,
                        'nombres' => $ins->estudiante->nombres,
                        'apellidos' => $ins->estudiante->apellidos,
                        'ci' => $ins->estudiante->ci,
                        'grado_academico' => $ins->estudiante->grado_academico,
                        'arma_especialidad' => $ins->estudiante->arma_especialidad,
                    ] : null,
                    'asistencias' => $ins->asistencias,
                    'notas' => $flatNotas
                ];
            });

        return response()->json($inscripciones);
    }
}
