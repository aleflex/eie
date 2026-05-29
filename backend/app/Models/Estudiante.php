<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Estudiante
 * Representa un estudiante en el sistema de inscripciones.
 * Almacena información personal, académica y de contacto de cada estudiante.
 *
 * Atributos principales:
 * - user_id: ID de la cuenta de usuario para autenticación
 * - tipo_usuario: Rol/tipo del usuario (estudiante militar, civil, etc.)
 * - grado_id: Grado académico del estudiante
 * - arma_id: Especialidad militar o área de adscripción
 * - ci: Cédula de identidad
 * - correo_electronico: Email de contacto
 * - nombres/apellidos: Información personal
 * - foto_4x4_url: URL de la fotografía de 4x4
 */
class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';
    public $timestamps = false;  // No usa created_at/updated_at

    protected $fillable = [
        'user_id',                  // ID del usuario autenticado
        'tipo_usuario',             // Tipo de usuario/rol
        'grado_id',                 // Grado académico o militar
        'arma_id',                  // Arma o especialidad
        'nombres',                  // Nombres del estudiante
        'apellidos',                // Apellidos del estudiante
        'grado_academico',          // Grado académico
        'arma_especialidad',        // Especialidad del arma
        'fecha_nacimiento',         // Fecha de nacimiento
        'lugar_nacimiento',         // Lugar de nacimiento
        'ci',                       // Cédula de identidad
        'carnet_militar',           // Número de carnet militar
        'carnet_cossmil',           // Número de carnet COSSMIL
        'estado_civil',             // Estado civil
        'grupo_sanguineo',          // Tipo de sangre
        'celular',                  // Número celular
        'anio_egreso_bachiller',    // Año de egreso del bachillerato
        'correo_electronico',       // Email de contacto
        'domicilio',                // Dirección del domicilio
        'foto_4x4_url',             // URL de la fotografía 4x4
        'nombre_padres',            // Nombres de los padres/tutores
        'ci_tutor',                 // CI del tutor o apoderado
        'hermanos_inscritos',       // Número de hermanos inscritos
        'contacto_emergencia'       // Número de contacto de emergencia
    ];

    /**
     * Relación muchos-a-uno con User
     * Cada estudiante tiene una cuenta de usuario para autenticarse
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación uno-a-muchos con Inscripcion
     * Un estudiante puede estar inscrito en múltiples cursos
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'estudiante_id');
    }
}
}
