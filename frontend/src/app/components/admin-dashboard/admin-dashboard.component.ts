import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { InscriptionService } from '../../services/inscription.service';
import { AuthService } from '../../services/auth.service';
import { Subscription, interval } from 'rxjs';

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

  // Sincronización en tiempo real
  private pollSub: Subscription | null = null;
  private focusHandler: any = null;
  private visibilityHandler: any = null;

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
    private router: Router
  ) {}

  ngOnInit() {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }
    
    this.user = this.authService.getUser();
    this.cargarInscripciones();

    // Polling en tiempo real cada 3.5 segundos para reflejar nuevas inscripciones automáticamente
    this.pollSub = interval(3500).subscribe(() => {
      this.silentSyncInscripciones();
    });

    if (typeof window !== 'undefined') {
      this.focusHandler = () => this.silentSyncInscripciones();
      this.visibilityHandler = () => {
        if (!document.hidden) this.silentSyncInscripciones();
      };
      window.addEventListener('focus', this.focusHandler);
      document.addEventListener('visibilitychange', this.visibilityHandler);
    }
  }

  ngOnDestroy() {
    if (this.pollSub) {
      this.pollSub.unsubscribe();
    }
    if (typeof window !== 'undefined') {
      if (this.focusHandler) window.removeEventListener('focus', this.focusHandler);
      if (this.visibilityHandler) document.removeEventListener('visibilitychange', this.visibilityHandler);
    }
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

  silentSyncInscripciones() {
    this.inscriptionService.listarInscripciones().subscribe({
      next: (data) => {
        if (!data || !Array.isArray(data)) return;
        const currentFingerprint = this.inscripciones.map(i => `${i.id || i.id_inscripcion}_${i.estado}_${i.curso_id}`).join('|');
        const newFingerprint = data.map(i => `${i.id || i.id_inscripcion}_${i.estado}_${i.curso_id}`).join('|');
        if (currentFingerprint !== newFingerprint) {
          this.inscripciones = data;
          this.actualizarEstadisticas();
        }
      },
      error: () => {}
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
