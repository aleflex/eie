<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Aula
 * Representa un aula o salón de clase en la institución.
 *
 * Atributos:
 * - nombre: Nombre o identificador del aula (Aula 101, Lab 1, etc.)
 * - capacidad: Número máximo de estudiantes que puede albergar
 */
class Aula extends Model
{
    use HasFactory;

    protected $table = 'aulas';
    public $timestamps = true;  // Usa created_at/updated_at

    protected $fillable = [
        'nombre',           // Nombre o código del aula
        'capacidad'         // Capacidad de personas
    ];

    /**
     * Relación uno-a-muchos con Paralelo
     * Un aula puede ser asignada a múltiples paralelos
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paralelos()
    {
        return $this->hasMany(Paralelo::class);
    }
}
