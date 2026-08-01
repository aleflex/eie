<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Aula extends Model
{
    use HasFactory;

    protected $table = 'aulas';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = [
        'nombre_aula',
        'nombre',
        'capacidad'
    ];

    protected $appends = ['nombre', 'id'];

    public function getIdAttribute()
    {
        return $this->id_aula;
    }

    public function getNombreAttribute()
    {
        return $this->nombre_aula;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_aula'] = $value;
    }

    /**
     * Relación uno-a-muchos con Paralelo
     */
    public function paralelos()
    {
        return $this->hasMany(Paralelo::class, 'id_aula', 'id_aula');
    }
}
