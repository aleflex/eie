import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Docentes
 * Gestiona todas las operaciones relacionadas con docentes/instructores, incluyendo:
 * - Registro de nuevos docentes
 * - Obtención de listados de docentes
 * - Actualización de datos de docentes
 * - Cambio de estado de docentes
 * - Obtención de paralelos asignados a cada docente
 */
@Injectable({
  providedIn: 'root'
})
export class DocenteService {
  private apiUrl = `${environment.apiUrl}/api/docentes`;

  constructor(private http: HttpClient) { }

  /**
   * Registra un nuevo docente en el sistema
   * @param datosDocente - Datos del docente a registrar
   * @returns Observable con la respuesta del servidor
   */
  registrarDocente(datosDocente: any): Observable<any> {
    return this.http.post(this.apiUrl, datosDocente);
  }

  /**
   * Registra un nuevo docente incluyendo su fotografía
   * @param datosFormulario - FormData con los datos y la foto del docente
   * @returns Observable con la respuesta del servidor
   */
  registrarDocenteConFoto(datosFormulario: FormData): Observable<any> {
    return this.http.post(this.apiUrl, datosFormulario);
  }

  /**
   * Obtiene el listado de todos los docentes
   * @returns Observable con el array de docentes
   */
  obtenerDocentes(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
  }

  /**
   * Obtiene los datos de un docente específico
   * @param id - ID del docente
   * @returns Observable con los datos del docente
   */
  obtenerDocente(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}`);
  }

  /**
   * Actualiza los datos de un docente
   * @param id - ID del docente a actualizar
   * @param datosDocente - Nuevos datos del docente
   * @returns Observable con la respuesta del servidor
   */
  actualizarDocente(id: number, datosDocente: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, datosDocente);
  }

  /**
   * Actualiza un docente incluyendo su fotografía
   * @param id - ID del docente
   * @param datosFormulario - FormData con los datos y la nueva foto
   * @returns Observable con la respuesta del servidor
   */
  actualizarDocenteConFoto(id: number, datosFormulario: FormData): Observable<any> {
    datosFormulario.append('_method', 'PUT');
    return this.http.post(`${this.apiUrl}/${id}`, datosFormulario);
  }

  /**
   * Cambia el estado del docente (activo/inactivo)
   * @param id - ID del docente
   * @returns Observable con la respuesta del servidor
   */
  cambiarEstado(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}/toggle-status`, {});
  }

  /**
   * Elimina un docente del sistema
   * @param id - ID del docente a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarDocente(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  /**
   * Obtiene los paralelos asignados a un docente específico
   * @param idUsuario - ID del usuario/docente
   * @returns Observable con el array de paralelos
   */
  obtenerMisParalelos(idUsuario: number): Observable<any> {
    const params = new HttpParams().set('user_id', idUsuario.toString());
    return this.http.get(`${this.apiUrl}/mis-paralelos`, { params });
  }

  // Métodos heredados para compatibilidad
  registerDocente(docenteData: any): Observable<any> {
    return this.registrarDocente(docenteData);
  }

  registerDocenteWithPhoto(formData: FormData): Observable<any> {
    return this.registrarDocenteConFoto(formData);
  }

  getDocentes(): Observable<any[]> {
    return this.obtenerDocentes();
  }

  getDocente(id: number): Observable<any> {
    return this.obtenerDocente(id);
  }

  updateDocente(id: number, docenteData: any): Observable<any> {
    return this.actualizarDocente(id, docenteData);
  }

  updateDocenteWithPhoto(id: number, formData: FormData): Observable<any> {
    return this.actualizarDocenteConFoto(id, formData);
  }

  toggleStatus(id: number): Observable<any> {
    return this.cambiarEstado(id);
  }

  deleteDocente(id: number): Observable<any> {
    return this.eliminarDocente(id);
  }

  getMisParalelos(userId: number): Observable<any> {
    return this.obtenerMisParalelos(userId);
  }
}
