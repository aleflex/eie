<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Idioma;
use App\Models\Nivel;
use App\Models\Modalidad;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Obtiene el listado de todos los cursos disponibles con el conteo de inscripciones
     */
    public function index()
    {
        $cursos = Curso::with(['idioma', 'nivelRel', 'modalidadRel'])->withCount('inscripciones')->get()->map(function($curso) {
            return [
                'id_curso' => $curso->id_curso,
                'id' => $curso->id_curso, // Fallback para compatibilidad con el frontend
                'idioma' => $curso->idioma ? ($curso->idioma->nombre_idioma ?? $curso->idioma->nombre) : '',
                'nivel' => $curso->nivel,
                'modalidad' => $curso->modalidad,
                'cupo_minimo' => $curso->cupo_minimo,
                'cupo_maximo' => $curso->cupo_maximo,
                'inscripciones_count' => $curso->inscripciones_count
            ];
        });
        return response()->json($cursos);
    }

    /**
     * Crea un nuevo curso en el sistema
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'idioma' => 'required|string|max:100',
                'nivel' => 'required|string|max:100',
                'modalidad' => 'required|string|max:100',
                'cupo_minimo' => 'nullable|numeric|min:1',
                'cupo_maximo' => 'nullable|numeric|min:1',
            ]);

            // Normalización en base de datos para catálogos 3NF
            $idioma = Idioma::firstOrCreate(['nombre_idioma' => $request->idioma]);
            $nivel = Nivel::firstOrCreate(['nombre_nivel' => $request->nivel]);
            $modalidad = Modalidad::firstOrCreate(['nombre_modalidad' => $request->modalidad]);

            $curso = Curso::create([
                'id_idioma' => $idioma->id_idioma,
                'id_nivel' => $nivel->id_nivel,
                'id_modalidad' => $modalidad->id_modalidad,
                'cupo_minimo' => $request->cupo_minimo ?? 0,
                'cupo_maximo' => $request->cupo_maximo ?? 30,
                'estado' => 'Activo'
            ]);

            return response()->json([
                'id_curso' => $curso->id_curso,
                'id' => $curso->id_curso,
                'idioma' => $idioma->nombre_idioma ?? $idioma->nombre,
                'nivel' => $curso->nivel,
                'modalidad' => $curso->modalidad,
                'cupo_minimo' => $curso->cupo_minimo,
                'cupo_maximo' => $curso->cupo_maximo,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los datos de un curso específico
     */
    public function show($id)
    {
        $curso = Curso::with(['idioma', 'nivelRel', 'modalidadRel'])->findOrFail($id);
        
        return response()->json([
            'id_curso' => $curso->id_curso,
            'id' => $curso->id_curso,
            'idioma' => $curso->idioma->nombre_idioma ?? $curso->idioma->nombre ?? '',
            'nivel' => $curso->nivel ?? '',
            'modalidad' => $curso->modalidad ?? '',
            'cupo_minimo' => $curso->cupo_minimo,
            'cupo_maximo' => $curso->cupo_maximo,
        ]);
    }

    /**
     * Actualiza los datos de un curso existente
     */
    public function update(Request $request, $id)
    {
        try {
            $curso = Curso::findOrFail($id);
            $request->validate([
                'idioma' => 'required|string|max:100',
                'nivel' => 'required|string|max:100',
                'modalidad' => 'required|string|max:100',
                'cupo_minimo' => 'nullable|numeric|min:1',
                'cupo_maximo' => 'nullable|numeric|min:1',
            ]);

            // Actualizar/Crear registros de catálogo 3NF
            $idioma = Idioma::firstOrCreate(['nombre_idioma' => $request->idioma]);
            $nivel = Nivel::firstOrCreate(['nombre_nivel' => $request->nivel]);
            $modalidad = Modalidad::firstOrCreate(['nombre_modalidad' => $request->modalidad]);

            $curso->update([
                'id_idioma' => $idioma->id_idioma,
                'id_nivel' => $nivel->id_nivel,
                'id_modalidad' => $modalidad->id_modalidad,
                'cupo_minimo' => $request->cupo_minimo ?? $curso->cupo_minimo,
                'cupo_maximo' => $request->cupo_maximo ?? $curso->cupo_maximo,
            ]);

            return response()->json([
                'id_curso' => $curso->id_curso,
                'id' => $curso->id_curso,
                'idioma' => $idioma->nombre_idioma ?? $idioma->nombre,
                'nivel' => $curso->nivel,
                'modalidad' => $curso->modalidad,
                'cupo_minimo' => $curso->cupo_minimo,
                'cupo_maximo' => $curso->cupo_maximo,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Elimina un curso específico del sistema
     */
    public function destroy($id)
    {
        try {
            $curso = Curso::findOrFail($id);
            
            // Eliminar dependencias vinculadas si existen
            $curso->paralelos()->delete();
            $curso->inscripciones()->delete();
            $curso->delete();

            return response()->json(['message' => 'Curso eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el curso.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
