import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

/**
 * Servicio API Genérico
 * Proporciona métodos base para realizar peticiones HTTP GET, POST, PUT, DELETE
 * Centraliza la configuración de la URL base y los encabezados HTTP
 * Soporta cambio dinámico de URL (útil para ngrok)
 */
@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private urlApi = environment.apiUrl;

  constructor(private http: HttpClient) {
    console.log('🔗 URL de API:', this.urlApi);
  }

  /**
   * Cambia la URL del API en tiempo de ejecución
   * Útil para trabajar con ngrok u otros servicios de tunelización
   * @param url - Nueva URL base del API
   */
  cambiarUrlApi(url: string): void {
    this.urlApi = url;
    console.log('✅ URL de API actualizada a:', this.urlApi);
  }

  /**
   * Realizar petición GET
   * @param punto - Endpoint del API (sin incluir la URL base)
   * @returns Observable con la respuesta del servidor
   */
  obtener<T>(punto: string): Observable<T> {
    return this.http.get<T>(`${this.urlApi}${punto}`, {
      headers: this.obtenerEncabezados()
    });
  }

  /**
   * Realizar petición POST
   * @param punto - Endpoint del API
   * @param datos - Datos a enviar en el body
   * @returns Observable con la respuesta del servidor
   */
  crear<T>(punto: string, datos: any): Observable<T> {
    return this.http.post<T>(`${this.urlApi}${punto}`, datos, {
      headers: this.obtenerEncabezados()
    });
  }

  /**
   * Realizar petición PUT
   * @param punto - Endpoint del API
   * @param datos - Datos a enviar en el body
   * @returns Observable con la respuesta del servidor
   */
  actualizar<T>(punto: string, datos: any): Observable<T> {
    return this.http.put<T>(`${this.urlApi}${punto}`, datos, {
      headers: this.obtenerEncabezados()
    });
  }

  /**
   * Realizar petición DELETE
   * @param punto - Endpoint del API
   * @returns Observable con la respuesta del servidor
   */
  eliminar<T>(punto: string): Observable<T> {
    return this.http.delete<T>(`${this.urlApi}${punto}`, {
      headers: this.obtenerEncabezados()
    });
  }

  /**
   * Obtiene los encabezados HTTP por defecto
   * @returns HttpHeaders con los encabezados configurados
   * @private
   */
  private obtenerEncabezados(): HttpHeaders {
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });
  }

  /**
   * Registra las peticiones HTTP en la consola
   * Útil para debugging y monitoreo
   * @param metodo - Tipo de petición (GET, POST, PUT, DELETE)
   * @param punto - Endpoint del API
   * @param datos - Datos enviados (opcional)
   */
  registrarPeticion(metodo: string, punto: string, datos?: any): void {
    console.log('📡 [PETICIÓN]', {
      timestamp: new Date().toISOString(),
      metodo,
      url: `${this.urlApi}${punto}`,
      datos: datos || 'N/A'
    });
  }

  // Métodos heredados para compatibilidad
  get<T>(endpoint: string): Observable<T> {
    return this.obtener<T>(endpoint);
  }

  post<T>(endpoint: string, data: any): Observable<T> {
    return this.crear<T>(endpoint, data);
  }

  put<T>(endpoint: string, data: any): Observable<T> {
    return this.actualizar<T>(endpoint, data);
  }

  delete<T>(endpoint: string): Observable<T> {
    return this.eliminar<T>(endpoint);
  }

  setApiUrl(url: string): void {
    this.cambiarUrlApi(url);
  }

  logRequest(method: string, endpoint: string, data?: any): void {
    this.registrarPeticion(method, endpoint, data);
  }
}
