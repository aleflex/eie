<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Curso
 * Representa un curso/paralelo disponible en el sistema.
 * Cada curso es una instancia específica de un idioma, nivel y modalidad.
 *
 * Atributos:
 * - idioma: Idioma que se imparte (Inglés, Francés, Alemán, Chino, etc.)
 * - nivel: Nivel del curso (NIVEL I, NIVEL II, etc.)
 * - modalidad: Modalidad de impartición (Presencial, Virtual, Híbrida)
 * - horario: Horario del curso (ej: 08:00 - 10:00)
 * - cupo_minimo: Número mínimo de estudiantes para que el curso se realice
 * - cupo_maximo: Número máximo de estudiantes que puede albergar el curso
 */
class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';
    public $timestamps = false;  // No usa created_at/updated_at

    protected $fillable = [
        'idioma',           // Idioma del curso
        'nivel',            // Nivel académico
        'modalidad',        // Forma de impartición
        'horario',          // Horario del curso
        'cupo_minimo',      // Cupo mínimo de estudiantes
        'cupo_maximo'       // Cupo máximo de estudiantes
    ];

    /**
     * Relación uno-a-muchos con Inscripcion
     * Un curso puede tener muchas inscripciones
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'curso_id');
    }
}
