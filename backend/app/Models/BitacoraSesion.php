<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraSesion extends Model
{
    use HasFactory;

    protected $table = 'bitacora_sesiones';
    protected $primaryKey = 'id_bitacora';

    protected $fillable = [
        'id_usuario',
        'usuario_nombre',
        'ip_address',
        'mac_address',
        'modelo_dispositivo',
        'ubicacion_lugar',
        'plataforma',
        'hora_ingreso',
        'hora_salida',
        'terminos_aceptados'
    ];

    protected $casts = [
        'hora_ingreso' => 'datetime',
        'hora_salida' => 'datetime',
        'terminos_aceptados' => 'boolean'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
