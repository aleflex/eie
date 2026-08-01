package com.eie.gestion.data.model

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    @SerializedName("email") val email: String,
    @SerializedName("password") val password: String
)

data class AuthResponse(
    @SerializedName("message") val message: String,
    @SerializedName("token") val token: String,
    @SerializedName("user") val user: User
)

data class User(
    @SerializedName("id_usuario") val idUsuario: Int,
    @SerializedName("id_rol") val idRol: Int,
    @SerializedName("correo_institucional") val correoInstitucional: String,
    @SerializedName("nombres") val nombres: String?,
    @SerializedName("apellidos") val apellidos: String?,
    @SerializedName("ci") val ci: String?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("foto_url") val fotoUrl: String?,
    @SerializedName("rol") val rol: String?,
    @SerializedName("token") val token: String?,
    @SerializedName("docente_id") val docenteId: Int?,
    @SerializedName("estudiante_id") val estudianteId: Int?
)

data class GradoInfo(
    @SerializedName("nombre") val nombre: String?
)

data class ArmaInfo(
    @SerializedName("nombre") val nombre: String?
)

data class Estudiante(
    @SerializedName("id_estudiante") val idEstudiante: Int,
    @SerializedName("id_usuario") val idUsuario: Int?,
    @SerializedName("fecha_nacimiento") val fechaNacimiento: String?,
    @SerializedName("lugar_nacimiento") val lugarNacimiento: String?,
    @SerializedName("carnet_militar") val carnetMilitar: String?,
    @SerializedName("carnet_cossmil") val carnetCossmil: String?,
    @SerializedName("celular") val celular: String?,
    @SerializedName("domicilio") val domicilio: String?,
    @SerializedName("nombres") val nombres: String?,
    @SerializedName("apellidos") val apellidos: String?,
    @SerializedName("ci") val ci: String?,
    @SerializedName("correo_electronico") val correoElectronico: String?,
    @SerializedName("foto_4x4_url") val foto4x4Url: String?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("grado") val grado: GradoInfo?,
    @SerializedName("arma") val arma: ArmaInfo?,
    @SerializedName("user") val user: User?,
    @SerializedName("inscripciones") val inscripciones: List<Inscripcion>?,
    @SerializedName("tipo_usuario") val tipoUsuario: String?,
    @SerializedName("grado_academico") val gradoAcademico: String?,
    @SerializedName("arma_especialidad") val armaEspecialidad: String?,
    @SerializedName("estado_civil") val estadoCivil: String?,
    @SerializedName("grupo_sanguineo") val grupoSanguineo: String?,
    @SerializedName("nombre_padres") val nombrePadres: String?,
    @SerializedName("ci_tutor") val ciTutor: String?,
    @SerializedName("contacto_emergencia") val contactoEmergencia: String?,
    @SerializedName("hermanos_inscritos") val hermanosInscritos: Int?
)

data class EstudianteCreateRequest(
    @SerializedName("nombres") val nombres: String,
    @SerializedName("apellidos") val apellidos: String,
    @SerializedName("ci") val ci: String,
    @SerializedName("correo_electronico") val correoElectronico: String,
    @SerializedName("celular") val celular: String?,
    @SerializedName("domicilio") val domicilio: String?,
    @SerializedName("fecha_nacimiento") val fechaNacimiento: String?,
    @SerializedName("lugar_nacimiento") val lugarNacimiento: String?,
    @SerializedName("estado") val estado: String = "ACTIVO"
)

data class Curso(
    @SerializedName("id_curso") val idCurso: Int,
    @SerializedName("nombre_curso") val nombreCurso: String,
    @SerializedName("idioma") val idioma: String?,
    @SerializedName("nivel") val nivel: String?,
    @SerializedName("modalidad") val modalidad: String?,
    @SerializedName("cupo_minimo") val cupoMinimo: Int?,
    @SerializedName("cupo_maximo") val cupoMaximo: Int?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("paralelos") val paralelos: List<Paralelo>?
)

data class Aula(
    @SerializedName("id_aula") val idAula: Int,
    @SerializedName("nombre_aula") val nombreAula: String,
    @SerializedName("capacidad") val capacidad: Int?
)

data class Paralelo(
    @SerializedName("id_paralelo") val idParalelo: Int,
    @SerializedName("id_curso") val idCurso: Int,
    @SerializedName("id_aula") val idAula: Int?,
    @SerializedName("nombre_paralelo") val nombreParalelo: String,
    @SerializedName("nombre") val nombre: String?,
    @SerializedName("curso") val curso: Curso?,
    @SerializedName("aula") val aula: Aula?,
    @SerializedName("inscripciones") val inscripciones: List<Inscripcion>?
)

data class DocenteInfo(
    @SerializedName("id_docente") val idDocente: Int,
    @SerializedName("id_usuario") val idUsuario: Int,
    @SerializedName("especialidad") val especialidad: String?,
    @SerializedName("telefono") val telefono: String?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("user") val user: User?
)

data class MisParalelosResponse(
    @SerializedName("docente") val docente: DocenteInfo?,
    @SerializedName("paralelos") val paralelos: List<Paralelo>
)

data class Inscripcion(
    @SerializedName("id_inscripcion") val idInscripcion: Int,
    @SerializedName("id_estudiante") val idEstudiante: Int,
    @SerializedName("id_paralelo") val idParalelo: Int,
    @SerializedName("id_curso") val idCurso: Int,
    @SerializedName("fecha_registro") val fechaRegistro: String?,
    @SerializedName("fecha_inscripcion") val fechaInscripcion: String?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("curso") val curso: Curso?,
    @SerializedName("paralelo") val paralelo: Paralelo?,
    @SerializedName("estudiante") val estudiante: Estudiante?,
    @SerializedName("notas") val flatNotas: List<Nota>?,
    @SerializedName("asistencias") val asistencias: List<Asistencia>?
)

data class InscripcionCreateRequest(
    @SerializedName("id_estudiante") val idEstudiante: Int,
    @SerializedName("id_paralelo") val idParalelo: Int,
    @SerializedName("id_curso") val idCurso: Int
)

data class Nota(
    @SerializedName("id_nota") val idNota: Int?,
    @SerializedName("id_inscripcion") val idInscripcion: Int?,
    @SerializedName("nota") val nota: Double?,
    @SerializedName("periodo") val periodo: String?, // "Parcial 1", "Parcial 2", "Examen Final"
    @SerializedName("observacion") val observacion: String?
)

data class SaveNotaRequest(
    @SerializedName("nota") val nota: Double,
    @SerializedName("periodo") val periodo: String,
    @SerializedName("observacion") val observacion: String?
)

data class Asistencia(
    @SerializedName("id_asistencia") val idAsistencia: Int?,
    @SerializedName("id_inscripcion") val idInscripcion: Int?,
    @SerializedName("fecha") val fecha: String,
    @SerializedName("estado") val estado: String, // presente, ausente, tardanza, justificado
    @SerializedName("observacion") val observacion: String?
)

data class SaveAsistenciaRequest(
    @SerializedName("fecha") val fecha: String,
    @SerializedName("estado") val estado: String,
    @SerializedName("observacion") val observacion: String?
)

data class EstudianteProfile(
    @SerializedName("id_estudiante") val idEstudiante: Int,
    @SerializedName("nombres") val nombres: String?,
    @SerializedName("apellidos") val apellidos: String?,
    @SerializedName("ci") val ci: String?
)

data class StudentHistoryResponse(
    @SerializedName("estudiante") val estudiante: EstudianteProfile?,
    @SerializedName("historial") val historial: List<Inscripcion>
)

data class GenericResponse(
    @SerializedName("message") val message: String
)
