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
        'id_estado_civil',
        'id_grupo_sanguineo',
        'estado_civil',
        'grupo_sanguineo',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'carnet_militar',
        'carnet_cossmil',
        'celular',
        'domicilio',
        'anio_egreso_bachiller',
        'foto_4x4_url',
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
        return $this->attributes['celular'] ?? null;
    }

    public function setCelularAttribute($value)
    {
        $this->attributes['celular'] = $value;
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
        return $this->attributes['foto_4x4_url'] ?? null;
    }

    public function setFoto4x4UrlAttribute($value)
    {
        $this->attributes['foto_4x4_url'] = $value;
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
        if ($this->id_estado_civil) {
            return \DB::table('estados_civil')->where('id_estado_civil', $this->id_estado_civil)->value('nombre_estado_civil') ?? 'Soltero/a';
        }
        return 'Soltero/a';
    }

    public function setEstadoCivilAttribute($value)
    {
        if (!empty($value)) {
            $ec = \DB::table('estados_civil')->where('nombre_estado_civil', $value)->first();
            if (!$ec) {
                $ec = \DB::table('estados_civil')->where('nombre_estado_civil', 'LIKE', '%' . strtok($value, '/') . '%')->first();
            }
            if ($ec) {
                $this->attributes['id_estado_civil'] = $ec->id_estado_civil;
            } else {
                $id = \DB::table('estados_civil')->insertGetId(['nombre_estado_civil' => $value]);
                $this->attributes['id_estado_civil'] = $id;
            }
        } else {
            $this->attributes['id_estado_civil'] = null;
        }
    }

    public function getGrupoSanguineoAttribute()
    {
        if ($this->id_grupo_sanguineo) {
            return \DB::table('grupos_sanguineo')->where('id_grupo_sanguineo', $this->id_grupo_sanguineo)->value('nombre_grupo_sanguineo') ?? 'O+';
        }
        return 'O+';
    }

    public function setGrupoSanguineoAttribute($value)
    {
        if (!empty($value)) {
            $clean = $value;
            if (preg_match('/\(([^)]+)\)/', $value, $m)) {
                $clean = trim($m[1]);
            }
            $gs = \DB::table('grupos_sanguineo')->where('nombre_grupo_sanguineo', $clean)->first();
            if (!$gs) {
                $gs = \DB::table('grupos_sanguineo')->where('nombre_grupo_sanguineo', 'LIKE', '%' . $clean . '%')->first();
            }
            if ($gs) {
                $this->attributes['id_grupo_sanguineo'] = $gs->id_grupo_sanguineo;
            } else {
                $id = \DB::table('grupos_sanguineo')->insertGetId(['nombre_grupo_sanguineo' => $clean]);
                $this->attributes['id_grupo_sanguineo'] = $id;
            }
        } else {
            $this->attributes['id_grupo_sanguineo'] = null;
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
        if (empty($value)) {
            return;
        }

        $parts = explode(' ', trim($value));
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
                ['id_estudiante' => $this->id_estudiante, 'id_responsable' => $id_responsable],
                ['parentesco' => 'Padre/Madre/Tutor']
            );
        }
    }

    public function getCiTutorAttribute()
    {
        $resp = \DB::table('responsables')
            ->join('estudiante_responsable', 'responsables.id_responsable', '=', 'estudiante_responsable.id_responsable')
            ->where('estudiante_responsable.id_estudiante', $this->id_estudiante)
            ->first();
        return $resp ? $resp->ci_responsable : null;
    }

    public function setCiTutorAttribute($value)
    {
        $resp = \DB::table('responsables')
            ->join('estudiante_responsable', 'responsables.id_responsable', '=', 'estudiante_responsable.id_responsable')
            ->where('estudiante_responsable.id_estudiante', $this->id_estudiante)
            ->first();

        if ($resp) {
            \DB::table('responsables')->where('id_responsable', $resp->id_responsable)->update([
                'ci_responsable' => $value
            ]);
        } else {
            $id_responsable = \DB::table('responsables')->insertGetId([
                'nombres_responsable' => 'Tutor',
                'apellido_paterno_responsable' => '',
                'apellido_materno_responsable' => '',
                'ci_responsable' => $value,
                'celular_responsable' => '',
                'direccion_responsable' => ''
            ]);

            \DB::table('estudiante_responsable')->updateOrInsert(
                ['id_estudiante' => $this->id_estudiante, 'id_responsable' => $id_responsable],
                ['parentesco' => 'Padre/Madre/Tutor']
            );
        }
    }

    public function getContactoEmergenciaAttribute()
    {
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
        if (empty($value)) {
            return;
        }

        $val = trim($value);
        \DB::table('contactos_emergencia')->updateOrInsert(
            ['id_estudiante' => $this->id_estudiante],
            [
                'nombre_contacto' => $this->nombre_padres ?: 'Tutor / Emergencia',
                'telefono' => $val,
                'relacion' => 'Padre/Madre/Tutor',
                'es_principal' => true
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
        $this->attributes['documentos_habilitados_hasta'] = empty($value) ? null : $value;
    }
}
