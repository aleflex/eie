import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

/**
 * Servicio de Inscripciones
 * Gestiona todas las operaciones relacionadas con inscripciones de estudiantes:
 * - Envío de inscripciones
 * - Actualización de datos de inscripciones
 * - Listado de inscripciones
 * - Descarga de certificados
 * - Manejo centralizado de errores
 */
@Injectable({
  providedIn: 'root'
})
export class InscriptionService {
  private apiUrl = `${environment.apiUrl}/api/inscripciones`;

  constructor(private http: HttpClient) { }

  /**
   * Envía una solicitud de inscripción con datos y archivos adjuntos
   * @param datos - FormData con los datos de inscripción
   * @returns Observable con la respuesta del servidor
   */
  enviarInscripcion(datos: FormData): Observable<any> {
    return this.http.post(this.apiUrl, datos).pipe(
      catchError(this.manejarError)
    );
  }

  /**
   * Actualiza datos específicos de una inscripción (estado, curso, etc.)
   * @param id - ID de la inscripción
   * @param datos - Nuevos datos a actualizar
   * @returns Observable con la respuesta del servidor
   */
  actualizarInscripcion(id: number, datos: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, datos).pipe(
      catchError(this.manejarError)
    );
  }

  /**
   * Obtiene el listado de todas las inscripciones (solo para administradores)
   * @returns Observable con el array de inscripciones
   */
  listarInscripciones(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl).pipe(
      catchError(this.manejarError)
    );
  }

  /**
   * Elimina una inscripción del sistema
   * @param id - ID de la inscripción a eliminar
   * @returns Observable con la respuesta del servidor
   */
  eliminarInscripcion(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  /**
   * Descarga el certificado de completación de una inscripción
   * @param id - ID de la inscripción
   * @returns Observable con el archivo PDF en formato Blob
   */
  descargarCertificado(id: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/${id}/certificate`, {
      responseType: 'blob'
    });
  }

  /**
   * Manejo centralizado de errores HTTP
   * @param error - Error de HTTP
   * @returns Observable con el error procesado
   * @private
   */
  private manejarError(error: HttpErrorResponse) {
    let mensajeError = 'Ocurrió un error inesperado';

    if (error.error instanceof ErrorEvent) {
      // Error del lado del cliente
      mensajeError = `Error: ${error.error.message}`;
    } else {
      // Error del lado del servidor
      if (error.status === 422) {
        // Error de validación de Laravel
        return throwError(() => ({
          status: 422,
          mensaje: 'Error de validación',
          errores: error.error.errors
        }));
      }
      mensajeError = `Código de error: ${error.status}\nMensaje: ${error.message}`;
    }

    return throwError(() => mensajeError);
  }

  // Métodos heredados para compatibilidad
  updateInscription(id: number, data: any): Observable<any> {
    return this.actualizarInscripcion(id, data);
  }

  listarInscripciones_legacy(): Observable<any[]> {
    return this.listarInscripciones();
  }

  deleteInscription(id: number): Observable<any> {
    return this.eliminarInscripcion(id);
  }

  downloadCertificate(id: number): Observable<Blob> {
    return this.descargarCertificado(id);
  }
}
