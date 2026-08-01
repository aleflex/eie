<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'dia_semana',
        'hora_inicio',
        'hora_fin'
    ];

    protected $appends = ['id'];

    public function getIdAttribute()
    {
        $dia = $this->dia_semana;
        $inicio = substr($this->hora_inicio, 0, 5);
        $fin = substr($this->hora_fin, 0, 5);

        $staticHorarios = [
            1 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
            2 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
            3 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
            4 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '16:00', 'hora_fin' => '18:00'],
            5 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '18:30', 'hora_fin' => '20:30'],
            6 => ['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
            7 => ['dia_semana' => 'Sábado', 'hora_inicio' => '08:00', 'hora_fin' => '13:00'],
            8 => ['dia_semana' => 'Sábado', 'hora_inicio' => '14:00', 'hora_fin' => '19:00'],
        ];

        foreach ($staticHorarios as $id => $static) {
            if ($dia === $static['dia_semana'] &&
                $inicio === $static['hora_inicio'] &&
                $fin === $static['hora_fin']) {
                return $id;
            }
        }

        return $this->id_horario;
    }

    public function paralelos()
    {
        return $this->belongsToMany(Paralelo::class, 'horario_paralelo', 'id_horario', 'id_paralelo');
    }
}
