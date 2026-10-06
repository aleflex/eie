import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { Title } from '@angular/platform-browser';
import { InscriptionService } from '../../services/inscription.service';
import { AuthService } from '../../services/auth.service';
import { WebSocketService } from '../../services/websocket.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrl: './admin-dashboard.component.css'
})
export class AdminDashboardComponent implements OnInit, OnDestroy {
  inscripciones: any[] = [];
  isLoading: boolean = true;
  user: any = null;

  // Suscripciones WebSocket en tiempo real
  private wsSubs: Subscription[] = [];

  // Control de la barra lateral (Sidebar)
  isSidebarCollapsed: boolean = false;
  isMobileMenuOpen: boolean = false;

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  constructor(
    private inscriptionService: InscriptionService,
    private authService: AuthService,
    private wsService: WebSocketService,
    private router: Router,
    private titleService: Title
  ) {}

  ngOnInit() {
    this.titleService.setTitle('Administrador - Escuela de Idiomas del Ejército');

    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }
    
    this.user = this.authService.getUser();
    this.cargarInscripciones();

    // Sincronización instantánea mediante WebSockets (cero peticiones repetidas)
    this.wsSubs.push(
      this.wsService.onInscriptionCreated().subscribe(() => {
        this.cargarInscripciones();
      }),
      this.wsService.onInscriptionUpdated().subscribe((data) => {
        if (data && (data.id_inscripcion || data.id)) {
          const targetId = data.id_inscripcion || data.id;
          const idx = this.inscripciones.findIndex(i => (i.id || i.id_inscripcion) == targetId);
          if (idx !== -1) {
            this.inscripciones[idx] = { ...this.inscripciones[idx], ...data };
            this.actualizarEstadisticas();
            return;
          }
        }
        this.cargarInscripciones();
      }),
      this.wsService.onInscriptionDeleted().subscribe((data) => {
        if (data && (data.id_inscripcion || data.id)) {
          const targetId = data.id_inscripcion || data.id;
          this.inscripciones = this.inscripciones.filter(i => (i.id || i.id_inscripcion) != targetId);
          this.actualizarEstadisticas();
        }
      })
    );
  }

  ngOnDestroy() {
    this.wsSubs.forEach(sub => sub.unsubscribe());
    this.wsSubs = [];
  }

  cargarInscripciones() {
    this.inscriptionService.listarInscripciones().subscribe({
      next: (data) => {
        this.inscripciones = data;
        this.isLoading = false;
        this.actualizarEstadisticas();
      },
      error: (err) => {
        console.error('Error al cargar inscripciones', err);
        this.isLoading = false;
      }
    });
  }

  habilitadosCount = 0;
  pendientesCount = 0;

  actualizarEstadisticas() {
    this.habilitadosCount = this.inscripciones.filter(i => i.estado === 'activo').length;
    this.pendientesCount = this.inscripciones.filter(i => i.estado === 'pendiente').length;
  }

  onLogout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
