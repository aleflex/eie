<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';
    protected $primaryKey = 'id_nota';
    public $timestamps = false;

    protected $fillable = [
        'id_inscripcion',
        'nota',
        'periodo',
        'observacion'
    ];

    protected $appends = ['descripcion'];

    public function getDescripcionAttribute()
    {
        return $this->attributes['periodo'] ?? null;
    }

    /**
     * Relación muchos-a-uno con Inscripcion
     */
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }
}
