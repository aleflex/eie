<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;

    protected $fillable = [
        'id_estudiante',
        'id_paralelo',
        'id_curso',
        'fecha_registro',
        'estado'
    ];

    protected $appends = ['id', 'fecha_inscripcion'];

    public function getIdAttribute()
    {
        return $this->id_inscripcion;
    }

    public function getFechaInscripcionAttribute()
    {
        return $this->attributes['fecha_registro'] ?? null;
    }

    public function setFechaInscripcionAttribute($value)
    {
        $this->attributes['fecha_registro'] = $value;
    }

    /**
     * Relación muchos-a-uno con Estudiante
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    /**
     * Relación muchos-a-uno con Curso
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    /**
     * Relación muchos-a-uno con Paralelo
     */
    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class, 'id_paralelo', 'id_paralelo');
    }

    /**
     * Relación uno-a-muchos con Nota
     */
    public function notas()
    {
        return $this->hasMany(Nota::class, 'id_inscripcion', 'id_inscripcion');
    }

    /**
     * Relación uno-a-muchos con Asistencia
     */
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_inscripcion', 'id_inscripcion');
    }

    /**
     * Scope para filtrado multi-criterio dinámico (RF 21 - HU 21)
     */
    public function scopeFilterMultiCriteria($query, array $filters = [])
    {
        if (!empty($filters['id_idioma'])) {
            $query->whereHas('curso', function ($q) use ($filters) {
                $q->where('id_idioma', $filters['id_idioma']);
            });
        }

        if (!empty($filters['id_nivel'])) {
            $query->whereHas('curso', function ($q) use ($filters) {
                $q->where('id_nivel', $filters['id_nivel']);
            });
        }

        if (!empty($filters['id_curso'])) {
            $query->where('id_curso', $filters['id_curso']);
        }

        if (!empty($filters['id_paralelo'])) {
            $query->where('id_paralelo', $filters['id_paralelo']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', strtolower($filters['estado']));
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_registro', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_registro', '<=', $filters['fecha_hasta']);
        }

        if (!empty($filters['gestion'])) {
            $query->whereYear('fecha_registro', $filters['gestion']);
        }

        if (!empty($filters['id_docente'])) {
            $query->whereHas('paralelo.docentes', function ($q) use ($filters) {
                $q->where('docentes.id_docente', $filters['id_docente']);
            });
        }

        if (!empty($filters['turno'])) {
            $turno = strtolower($filters['turno']);
            $query->whereHas('paralelo.horarios', function ($q) use ($turno) {
                if ($turno === 'sabado' || $turno === 'sábado') {
                    $q->where(function($sq) {
                        $sq->where('dia_semana', 'like', '%sábado%')
                           ->orWhere('dia_semana', 'like', '%sabado%');
                    });
                } elseif ($turno === 'manana' || $turno === 'mañana') {
                    $q->where('hora_inicio', '<', '12:00:00')
                      ->where('dia_semana', 'not like', '%sábado%')
                      ->where('dia_semana', 'not like', '%sabado%');
                } elseif ($turno === 'tarde') {
                    $q->where('hora_inicio', '>=', '12:00:00')
                      ->where('hora_inicio', '<', '18:00:00')
                      ->where('dia_semana', 'not like', '%sábado%')
                      ->where('dia_semana', 'not like', '%sabado%');
                } elseif ($turno === 'noche') {
                    $q->where('hora_inicio', '>=', '18:00:00');
                }
            });
        }

        return $query;
    }
}
