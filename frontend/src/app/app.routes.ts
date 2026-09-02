import { Routes } from '@angular/router';
import { HomeComponent } from './components/home/home.component';
import { InscriptionComponent } from './components/inscription/inscription.component';
import { LoginComponent } from './components/login/login.component';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard.component';
import { StudentsComponent } from './components/students/students.component';
import { CoursesComponent } from './components/courses/courses.component';

import { DocentesComponent } from './components/docentes/docentes.component';
import { DocentesListComponent } from './components/docentes-list/docentes-list.component';
import { ParalelosComponent } from './components/paralelos/paralelos.component';
import { DocenteDashboardComponent } from './components/docente-dashboard/docente-dashboard.component';
import { SettingsComponent } from './components/settings/settings.component';
import { AccesosComponent } from './components/accesos/accesos.component';
import { StudentDashboardComponent } from './components/student-dashboard/student-dashboard.component';
import { ReportsComponent } from './components/reports/reports.component';
import { authGuard } from './guards/auth.guard';

/**
 * Configuración de Rutas de la Aplicación
 * Define todas las rutas disponibles en el sistema.
 *
 * Rutas públicas:
 * - '': Página de inicio
 * - 'inscripcion': Formulario de inscripción de estudiantes
 * - 'login': Página de inicio de sesión
 *
 * Rutas privadas (requieren autenticación obligatoria):
 * - 'admin', 'students', 'courses', 'docentes-list', 'paralelos', 'reports', 'settings', 'accesos'
 */
export const routes: Routes = [
    // Ruta por defecto - Página de inicio
    { path: '', component: HomeComponent },
    { path: 'home', redirectTo: '', pathMatch: 'full' },

    // Rutas públicas
    { path: 'inscripcion', component: InscriptionComponent },  // Inscripción de estudiantes
    { path: 'login', component: LoginComponent },              // Inicio de sesión

    // Rutas de administrador (Protegidas)
    { path: 'admin', component: AdminDashboardComponent, canActivate: [authGuard] },
    { path: 'students', component: StudentsComponent, canActivate: [authGuard] },
    { path: 'courses', component: CoursesComponent, canActivate: [authGuard] },
    { path: 'docentes-list', component: DocentesListComponent, canActivate: [authGuard] },
    { path: 'paralelos', component: ParalelosComponent, canActivate: [authGuard] },
    { path: 'reports', component: ReportsComponent, canActivate: [authGuard] },
    { path: 'settings', component: SettingsComponent, canActivate: [authGuard] },
    { path: 'accesos', component: AccesosComponent, canActivate: [authGuard] },

    // Rutas de rol específicos (Protegidas)
    { path: 'docente-dashboard', component: DocenteDashboardComponent, canActivate: [authGuard] },
    { path: 'student-dashboard', component: StudentDashboardComponent, canActivate: [authGuard] },

    // Redirección por defecto para rutas no reconocidas
    { path: '**', redirectTo: '' }
];
