<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Nota
 * Representa las calificaciones y evaluaciones de un estudiante en un curso.
 *
 * Atributos:
 * - inscripcion_id: ID de la inscripción a la que pertenece la nota
 * - nota: Valor numérico de la calificación
 * - periodo: Período evaluativo (1er Parcial, 2do Parcial, Final, etc.)
 * - observacion: Comentarios o observaciones sobre el desempeño
 */
class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';
    public $timestamps = true;  // Usa created_at/updated_at

    protected $fillable = [
        'inscripcion_id',   // ID de la inscripción
        'nota',             // Calificación numérica
        'periodo',          // Período evaluativo
        'observacion'       // Comentarios sobre la calificación
    ];

    /**
     * Relación muchos-a-uno con Inscripcion
     * Varias notas pueden pertenecer a una misma inscripción
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }
}
