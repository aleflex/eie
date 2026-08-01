<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'id_rol',
        'correo_institucional',
        'usuario',
        'password',
        'debe_cambiar_password',
        'estado',
        'nombres',
        'apellidos',
        'ci',
        'foto_url',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['id', 'name', 'email', 'foto_url'];

    /**
     * Obtener los atributos que deben ser convertidos.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'debe_cambiar_password' => 'boolean',
        ];
    }

    /**
     * Nombre del campo para la contraseña en la autenticación.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getIdAttribute()
    {
        return $this->id_usuario;
    }

    public function getNameAttribute()
    {
        if ($this->nombres || $this->apellidos) {
            return trim(($this->nombres ?? '') . ' ' . ($this->apellidos ?? ''));
        }
        return 'Administrador';
    }

    public function getEmailAttribute()
    {
        return $this->correo_institucional;
    }

    public function getFotoUrlAttribute()
    {
        return $this->attributes['foto_url'] ?? null;
    }

    /**
     * Genera un nombre de usuario único basado en nombres y apellidos
     * Ej: "Juan Carlos", "Pérez López" -> "juan.perez"
     */
    public static function generateUsername(string $nombres, string $apellidos, ?int $ignoreUserId = null): string
    {
        // Limpiar acentos y caracteres especiales
        $cleanString = function($string) {
            $string = Str::ascii(trim($string));
            $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
            return strtolower($string);
        };

        $nombresArr = explode(' ', $cleanString($nombres));
        $apellidosArr = explode(' ', $cleanString($apellidos));

        $firstName = !empty($nombresArr[0]) ? $nombresArr[0] : 'usuario';
        $firstLastName = !empty($apellidosArr[0]) ? $apellidosArr[0] : 'eie';

        $baseUsername = strtolower($firstName . '.' . $firstLastName);
        $username = $baseUsername;
        $counter = 1;

        while (static::where('usuario', $username)
                    ->when($ignoreUserId, fn($q) => $q->where('id_usuario', '!=', $ignoreUserId))
                    ->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Relación uno-a-uno con Docente
     */
    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Relación uno-a-uno con Estudiante
     */
    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'id_usuario', 'id_usuario');
    }
}
