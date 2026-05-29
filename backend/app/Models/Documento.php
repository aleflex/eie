<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $fillable = [
        'estudiante_id',
        'tipo_documento',
        'nombre_archivo',
        'ruta_archivo',
        'extension',
        'peso'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}

