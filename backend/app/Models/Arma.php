<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arma extends Model
{
    use HasFactory;

    protected $table = 'armas';
    protected $primaryKey = 'id_arma';

    protected $fillable = [
        'nombre_arma'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_arma;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_arma'] = $value;
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'id_arma', 'id_arma');
    }
}
