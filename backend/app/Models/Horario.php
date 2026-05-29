<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = ['dia_semana', 'hora_inicio', 'hora_fin'];

    public function paralelos()
    {
        return $this->belongsToMany(Paralelo::class, 'horario_paralelo');
    }
}
