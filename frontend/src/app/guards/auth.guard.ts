import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Guard de Autenticación y Seguridad Estricta por Rol
 * Impide rigurosamente que estudiantes o docentes ingresen a módulos administrativos o de otros roles.
 */
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isLoggedIn()) {
    return router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } });
  }

  const user = authService.obtenerUsuario();
  const path = (route.routeConfig?.path || '').toLowerCase();

  if (user) {
    const rolLower = (user.rol || '').toLowerCase();
    const isEstudiante = !!user.estudiante_id || rolLower === 'estudiante';
    const isDocente = !!user.docente_id || rolLower === 'docente' || rolLower === 'instructor';

    // 🛡️ REGLA ESTRICTA DE SEGURIDAD PARA ESTUDIANTES:
    // Un estudiante NUNCA puede ingresar a /roles, /admin, /students, /courses, /docentes-list, /paralelos, /reports, /settings, /accesos, etc.
    if (isEstudiante) {
      if (path !== 'student-dashboard') {
        console.warn(`[Seguridad Guard] Bloqueado acceso no autorizado del estudiante (${user.name}) a /${path}. Redirigiendo a student-dashboard.`);
        return router.createUrlTree(['/student-dashboard']);
      }
      return true;
    }

    // 🛡️ REGLA ESTRICTA DE SEGURIDAD PARA DOCENTES/INSTRUCTORES:
    // Un docente NUNCA puede ingresar al portal de estudiantes ni a roles/configuración administrativa no permitida.
    if (isDocente) {
      if (path === 'student-dashboard' || path === 'roles' || path === 'accesos') {
        console.warn(`[Seguridad Guard] Bloqueado acceso no autorizado del docente (${user.name}) a /${path}. Redirigiendo a docente-dashboard.`);
        return router.createUrlTree(['/docente-dashboard']);
      }
      return true;
    }
  }

  return true;
};
