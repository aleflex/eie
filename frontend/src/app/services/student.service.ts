import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Estudiantes
 * Gestiona todas las operaciones CRUD de estudiantes, incluyendo:
 * - Obtención de listados
 * - Creación, edición y eliminación de estudiantes
 * - Gestión de documentos asociados
 * - Búsqueda y filtrado de estudiantes
 */
@Injectable({
  providedIn: 'root'
})
export class StudentService {
  private apiUrl = `${environment.apiUrl}/api/estudiantes`;

  constructor(private http: HttpClient) { }

  /**
   * Obtiene el listado de todos los estudiantes
   * @returns Observable con el array de estudiantes
   */
  obtenerEstudiantes(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
  }

  /**
   * Crea un nuevo estudiante en el sistema
   * @param datos - Datos del estudiante a crear
   * @returns Observable con la respuesta del servidor
   */
  crearEstudiante(datos: any): Observable<any> {
    return this.http.post<any>(this.apiUrl, datos);
  }

  /**
   * Obtiene los datos de un estudiante específico
   * @param id - ID del estudiante
   * @returns Observable con los datos del estudiante
   */
  obtenerEstudiante(id: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/${id}`);
  }

  /**
   * Actualiza los datos de un estudiante
   * @param id - ID del estudiante a actualizar
   * @param datos - Nuevos datos del estudiante
   * @returns Observable con la respuesta del servidor
   */
  actualizarEstudiante(id: number, datos: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, datos);
  }

  /**
   * Actualiza un estudiante incluyendo una foto de perfil
   * @param id - ID del estudiante
   * @param datosFormulario - FormData con los datos y la imagen
   * @returns Observable con la respuesta del servidor
   */
  actualizarEstudianteConFoto(id: number, datosFormulario: FormData): Observable<any> {
    // Agregar método PUT para compatibilidad con Laravel
    datosFormulario.append('_method', 'PUT');
    return this.http.post(`${this.apiUrl}/${id}`, datosFormulario);
  }

  /**
   * Elimina un estudiante del sistema
   * @param id - ID del estudiante a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarEstudiante(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  /**
   * Obtiene el historial de inscripciones de un estudiante
   * @param id - ID del estudiante
   * @returns Observable con el historial del estudiante
   */
  obtenerHistorial(id: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/${id}/historial`);
  }

  /**
   * Busca estudiantes por un término específico
   * @param termino - Término de búsqueda
   * @param tipo - Tipo de búsqueda (nombre, ci, email, etc.)
   * @returns Observable con el array de estudiantes que coinciden
   */
  buscarEstudiantes(termino: string, tipo: string = 'nombre'): Observable<any[]> {
    const url = `${this.apiUrl}/buscar?${tipo}=${termino}`;
    return this.http.get<any[]>(url);
  }

  // ============ Gestión de Documentos ============

  /**
   * Obtiene los documentos asociados a un estudiante
   * @param idEstudiante - ID del estudiante
   * @returns Observable con el array de documentos
   */
  obtenerDocumentos(idEstudiante: number): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/api/estudiantes/${idEstudiante}/documentos`);
  }

  /**
   * Carga un nuevo documento para un estudiante
   * @param datosFormulario - FormData con el archivo y datos del documento
   * @returns Observable con la respuesta del servidor
   */
  cargarDocumento(datosFormulario: FormData): Observable<any> {
    return this.http.post(`${environment.apiUrl}/api/documentos/subir`, datosFormulario);
  }

  /**
   * Elimina un documento específico
   * @param idDocumento - ID del documento a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarDocumento(idDocumento: number): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/api/documentos/${idDocumento}`);
  }

  // Métodos heredados para compatibilidad
  getStudents(): Observable<any[]> {
    return this.obtenerEstudiantes();
  }

  createStudent(data: any): Observable<any> {
    return this.crearEstudiante(data);
  }

  getStudent(id: number): Observable<any> {
    return this.obtenerEstudiante(id);
  }

  updateStudent(id: number, data: any): Observable<any> {
    return this.actualizarEstudiante(id, data);
  }

  updateStudentWithPhoto(id: number, formData: FormData): Observable<any> {
    return this.actualizarEstudianteConFoto(id, formData);
  }

  deleteStudent(id: number): Observable<any> {
    return this.eliminarEstudiante(id);
  }

  getStudentHistory(id: number): Observable<any> {
    return this.obtenerHistorial(id);
  }

  searchStudents(termino: string, tipo: string = 'nombre'): Observable<any[]> {
    return this.buscarEstudiantes(termino, tipo);
  }

  getDocuments(studentId: number): Observable<any[]> {
    return this.obtenerDocumentos(studentId);
  }

  uploadDocument(formData: FormData): Observable<any> {
    return this.cargarDocumento(formData);
  }

  deleteDocument(docId: number): Observable<any> {
    return this.eliminarDocumento(docId);
  }
}


