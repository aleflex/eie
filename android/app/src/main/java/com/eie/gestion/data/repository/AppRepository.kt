package com.eie.gestion.data.repository

import com.eie.gestion.data.model.*
import com.eie.gestion.data.remote.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class AppRepository(private val sessionManager: SessionManager) {

    private val api get() = RetrofitClient.getApiService(sessionManager)

    suspend fun login(request: LoginRequest): Result<AuthResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.login(request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                val errorMsg = response.errorBody()?.string() ?: ""
                Result.failure(Exception(if (errorMsg.contains("message")) errorMsg else "Credenciales incorrectas o servidor inaccesible"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getEstudiantes(): Result<List<Estudiante>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getEstudiantes()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar estudiantes: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun buscarEstudiantes(nombre: String?, ci: String?): Result<List<Estudiante>> = withContext(Dispatchers.IO) {
        try {
            val response = api.buscarEstudiantes(nombre, ci)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al buscar estudiantes: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getHistorialEstudiante(id: Int): Result<StudentHistoryResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.getHistorialEstudiante(id)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al obtener historial de estudiante: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun crearEstudiante(request: EstudianteCreateRequest): Result<Estudiante> = withContext(Dispatchers.IO) {
        try {
            val response = api.crearEstudiante(request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al crear estudiante: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun actualizarEstudiante(id: Int, request: EstudianteCreateRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.actualizarEstudiante(id, request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al actualizar estudiante: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun eliminarEstudiante(id: Int): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.eliminarEstudiante(id)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al eliminar estudiante: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getCursos(): Result<List<Curso>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getCursos()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar cursos: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getParalelos(): Result<List<Paralelo>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getParalelos()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar paralelos: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getInscripciones(): Result<List<Inscripcion>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getInscripciones()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar inscripciones: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun crearInscripcion(request: InscripcionCreateRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.crearInscripcion(request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al crear inscripción: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMisParalelos(userId: Int): Result<MisParalelosResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.getMisParalelos(userId)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar paralelos asignados: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getNotas(inscripcionId: Int): Result<List<Nota>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getNotas(inscripcionId)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar notas: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveNota(inscripcionId: Int, request: SaveNotaRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.saveNota(inscripcionId, request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al guardar nota: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAsistencias(inscripcionId: Int): Result<List<Asistencia>> = withContext(Dispatchers.IO) {
        try {
            val response = api.getAsistencias(inscripcionId)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al cargar asistencias: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveAsistencia(inscripcionId: Int, request: SaveAsistenciaRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = api.saveAsistencia(inscripcionId, request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Error al guardar asistencia: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
