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

/**
 * Configuración de Rutas de la Aplicación
 * Define todas las rutas disponibles en el sistema.
 *
 * Rutas públicas:
 * - '': Página de inicio
 * - 'inscripcion': Formulario de inscripción de estudiantes
 * - 'login': Página de inicio de sesión
 *
 * Rutas privadas (requieren autenticación):
 * - 'admin': Panel del administrador
 * - 'students': Gestión de estudiantes
 * - 'courses': Gestión de cursos/paralelos
 * - 'docentes-list': Listado de docentes
 * - 'paralelos': Gestión de paralelos
 * - 'docente-dashboard': Panel del docente
 * - 'settings': Configuración del sistema
 * - 'accesos': Gestión de accesos y credenciales
 * - 'student-dashboard': Panel del estudiante
 */
export const routes: Routes = [
    // Ruta por defecto - Página de inicio
    { path: '', component: HomeComponent },

    // Rutas públicas
    { path: 'inscripcion', component: InscriptionComponent },  // Inscripción de estudiantes
    { path: 'login', component: LoginComponent },              // Inicio de sesión

    // Rutas de administrador
    { path: 'admin', component: AdminDashboardComponent },     // Panel administrativo
    { path: 'students', component: StudentsComponent },        // Gestión de estudiantes
    { path: 'courses', component: CoursesComponent },          // Gestión de cursos
    { path: 'docentes-list', component: DocentesListComponent }, // Listado de docentes
    { path: 'paralelos', component: ParalelosComponent },      // Gestión de paralelos
    { path: 'settings', component: SettingsComponent },        // Configuración
    { path: 'accesos', component: AccesosComponent },          // Control de accesos

    // Rutas de rol específicos
    { path: 'docente-dashboard', component: DocenteDashboardComponent }, // Panel del docente
    { path: 'student-dashboard', component: StudentDashboardComponent }, // Panel del estudiante
];
