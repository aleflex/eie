<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Estudiante;
use App\Models\Documento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que valida la obtención de la lista de documentos de un estudiante.
     */
    public function test_can_list_student_documents(): void
    {
        $estudiante = Estudiante::create([
            'id' => 1,
            'ci' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo_usuario' => 'normal',
            'correo_electronico' => 'juan@ejemplo.com',
            'celular' => '76543210',
            'fecha_nacimiento' => '1995-05-15',
            'lugar_nacimiento' => 'La Paz',
            'anio_egreso_bachiller' => 2013,
            'estado_civil' => 'soltero',
            'grupo_sanguineo' => 'O+',
            'domicilio' => 'Calle Central 123'
        ]);

        Documento::create([
            'estudiante_id' => $estudiante->id,
            'tipo_documento' => 'Carnet de Identidad',
            'nombre_archivo' => 'ci.pdf',
            'ruta_archivo' => 'estudiantes/1/12345_ci.pdf',
            'extension' => 'pdf',
            'peso' => 150000
        ]);

        $response = $this->getJson("/api/estudiantes/{$estudiante->id}/documentos");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'tipo_documento' => 'Carnet de Identidad',
            'nombre_archivo' => 'ci.pdf'
        ]);
    }

    /**
     * Test que valida la subida de un documento del estudiante.
     */
    public function test_can_upload_student_document(): void
    {
        Storage::fake('documentos');

        $estudiante = Estudiante::create([
            'id' => 1,
            'ci' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo_usuario' => 'normal',
            'correo_electronico' => 'juan@ejemplo.com',
            'celular' => '76543210',
            'fecha_nacimiento' => '1995-05-15',
            'lugar_nacimiento' => 'La Paz',
            'anio_egreso_bachiller' => 2013,
            'estado_civil' => 'soltero',
            'grupo_sanguineo' => 'O+',
            'domicilio' => 'Calle Central 123'
        ]);

        $file = UploadedFile::fake()->create('ci_frontal.jpg', 500); // 500 KB image

        $data = [
            'estudiante_id' => $estudiante->id,
            'tipo_documento' => 'Carnet de Identidad',
            'archivo' => $file
        ];

        $response = $this->postJson('/api/documentos/subir', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'documento' => [
                'id',
                'estudiante_id',
                'tipo_documento',
                'nombre_archivo',
                'ruta_archivo',
                'extension',
                'peso'
            ]
        ]);

        // Verificar registro en BD
        $this->assertDatabaseHas('documentos', [
            'estudiante_id' => $estudiante->id,
            'nombre_archivo' => 'ci_frontal.jpg',
            'tipo_documento' => 'Carnet de Identidad'
        ]);

        // Verificar almacenamiento físico simulado
        $documento = Documento::first();
        Storage::disk('documentos')->assertExists($documento->ruta_archivo);
    }

    /**
     * Test que valida la eliminación correcta de un documento cargado.
     */
    public function test_can_delete_student_document(): void
    {
        Storage::fake('documentos');

        $estudiante = Estudiante::create([
            'id' => 1,
            'ci' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo_usuario' => 'normal',
            'correo_electronico' => 'juan@ejemplo.com',
            'celular' => '76543210',
            'fecha_nacimiento' => '1995-05-15',
            'lugar_nacimiento' => 'La Paz',
            'anio_egreso_bachiller' => 2013,
            'estado_civil' => 'soltero',
            'grupo_sanguineo' => 'O+',
            'domicilio' => 'Calle Central 123'
        ]);

        // Guardar archivo fake físicamente en disco
        $file = UploadedFile::fake()->create('deposito.pdf', 300);
        $path = Storage::disk('documentos')->putFileAs('estudiantes/' . $estudiante->id, $file, 'deposito.pdf');

        $documento = Documento::create([
            'estudiante_id' => $estudiante->id,
            'tipo_documento' => 'Boleta de Depósito',
            'nombre_archivo' => 'deposito.pdf',
            'ruta_archivo' => $path,
            'extension' => 'pdf',
            'peso' => 300000
        ]);

        // El archivo debe existir antes de borrarlo
        Storage::disk('documentos')->assertExists($documento->ruta_archivo);

        // Petición DELETE a la API
        $response = $this->deleteJson("/api/documentos/{$documento->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Documento eliminado correctamente'
        ]);

        // Verificar eliminación en BD
        $this->assertDatabaseMissing('documentos', [
            'id' => $documento->id
        ]);

        // Verificar eliminación física del archivo
        Storage::disk('documentos')->assertMissing($documento->ruta_archivo);
    }
}
