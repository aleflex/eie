<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Retorna todos los roles disponibles en el sistema.
     */
    public function index()
    {
        $roles = Rol::all();
        return response()->json($roles);
    }

    /**
     * Crea un nuevo rol institucional.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_rol' => 'required|string|max:100|unique:roles,nombre_rol',
            'descripcion' => 'nullable|string|max:255'
        ]);

        $rol = Rol::create([
            'nombre_rol' => trim($request->nombre_rol),
            'descripcion' => $request->descripcion
        ]);

        return response()->json([
            'message' => "Rol '{$rol->nombre_rol}' creado con éxito.",
            'rol' => $rol
        ], 201);
    }

    /**
     * Actualiza los datos de un rol existente.
     */
    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        $request->validate([
            'nombre_rol' => 'required|string|max:100|unique:roles,nombre_rol,' . $id . ',id_rol',
            'descripcion' => 'nullable|string|max:255'
        ]);

        $rol->update([
            'nombre_rol' => trim($request->nombre_rol),
            'descripcion' => $request->descripcion
        ]);

        return response()->json([
            'message' => "Rol '{$rol->nombre_rol}' actualizado correctamente.",
            'rol' => $rol
        ]);
    }

    /**
     * Elimina un rol personalizado (no permite eliminar los roles base del sistema).
     */
    public function destroy($id)
    {
        if (in_array((int)$id, [1, 2, 3])) {
            return response()->json([
                'message' => 'No es posible eliminar los roles base del sistema (Administrador, Docente, Estudiante).'
            ], 422);
        }

        $rol = Rol::findOrFail($id);
        $nombre = $rol->nombre_rol;
        $rol->delete();

        return response()->json([
            'message' => "El rol '{$nombre}' ha sido eliminado exitosamente."
        ]);
    }

    /**
     * Retorna la matriz de permisos de módulos por rol.
     */
    public function getPermisos()
    {
        $config = Configuracion::where('clave', 'roles_permisos')->first();
        if ($config && $config->valor) {
            $permisos = json_decode($config->valor, true);
            if (is_array($permisos)) {
                return response()->json($permisos);
            }
        }

        // Matriz por defecto si aún no está configurada
        $defaultPermisos = [
            '1' => ['admin' => true, 'students' => true, 'courses' => true, 'docentes-list' => true, 'paralelos' => true, 'reports' => true, 'accesos' => true, 'settings' => true],
            '4' => ['admin' => true, 'students' => true, 'courses' => true, 'docentes-list' => true, 'paralelos' => true, 'reports' => true, 'accesos' => false, 'settings' => false],
            '5' => ['admin' => true, 'students' => true, 'courses' => true, 'docentes-list' => false, 'paralelos' => false, 'reports' => true, 'accesos' => false, 'settings' => false],
        ];

        return response()->json($defaultPermisos);
    }

    /**
     * Guarda la matriz de permisos de módulos por rol.
     */
    public function savePermisos(Request $request)
    {
        $permisos = $request->all();

        Configuracion::updateOrCreate(
            ['clave' => 'roles_permisos'],
            [
                'valor' => json_encode($permisos),
                'tipo' => 'json',
                'grupo' => 'seguridad',
                'descripcion' => 'Matriz de permisos de acceso a módulos por rol institucional'
            ]
        );

        return response()->json([
            'message' => 'Matriz de permisos actualizada y aplicada exitosamente.',
            'permisos' => $permisos
        ]);
    }
}
