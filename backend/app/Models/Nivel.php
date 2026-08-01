<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    use HasFactory;

    protected $table = 'niveles';
    protected $primaryKey = 'id_nivel';
    public $timestamps = false;

    protected $fillable = [
        'nombre_nivel',
        'nombre',
        'book_inicio',
        'book_fin'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        return $this->nombre_nivel;
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre_nivel'] = $value;
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'id_nivel', 'id_nivel');
    }
}
