import { HttpInterceptorFn } from '@angular/common/http';

/**
 * Interceptor HTTP de Autenticación y Seguridad
 * Inyecta automáticamente el Token de Autorización (Bearer Token) en cada petición al backend.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
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

  const clonedRequest = req.clone({ headers });
  return next(clonedRequest);
};
