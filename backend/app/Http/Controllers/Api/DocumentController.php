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
        $documentos = Documento::where('estudiante_id', $estudianteId)->get();
        return response()->json($documentos);
    }

    // Subir un documento
    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_documento' => 'required|string',
            'archivo' => 'required|file|mimes:pdf,jpg,png|max:5120', // Máximo 5MB
        ]);

        try {
            $file = $request->file('archivo');
            $estudianteId = $request->estudiante_id;

            // Generar un nombre único para el archivo
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            // Guardar en el disco 'documentos' (storage/app/public/documentos)
            $path = $file->storeAs('estudiantes/' . $estudianteId, $fileName, 'documentos');

            // Crear el registro en la base de datos
            $documento = Documento::create([
                'estudiante_id' => $estudianteId,
                'tipo_documento' => $request->tipo_documento,
                'nombre_archivo' => $file->getClientOriginalName(),
                'ruta_archivo' => $path,
                'extension' => $file->getClientOriginalExtension(),
                'peso' => $file->getSize(),
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
            // Eliminar el archivo físico
            Storage::disk('documentos')->delete($documento->ruta_archivo);
            
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

