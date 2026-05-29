<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Asistencia
 * Registra la asistencia de los estudiantes en cada sesión de clase.
 *
 * Atributos:
 * - inscripcion_id: ID de la inscripción del estudiante
 * - fecha: Fecha de la clase
 * - estado: Estado de asistencia (Presente, Ausente, Retraso, etc.)
 * - observacion: Notas sobre la asistencia (justificación de faltas, etc.)
 */
class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';
    public $timestamps = true;  // Usa created_at/updated_at

    protected $fillable = [
        'inscripcion_id',   // ID de la inscripción
        'fecha',            // Fecha de la clase
        'estado',           // Presente, Ausente, Retraso, Justificado
        'observacion'       // Comentarios sobre la asistencia
    ];

    protected $casts = [
        'fecha' => 'date'   // Castear fecha como objeto Date
    ];

    /**
     * Relación muchos-a-uno con Inscripcion
     * Varios registros de asistencia pertenecen a una inscripción
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }
}
