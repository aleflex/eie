<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Paralelo extends Model
{
    use HasFactory;

    protected $table = 'paralelos';
    protected $primaryKey = 'id_paralelo';
    public $timestamps = false;

    protected $fillable = [
        'id_curso',
        'id_aula',
        'nombre_paralelo'
    ];

    protected $appends = ['id', 'curso_id', 'aula_id', 'nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_paralelo;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_paralelo'] = $value;
    }

    public function getIdAttribute()
    {
        return $this->id_paralelo;
    }

    public function getCursoIdAttribute()
    {
        return $this->id_curso;
    }

    public function getAulaIdAttribute()
    {
        return $this->id_aula;
    }

    /**
     * Relación muchos-a-uno con Curso
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    /**
     * Relación muchos-a-uno con Aula
     */
    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    /**
     * Relación muchos-a-muchos con Docente
     */
    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'docente_paralelo', 'id_paralelo', 'id_docente');
    }

    /**
     * Relación muchos-a-muchos con Horario
     */
    public function horarios()
    {
        return $this->belongsToMany(Horario::class, 'horario_paralelo', 'id_paralelo', 'id_horario');
    }

    /**
     * Relación uno-a-muchos con Inscripcion
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_paralelo', 'id_paralelo');
    }
}
