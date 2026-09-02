import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ModuloInfo {
  key: string;
  nombre: string;
  icon: string;
  ruta: string;
  descripcion: string;
}

@Injectable({
  providedIn: 'root'
})
export class RoleService {
  private apiUrl = `${environment.apiUrl}/api/roles`;

  public readonly MODULOS_SISTEMA: ModuloInfo[] = [
    { key: 'admin', nombre: 'Panel Principal', icon: 'dashboard', ruta: '/admin', descripcion: 'Métricas generales, acceso rápido y resúmenes de gestión' },
    { key: 'students', nombre: 'Gestión de Estudiantes', icon: 'people', ruta: '/students', descripcion: 'CRUD de alumnos, expediente digital y emisión de certificados' },
    { key: 'courses', nombre: 'Cursos', icon: 'school', ruta: '/courses', descripcion: 'Administración de cursos e idiomas ofertados' },
    { key: 'docentes-list', nombre: 'Instructores / Docentes', icon: 'person', ruta: '/docentes-list', descripcion: 'Registro de profesores, estado y carga docente' },
    { key: 'paralelos', nombre: 'Paralelos', icon: 'class', ruta: '/paralelos', descripcion: 'Asignación de aulas, horarios, cupos e inscripciones por paralelo' },
    { key: 'reports', nombre: 'Reportes y Estadísticas', icon: 'bar_chart', ruta: '/reports', descripcion: 'Exportación de listas oficiales, nóminas y reportes en Excel/PDF' },
    { key: 'accesos', nombre: 'Credenciales / Accesos', icon: 'vpn_key', ruta: '/accesos', descripcion: 'Gestión de contraseñas, cuentas de usuario y roles de acceso' },
    { key: 'settings', nombre: 'Configuración del Sistema', icon: 'settings', ruta: '/settings', descripcion: 'Periodos de inscripción, firmas oficiales y reglas institucionales' }
  ];

  private cachedPermisos: any = null;

  constructor(private http: HttpClient) {}

  getRoles(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
  }

  createRole(data: { nombre_rol: string; descripcion?: string }): Observable<any> {
    return this.http.post<any>(this.apiUrl, data);
  }

  updateRole(id: number, data: { nombre_rol: string; descripcion?: string }): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/${id}`, data);
  }

  deleteRole(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/${id}`);
  }

  getPermisos(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/permisos`).pipe(
      tap(permisos => {
        this.cachedPermisos = permisos;
        localStorage.setItem('eie_roles_permisos', JSON.stringify(permisos));
      })
    );
  }

  savePermisos(permisos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/permisos`, permisos).pipe(
      tap(() => {
        this.cachedPermisos = permisos;
        localStorage.setItem('eie_roles_permisos', JSON.stringify(permisos));
      })
    );
  }

  /**
   * Verifica si un rol específico tiene acceso a un módulo dado.
   */
  hasAccessToModule(idRol: number, moduleKey: string): boolean {
    if (idRol === 1) return true; // Administrador General siempre tiene acceso total

    if (!this.cachedPermisos) {
      const stored = localStorage.getItem('eie_roles_permisos');
      if (stored) {
        try {
          this.cachedPermisos = JSON.parse(stored);
        } catch (e) {}
      }
    }

    if (this.cachedPermisos && this.cachedPermisos[idRol]) {
      return this.cachedPermisos[idRol][moduleKey] === true;
    }

    // Valores por defecto
    if (idRol === 4) { // Jefe de Unidad
      return ['admin', 'students', 'courses', 'docentes-list', 'paralelos', 'reports'].includes(moduleKey);
    }
    if (idRol === 5) { // Secretaría
      return ['admin', 'students', 'courses', 'reports'].includes(moduleKey);
    }

    return false;
  }
}
