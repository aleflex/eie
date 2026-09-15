import { Component, OnInit } from '@angular/core';
import { RouterOutlet, Router, NavigationEnd } from '@angular/router';
import { Title } from '@angular/platform-browser';
import { Capacitor } from '@capacitor/core';
import { AuthService } from './services/auth.service';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit {
  title = 'frontend';

  constructor(
    private titleService: Title,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    if (Capacitor.isNativePlatform()) {
      document.documentElement.classList.add('capacitor-native');
      if (document.body) document.body.classList.add('capacitor-native');
    }

    this.actualizarTitulo();

    // Actualizar cuando cambie el usuario en sesión
    this.authService.usuario$.subscribe(() => {
      this.actualizarTitulo();
    });

    // Actualizar cuando cambie la ruta / navegación
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.actualizarTitulo();
    });
  }

  private actualizarTitulo(): void {
    const user = this.authService.getUser();
    const urlClean = (this.router.url || '').split('?')[0].replace('/', '').toLowerCase();
    const baseInstitucion = 'Escuela de Idiomas del Ejército';

    if (user) {
      const rolLower = (user.rol || '').toLowerCase();
      let roleLabel = '';

      if (user.estudiante_id || rolLower === 'estudiante' || urlClean === 'student-dashboard') {
        roleLabel = 'Estudiante';
      } else if (user.docente_id || rolLower === 'docente' || rolLower === 'instructor' || urlClean === 'docente-dashboard') {
        roleLabel = 'Docente';
      } else if (rolLower === 'admin' || !rolLower) {
        roleLabel = 'Administrador';
      } else {
        roleLabel = user.rol.charAt(0).toUpperCase() + user.rol.slice(1);
      }

      this.titleService.setTitle(`${roleLabel} - ${baseInstitucion}`);
      return;
    }

    // Títulos contextuales si aún no hay usuario en memoria pero según la ruta
    if (urlClean === 'student-dashboard') {
      this.titleService.setTitle(`Estudiante - ${baseInstitucion}`);
    } else if (urlClean === 'docente-dashboard') {
      this.titleService.setTitle(`Docente - ${baseInstitucion}`);
    } else if (['admin', 'students', 'courses', 'paralelos', 'docentes-list', 'reports', 'settings', 'roles', 'accesos'].includes(urlClean)) {
      this.titleService.setTitle(`Administrador - ${baseInstitucion}`);
    } else if (urlClean === 'login') {
      this.titleService.setTitle(`Iniciar Sesión - ${baseInstitucion}`);
    } else if (urlClean === 'inscription') {
      this.titleService.setTitle(`Inscripción - ${baseInstitucion}`);
    } else {
      this.titleService.setTitle(baseInstitucion);
    }
  }
}

