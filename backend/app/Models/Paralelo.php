<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Paralelo
 * Representa un paralelo o sección específica de un curso.
 * Es la combinación de un curso, aula, docentes y horarios.
 *
 * Atributos:
 * - curso_id: ID del curso al que pertenece el paralelo
 * - aula_id: ID del aula donde se imparte el paralelo
 * - nombre: Nombre/etiqueta del paralelo (Paralelo A, Paralelo B, etc.)
 */
class Paralelo extends Model
{
    use HasFactory;

    protected $table = 'paralelos';
    public $timestamps = true;  // Usa created_at/updated_at

    protected $fillable = [
        'curso_id',         // ID del curso
        'aula_id',          // ID del aula
        'nombre'            // Nombre del paralelo (A, B, C, etc.)
    ];

    /**
     * Relación muchos-a-uno con Curso
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * Relación muchos-a-uno con Aula
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    /**
     * Relación muchos-a-muchos con Docente
     * Un paralelo puede tener múltiples docentes
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'docente_paralelo');
    }

    /**
     * Relación muchos-a-muchos con Horario
     * Un paralelo puede tener múltiples franjas horarias
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function horarios()
    {
        return $this->belongsToMany(Horario::class, 'horario_paralelo');
    }

    /**
     * Relación uno-a-muchos con Inscripcion
     * Un paralelo puede tener muchas inscripciones
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'paralelo_id');
    }
}
