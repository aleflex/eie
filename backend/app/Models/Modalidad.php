<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    use HasFactory;

    protected $table = 'modalidades';
    protected $primaryKey = 'id_modalidad';
    public $timestamps = false;

    protected $fillable = [
        'nombre_modalidad',
        'nombre'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_modalidad;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_modalidad'] = $value;
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'id_modalidad', 'id_modalidad');
    }
}
