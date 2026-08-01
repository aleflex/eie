<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    use HasFactory;

    protected $table = 'idiomas';
    protected $primaryKey = 'id_idioma';
    public $timestamps = false;

    protected $fillable = [
        'nombre_idioma',
        'nombre'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_idioma;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_idioma'] = $value;
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'id_idioma', 'id_idioma');
    }
}
