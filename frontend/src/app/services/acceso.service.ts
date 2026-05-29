import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Accesos y Control de Credenciales
 * Gestiona las operaciones de control de acceso a cuentas:
 * - Obtención de listados de accesos
 * - Asignación de credenciales de acceso a usuarios
 * - Actualización de credenciales existentes
 * - Desvinculación de cuentas
 */
@Injectable({
  providedIn: 'root'
})
export class AccesoService {
  private apiUrl = `${environment.apiUrl}/api/accesos`;

  constructor(private http: HttpClient) { }

  /**
   * Obtiene el listado de todos los accesos registrados
   * @returns Observable con el array de accesos
   */
  obtenerAccesos(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
  }

  /**
   * Asigna credenciales de acceso a un usuario
   * @param datos - Datos con la información del usuario y credenciales
   * @returns Observable con la respuesta del servidor
   */
  asignarCredenciales(datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/asignar`, datos);
  }

  /**
   * Actualiza las credenciales de acceso de un usuario existente
   * @param idUsuario - ID del usuario
   * @param datos - Nuevas credenciales
   * @returns Observable con la respuesta del servidor
   */
  actualizarCredenciales(idUsuario: number, datos: any): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/actualizar/${idUsuario}`, datos);
  }

  /**
   * Desvincula/elimina la cuenta de acceso de un usuario
   * @param idUsuario - ID del usuario cuya cuenta será desvinculada
   * @returns Observable con la respuesta del servidor
   */
  desvincularCuenta(idUsuario: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/desvincular/${idUsuario}`);
  }

  // Métodos heredados para compatibilidad
  getAccesos(): Observable<any[]> {
    return this.obtenerAccesos();
  }

  asignarCredenciales_legacy(data: any): Observable<any> {
    return this.asignarCredenciales(data);
  }

  actualizarCredenciales_legacy(userId: number, data: any): Observable<any> {
    return this.actualizarCredenciales(userId, data);
  }

  desvincularCuenta_legacy(userId: number): Observable<any> {
    return this.desvincularCuenta(userId);
  }
}
