<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Docente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'docentes';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'especialidad',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'id_tipo_contrato',
        'telefono',
        'fecha_contrato',
        'estado',
        'motivo_baja',
        'fecha_baja'
    ];

    protected $appends = [
        'id',
        'nombres',
        'apellidos',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'telefono',
        'correo_electronico',
        'tipo_contrato',
        'fecha_inicio_contrato',
        'fecha_fin_contrato',
        'foto_url',
        'expedido'
    ];

    public function getIdAttribute()
    {
        return $this->id_docente;
    }

    public function getExpedidoAttribute()
    {
        return $this->user ? $this->user->expedido : null;
    }

    public function setExpedidoAttribute($value)
    {
        if ($this->user) {
            $this->user->expedido = $value;
            $this->user->save();
        }
    }

    public function getFotoUrlAttribute()
    {
        return $this->user ? $this->user->foto_url : null;
    }

    public function setFotoUrlAttribute($value)
    {
        if ($this->user) {
            $this->user->foto_url = $value;
            $this->user->saveQuietly();
        }
    }

    public function getNombresAttribute()
    {
        return $this->user ? $this->user->nombres : null;
    }

    public function setNombresAttribute($value)
    {
        if ($this->user) {
            $this->user->nombres = $value;
            $this->user->save();
        }
    }

    public function getApellidosAttribute()
    {
        return $this->user ? $this->user->apellidos : null;
    }

    public function setApellidosAttribute($value)
    {
        if ($this->user) {
            $this->user->apellidos = $value;
            $this->user->save();
        }
    }

    public function getApellidoPaternoAttribute()
    {
        $apellidos = trim($this->user ? ($this->user->apellidos ?? '') : '');
        if (!$apellidos) return '';
        $parts = preg_split('/\s+/', $apellidos);
        return $parts[0] ?? '';
    }

    public function setApellidoPaternoAttribute($value)
    {
        $pat = trim((string)$value);
        $mat = $this->apellido_materno;
        $this->apellidos = trim($pat . ' ' . $mat);
    }

    public function getApellidoMaternoAttribute()
    {
        $apellidos = trim($this->user ? ($this->user->apellidos ?? '') : '');
        if (!$apellidos) return '';
        $parts = preg_split('/\s+/', $apellidos);
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    }

    public function setApellidoMaternoAttribute($value)
    {
        $pat = $this->apellido_paterno;
        $mat = trim((string)$value);
        $this->apellidos = trim($pat . ' ' . $mat);
    }

    public function getCiAttribute()
    {
        return $this->user ? $this->user->ci : null;
    }

    public function setCiAttribute($value)
    {
        if ($this->user) {
            $this->user->ci = $value;
            $this->user->save();
        }
    }

    public function getTelefonoAttribute()
    {
        return $this->attributes['telefono'] ?? ($this->user ? ($this->user->telefono ?: $this->user->celular) : null);
    }

    public function setTelefonoAttribute($value)
    {
        $this->attributes['telefono'] = $value;
        if ($this->user) {
            $this->user->telefono = $value;
            $this->user->celular = $value;
            $this->user->saveQuietly();
        }
    }

    public function getCorreoElectronicoAttribute()
    {
        return $this->user ? $this->user->correo_institucional : null;
    }

    public function setCorreoElectronicoAttribute($value)
    {
        if ($this->user) {
            $this->user->correo_institucional = $value;
            $this->user->save();
        }
    }

    public function getTipoContratoAttribute()
    {
        if (!empty($this->id_tipo_contrato)) {
            $tipo = \DB::table('tipos_contrato_docente')->where('id_tipo_contrato', $this->id_tipo_contrato)->first();
            if ($tipo) {
                return $tipo->nombre_tipo_contrato === 'Titular' ? 'Ítem' : ($tipo->nombre_tipo_contrato === 'Contratado' ? 'Contrato' : $tipo->nombre_tipo_contrato);
            }
        }
        return 'Contrato';
    }

    public function getFechaInicioContratoAttribute()
    {
        return $this->attributes['fecha_contrato'] ?? null;
    }

    public function getFechaFinContratoAttribute()
    {
        return $this->attributes['fecha_contrato'] ?? null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function paralelos()
    {
        return $this->belongsToMany(Paralelo::class, 'docente_paralelo', 'id_docente', 'id_paralelo');
    }
}
