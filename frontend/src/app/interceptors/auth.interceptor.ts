import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError, of } from 'rxjs';

/**
 * Interceptor HTTP de Autenticación y Seguridad
 * Inyecta automáticamente el Token de Autorización (Bearer Token) en cada petición al backend.
 * Si el servidor responde 401 Unauthorized, redirige automáticamente al login.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  let headers = req.headers;

  if (req.url.includes('ngrok-free.app')) {
    headers = headers.set('ngrok-skip-browser-warning', 'true');
  }

  const rawUser = sessionStorage.getItem('usuario') || localStorage.getItem('usuario');
  if (rawUser) {
    try {
      const user = JSON.parse(rawUser);
      const token = user?.token || user?.token_acceso || null;
      if (token && !headers.has('Authorization')) {
        headers = headers.set('Authorization', `Bearer ${token}`);
      }
    } catch (e) {
      console.warn('Error al leer token de usuario para interceptor HTTP:', e);
    }
  }

  // Asegurar formato JSON para evitar redirecciones HTML en respuestas de validación
  if (req.url.includes('/api/') && !headers.has('Accept')) {
    headers = headers.set('Accept', 'application/json');
  }

  const clonedRequest = req.clone({ headers });
  return next(clonedRequest).pipe(
    catchError((error: HttpErrorResponse) => {
      // Si el servidor rechaza la petición por falta de autenticación o token expirado
      if (error.status === 401) {
        sessionStorage.removeItem('usuario');
        localStorage.removeItem('usuario');
        if (!req.url.includes('/api/login') && !req.url.includes('/api/logout')) {
          router.navigate(['/login']);
        }
        return of(null as any);
      }
      return throwError(() => error);
    })
  );
};
