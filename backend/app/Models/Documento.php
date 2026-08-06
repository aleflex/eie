<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';
    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'id_estudiante',
        'tipo_documento',
        'nombre_archivo',
        'ruta_archivo',
        'archivo',
        'extension',
        'peso'
    ];

    protected $appends = ['archivo'];

    public function getArchivoAttribute()
    {
        return $this->attributes['ruta_archivo'] ?? null;
    }

    public function setArchivoAttribute($value)
    {
        $this->attributes['ruta_archivo'] = $value;
        if (empty($this->attributes['nombre_archivo'])) {
            $this->attributes['nombre_archivo'] = basename($value);
        }
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }
}
