<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Estudiante;
use App\Models\Configuracion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Verifica si el usuario o estudiante tiene autorización para subir o eliminar documentos.
     * Criterio:
     * 1. Administrador / personal directivo siempre tiene autorización.
     * 2. Si el periodo oficial de inscripciones está activo (fecha_inicio <= ahora <= fecha_fin),
     *    TODOS los estudiantes tienen autorización abierta para cargar y modificar su expediente.
     * 3. Si el periodo oficial de inscripciones ha finalizado o no está activo, el estudiante
     *    requiere habilitación/autorización expresa otorgada por el administrador (documentos_habilitados_hasta >= ahora).
     */
    private function tienePermisoDocumentos($estudianteId, $user = null): array
    {
        // 1. Roles administrativos y docentes con permisos siempre autorizados
        $esAdmin = $user && (
            ($user->id_rol == 1) ||
            in_array(strtolower($user->rol ?? ''), ['admin', 'administrador', 'superadmin', 'director', 'secretaria']) ||
            in_array(strtolower($user->tipo_usuario ?? ''), ['admin', 'docente'])
        );
        if ($esAdmin) {
            return ['permitido' => true];
        }

        $ahora = now();

        // 2. Verificar periodo oficial de inscripción y matrículas
        $fechaInicio = Configuracion::where('clave', 'fecha_inicio_inscripcion')->value('valor');
        $fechaFin = Configuracion::where('clave', 'fecha_fin_inscripcion')->value('valor');

        $periodoAbierto = true;
        if (!empty($fechaInicio)) {
            try {
                $inicio = Carbon::parse($fechaInicio);
                if ($ahora->lessThan($inicio)) {
                    $periodoAbierto = false;
                }
            } catch (\Throwable $e) {}
        }
        if ($periodoAbierto && !empty($fechaFin)) {
            try {
                $fin = Carbon::parse($fechaFin);
                if ($ahora->greaterThan($fin)) {
                    $periodoAbierto = false;
                }
            } catch (\Throwable $e) {}
        }

        // Si estamos dentro del periodo de inscripción, TODOS los estudiantes tienen acceso abierto
        if ($periodoAbierto && (!empty($fechaInicio) || !empty($fechaFin))) {
            return ['permitido' => true, 'motivo' => 'periodo_general'];
        }

        // 3. Fuera del periodo de inscripciones: verificación individual por autorización del administrador
        $estudiante = Estudiante::find($estudianteId);
        if ($estudiante && !empty($estudiante->documentos_habilitados_hasta)) {
            try {
                $hasta = Carbon::parse($estudiante->documentos_habilitados_hasta);
                if ($ahora->lessThanOrEqualTo($hasta)) {
                    return ['permitido' => true, 'motivo' => 'autorizacion_individual', 'hasta' => $hasta];
                }
            } catch (\Throwable $e) {}
        }

        return [
            'permitido' => false,
            'mensaje' => 'La carga de documentos se encuentra bloqueada. El periodo de inscripciones no está activo y no cuentas con autorización individual vigente del administrador.'
        ];
    }

    // Listar documentos de un estudiante
    public function index($estudianteId)
    {
        $documentos = Documento::where('id_estudiante', $estudianteId)->get();
        return response()->json($documentos);
    }

    // Subir un documento
    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id_estudiante',
            'tipo_documento' => 'required|string',
            'archivo' => 'required|file|mimes:pdf,jpg,png|max:5120', // Máximo 5MB
            'observacion' => 'nullable|string'
        ]);

        try {
            $file = $request->file('archivo');
            $estudianteId = $request->estudiante_id;

            // Verificar permisos de carga de documentos
            $user = auth()->user() ?? request()->user();
            $checkPermiso = $this->tienePermisoDocumentos($estudianteId, $user);
            if (!$checkPermiso['permitido']) {
                return response()->json([
                    'message' => $checkPermiso['mensaje'] ?? 'No tienes permisos activos para subir documentos. Solicita autorización al administrador.'
                ], 403);
            }

            // Generar un nombre único para el archivo
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file->getClientOriginalName());
            $mime = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/pdf';
            $fileBinary = file_get_contents($file->getRealPath());
            $remotePath = 'documentos/estudiantes/' . $estudianteId . '/' . $fileName;

            // Intentar subir a Supabase Storage
            $supabaseUrl = \App\Services\SupabaseStorageService::uploadFile($fileBinary, $remotePath, $mime);

            if ($supabaseUrl) {
                $finalPath = $supabaseUrl;
            } else {
                $path = $file->storeAs('estudiantes/' . $estudianteId, $fileName, 'documentos');
                $finalPath = '/storage/documentos/' . $path;
            }

            // Buscar tipo documento en catalogo
            $tipoDoc = \DB::table('tipos_documentos')
                ->where('nombre_tipo_documento', $request->tipo_documento)
                ->first();

            if (!$tipoDoc) {
                $tipoDoc = \DB::table('tipos_documentos')
                    ->whereRaw('LOWER(nombre_tipo_documento) = ?', [strtolower($request->tipo_documento)])
                    ->first();
            }

            $idTipoDoc = $tipoDoc ? $tipoDoc->id_tipo_documento : 1;

            // Crear el registro en la base de datos con la URL de Supabase Storage
            $documento = Documento::create([
                'id_estudiante' => $estudianteId,
                'tipo_documento' => $request->tipo_documento,
                'nombre_archivo' => $fileName,
                'ruta_archivo' => $finalPath,
            ]);

            return response()->json([
                'message' => 'Documento subido con éxito',
                'documento' => $documento
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir el archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar un documento
    public function destroy($id)
    {
        $documento = Documento::find($id);
        if (!$documento) {
            return response()->json(['message' => 'Documento no encontrado'], 404);
        }

        // Verificar permisos de modificación/eliminación de documentos
        $user = auth()->user() ?? request()->user();
        $checkPermiso = $this->tienePermisoDocumentos($documento->id_estudiante, $user);
        if (!$checkPermiso['permitido']) {
            return response()->json([
                'message' => $checkPermiso['mensaje'] ?? 'No cuentas con un plazo activo para modificar o eliminar documentos. Contacta con la administración de la Escuela de Idiomas.'
            ], 403);
        }

        try {
            // Eliminar el archivo físico si existe una ruta de almacenamiento válida
            $filePath = str_replace('/storage/documentos/', '', $documento->ruta_archivo);
            if ($filePath && !str_contains($documento->ruta_archivo, 'data:')) {
                Storage::disk('documentos')->delete($filePath);
            }

            // Eliminar el registro de la base de datos
            $documento->delete();

            return response()->json(['message' => 'Documento eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
