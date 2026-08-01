package com.eie.gestion.data.remote

import com.eie.gestion.data.model.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("api/login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>

    // Estudiantes
    @GET("api/estudiantes")
    suspend fun getEstudiantes(): Response<List<Estudiante>>

    @GET("api/estudiantes/buscar")
    suspend fun buscarEstudiantes(
        @Query("nombre") nombre: String?,
        @Query("ci") ci: String?
    ): Response<List<Estudiante>>

    @GET("api/estudiantes/{id}/historial")
    suspend fun getHistorialEstudiante(
        @Path("id") id: Int
    ): Response<StudentHistoryResponse>

    @POST("api/estudiantes")
    suspend fun crearEstudiante(@Body request: EstudianteCreateRequest): Response<Estudiante>

    @PUT("api/estudiantes/{id}")
    suspend fun actualizarEstudiante(
        @Path("id") id: Int,
        @Body request: EstudianteCreateRequest
    ): Response<GenericResponse>

    @DELETE("api/estudiantes/{id}")
    suspend fun eliminarEstudiante(@Path("id") id: Int): Response<GenericResponse>

    // Cursos & Paralelos
    @GET("api/cursos")
    suspend fun getCursos(): Response<List<Curso>>

    @GET("api/paralelos")
    suspend fun getParalelos(): Response<List<Paralelo>>

    // Inscripciones
    @GET("api/inscripciones")
    suspend fun getInscripciones(): Response<List<Inscripcion>>

    @POST("api/inscripciones")
    suspend fun crearInscripcion(@Body request: InscripcionCreateRequest): Response<GenericResponse>

    // Docente
    @GET("api/docentes/mis-paralelos")
    suspend fun getMisParalelos(
        @Query("user_id") userId: Int
    ): Response<MisParalelosResponse>

    // Notas
    @GET("api/inscripciones/{id}/notas")
    suspend fun getNotas(@Path("id") inscripcionId: Int): Response<List<Nota>>

    @POST("api/inscripciones/{id}/notas")
    suspend fun saveNota(
        @Path("id") inscripcionId: Int,
        @Body request: SaveNotaRequest
    ): Response<GenericResponse>

    // Asistencias
    @GET("api/inscripciones/{id}/asistencias")
    suspend fun getAsistencias(@Path("id") inscripcionId: Int): Response<List<Asistencia>>

    @POST("api/inscripciones/{id}/asistencias")
    suspend fun saveAsistencia(
        @Path("id") inscripcionId: Int,
        @Body request: SaveAsistenciaRequest
    ): Response<GenericResponse>
}
