<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';
    protected $primaryKey = 'id_curso';
    public $timestamps = false;

    protected $fillable = [
        'id_idioma',
        'id_nivel',
        'id_modalidad',
        'cupo_minimo',
        'cupo_maximo',
        'estado'
    ];

    protected $appends = ['id_modality', 'nombre_curso', 'id', 'nivel', 'modalidad'];

    public function getIdAttribute()
    {
        return $this->id_curso;
    }

    public function getIdModalityAttribute()
    {
        return $this->id_modalidad;
    }

    public function setIdModalityAttribute($value)
    {
        $this->attributes['id_modalidad'] = $value;
    }

    public function getNivelAttribute()
    {
        if (!empty($this->attributes['nivel'])) {
            return $this->attributes['nivel'];
        }
        return $this->nivelRel ? ($this->nivelRel->nombre_nivel ?? $this->nivelRel->nombre ?? '') : '';
    }

    public function getModalidadAttribute()
    {
        if (!empty($this->attributes['modalidad'])) {
            return $this->attributes['modalidad'];
        }
        return $this->modalidadRel ? ($this->modalidadRel->nombre_modalidad ?? $this->modalidadRel->nombre ?? '') : '';
    }

    public function getNombreCursoAttribute()
    {
        $idioma = $this->idioma ? ($this->idioma->nombre_idioma ?? $this->idioma->nombre) : '';
        $nivel = $this->nivel ?? '';
        $modalidad = $this->modalidad ?? '';
        return trim("$idioma $nivel $modalidad");
    }

    public function setNombreCursoAttribute($value)
    {
        // No-op (campo calculado en 3NF)
    }

    /**
     * Relación muchos-a-uno con Idioma
     */
    public function idioma()
    {
        return $this->belongsTo(Idioma::class, 'id_idioma', 'id_idioma');
    }

    /**
     * Relación muchos-a-uno con Nivel
     */
    public function nivelRel()
    {
        return $this->belongsTo(Nivel::class, 'id_nivel', 'id_nivel');
    }

    /**
     * Relación muchos-a-uno con Modalidad
     */
    public function modalidadRel()
    {
        return $this->belongsTo(Modalidad::class, 'id_modalidad', 'id_modalidad');
    }

    /**
     * Relación uno-a-muchos con Paralelo
     */
    public function paralelos()
    {
        return $this->hasMany(Paralelo::class, 'id_curso', 'id_curso');
    }

    /**
     * Relación uno-a-muchos con Inscripcion
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_curso', 'id_curso');
    }

    /**
     * Personalizar la serialización a array/JSON.
     */
    public function toArray()
    {
        $array = parent::toArray();
        if ($this->relationLoaded('idioma')) {
            $array['idioma'] = $this->idioma ? ($this->idioma->nombre_idioma ?? $this->idioma->nombre) : '';
        }
        if ($this->relationLoaded('nivelRel')) {
            $array['nivel'] = $this->nivelRel ? ($this->nivelRel->nombre_nivel ?? $this->nivelRel->nombre) : ($array['nivel'] ?? '');
        }
        if ($this->relationLoaded('modalidadRel')) {
            $array['modalidad'] = $this->modalidadRel ? ($this->modalidadRel->nombre_modalidad ?? $this->modalidadRel->nombre) : ($array['modalidad'] ?? '');
        }
        return $array;
    }
}
