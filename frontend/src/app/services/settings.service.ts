import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Configuración
 * Gestiona las configuraciones generales del sistema como:
 * - Períodos académicos
 * - Cupos de inscripción
 * - Parámetros del sistema
 */
@Injectable({
  providedIn: 'root'
})
export class SettingsService {
  private apiUrl = `${environment.apiUrl}/api/settings`;

  constructor(private http: HttpClient) { }

  /**
   * Obtiene todas las configuraciones del sistema
   * @returns Observable con los datos de configuración
   */
  obtenerConfiguracion(): Observable<any> {
    return this.http.get<any>(this.apiUrl);
  }

  /**
   * Guarda o actualiza las configuraciones del sistema
   * @param datos - Nuevos datos de configuración
   * @returns Observable con la respuesta del servidor
   */
  guardarConfiguracion(datos: any): Observable<any> {
    return this.http.post<any>(this.apiUrl, datos);
  }

  // Métodos heredados para compatibilidad
  getSettings(): Observable<any> {
    return this.obtenerConfiguracion();
  }

  saveSettings(data: any): Observable<any> {
    return this.guardarConfiguracion(data);
  }
}
