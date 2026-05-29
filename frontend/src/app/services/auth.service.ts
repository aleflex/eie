import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

/**
 * Servicio de Autenticación
 * Gestiona el inicio de sesión, cierre de sesión y gestión del usuario autenticado.
 * Almacena los datos del usuario en localStorage para acceso en toda la aplicación.
 */
@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) { }

  /**
   * Inicia sesión del usuario
   * @param credenciales - Objeto con email y contraseña del usuario
   * @returns Observable con la respuesta del servidor (usuario y token)
   */
  iniciarSesion(credenciales: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, credenciales).pipe(
      tap((respuesta: any) => {
        if (respuesta.user) {
          localStorage.setItem('usuario', JSON.stringify(respuesta.user));
        }
      })
    );
  }

  /**
   * Cierra la sesión del usuario actual
   * @returns Observable con la respuesta del servidor
   */
  cerrarSesion() {
    localStorage.removeItem('usuario');
    return this.http.post(`${this.apiUrl}/logout`, {});
  }

  /**
   * Verifica si hay un usuario autenticado
   * @returns true si existe sesión activa, false en caso contrario
   */
  estaAutenticado(): boolean {
    return localStorage.getItem('usuario') !== null;
  }

  /**
   * Obtiene los datos del usuario autenticado
   * @returns Objeto del usuario almacenado en localStorage o null
   */
  obtenerUsuario() {
    const usuario = localStorage.getItem('usuario');
    return usuario ? JSON.parse(usuario) : null;
  }

  /**
   * Actualiza el perfil del usuario autenticado
   * @param datosFormulario - FormData con los nuevos datos del usuario
   * @returns Observable con la respuesta del servidor
   */
  actualizarPerfil(datosFormulario: FormData): Observable<any> {
    return this.http.post(`${this.apiUrl}/user/profile`, datosFormulario).pipe(
      tap((respuesta: any) => {
        if (respuesta.user) {
          localStorage.setItem('usuario', JSON.stringify(respuesta.user));
        }
      })
    );
  }

  // Métodos heredados para compatibilidad con código existente
  login(credenciales: any): Observable<any> {
    return this.iniciarSesion(credenciales);
  }

  logout() {
    return this.cerrarSesion();
  }

  isLoggedIn(): boolean {
    return this.estaAutenticado();
  }

  getUser() {
    return this.obtenerUsuario();
  }

  updateProfile(datosFormulario: FormData): Observable<any> {
    return this.actualizarPerfil(datosFormulario);
  }
}
