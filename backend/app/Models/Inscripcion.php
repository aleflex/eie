<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Inscripcion
 * Representa la inscripción de un estudiante en un curso.
 * Es la tabla pivote central que conecta estudiantes, cursos y paralelos.
 *
 * Atributos:
 * - estudiante_id: ID del estudiante inscrito
 * - curso_id: ID del curso en el que se inscribe
 * - paralelo_id: ID del paralelo/sección del curso
 * - fecha_registro: Fecha en que se realizó la inscripción
 * - estado: Estado actual de la inscripción (activa, completada, cancelada, etc.)
 *
 * Una inscripción también puede tener:
 * - Notas de evaluación
 * - Registros de asistencia
 */
class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';
    public $timestamps = false;  // No usa created_at/updated_at

    protected $fillable = [
        'estudiante_id',    // ID del estudiante
        'curso_id',         // ID del curso
        'paralelo_id',      // ID del paralelo/sección
        'fecha_registro',   // Fecha de la inscripción
        'estado'            // Estado de la inscripción (activa, completada, cancelada)
    ];

    /**
     * Relación muchos-a-uno con Estudiante
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    /**
     * Relación muchos-a-uno con Curso
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /**
     * Relación muchos-a-uno con Paralelo
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class, 'paralelo_id');
    }

    /**
     * Relación uno-a-muchos con Nota
     * Una inscripción puede tener varias notas de evaluación
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notas()
    {
        return $this->hasMany(Nota::class, 'inscripcion_id');
    }

    /**
     * Relación uno-a-muchos con Asistencia
     * Una inscripción puede tener múltiples registros de asistencia
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'inscripcion_id');
    }
}
