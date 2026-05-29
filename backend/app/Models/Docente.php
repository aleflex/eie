<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Docente
 * Representa un docente/instructor del sistema.
 * Almacena información personal, profesional y contratación de docentes.
 *
 * Atributos:
 * - user_id: ID del usuario autenticado asociado
 * - nombres: Nombres del docente
 * - apellidos: Apellidos del docente
 * - correo_electronico: Email del docente
 * - foto_url: URL de la foto de perfil
 * - ci: Cédula de identidad
 * - especialidad: Área de especialización (idiomas, método de enseñanza, etc.)
 * - telefono: Número de teléfono de contacto
 * - estado: Estado actual (activo, inactivo, suspendido)
 * - tipo_contrato: Tipo de contrato (Permanente, Contrato, Temporal)
 * - fecha_contrato: Fecha de expiración del contrato (si aplica)
 */
class Docente extends Model
{
    use HasFactory;

    protected $table = 'docentes';
    public $timestamps = true;  // Usa created_at/updated_at por defecto

    protected $fillable = [
        'user_id',                // ID del usuario en la tabla users
        'nombres',                // Nombre(s) del docente
        'apellidos',              // Apellido(s) del docente
        'correo_electronico',     // Email del docente
        'foto_url',               // URL de la foto de perfil
        'ci',                     // Cédula de identidad
        'especialidad',           // Área de especialidad
        'telefono',               // Teléfono de contacto
        'estado',                 // Estado (activo/inactivo)
        'tipo_contrato',          // Tipo de contrato
        'fecha_contrato'          // Fecha de expiración del contrato
    ];

    /**
     * Relación muchos-a-uno con User
     * Un docente pertenece a una cuenta de usuario
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación muchos-a-muchos con Paralelo
     * Un docente puede tener muchos paralelos asignados
     * Un paralelo puede tener muchos docentes
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function paralelos()
    {
        return $this->belongsToMany(Paralelo::class, 'docente_paralelo');
    }
}
