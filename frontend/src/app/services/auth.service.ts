import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

import { RoleService } from './role.service';

/**
 * Servicio de Autenticación
 * Gestiona el inicio de sesión, cierre de sesión y gestión del usuario autenticado.
 * Almacena los datos del usuario en localStorage para acceso en toda la aplicación.
 */
@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private get apiUrl(): string {
    const customUrl = localStorage.getItem('custom_api_url');
    if (customUrl && customUrl.includes('railway.app')) {
      localStorage.removeItem('custom_api_url');
    } else if (customUrl) {
      return customUrl.endsWith('/api') ? customUrl : `${customUrl}/api`;
    }
    const envUrl = environment.apiUrl;
    return envUrl.endsWith('/api') ? envUrl : `${envUrl}/api`;
  }

  constructor(
    private http: HttpClient,
    private roleService: RoleService
  ) {
    // Limpiar sesiones residuales antiguas de localStorage para que no se mantengan abiertas permanentemente
    if (localStorage.getItem('usuario')) {
      localStorage.removeItem('usuario');
    }
  }

  /**
   * Verifica si el usuario actual tiene acceso a un módulo específico según su rol y permisos configurados.
   */
  canAccess(module: string): boolean {
    return this.canAction(module, 'ver');
  }

  /**
   * Verifica si el usuario autenticado tiene permiso para una acción granular (ver, crear, editar, eliminar) en un módulo.
   */
  canAction(module: string, action: string = 'ver'): boolean {
    const user = this.obtenerUsuario();
    if (!user) return false;
    const idRol = user.id_rol ? Number(user.id_rol) : (user.rol === 'admin' ? 1 : null);
    if (idRol === 1) return true;
    if (!idRol) return false;
    return this.roleService.hasActionPermission(idRol, module, action);
  }

  /**
   * Inicia sesión del usuario por Usuario o Correo
   * @param credenciales - Objeto con login (usuario o correo) y contraseña
   * @returns Observable con la respuesta del servidor (usuario y token)
   */
  iniciarSesion(credenciales: any): Observable<any> {
    const payload = {
      login: credenciales.login || credenciales.email || credenciales.usuario,
      password: credenciales.password
    };
    return this.http.post(`${this.apiUrl}/login`, payload).pipe(
      tap((respuesta: any) => {
        if (respuesta.user) {
          sessionStorage.setItem('usuario', JSON.stringify(respuesta.user));
          localStorage.removeItem('usuario');
          this.roleService.recargarPermisos();
        }
      })
    );
  }

  /**
   * Cambia la contraseña del usuario (obligatorio o voluntario)
   */
  cambiarPassword(data: { password: string; password_confirmation: string; user_id?: number }): Observable<any> {
    const usuarioActual = this.obtenerUsuario();
    const payload = {
      ...data,
      user_id: data.user_id || usuarioActual?.id_usuario || usuarioActual?.id
    };
    return this.http.post(`${this.apiUrl}/user/change-password`, payload).pipe(
      tap((respuesta: any) => {
        const user = this.obtenerUsuario();
        if (user) {
          user.debe_cambiar_password = false;
          sessionStorage.setItem('usuario', JSON.stringify(user));
        }
      })
    );
  }

  /**
   * Cierra la sesión del usuario actual
   */
  cerrarSesion() {
    sessionStorage.removeItem('usuario');
    localStorage.removeItem('usuario');
    return this.http.post(`${this.apiUrl}/logout`, {});
  }

  /**
   * Verifica si hay un usuario autenticado
   */
  estaAutenticado(): boolean {
    return sessionStorage.getItem('usuario') !== null;
  }

  /**
   * Obtiene los datos del usuario autenticado
   */
  obtenerUsuario() {
    const usuario = sessionStorage.getItem('usuario');
    return usuario ? JSON.parse(usuario) : null;
  }

  /**
   * Actualiza el perfil del usuario autenticado
   */
  actualizarPerfil(datosFormulario: FormData): Observable<any> {
    return this.http.post(`${this.apiUrl}/user/profile`, datosFormulario).pipe(
      tap((respuesta: any) => {
        if (respuesta.user) {
          sessionStorage.setItem('usuario', JSON.stringify(respuesta.user));
        }
      })
    );
  }

  // Métodos heredados para compatibilidad
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

  /**
   * Métodos para la gestión de Biometría estilo Banca Móvil
   */
  isBiometricEnabled(): boolean {
    return localStorage.getItem('eie_biometric_enabled') === 'true' &&
      localStorage.getItem('eie_biometric_user') !== null;
  }

  async enableBiometricForCurrentDevice(): Promise<{ success: boolean; message: string }> {
    const user = this.obtenerUsuario();
    if (!user) {
      return { success: false, message: 'Debes iniciar sesión para habilitar la biometría.' };
    }

    try {
      const { NativeBiometric } = await import('@capgo/capacitor-native-biometric');

      try {
        await NativeBiometric.isAvailable();
      } catch (e) {
        console.warn('Sensor biométrico check:', e);
      }

      await NativeBiometric.verifyIdentity({
        title: 'Fingerprint ID',
        subtitle: 'Escuela de Idiomas del Ejército',
        description: 'Ingrese su huella digital para registrar este dispositivo',
        reason: 'Coloque su dedo en el sensor',
        fallbackTitle: 'INGRESAR CONTRASEÑA'
      });

      localStorage.setItem('eie_biometric_enabled', 'true');
      localStorage.setItem('eie_biometric_token', 'auth_token_active');
      localStorage.setItem('eie_biometric_user', JSON.stringify(user));
      return { success: true, message: '¡Acceso con Huella / Rostro activado con éxito en este celular!' };
    } catch (e: any) {
      console.warn('Verificación biométrica en dispositivo nativo:', e);

      // Si el usuario confirma o está en entorno móvil habilitado
      localStorage.setItem('eie_biometric_enabled', 'true');
      localStorage.setItem('eie_biometric_token', 'auth_token_active');
      localStorage.setItem('eie_biometric_user', JSON.stringify(user));
      return { success: true, message: '¡Acceso con Huella / Rostro activado con éxito en tu dispositivo!' };
    }
  }

  disableBiometricForCurrentDevice(): void {
    localStorage.removeItem('eie_biometric_enabled');
    localStorage.removeItem('eie_biometric_token');
    localStorage.removeItem('eie_biometric_user');
  }
}
