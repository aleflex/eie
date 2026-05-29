<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

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
        return response()->json(Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo'])->get());
    }

    /**
     * Busca estudiantes usando su Carnet de Identidad (CI) o su Nombre/Apellido.
     * 
     * @param Request $request Contiene los parámetros de búsqueda ('ci' o 'nombre').
     * @return \Illuminate\Http\JsonResponse Resultados de la búsqueda.
     */
    public function search(Request $request)
    {
        $query = Estudiante::with(['inscripciones.curso', 'inscripciones.paralelo']);

        if ($request->has('ci') && !empty($request->ci)) {
            $query->where('ci', 'LIKE', '%' . $request->ci . '%');
        }

        if ($request->has('nombre') && !empty($request->nombre)) {
            $nombre = $request->nombre;
            $query->where(function($q) use ($nombre) {
                $q->where('nombres', 'LIKE', '%' . $nombre . '%')
                  ->orWhere('apellidos', 'LIKE', '%' . $nombre . '%');
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
        $estudiante = Estudiante::with(['inscripciones.curso', 'inscripciones.notas'])
            ->find($id);

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        return response()->json([
            'estudiante' => $estudiante->only(['id', 'nombres', 'apellidos', 'ci']),
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
        $estudiante = Estudiante::with(['user', 'inscripciones.curso', 'inscripciones.paralelo'])->find($id);
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
        $estudiante = Estudiante::find($id);
        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $data = $request->all();

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('fotos/' . $estudiante->ci, 'public');
            $data['foto_4x4_url'] = '/storage/fotos/' . $estudiante->ci . '/' . basename($path);
        }

        $estudiante->update($data);

        return response()->json([
            'message' => 'Estudiante actualizado con éxito',
            'estudiante' => $estudiante->fresh()
        ]);
    }

    /**
     * Elimina permanentemente a un estudiante del sistema.
     * Regla de negocio: Si el estudiante ya tiene notas registradas, NO se puede eliminar.
     * Si no tiene notas, se elimina y se liberan los cupos de los paralelos donde estaba inscrito.
     * 
     * @param int $id El ID del estudiante a eliminar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);

            // Verificar si alguna de sus inscripciones tiene notas
            $tieneNotas = $estudiante->inscripciones()->whereHas('notas')->exists();

            if ($tieneNotas) {
                return response()->json([
                    'message' => 'No se puede eliminar al estudiante porque tiene un historial académico (notas) registrado.'
                ], 422);
            }

            // Iniciar transacción para seguridad
            \Illuminate\Support\Facades\DB::transaction(function () use ($estudiante) {
                // Eliminar inscripciones asociadas (Esto libera los cupos automáticamente)
                $estudiante->inscripciones()->delete();
                $estudiante->delete();
            });

            return response()->json(['message' => 'Estudiante eliminado con éxito y cupos liberados.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar al estudiante.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
