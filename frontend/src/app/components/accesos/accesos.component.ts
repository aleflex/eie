import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AccesoService } from '../../services/acceso.service';
import { AuthService } from '../../services/auth.service';
import { RoleService } from '../../services/role.service';

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

  // Filtros
  searchTerm: string = '';
  activeTab: string = 'todos'; // 'todos', 'docente', 'estudiante', 'admin'
  statusFilter: string = 'todos'; // 'todos', 'con_acceso', 'sin_acceso'

  // Modales
  showAssignModal: boolean = false;
  showEditModal: boolean = false;

  // Catálogo de Roles para Personal Administrativo
  rolesAdministrativos = [
    { id: 1, nombre: 'Administrador General' },
    { id: 4, nombre: 'Jefe de Unidad / Dirección' },
    { id: 5, nombre: 'Secretaría' }
  ];

  // Modelos de formulario
  selectedPersona: any = null;
  formModel: any = {
    nombres: '',
    usuario: '',
    email: '',
    password: '',
    id_rol: 1
  };

  // Gestión de Roles y Permisos Granulares
  rolesList: any[] = [];
  rolesPermisos: any = {};
  selectedRoleId: number = 5; // Secretaría por defecto
  rolesLoading: boolean = false;
  isSavingPermisos: boolean = false;
  showRoleModal: boolean = false;
  roleFormModel = { id: null as number | null, nombre_rol: '', descripcion: '' };
  modulosSistema = this.roleService.MODULOS_SISTEMA;
  roleSuccessMessage: string = '';
  roleErrorMessage: string = '';

  constructor(
    private accesoService: AccesoService,
    private authService: AuthService,
    private roleService: RoleService,
    private router: Router
  ) {}

  ngOnInit() {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }
    this.user = this.authService.getUser();
    this.cargarAccesos();
    this.cargarRoles();
    this.cargarRolesYPermisos();
  }

  cargarRoles() {
    this.roleService.getRoles().subscribe({
      next: (roles: any[]) => {
        const adminRoles = roles.filter(r => r.id_rol !== 2 && r.id_rol !== 3);
        if (adminRoles.length > 0) {
          this.rolesAdministrativos = adminRoles.map(r => {
            const lower = (r.nombre_rol || '').toLowerCase().trim();
            const friendlyName = lower === 'admin' ? 'Administrador General' :
                                 lower === 'directivo' ? 'Jefe de Unidad / Directivo' :
                                 lower === 'secretaria' ? 'Secretaría' :
                                 (r.nombre_rol.charAt(0).toUpperCase() + r.nombre_rol.slice(1));
            return { id: r.id_rol, nombre: friendlyName };
          });
        }
      },
      error: (e) => console.warn('No se pudieron cargar roles dinámicos:', e)
    });
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
    this.selectedPersona = { tipo: 'admin', nombres: 'Nuevo Usuario Administrativo' };
    this.formModel = {
      nombres: '',
      usuario: '',
      email: '',
      password: '',
      id_rol: 1
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
    
    let usuarioSugerido = persona.usuario || '';
    if (!usuarioSugerido && persona.nombres) {
      const nom = persona.nombres.split(' ')[0].toLowerCase();
      const ape = (persona.apellidos || '').split(' ')[0].toLowerCase();
      usuarioSugerido = ape ? `${nom}.${ape}` : nom;
      usuarioSugerido = usuarioSugerido.replace(/[^a-z0-9.]/g, '');
    }

    this.formModel = {
      nombres: '',
      usuario: usuarioSugerido,
      email: persona.correo_electronico || '',
      password: '',
      id_rol: persona.tipo === 'admin' ? 1 : (persona.tipo === 'docente' ? 3 : 2)
    };
    this.showAssignModal = true;
  }

  closeAssignModal() {
    this.showAssignModal = false;
    this.selectedPersona = null;
    this.formModel = { nombres: '', usuario: '', email: '', password: '', id_rol: 1 };
  }

  asignarCredenciales() {
    if (!this.selectedPersona) return;

    if (this.selectedPersona.tipo === 'admin' && !this.formModel.nombres) {
      alert('Por favor complete el nombre completo del administrador o personal.');
      return;
    }

    if (!this.formModel.usuario || !this.formModel.password) {
      alert('Por favor complete el Nombre de Usuario y la Contraseña obligatorios.');
      return;
    }

    const payload: any = {
      tipo: this.selectedPersona.tipo,
      usuario: this.formModel.usuario,
      email: this.formModel.email || `${this.formModel.usuario}@eie.edu.bo`,
      password: this.formModel.password,
      id_rol: this.formModel.id_rol
    };

    if (this.selectedPersona.tipo === 'admin') {
      payload.nombres = this.formModel.nombres;
    } else {
      payload.persona_id = this.selectedPersona.persona_id;
    }

    this.accesoService.asignarCredenciales(payload).subscribe({
      next: () => {
        alert(this.selectedPersona.tipo === 'admin' ? 'Usuario administrativo creado con éxito' : 'Credenciales asignadas y cuenta de acceso creada con éxito.');
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
      usuario: persona.usuario || '',
      email: persona.correo_electronico || '',
      password: '',
      id_rol: persona.id_rol || 1
    };
    this.showEditModal = true;
  }

  closeEditModal() {
    this.showEditModal = false;
    this.selectedPersona = null;
    this.formModel = { nombres: '', usuario: '', email: '', password: '', id_rol: 1 };
  }

  actualizarCredenciales() {
    if (!this.selectedPersona || !this.formModel.usuario) {
      alert('El Nombre de Usuario es requerido.');
      return;
    }

    const payload: any = {
      usuario: this.formModel.usuario,
      email: this.formModel.email,
      id_rol: this.formModel.id_rol
    };

    if (this.formModel.password && this.formModel.password.trim() !== '') {
      payload.password = this.formModel.password;
    }

    this.accesoService.actualizarCredenciales(this.selectedPersona.user_id, payload).subscribe({
      next: () => {
        alert('Credenciales y rol actualizados correctamente.');
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
    if (this.user && (this.user.id === persona.user_id || this.user.id_usuario === persona.user_id)) {
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

  // --- GESTIÓN DE ROLES Y MATRIZ DE PERMISOS GRANULARES ---
  cargarRolesYPermisos() {
    this.rolesLoading = true;
    this.roleService.getRoles().subscribe({
      next: (roles) => {
        this.rolesList = roles.filter(r => r.id_rol !== 2 && r.id_rol !== 3);
        if (this.rolesList.length > 0 && !this.rolesList.some(r => r.id_rol === this.selectedRoleId)) {
          this.selectedRoleId = this.rolesList[0].id_rol;
        }

        this.roleService.getPermisos().subscribe({
          next: (permisos) => {
            this.rolesPermisos = permisos || {};
            this.ensureRolePermisosStructure(this.selectedRoleId);
            this.rolesLoading = false;
          },
          error: () => {
            this.rolesLoading = false;
          }
        });
      },
      error: () => {
        this.rolesLoading = false;
      }
    });
  }

  ensureRolePermisosStructure(roleId: number) {
    if (!this.rolesPermisos[roleId]) {
      this.rolesPermisos[roleId] = {};
    }
    this.modulosSistema.forEach(m => {
      const existing = this.rolesPermisos[roleId][m.key];
      if (existing === undefined || existing === null) {
        if (roleId === 1) {
          this.rolesPermisos[roleId][m.key] = { ver: true, crear: true, editar: true, eliminar: true };
        } else if (roleId === 4) {
          const ok = ['admin', 'students', 'courses', 'docentes-list', 'paralelos', 'reports'].includes(m.key);
          this.rolesPermisos[roleId][m.key] = { ver: ok, crear: ok, editar: ok, eliminar: ok && m.key !== 'docentes-list' };
        } else if (roleId === 5) {
          const ok = ['admin', 'students', 'courses', 'reports'].includes(m.key);
          this.rolesPermisos[roleId][m.key] = { ver: ok, crear: ok && m.key === 'students', editar: ok && m.key === 'students', eliminar: false };
        } else {
          this.rolesPermisos[roleId][m.key] = { ver: false, crear: false, editar: false, eliminar: false };
        }
      } else if (typeof existing === 'boolean') {
        this.rolesPermisos[roleId][m.key] = {
          ver: existing,
          crear: existing && roleId !== 5,
          editar: existing && roleId !== 5,
          eliminar: existing && roleId === 1
        };
      } else {
        this.rolesPermisos[roleId][m.key] = {
          ver: existing.ver !== false,
          crear: !!existing.crear,
          editar: !!existing.editar,
          eliminar: !!existing.eliminar
        };
      }
    });
  }

  selectRole(roleId: number) {
    this.selectedRoleId = roleId;
    this.ensureRolePermisosStructure(roleId);
  }

  getSelectedRole() {
    return this.rolesList.find(r => r.id_rol === this.selectedRoleId) || { id_rol: this.selectedRoleId, nombre_rol: 'Rol Seleccionado' };
  }

  formatRoleName(name: string): string {
    if (!name) return 'Rol';
    const lower = name.toLowerCase().trim();
    if (lower === 'admin') return 'Administrador General';
    if (lower === 'directivo') return 'Jefe de Unidad / Directivo';
    if (lower === 'secretaria') return 'Secretaría';
    return name.charAt(0).toUpperCase() + name.slice(1);
  }

  isModuleEnabled(roleId: number, moduleKey: string): boolean {
    const p = this.rolesPermisos[roleId]?.[moduleKey];
    if (!p) return false;
    if (typeof p === 'boolean') return p;
    return !!p.ver || !!p.crear || !!p.editar || !!p.eliminar;
  }

  toggleModule(moduleKey: string) {
    if (this.selectedRoleId === 1) return;
    this.ensureRolePermisosStructure(this.selectedRoleId);
    const curr = this.isModuleEnabled(this.selectedRoleId, moduleKey);
    const nextVal = !curr;
    this.rolesPermisos[this.selectedRoleId][moduleKey] = {
      ver: nextVal,
      crear: nextVal,
      editar: nextVal,
      eliminar: nextVal
    };
  }

  isActionAllowed(roleId: number, moduleKey: string, action: string): boolean {
    if (roleId === 1) return true;
    const p = this.rolesPermisos[roleId]?.[moduleKey];
    if (!p) return false;
    if (typeof p === 'boolean') return p;
    return p[action] === true;
  }

  toggleAction(moduleKey: string, action: string, event?: Event) {
    if (event) event.stopPropagation();
    if (this.selectedRoleId === 1) return;
    this.ensureRolePermisosStructure(this.selectedRoleId);
    const p = this.rolesPermisos[this.selectedRoleId][moduleKey];
    p[action] = !p[action];
    if ((action === 'crear' || action === 'editar' || action === 'eliminar') && p[action]) {
      p.ver = true;
    }
    if (action === 'ver' && !p.ver) {
      p.crear = false;
      p.editar = false;
      p.eliminar = false;
    }
  }

  savePermissions() {
    this.isSavingPermisos = true;
    this.roleService.savePermisos(this.rolesPermisos).subscribe({
      next: () => {
        this.isSavingPermisos = false;
        this.roleSuccessMessage = '¡Matriz de permisos guardada y aplicada exitosamente!';
        setTimeout(() => this.roleSuccessMessage = '', 4000);
      },
      error: (err) => {
        this.isSavingPermisos = false;
        this.roleErrorMessage = 'Error al guardar permisos: ' + (err.error?.message || err.message);
        setTimeout(() => this.roleErrorMessage = '', 4000);
      }
    });
  }

  openCreateRoleModal() {
    this.roleFormModel = { id: null, nombre_rol: '', descripcion: '' };
    this.showRoleModal = true;
  }

  openEditRoleModal(rol: any) {
    this.roleFormModel = { id: rol.id_rol, nombre_rol: rol.nombre_rol, descripcion: rol.descripcion || '' };
    this.showRoleModal = true;
  }

  closeRoleModal() {
    this.showRoleModal = false;
    this.roleFormModel = { id: null, nombre_rol: '', descripcion: '' };
  }

  saveRole() {
    if (!this.roleFormModel.nombre_rol || !this.roleFormModel.nombre_rol.trim()) {
      alert('Por favor ingresa el nombre del nuevo rol.');
      return;
    }

    if (this.roleFormModel.id) {
      this.roleService.updateRole(this.roleFormModel.id, this.roleFormModel).subscribe({
        next: () => {
          alert('Rol actualizado correctamente.');
          this.closeRoleModal();
          this.cargarRolesYPermisos();
        },
        error: (err) => alert('Error al actualizar rol: ' + (err.error?.message || err.message))
      });
    } else {
      this.roleService.createRole(this.roleFormModel).subscribe({
        next: (res) => {
          alert(`Rol "${this.roleFormModel.nombre_rol}" creado exitosamente.`);
          this.closeRoleModal();
          this.cargarRolesYPermisos();
          if (res.rol) {
            this.selectedRoleId = res.rol.id_rol;
            this.formModel.id_rol = res.rol.id_rol;
            this.ensureRolePermisosStructure(this.selectedRoleId);
            this.roleService.savePermisos(this.rolesPermisos).subscribe();
          }
        },
        error: (err) => alert('Error al crear rol: ' + (err.error?.message || err.message))
      });
    }
  }

  deleteRole(rol: any) {
    if ([1, 2, 3].includes(rol.id_rol)) {
      alert('Los roles base del sistema no pueden ser eliminados.');
      return;
    }
    if (!confirm(`¿Estás seguro de eliminar el rol "${rol.nombre_rol}"?`)) return;

    this.roleService.deleteRole(rol.id_rol).subscribe({
      next: () => {
        alert('Rol eliminado exitosamente.');
        this.cargarRolesYPermisos();
      },
      error: (err) => alert('Error al eliminar rol: ' + (err.error?.message || err.message))
    });
  }

  onLogout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
