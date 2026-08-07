<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
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

            // Verificar si el usuario autenticado es un estudiante y si tiene el permiso vencido/inactivo
            $user = auth()->user();
            if ($user) {
                $checkEstudiante = \App\Models\Estudiante::where('id_usuario', $user->id_usuario)->first();
                if ($checkEstudiante && $checkEstudiante->id_estudiante == $estudianteId) {
                    $hasta = $checkEstudiante->documentos_habilitados_hasta;
                    if (!$hasta || now()->greaterThan(\Carbon\Carbon::parse($hasta))) {
                        return response()->json([
                            'message' => 'No tienes permisos activos para subir documentos. Solicita autorización al administrador.'
                        ], 403);
                    }
                }
            } else {
                // Fallback: Si no hay sesión activa en el request, validar la fecha en el estudiante objetivo
                $estudianteObj = Estudiante::find($estudianteId);
                if ($estudianteObj) {
                    $hasta = $estudianteObj->documentos_habilitados_hasta;
                    if (!$hasta || now()->greaterThan(\Carbon\Carbon::parse($hasta))) {
                        return response()->json([
                            'message' => 'No tienes permisos activos para subir documentos. Solicita autorización al administrador.'
                        ], 403);
                    }
                }
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
                // Respaldar documento como Data URI en MySQL para que NUNCA se borre
                $finalPath = 'data:' . $mime . ';base64,' . base64_encode($fileBinary);
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
