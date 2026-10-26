<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_grado',
        'id_arma',
        'estado_civil',
        'grupo_sanguineo',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'carnet_militar',
        'carnet_cossmil',
        'celular',
        'domicilio',
        'anio_egreso_bachiller',
        'hermanos_inscritos',
        'estado',
        'nombres',
        'apellidos',
        'ci',
        'correo_electronico',
        'grado_academico',
        'arma_especialidad',
        'nombre_padres',
        'ci_tutor',
        'contacto_emergencia',
        'tipo_usuario',
        'documentos_habilitados_hasta'
    ];

    protected $appends = [
        'id',
        'nombres',
        'apellidos',
        'ci',
        'celular',
        'correo_electronico',
        'foto_4x4_url',
        'grado',
        'arma',
        'estado',
        'nombre_padres',
        'ci_tutor',
        'contacto_emergencia',
        'grado_academico',
        'arma_especialidad',
        'tipo_usuario',
        'estado_civil',
        'grupo_sanguineo',
        'hermanos_inscritos'
    ];

    public function getIdAttribute()
    {
        return $this->id_estudiante;
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

    public function getCelularAttribute()
    {
        return $this->attributes['celular'] ?? ($this->user ? $this->user->celular : null);
    }

    public function setCelularAttribute($value)
    {
        $this->attributes['celular'] = $value;
        if ($this->user) {
            $this->user->celular = $value;
            $this->user->saveQuietly();
        }
    }

    public function getDomicilioAttribute()
    {
        return $this->attributes['domicilio'] ?? ($this->user ? $this->user->direccion : null);
    }

    public function setDomicilioAttribute($value)
    {
        $this->attributes['domicilio'] = $value;
        if ($this->user) {
            $this->user->direccion = $value;
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

    public function getFoto4x4UrlAttribute()
    {
        return $this->user ? $this->user->foto_url : null;
    }

    public function setFoto4x4UrlAttribute($value)
    {
        if ($this->user) {
            $this->user->foto_url = $value;
            $this->user->saveQuietly();
        }
    }

    public function getExpedidoAttribute()
    {
        return $this->user ? $this->user->expedido : null;
    }

    public function setExpedidoAttribute($value)
    {
        if ($this->user) {
            $this->user->expedido = $value;
            $this->user->saveQuietly();
        }
    }

    public function gradoRel()
    {
        return $this->belongsTo(Grado::class, 'id_grado', 'id_grado');
    }

    public function armaRel()
    {
        return $this->belongsTo(Arma::class, 'id_arma', 'id_arma');
    }

    public function getGradoAttribute()
    {
        return (object) ['nombre' => $this->gradoRel ? $this->gradoRel->nombre_grado : ''];
    }

    public function getArmaAttribute()
    {
        return (object) ['nombre' => $this->armaRel ? $this->armaRel->nombre_arma : ''];
    }

    public function getGradoAcademicoAttribute()
    {
        return $this->gradoRel ? $this->gradoRel->nombre_grado : '';
    }

    public function setGradoAcademicoAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['id_grado'] = null;
        } else {
            $map = [
                'Sbtte.' => 'Subteniente',
                'Tte.' => 'Teniente',
                'Cap.' => 'Capitán',
                'My.' => 'Mayor',
                'Tcnl.' => 'Teniente Coronel',
                'Cnl.' => 'Coronel',
                'Gral.' => 'General',
            ];
            $nombreGrado = $map[$value] ?? $value;
            $grado = Grado::firstOrCreate(['nombre_grado' => $nombreGrado]);
            $this->attributes['id_grado'] = $grado->id_grado;
        }
    }

    public function getArmaEspecialidadAttribute()
    {
        return $this->armaRel ? $this->armaRel->nombre_arma : '';
    }

    public function setArmaEspecialidadAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['id_arma'] = null;
        } else {
            $arma = Arma::firstOrCreate(['nombre_arma' => $value]);
            $this->attributes['id_arma'] = $arma->id_arma;
        }
    }

    public function getTipoUsuarioAttribute()
    {
        if (!empty($this->attributes['tipo_usuario'])) {
            return $this->attributes['tipo_usuario'];
        }
        if (!empty($this->carnet_militar) || ($this->gradoRel && !in_array($this->gradoRel->nombre_grado, ['Civil', 'Premilitar']))) {
            return 'militar';
        }
        if (!empty($this->carnet_cossmil)) {
            return 'hijo_militar';
        }
        return 'normal';
    }

    public function setTipoUsuarioAttribute($value)
    {
        $this->attributes['tipo_usuario'] = $value;
    }

    public function getEstadoCivilAttribute()
    {
        return $this->user ? $this->user->estado_civil : 'Soltero(a)';
    }

    public function setEstadoCivilAttribute($value)
    {
        if ($this->user) {
            $this->user->estado_civil = $value;
            $this->user->saveQuietly();
        }
    }

    public function getGrupoSanguineoAttribute()
    {
        return $this->user ? $this->user->grupo_sanguineo : 'O+';
    }

    public function setGrupoSanguineoAttribute($value)
    {
        if ($this->user) {
            $this->user->grupo_sanguineo = $value;
            $this->user->saveQuietly();
        }
    }

    public function getHermanosInscritosAttribute()
    {
        return $this->attributes['hermanos_inscritos'] ?? 0;
    }

    public function setHermanosInscritosAttribute($value)
    {
        $this->attributes['hermanos_inscritos'] = intval($value);
    }

    public function getNombrePadresAttribute()
    {
        $resp = \DB::table('responsables')
            ->join('estudiante_responsable', 'responsables.id_responsable', '=', 'estudiante_responsable.id_responsable')
            ->where('estudiante_responsable.id_estudiante', $this->id_estudiante)
            ->first();
        if ($resp) {
            return trim(($resp->nombres_responsable ?? '') . ' ' . ($resp->apellido_paterno_responsable ?? '') . ' ' . ($resp->apellido_materno_responsable ?? ''));
        }
        return null;
    }

    public function setNombrePadresAttribute($value)
    {
        $val = !empty($value) ? trim((string)$value) : '';
        if ($val === '' || empty($this->id_estudiante)) {
            return;
        }

        $parts = explode(' ', $val);
        $nombres_resp = $parts[0];
        $paterno_resp = '';
        $materno_resp = '';

        if (count($parts) > 1) {
            $paterno_resp = $parts[1];
        }
        if (count($parts) > 2) {
            $materno_resp = implode(' ', array_slice($parts, 2));
        }

        $resp = \DB::table('responsables')
            ->join('estudiante_responsable', 'responsables.id_responsable', '=', 'estudiante_responsable.id_responsable')
            ->where('estudiante_responsable.id_estudiante', $this->id_estudiante)
            ->first();

        if ($resp) {
            \DB::table('responsables')->where('id_responsable', $resp->id_responsable)->update([
                'nombres_responsable' => $nombres_resp,
                'apellido_paterno_responsable' => $paterno_resp,
                'apellido_materno_responsable' => $materno_resp,
            ]);
        } else {
            $id_responsable = \DB::table('responsables')->insertGetId([
                'nombres_responsable' => $nombres_resp,
                'apellido_paterno_responsable' => $paterno_resp,
                'apellido_materno_responsable' => $materno_resp,
                'ci_responsable' => null,
                'celular_responsable' => '',
                'direccion_responsable' => ''
            ]);

            \DB::table('estudiante_responsable')->updateOrInsert(
                ['id_estudiante' => $this->id_estudiante],
                ['id_responsable' => $id_responsable, 'parentesco' => 'Padre/Madre/Tutor']
            );
        }
    }

    public function getCiTutorAttribute()
    {
        if (empty($this->id_estudiante)) {
            return null;
        }
        $resp = \DB::table('responsables')
            ->join('estudiante_responsable', 'responsables.id_responsable', '=', 'estudiante_responsable.id_responsable')
            ->where('estudiante_responsable.id_estudiante', $this->id_estudiante)
            ->first();
        return $resp ? $resp->ci_responsable : null;
    }

    public function setCiTutorAttribute($value)
    {
        if (empty($this->id_estudiante)) {
            return;
        }

        $cleanCi = !empty($value) ? trim((string)$value) : null;
        if ($cleanCi === '') {
            $cleanCi = null;
        }

        // Buscar si ya existe un responsable con este CI en la base de datos
        $existingWithCi = $cleanCi ? \DB::table('responsables')->where('ci_responsable', $cleanCi)->first() : null;

        $respLink = \DB::table('estudiante_responsable')
            ->where('id_estudiante', $this->id_estudiante)
            ->first();

        if ($existingWithCi) {
            // Si el tutor ya existe con este CI (por ejemplo, tutor de otro hermano/estudiante)
            if ($respLink) {
                if ($respLink->id_responsable != $existingWithCi->id_responsable) {
                    \DB::table('estudiante_responsable')
                        ->where('id_estudiante_responsable', $respLink->id_estudiante_responsable)
                        ->update(['id_responsable' => $existingWithCi->id_responsable]);
                }
            } else {
                \DB::table('estudiante_responsable')->insert([
                    'id_estudiante' => $this->id_estudiante,
                    'id_responsable' => $existingWithCi->id_responsable,
                    'parentesco' => 'Padre/Madre/Tutor'
                ]);
            }
            return;
        }

        // Si no existe un responsable con este CI
        if ($respLink) {
            \DB::table('responsables')->where('id_responsable', $respLink->id_responsable)->update([
                'ci_responsable' => $cleanCi
            ]);
        } elseif ($cleanCi !== null) {
            $id_responsable = \DB::table('responsables')->insertGetId([
                'nombres_responsable' => 'Tutor',
                'apellido_paterno_responsable' => '',
                'apellido_materno_responsable' => '',
                'ci_responsable' => $cleanCi,
                'celular_responsable' => '',
                'direccion_responsable' => ''
            ]);

            \DB::table('estudiante_responsable')->insert([
                'id_estudiante' => $this->id_estudiante,
                'id_responsable' => $id_responsable,
                'parentesco' => 'Padre/Madre/Tutor'
            ]);
        }
    }

    public function getContactoEmergenciaAttribute()
    {
        if (empty($this->id_estudiante)) {
            return null;
        }
        $cont = \DB::table('contactos_emergencia')
            ->where('id_estudiante', $this->id_estudiante)
            ->first();
        if ($cont) {
            return !empty($cont->telefono) ? $cont->telefono : $cont->nombre_contacto;
        }
        return null;
    }

    public function setContactoEmergenciaAttribute($value)
    {
        if (empty($value) || empty($this->id_estudiante)) {
            return;
        }

        $val = trim($value);
        \DB::table('contactos_emergencia')->updateOrInsert(
            ['id_estudiante' => $this->id_estudiante, 'es_principal' => 1],
            [
                'nombre_contacto' => $this->nombre_padres ?: 'Padre/Madre/Tutor',
                'telefono' => $val,
                'relacion' => 'Padre/Madre/Tutor',
                'es_principal' => 1
            ]
        );
    }

    /**
     * Relación muchos-a-uno con Usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Relación uno-a-muchos con Inscripcion
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_estudiante', 'id_estudiante');
    }

    /**
     * Relación uno-a-muchos con Documento
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_estudiante', 'id_estudiante');
    }

    public function getEstadoAttribute()
    {
        return $this->user->estado ?? 'ACTIVO';
    }

    public function setEstadoAttribute($value)
    {
        if ($this->user) {
            $this->user->estado = strtoupper($value);
            $this->user->save();
        }
    }

    public function setDocumentosHabilitadosHastaAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['documentos_habilitados_hasta'] = null;
        } else {
            try {
                $this->attributes['documentos_habilitados_hasta'] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $clean = str_replace('T', ' ', trim((string)$value));
                $this->attributes['documentos_habilitados_hasta'] = strlen($clean) === 16 ? ($clean . ':00') : $clean;
            }
        }
    }
}