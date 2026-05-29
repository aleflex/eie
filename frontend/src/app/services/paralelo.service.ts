import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Paralelos, Aulas y Horarios
 * Gestiona todas las operaciones relacionadas con:
 * - Paralelos (grupos de estudiantes)
 * - Aulas (salones de clase)
 * - Horarios (jornadas y tiempos de clase)
 */
@Injectable({
  providedIn: 'root'
})
export class ParaleloService {
  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) { }

  // ============ Operaciones con Paralelos ============

  /**
   * Obtiene el listado de todos los paralelos
   * @returns Observable con el array de paralelos
   */
  obtenerParalelos(): Observable<any> {
    return this.http.get(`${this.apiUrl}/paralelos`);
  }

  /**
   * Guarda o actualiza un paralelo
   * Si el paralelo tiene ID, actualiza; si no, crea uno nuevo
   * @param datos - Datos del paralelo a guardar
   * @returns Observable con la respuesta del servidor
   */
  guardarParalelo(datos: any): Observable<any> {
    if (datos.id) {
      return this.http.put(`${this.apiUrl}/paralelos/${datos.id}`, datos);
    }
    return this.http.post(`${this.apiUrl}/paralelos`, datos);
  }

  /**
   * Elimina un paralelo del sistema
   * @param id - ID del paralelo a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarParalelo(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/paralelos/${id}`);
  }

  // ============ Operaciones con Aulas ============

  /**
   * Obtiene el listado de todas las aulas disponibles
   * @returns Observable con el array de aulas
   */
  obtenerAulas(): Observable<any> {
    return this.http.get(`${this.apiUrl}/aulas`);
  }

  /**
   * Guarda o actualiza un aula
   * Si el aula tiene ID, actualiza; si no, crea una nueva
   * @param datos - Datos del aula a guardar
   * @returns Observable con la respuesta del servidor
   */
  guardarAula(datos: any): Observable<any> {
    if (datos.id) {
      return this.http.put(`${this.apiUrl}/aulas/${datos.id}`, datos);
    }
    return this.http.post(`${this.apiUrl}/aulas`, datos);
  }

  /**
   * Elimina un aula del sistema
   * @param id - ID del aula a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarAula(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/aulas/${id}`);
  }

  // ============ Operaciones con Horarios ============

  /**
   * Obtiene el listado de todos los horarios disponibles
   * @returns Observable con el array de horarios
   */
  obtenerHorarios(): Observable<any> {
    return this.http.get(`${this.apiUrl}/horarios`);
  }

  // Métodos heredados para compatibilidad
  getParalelos(): Observable<any> {
    return this.obtenerParalelos();
  }

  getAulas(): Observable<any> {
    return this.obtenerAulas();
  }

  getHorarios(): Observable<any> {
    return this.obtenerHorarios();
  }

  saveParalelo(data: any): Observable<any> {
    return this.guardarParalelo(data);
  }

  deleteParalelo(id: number): Observable<any> {
    return this.eliminarParalelo(id);
  }

  saveAula(data: any): Observable<any> {
    return this.guardarAula(data);
  }

  deleteAula(id: number): Observable<any> {
    return this.eliminarAula(id);
  }
}
