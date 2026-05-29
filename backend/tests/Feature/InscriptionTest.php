<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Inscripcion;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que valida el registro exitoso de una inscripción.
     */
    public function test_can_register_civil_inscription(): void
    {
        // 1. Crear el curso con ID 1 para evitar fallos de clave foránea
        $curso = Curso::create([
            'id' => 1,
            'idioma' => 'Inglés',
            'nivel' => 'NIVEL I (BOOK 1-6)',
            'modalidad' => 'regular',
            'horario' => '08:00 - 10:00',
            'cupo_minimo' => 5,
            'cupo_maximo' => 20
        ]);

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez Mamani',
            'ci' => '12345678',
            'email' => 'juan@ejemplo.com',
            'celular' => '76543210',
            'lugarNacimiento' => 'La Paz',
            'fechaNacimiento' => '1995-05-15',
            'anioBachiller' => 2013,
            'estadoCivil' => 'soltero',
            'grupoSanguineo' => 'O+',
            'domicilio' => 'Calle Central 123',
            'userType' => 'normal',
            'edad' => 31,
            'tipoCurso' => 'regular',
            'idioma' => 'Inglés',
            'nivel' => 'NIVEL I (BOOK 1-6)',
            'horario' => '08:00 - 10:00'
        ];

        // Realizar la petición POST a la API
        $response = $this->postJson('/api/inscripciones', $data);

        // Validar que retorne HTTP 201 (Created)
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'id'
        ]);

        // Validar que el estudiante se guardó en la base de datos
        $this->assertDatabaseHas('estudiantes', [
            'ci' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez Mamani',
            'tipo_usuario' => 'normal'
        ]);

        // Validar que la inscripción se creó correctamente
        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => 1,
            'curso_id' => 1,
            'estado' => 'pendiente'
        ]);
    }

    /**
     * Test que valida fallos de validación por campos requeridos ausentes.
     */
    public function test_fails_to_register_without_required_fields(): void
    {
        $data = [
            'nombres' => '', // Campo nombres vacío
            'apellidos' => 'Pérez',
            'ci' => '', // Campo ci vacío
        ];

        $response = $this->postJson('/api/inscripciones', $data);

        // Validar que retorne HTTP 422 (Unprocessable Entity)
        $response->assertStatus(422);
        
        // Comprobar que contenga errores específicos en la validación
        $response->assertJsonValidationErrors(['nombres', 'ci']);
    }
}
