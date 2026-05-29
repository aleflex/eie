<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;

/**
 * Controlador de Cursos/Paralelos
 * Gestiona todas las operaciones CRUD de cursos disponibles en el sistema.
 * Cada curso representa un paralelo específico de un idioma y nivel.
 */
class CourseController extends Controller
{
    /**
     * Obtiene el listado de todos los cursos disponibles con el conteo de inscripciones
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Curso::withCount('inscripciones')->get());
    }

    /**
     * Crea un nuevo curso en el sistema
     * Valida que los datos requeridos sean correctos antes de guardar
     *
     * @param Request $request Contiene los datos del nuevo curso
     * @return \Illuminate\Http\JsonResponse Curso creado o errores de validación
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Datos recibidos para nuevo curso:', $request->all());
        try {
            $validado = $request->validate([
                'idioma' => 'nullable|string|max:100',        // Ej: Inglés, Francés, Alemán
                'nivel' => 'nullable|string|max:100',         // Ej: NIVEL I, NIVEL II, etc.
                'modalidad' => 'nullable|string|max:100',     // Ej: Presencial, Virtual, Híbrida
                'horario' => 'nullable|string|max:100',       // Ej: 08:00 - 10:00
                'cupo_minimo' => 'nullable|numeric|min:1',    // Mínimo de estudiantes
                'cupo_maximo' => 'nullable|numeric|min:1',    // Máximo de estudiantes
            ]);

            $curso = Curso::create($validado);
            return response()->json($curso, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los datos de un curso específico
     * @param int $id ID del curso
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        return response()->json(Curso::findOrFail($id));
    }

    /**
     * Actualiza los datos de un curso existente
     * @param Request $request Nuevos datos del curso
     * @param int $id ID del curso a actualizar
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $curso = Curso::findOrFail($id);
            $validado = $request->validate([
                'idioma' => 'nullable|string|max:100',
                'nivel' => 'nullable|string|max:100',
                'modalidad' => 'nullable|string|max:100',
                'horario' => 'nullable|string|max:100',
                'cupo_minimo' => 'nullable|numeric|min:1',
                'cupo_maximo' => 'nullable|numeric|min:1',
            ]);

            $curso->update($validado);
            return response()->json($curso);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Elimina un curso del sistema
     * Primero elimina todas las inscripciones asociadas
     *
     * @param int $id ID del curso a eliminar
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $curso = Curso::findOrFail($id);

        // Eliminar inscripciones vinculadas primero (cascada)
        $curso->inscripciones()->delete();

        // Eliminar el curso
        $curso->delete();
        return response()->json(['message' => 'Curso eliminado exitosamente']);
    }
}
