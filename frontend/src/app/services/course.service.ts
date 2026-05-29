import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Interfaz para representar un Curso/Paralelo
 */
export interface Curso {
  id?: number;
  docente_id?: number;
  idioma: string;           // Idioma que se imparte
  nivel: string;            // Nivel del curso
  modalidad: string;        // Modalidad (presencial, virtual, híbrida)
  horario: string;          // Horario del curso
  cupo_minimo: number;      // Cupo mínimo de estudiantes
  cupo_maximo: number;      // Cupo máximo de estudiantes
  inscripciones_count?: number;  // Cantidad de inscripciones actuales
}

/**
 * Servicio de Cursos
 * Gestiona todas las operaciones CRUD de cursos/paralelos, incluyendo:
 * - Obtención de listados de cursos
 * - Creación de nuevos cursos
 * - Actualización de datos de cursos
 * - Eliminación de cursos
 */
@Injectable({
  providedIn: 'root'
})
export class CourseService {
  private apiUrl = `${environment.apiUrl}/api/cursos`;

  constructor(private http: HttpClient) { }

  /**
   * Obtiene el listado de todos los cursos disponibles
   * @returns Observable con el array de cursos
   */
  obtenerCursos(): Observable<Curso[]> {
    return this.http.get<Curso[]>(this.apiUrl);
  }

  /**
   * Obtiene los datos de un curso específico
   * @param id - ID del curso a obtener
   * @returns Observable con los datos del curso
   */
  obtenerCurso(id: number): Observable<Curso> {
    return this.http.get<Curso>(`${this.apiUrl}/${id}`);
  }

  /**
   * Crea un nuevo curso en el sistema
   * @param curso - Datos del curso a crear
   * @returns Observable con la respuesta del servidor
   */
  crearCurso(curso: Curso): Observable<any> {
    return this.http.post(this.apiUrl, curso);
  }

  /**
   * Actualiza los datos de un curso existente
   * @param id - ID del curso a actualizar
   * @param curso - Nuevos datos del curso
   * @returns Observable con la respuesta del servidor
   */
  actualizarCurso(id: number, curso: Curso): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, curso);
  }

  /**
   * Elimina un curso del sistema
   * @param id - ID del curso a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarCurso(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  // Métodos heredados para compatibilidad
  getCourses(): Observable<Curso[]> {
    return this.obtenerCursos();
  }

  getCourse(id: number): Observable<Curso> {
    return this.obtenerCurso(id);
  }

  createCourse(course: Curso): Observable<any> {
    return this.crearCurso(course);
  }

  updateCourse(id: number, course: Curso): Observable<any> {
    return this.actualizarCurso(id, course);
  }

  deleteCourse(id: number): Observable<any> {
    return this.eliminarCurso(id);
  }
}
