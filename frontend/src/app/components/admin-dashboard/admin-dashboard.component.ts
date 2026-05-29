import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { InscriptionService } from '../../services/inscription.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrl: './admin-dashboard.component.css'
})
export class AdminDashboardComponent implements OnInit {
  inscripciones: any[] = [];
  isLoading: boolean = true;
  user: any = null;

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
