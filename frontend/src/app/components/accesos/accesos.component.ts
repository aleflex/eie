import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AccesoService } from '../../services/acceso.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-accesos',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './accesos.component.html',
  styleUrl: './accesos.component.css'
})
export class AccesosComponent implements OnInit {
  personas: any[] = [];
  filteredPersonas: any[] = [];
  isLoading: boolean = true;
  user: any = null;

  // Filtros
  searchTerm: string = '';
  activeTab: string = 'todos'; // 'todos', 'docente', 'estudiante', 'admin'
  statusFilter: string = 'todos'; // 'todos', 'con_acceso', 'sin_acceso'

  // Modales
  showAssignModal: boolean = false;
  showEditModal: boolean = false;

  // Modelos de formulario
  selectedPersona: any = null;
  formModel: any = {
    nombres: '',
    email: '',
    password: ''
  };

  constructor(
    private accesoService: AccesoService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }
    this.user = this.authService.getUser();
    this.cargarAccesos();
  }

  cargarAccesos() {
    this.isLoading = true;
    this.accesoService.getAccesos().subscribe({
      next: (data: any[]) => {
        this.personas = data;
        this.filtrarPersonas();
        this.isLoading = false;
      },
      error: (err: any) => {
        console.error('Error al cargar accesos', err);
        this.isLoading = false;
      }
    });
  }

  filtrarPersonas() {
    let result = [...this.personas];

    // Filtro por pestaña (todos, docentes, estudiantes, administradores)
    if (this.activeTab !== 'todos') {
      result = result.filter(p => p.tipo === this.activeTab);
    }

    // Filtro por estado de cuenta (con acceso, sin acceso)
    if (this.statusFilter === 'con_acceso') {
      result = result.filter(p => p.tiene_cuenta);
    } else if (this.statusFilter === 'sin_acceso') {
      result = result.filter(p => !p.tiene_cuenta);
    }

    // Filtro por término de búsqueda (Nombre, CI o Correo)
    if (this.searchTerm && this.searchTerm.trim() !== '') {
      const term = this.searchTerm.toLowerCase().trim();
      result = result.filter(p => {
        const nombreCompleto = `${p.nombres} ${p.apellidos}`.toLowerCase();
        const ci = p.ci.toLowerCase();
        const correo = p.correo_electronico ? p.correo_electronico.toLowerCase() : '';
        return nombreCompleto.includes(term) || ci.includes(term) || correo.includes(term);
      });
    }

    this.filteredPersonas = result;
  }

  cambiarTab(tab: string) {
    this.activeTab = tab;
    // Si se selecciona la pestaña de administradores, reseteamos el filtro de estado ya que todos tienen cuenta
    if (tab === 'admin') {
      this.statusFilter = 'todos';
    }
    this.filtrarPersonas();
  }

  cambiarFiltroEstado(filter: string) {
    this.statusFilter = filter;
    this.filtrarPersonas();
  }

  onSearchInput() {
    this.filtrarPersonas();
  }

  // --- REGISTRAR NUEVO ADMINISTRADOR ---
  openAdminCreateForm() {
    this.selectedPersona = { tipo: 'admin', nombres: 'Nuevo Administrador' };
    this.formModel = {
      nombres: '',
      email: '',
      password: ''
    };
    this.showAssignModal = true;
  }

  // Generador de correo institucional basado en el rol y nombre
  generarCorreoInstitucional(persona: any): string {
    if (!persona) return '';
    
    let nombres = (persona.nombres || '').trim().toLowerCase();
    let apellidos = (persona.apellidos || '').trim().toLowerCase();
    
    const titulos = ['tte', 'cap', 'cnl', 'sofi', 'sof', 'my', 'sgt', 'subtte', 'dr', 'dra', 'lic', 'ing', 'daen', 'tcnl', 'subof'];
    
    let nombreParts = nombres.split(' ').filter((part: string) => !titulos.includes(part.replace(/\./g, '')));
    let apellidoParts = apellidos.split(' ').filter((part: string) => !titulos.includes(part.replace(/\./g, '')));
    
    let base = '';
    if (nombreParts.length > 0 && apellidoParts.length > 0) {
      base = `${nombreParts[0]}.${apellidoParts[0]}`;
    } else if (nombreParts.length > 0) {
      base = nombreParts.length > 1 ? `${nombreParts[0]}.${nombreParts[1]}` : nombreParts[0];
    } else if (apellidoParts.length > 0) {
      base = apellidoParts.length > 1 ? `${apellidoParts[0]}.${apellidoParts[1]}` : apellidoParts[0];
    } else {
      base = 'usuario';
    }
    
    base = base
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9.]/g, '');
      
    let subdominio = '';
    if (persona.tipo === 'estudiante') {
      subdominio = 'est.';
    } else if (persona.tipo === 'docente') {
      subdominio = 'doc.';
    } else if (persona.tipo === 'admin') {
      subdominio = 'admin.';
    }
    
    return `${base}@${subdominio}eie.edu.bo`;
  }

  onAdminNameChange() {
    if (this.selectedPersona?.tipo !== 'admin') return;
    
    const name = this.formModel.nombres || '';
    if (!name.trim()) {
      this.formModel.email = '';
      return;
    }
    
    let base = name.trim().toLowerCase();
    const titulos = ['tte', 'cap', 'cnl', 'sofi', 'sof', 'my', 'sgt', 'subtte', 'dr', 'dra', 'lic', 'ing', 'daen', 'tcnl', 'subof'];
    let parts = base.split(' ').filter((part: string) => !titulos.includes(part.replace(/\./g, '')));
    
    if (parts.length > 1) {
      base = `${parts[0]}.${parts[1]}`;
    } else if (parts.length > 0) {
      base = parts[0];
    }
    
    base = base
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9.]/g, '');
      
    this.formModel.email = `${base}@admin.eie.edu.bo`;
  }

  // --- MODAL ASIGNAR / CREAR ---
  openAssignModal(persona: any) {
    this.selectedPersona = persona;
    
    // Sugerir el correo institucional por defecto para cuentas nuevas
    const correoActual = persona.correo_electronico || '';
    const esInstitucional = correoActual.includes('@est.eie.edu.bo') || correoActual.includes('@doc.eie.edu.bo');
    const emailSugerido = esInstitucional ? correoActual : this.generarCorreoInstitucional(persona);

    this.formModel = {
      nombres: '',
      email: emailSugerido,
      password: ''
    };
    this.showAssignModal = true;
  }

  closeAssignModal() {
    this.showAssignModal = false;
    this.selectedPersona = null;
    this.formModel = { nombres: '', email: '', password: '' };
  }

  asignarCredenciales() {
    if (!this.selectedPersona) return;

    if (this.selectedPersona.tipo === 'admin' && !this.formModel.nombres) {
      alert('Por favor complete el nombre completo del administrador.');
      return;
    }

    if (!this.formModel.email || !this.formModel.password) {
      alert('Por favor complete todos los campos obligatorios.');
      return;
    }

    const payload: any = {
      tipo: this.selectedPersona.tipo,
      email: this.formModel.email,
      password: this.formModel.password
    };

    if (this.selectedPersona.tipo === 'admin') {
      payload.nombres = this.formModel.nombres;
    } else {
      payload.persona_id = this.selectedPersona.persona_id;
    }

    this.accesoService.asignarCredenciales(payload).subscribe({
      next: () => {
        alert(this.selectedPersona.tipo === 'admin' ? 'Administrador creado con éxito' : 'Credenciales asignadas y cuenta de acceso creada con éxito.');
        this.closeAssignModal();
        this.cargarAccesos();
      },
      error: (err: any) => {
        alert('Error al procesar solicitud: ' + (err.error?.message || err.message));
      }
    });
  }

  // --- MODAL EDITAR ---
  openEditModal(persona: any) {
    this.selectedPersona = persona;
    this.formModel = {
      nombres: persona.nombres || '',
      email: persona.correo_electronico || '',
      password: ''
    };
    this.showEditModal = true;
  }

  closeEditModal() {
    this.showEditModal = false;
    this.selectedPersona = null;
    this.formModel = { nombres: '', email: '', password: '' };
  }

  actualizarCredenciales() {
    if (!this.selectedPersona || !this.formModel.email) {
      alert('El correo electrónico es requerido.');
      return;
    }

    const payload: any = {
      email: this.formModel.email
    };

    if (this.formModel.password && this.formModel.password.trim() !== '') {
      payload.password = this.formModel.password;
    }

    this.accesoService.actualizarCredenciales(this.selectedPersona.user_id, payload).subscribe({
      next: () => {
        alert('Credenciales actualizadas y aplicadas correctamente.');
        this.closeEditModal();
        this.cargarAccesos();
      },
      error: (err: any) => {
        alert('Error al actualizar credenciales: ' + (err.error?.message || err.message));
      }
    });
  }

  // --- DESVINCULAR CUENTA ---
  desvincularCuenta(persona: any) {
    // Prohibir la auto-eliminación
    if (this.user && this.user.id === persona.user_id) {
      alert('Seguridad del Sistema: No puedes eliminar tu propia cuenta de acceso activa.');
      return;
    }

    const confirmMsg = persona.tipo === 'admin' 
      ? `¿Estás seguro de que deseas eliminar permanentemente la cuenta del administrador ${persona.nombres}?` 
      : `¿Estás seguro de que deseas desvincular y eliminar la cuenta de acceso de ${persona.nombres} ${persona.apellidos}? El estudiante/docente no podrá volver a iniciar sesión, pero sus datos académicos permanecerán intactos.`;
      
    if (!confirm(confirmMsg)) return;

    this.accesoService.desvincularCuenta(persona.user_id).subscribe({
      next: () => {
        alert(persona.tipo === 'admin' ? 'Administrador eliminado con éxito.' : 'Cuenta de acceso desvinculada y eliminada con éxito.');
        this.cargarAccesos();
      },
      error: (err: any) => {
        alert('Error al desvincular cuenta: ' + (err.error?.message || err.message));
      }
    });
  }

  onLogout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
