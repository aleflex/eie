import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Guard de Autenticación y Seguridad por Rol
 * Verifica si la sesión está activa y redirige según el rol correspondiente.
 */
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isLoggedIn()) {
    // Si no ha iniciado sesión, redirigir inmediatamente al login guardando la URL intentada
    return router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } });
  }

  const user = authService.obtenerUsuario();
  const path = route.routeConfig?.path || '';

  // Redirección inteligente y seguridad por rol de usuario
  if (user) {
    const rol = (user.rol || '').toLowerCase();
    
    // Si un estudiante intenta entrar al dashboard de docentes o áreas administrativas
    if (rol === 'estudiante') {
      if (path === 'docente-dashboard' || path === 'admin' || path === 'roles' || path === 'accesos') {
        return router.createUrlTree(['/student-dashboard']);
      }
    }

    // Si un docente intenta entrar al dashboard de estudiantes
    if (rol === 'docente') {
      if (path === 'student-dashboard') {
        return router.createUrlTree(['/docente-dashboard']);
      }
    }
  }

  return true;
};
