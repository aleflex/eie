import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { RoleService, ModuloInfo } from '../../services/role.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-roles',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './roles.component.html',
  styleUrl: './roles.component.css'
})
export class RolesComponent implements OnInit {
  apiUrl = environment.apiUrl;
  user: any = null;

  // Control de la barra lateral (Sidebar)
  isSidebarCollapsed: boolean = false;
  isMobileMenuOpen: boolean = false;

  // Roles y Permisos de Módulos
  rolesList: any[] = [];
  rolesPermisos: any = {};
  selectedRoleId: number = 1;
  modulosSistema: ModuloInfo[] = [];
  rolesLoading: boolean = false;
  isSavingPermisos: boolean = false;

  // Feedback
  successMessage: string = '';
  errorMessage: string = '';

  // Modal Crear / Editar Rol
  showRoleModal: boolean = false;
  roleFormModel: { id?: number; nombre_rol: string; descripcion: string } = {
    nombre_rol: '',
    descripcion: ''
  };

  constructor(
    private authService: AuthService,
    private roleService: RoleService,
    private router: Router
  ) {}

  ngOnInit(): void {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }

    this.user = this.authService.getUser();

    this.authService.usuario$.subscribe(u => {
      if (u) this.user = u;
    });

    this.authService.cargarPerfilActualizado().subscribe({
      next: (res: any) => {
        if (res && res.user) this.user = res.user;
      },
      error: (e) => console.warn('No se pudo refrescar perfil en tiempo real', e)
    });

    this.loadRolesAndPermissions();
  }

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  toggleSidebar(): void {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu(): void {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu(): void {
    this.isMobileMenuOpen = false;
  }

  onLogout(): void {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }

  getPhotoUrl(url: string | null | undefined): string {
    if (!url) return '';
    if (url.startsWith('data:') || url.startsWith('http://') || url.startsWith('https://')) {
      return url;
    }
    const apiBase = this.apiUrl.replace(/\/api\/?$/, '');
    return apiBase + (url.startsWith('/') ? '' : '/') + url;
  }

  onImageError(event: any): void {
    if (event && event.target) {
      const src = event.target.src || '';
      if (!src.includes('default-avatar.svg') && !src.includes('default-avatar.png')) {
        event.target.src = '/assets/default-avatar.svg';
      }
    }
  }

  // --- GESTIÓN DE ROLES Y MATRIZ DE PERMISOS ---
  loadRolesAndPermissions(): void {
    this.rolesLoading = true;
    this.modulosSistema = this.roleService.MODULOS_SISTEMA;

    this.roleService.getRoles().subscribe({
      next: (roles) => {
        // Excluir estudiantes (2) y docentes (3) de la matriz de módulos administrativos
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
        this.errorMessage = 'No se pudieron cargar los roles del sistema.';
      }
    });
  }

  ensureRolePermisosStructure(roleId: number): void {
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

  formatRoleName(name: string): string {
    if (!name) return 'Rol';
    const lower = name.toLowerCase().trim();
    if (lower === 'admin') return 'Administrador General';
    if (lower === 'directivo') return 'Jefe de Unidad / Directivo';
    if (lower === 'secretaria') return 'Secretaría';
    return name.charAt(0).toUpperCase() + name.slice(1);
  }

  selectRole(roleId: number): void {
    this.selectedRoleId = roleId;
    this.ensureRolePermisosStructure(roleId);
  }

  isModuleEnabled(roleId: number, moduleKey: string): boolean {
    const p = this.rolesPermisos[roleId]?.[moduleKey];
    if (!p) return false;
    if (typeof p === 'boolean') return p;
    return !!p.ver || !!p.crear || !!p.editar || !!p.eliminar;
  }

  toggleModule(moduleKey: string): void {
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

  toggleAction(moduleKey: string, action: string, event?: Event): void {
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

  savePermissions(): void {
    this.isSavingPermisos = true;
    this.roleService.savePermisos(this.rolesPermisos).subscribe({
      next: () => {
        this.isSavingPermisos = false;
        this.successMessage = '¡Matriz de permisos guardada exitosamente! Se aplicará al menú y accesos de cada rol.';
        setTimeout(() => this.successMessage = '', 4500);
      },
      error: (err) => {
        this.isSavingPermisos = false;
        this.errorMessage = 'Error al guardar los permisos: ' + (err.error?.message || err.message);
        setTimeout(() => this.errorMessage = '', 4500);
      }
    });
  }

  openCreateRoleModal(): void {
    this.roleFormModel = { nombre_rol: '', descripcion: '' };
    this.showRoleModal = true;
  }

  openEditRoleModal(rol: any): void {
    this.roleFormModel = { id: rol.id_rol, nombre_rol: rol.nombre_rol, descripcion: rol.descripcion || '' };
    this.showRoleModal = true;
  }

  closeRoleModal(): void {
    this.showRoleModal = false;
    this.roleFormModel = { nombre_rol: '', descripcion: '' };
  }

  saveRole(): void {
    if (!this.roleFormModel.nombre_rol || !this.roleFormModel.nombre_rol.trim()) {
      alert('Por favor ingresa el nombre del nuevo rol.');
      return;
    }

    if (this.roleFormModel.id) {
      this.roleService.updateRole(this.roleFormModel.id, this.roleFormModel).subscribe({
        next: () => {
          this.closeRoleModal();
          this.successMessage = 'Rol actualizado correctamente.';
          setTimeout(() => this.successMessage = '', 4000);
          this.loadRolesAndPermissions();
        },
        error: (err) => alert('Error al actualizar rol: ' + (err.error?.message || err.message))
      });
    } else {
      this.roleService.createRole(this.roleFormModel).subscribe({
        next: (res) => {
          this.closeRoleModal();
          this.successMessage = `Rol "${this.roleFormModel.nombre_rol}" creado exitosamente.`;
          setTimeout(() => this.successMessage = '', 4000);
          this.loadRolesAndPermissions();
          if (res.rol) {
            this.selectedRoleId = res.rol.id_rol;
            this.ensureRolePermisosStructure(this.selectedRoleId);
            this.roleService.savePermisos(this.rolesPermisos).subscribe();
          }
        },
        error: (err) => alert('Error al crear rol: ' + (err.error?.message || err.message))
      });
    }
  }

  deleteRole(rol: any): void {
    if ([1, 2, 3, 4, 5].includes(rol.id_rol)) {
      alert('Los roles base del sistema no pueden ser eliminados.');
      return;
    }

    if (!confirm(`¿Estás seguro de eliminar el rol "${rol.nombre_rol}"?`)) {
      return;
    }

    this.roleService.deleteRole(rol.id_rol).subscribe({
      next: () => {
        this.successMessage = 'Rol eliminado exitosamente.';
        setTimeout(() => this.successMessage = '', 4000);
        this.loadRolesAndPermissions();
      },
      error: (err) => alert('Error al eliminar rol: ' + (err.error?.message || err.message))
    });
  }

  getSelectedRole(): any {
    return this.rolesList.find(r => r.id_rol === this.selectedRoleId) || { id_rol: this.selectedRoleId, nombre_rol: 'Rol Seleccionado' };
  }
}
