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
        'correo_personal',
        'usuario',
        'password',
        'debe_cambiar_password',
        'estado',
        'nombres',
        'apellidos',
        'ci',
        'expedido',
        'celular',
        'telefono',
        'direccion',
        'estado_civil',
        'grupo_sanguineo',
        'foto_url',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['id', 'name', 'email', 'correo_electronico', 'correo_personal', 'foto_url', 'estado_civil', 'grupo_sanguineo', 'apellido_paterno', 'apellido_materno'];

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
        $nombres = trim($this->nombres ?? '');
        $apellidos = trim($this->apellidos ?? '');

        if ($nombres && $apellidos) {
            if (str_ends_with(strtolower($nombres), strtolower($apellidos))) {
                return $nombres;
            }
            return trim($nombres . ' ' . $apellidos);
        }

        if ($nombres) return $nombres;
        if ($apellidos) return $apellidos;

        return 'Administrador';
    }

    public function getApellidoPaternoAttribute()
    {
        $apellidos = trim($this->apellidos ?? '');
        if (!$apellidos) return '';
        $parts = preg_split('/\s+/', $apellidos);
        return $parts[0] ?? '';
    }

    public function getApellidoMaternoAttribute()
    {
        $apellidos = trim($this->apellidos ?? '');
        if (!$apellidos) return '';
        $parts = preg_split('/\s+/', $apellidos);
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    }

    public function getEmailAttribute()
    {
        return $this->correo_institucional;
    }

    public function getCorreoElectronicoAttribute()
    {
        return $this->correo_institucional;
    }

    public function getFotoUrlAttribute()
    {
        return $this->attributes['foto_url'] ?? null;
    }

    public function getEstadoCivilAttribute()
    {
        return $this->attributes['estado_civil'] ?? 'Soltero(a)';
    }

    public function setEstadoCivilAttribute($value)
    {
        $this->attributes['estado_civil'] = !empty($value) ? trim($value) : 'Soltero(a)';
    }

    public function getGrupoSanguineoAttribute()
    {
        return $this->attributes['grupo_sanguineo'] ?? 'O+';
    }

    public function setGrupoSanguineoAttribute($value)
    {
        if (!empty($value)) {
            $clean = $value;
            if (preg_match('/\(([^)]+)\)/', $value, $m)) {
                $clean = trim($m[1]);
            }
            $this->attributes['grupo_sanguineo'] = trim($clean);
        } else {
            $this->attributes['grupo_sanguineo'] = 'O+';
        }
    }

    public function getCelularAttribute()
    {
        return $this->attributes['celular'] ?? null;
    }

    public function setCelularAttribute($value)
    {
        $this->attributes['celular'] = $value;
    }

    public function getTelefonoAttribute()
    {
        return $this->attributes['telefono'] ?? null;
    }

    public function setTelefonoAttribute($value)
    {
        $this->attributes['telefono'] = $value;
    }

    public function getDireccionAttribute()
    {
        return $this->attributes['direccion'] ?? null;
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = $value;
    }

    /**
     * Genera un nombre de usuario único y corto basado en inicial del primer nombre + primer apellido
     * Ej: "Govinda Alejandro", "Maldonado Arredondo" -> "gmaldonado"
     */
    public static function generateUsername(string $nombres, string $apellidos, ?int $ignoreUserId = null): string
    {
        // Limpiar acentos y caracteres especiales
        $cleanString = function($string) {
            $string = Str::ascii(trim((string)$string));
            $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
            return strtolower($string);
        };

        $nombresArr = array_values(array_filter(explode(' ', $cleanString($nombres))));
        $apellidosArr = array_values(array_filter(explode(' ', $cleanString($apellidos))));

        $firstName = !empty($nombresArr[0]) ? $nombresArr[0] : 'usuario';
        $firstLastName = !empty($apellidosArr[0]) ? $apellidosArr[0] : 'eie';

        // Inicial del primer nombre + primer apellido (ej: gmaldonado)
        $firstInitial = substr($firstName, 0, 1);
        $baseUsername = strtolower($firstInitial . $firstLastName);
        $username = $baseUsername;
        $counter = 2;

        while (static::where('usuario', $username)
                    ->when($ignoreUserId, fn($q) => $q->where('id_usuario', '!=', $ignoreUserId))
                    ->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Genera un correo institucional único basado en el rol y el usuario
     * Estudiante: usuario@est.edu.eie.bo
     * Docente: usuario@doc.edu.eie.bo
     * Admin: usuario@eie.edu.bo
     */
    public static function generateInstitutionalEmail(string $username, int $roleId = 2, ?int $ignoreUserId = null): string
    {
        $domain = match ($roleId) {
            3 => 'doc.edu.eie.bo',
            2 => 'est.edu.eie.bo',
            default => 'eie.edu.bo',
        };

        $baseEmail = strtolower($username) . '@' . $domain;
        $email = $baseEmail;
        $counter = 2;

        while (static::where('correo_institucional', $email)
                    ->when($ignoreUserId, fn($q) => $q->where('id_usuario', '!=', $ignoreUserId))
                    ->exists()) {
            $email = strtolower($username) . $counter . '@' . $domain;
            $counter++;
        }

        return $email;
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
