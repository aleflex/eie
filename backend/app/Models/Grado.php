<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;

    protected $table = 'grados';
    protected $primaryKey = 'id_grado';

    protected $fillable = [
        'nombre_grado'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_grado;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_grado'] = $value;
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'id_grado', 'id_grado');
    }
}
