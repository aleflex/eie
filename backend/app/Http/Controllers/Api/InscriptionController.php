<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|min:3|max:255',
            'apellidos' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'], // Exige al menos dos palabras (dos apellidos)
            'ci' => 'required|string|min:6|max:20',
            'email' => 'required|email',
            'celularPrefix' => 'nullable|string',
            'celular' => 'required|string',
            'lugarNacimiento' => 'required|string|min:4',
            'fechaNacimiento' => 'required|date',
            'anioBachiller' => 'required|numeric',
            'estadoCivil' => 'required|string',
            'grupoSanguineo' => 'required|string',
            'domicilio' => 'required|string',
            'nombrePadres' => 'required_unless:userType,militar|nullable|string|max:255',
            'ciTutor' => 'required_unless:userType,militar|nullable|string|min:6|max:20',
            'hermanosInscritos' => 'nullable|string|max:255',
            'contactoEmergencia' => 'required|string|max:255',
            'idioma' => 'required|string',
            'nivel' => 'required|string',
            'horario' => 'required|string',
            'tipoCurso' => 'required|string',
        ], [
            'apellidos.regex' => 'Debe ingresar sus dos apellidos (Paterno y Materno).',
            'nombres.min' => 'El nombre debe tener al menos 3 caracteres.',
            'ci.min' => 'El carnet de identidad debe tener al menos 6 dígitos.',
            'lugarNacimiento.min' => 'El lugar de nacimiento debe tener al menos 4 caracteres.',
            'ciTutor.min' => 'El C.I. del tutor debe tener al menos 6 dígitos.',
        ]);

        try {
            DB::beginTransaction();

            $celularCompleto = ($request->celularPrefix ? ($request->celularPrefix . ' ') : '') . $request->celular;

            $estudiante = Estudiante::updateOrCreate(
                ['ci' => $request->ci],
                [
                    'tipo_usuario' => $request->userType,
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'grado_academico' => $request->gradoAcademico,
                    'arma_especialidad' => $request->armaEspecialidad,
                    'correo_electronico' => $request->email,
                    'celular' => $celularCompleto,
                    'fecha_nacimiento' => $request->fechaNacimiento,
                    'lugar_nacimiento' => $request->lugarNacimiento,
                    'anio_egreso_bachiller' => $request->anioBachiller,
                    'estado_civil' => $request->estadoCivil,
                    'grupo_sanguineo' => $request->grupoSanguineo,
                    'domicilio' => $request->domicilio,
                    'carnet_militar' => $request->carnetMilitar,
                    'carnet_cossmil' => $request->carnetCossmil,
                    'nombre_padres' => $request->nombrePadres,
                    'ci_tutor' => $request->ciTutor,
                    'hermanos_inscritos' => $request->hermanosInscritos,
                    'contacto_emergencia' => $request->contactoEmergencia,
                ]
            );

            // 2. Procesar Archivo de Foto (si existe)
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('fotos/' . $estudiante->ci, 'public');
                $estudiante->foto_4x4_url = '/storage/fotos/' . $estudiante->ci . '/' . basename($path);
                $estudiante->save();
            }

            // Procesar y almacenar los demás documentos en la tabla 'documentos'
            $filesToProcess = [
                'carnet' => 'Carnet de Identidad',
                'titulo' => 'Título de Bachiller',
                'nacimiento' => 'Certificado de Nacimiento',
                'deposito' => 'Boleta de Depósito'
            ];

            if ($request->userType === 'emi') {
                $filesToProcess['credencialEmi'] = 'Credencial o Factura EMI';
            }

            foreach ($filesToProcess as $fileKey => $documentType) {
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
                    
                    // Generar un nombre único para el archivo
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    
                    // Guardar en el disco 'documentos' (storage/app/public/documentos)
                    $path = $file->storeAs('estudiantes/' . $estudiante->id, $fileName, 'documentos');

                    // Crear el registro en la base de datos
                    \App\Models\Documento::create([
                        'estudiante_id' => $estudiante->id,
                        'tipo_documento' => $documentType,
                        'nombre_archivo' => $file->getClientOriginalName(),
                        'ruta_archivo' => $path,
                        'extension' => $file->getClientOriginalExtension(),
                        'peso' => $file->getSize(),
                    ]);
                }
            }

            // Dynamically resolve course matching choices
            $curso = \App\Models\Curso::firstOrCreate([
                'idioma' => $request->idioma,
                'nivel' => $request->nivel,
                'horario' => $request->horario,
                'modalidad' => $request->tipoCurso ?? 'presencial',
            ], [
                'cupo_minimo' => 5,
                'cupo_maximo' => 30
            ]);

            // 3. Crear Inscripción
            $inscripcion = Inscripcion::create([
                'estudiante_id' => $estudiante->id,
                'curso_id' => $curso->id,
                'fecha_registro' => now(),
                'estado' => 'pendiente'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Inscripción registrada con éxito',
                'id' => $inscripcion->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log para el desarrollador
            \Log::error("Error en Inscripción: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Error crítico en el servidor',
                'detalle' => $e->getMessage(), // Esto nos dirá el campo exacto que falla
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function index()
    {
        return response()->json(Inscripcion::with('estudiante', 'curso', 'paralelo')->get());
    }

    /**
     * Actualizar una inscripción específica
     */
    public function update(Request $request, $id)
    {
        try {
            $inscripcion = Inscripcion::findOrFail($id);
            
            $validated = $request->validate([
                'curso_id' => 'sometimes|exists:cursos,id',
                'paralelo_id' => 'sometimes|nullable|exists:paralelos,id',
                'estado' => 'sometimes|string|in:pendiente,activo,finalizado,retirado',
                'fecha_registro' => 'sometimes|date'
            ]);

            $inscripcion->update($validated);

            return response()->json([
                'message' => 'Inscripción actualizada con éxito',
                'inscripcion' => $inscripcion->load('estudiante', 'curso', 'paralelo')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una inscripción
     * Verifica integridad: No permite borrar si tiene notas asociadas.
     */
    public function destroy($id)
    {
        try {
            $inscripcion = Inscripcion::findOrFail($id);

            // Verificar si tiene notas asociadas
            if ($inscripcion->notas()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar la inscripción porque tiene notas asociadas.'
                ], 422);
            }

            $inscripcion->delete();

            return response()->json([
                'message' => 'Inscripción eliminada correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la inscripción.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
