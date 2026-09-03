import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute, RouterModule } from '@angular/router';
import { SettingsService } from '../../services/settings.service';
import { AuthService } from '../../services/auth.service';
import { RoleService } from '../../services/role.service';
import { ImageCompressorService } from '../../services/image-compressor.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './settings.component.html',
  styleUrl: './settings.component.css'
})
export class SettingsComponent implements OnInit {
  apiUrl = environment.apiUrl;
  activeTab: string = 'academic';
  isLoading: boolean = true;
  isSaving: boolean = false;
  isSavingProfile: boolean = false;
  user: any = null;

  // Control de la barra lateral (Sidebar)
  isSidebarCollapsed: boolean = false;
  isMobileMenuOpen: boolean = false;

  // Roles y Permisos de Módulos
  rolesList: any[] = [];
  rolesPermisos: any = {};
  selectedRoleId: number = 1;
  modulosSistema: any[] = [];
  rolesLoading: boolean = false;
  isSavingPermisos: boolean = false;

  // Modal Nuevo/Editar Rol
  showRoleModal: boolean = false;
  roleFormModel: { id?: number; nombre_rol: string; descripcion: string } = {
    nombre_rol: '',
    descripcion: ''
  };

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  // Datos para Mi Perfil
  profileData: any = {
    name: '',
    email: '',
    password: ''
  };
  profilePhotoFile: File | null = null;
  profilePhotoFileName: string = '';

  // Objeto local para almacenar las configuraciones
  settings: any = {
    fecha_inicio_inscripcion: '',
    fecha_fin_inscripcion: '',
    limite_pdf_mb: 5,
    comprimir_imagenes: true,
    nombre_institucion: '',
    nombre_director: '',
    grado_director: '',
    cupo_defecto_paralelo: 25
  };

  // Mensajes de feedback
  successMessage: string = '';
  errorMessage: string = '';

  constructor(
    private settingsService: SettingsService,
    private authService: AuthService,
    private roleService: RoleService,
    private router: Router,
    private route: ActivatedRoute,
    private imageCompressor: ImageCompressorService
  ) {}

  /**
   * Se ejecuta al cargar la pantalla de configuración.
   * Verifica si el usuario está conectado y carga sus configuraciones.
   */
  ngOnInit() {
    // Protección de ruta simple
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }

    this.user = this.authService.getUser();
    if (this.user) {
      this.profileData.name = this.user.name || '';
      this.profileData.email = this.user.email || '';
    }
    this.biometricEnabled = this.authService.isBiometricEnabled();
    this.cargarConfiguraciones();

    this.route.queryParams.subscribe(params => {
      if (params['tab'] === 'roles') {
        this.onSelectRolesTab();
      }
    });
  }

  biometricEnabled: boolean = false;

  async toggleBiometric(event: any) {
    const isChecked = event.target.checked;
    if (isChecked) {
      const res = await this.authService.enableBiometricForCurrentDevice();
      if (res.success) {
        this.biometricEnabled = true;
        this.successMessage = res.message;
        setTimeout(() => this.successMessage = '', 4000);
      } else {
        this.biometricEnabled = false;
        event.target.checked = false;
        this.errorMessage = res.message;
        setTimeout(() => this.errorMessage = '', 4000);
      }
    } else {
      this.authService.disableBiometricForCurrentDevice();
      this.biometricEnabled = false;
      this.successMessage = 'Acceso biométrico (Huella/Rostro) desactivado en este dispositivo.';
      setTimeout(() => this.successMessage = '', 4000);
    }
  }

  onImageError(event: any) {
    if (event && event.target) {
      const src = event.target.src || '';
      if (!src.includes('default-avatar.svg') && !src.includes('default-avatar.png')) {
        event.target.src = '/assets/default-avatar.svg';
      }
    }
  }

  getPhotoUrl(url: string | null | undefined): string {
    if (!url) {
      return '';
    }
    if (url.startsWith('data:') || url.startsWith('http://') || url.startsWith('https://')) {
      return url;
    }
    const apiBase = this.apiUrl.replace(/\/api\/?$/, '');
    return apiBase + (url.startsWith('/') ? '' : '/') + url;
  }

  /**
   * Pide al servidor las configuraciones actuales del sistema (fechas, tamaños de archivos)
   * y las guarda en la variable local `settings` para mostrarlas en pantalla.
   */
  cargarConfiguraciones() {
    this.isLoading = true;
    this.settingsService.getSettings().subscribe({
      next: (data: any) => {
        // Combinar datos recibidos con los valores por defecto locales
        this.settings = { ...this.settings, ...data };
        this.isLoading = false;
      },
      error: (err: any) => {
        console.error('Error al cargar configuraciones', err);
        this.errorMessage = 'No se pudieron cargar las configuraciones del servidor.';
        this.isLoading = false;
      }
    });
  }

  /**
   * Toma las configuraciones modificadas en la pantalla y las envía al servidor
   * para guardarlas permanentemente en la base de datos.
   */
  guardarConfiguraciones() {
    this.isSaving = true;
    this.successMessage = '';
    this.errorMessage = '';

    this.settingsService.saveSettings(this.settings).subscribe({
      next: (response: any) => {
        this.settings = { ...this.settings, ...response.settings };
        this.successMessage = '¡Configuraciones guardadas y aplicadas con éxito!';
        this.isSaving = false;

        // Limpiar mensaje de éxito tras 4 segundos
        setTimeout(() => {
          this.successMessage = '';
        }, 4000);
      },
      error: (err: any) => {
        console.error('Error al guardar configuraciones', err);
        this.errorMessage = 'Hubo un error al intentar guardar los cambios.';
        this.isSaving = false;
      }
    });
  }

  /**
   * Se activa cuando el usuario elige una nueva foto de perfil.
   * Verifica que sea una imagen válida y la comprime para ahorrar espacio antes de subirla.
   * @param event Evento del selector de archivos.
   */
  onProfilePhotoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      alert('Por favor, selecciona una imagen de perfil válida.');
      return;
    }
    // Comprimir foto de perfil del administrador (RF16 - T2)
    this.imageCompressor.compressImage(file, 800, 800, 0.85).then(compressed => {
      this.profilePhotoFile = compressed;
      this.profilePhotoFileName = compressed.name;
      console.log(`[RF16] Foto admin: ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
    });
  }

  /**
   * Envía los nuevos datos personales del usuario (nombre, correo, nueva contraseña o foto)
   * al servidor para actualizar su perfil.
   */
  guardarPerfil() {
    if (!this.profileData.name || !this.profileData.email) {
      this.errorMessage = 'Los campos Nombre y Correo son obligatorios.';
      return;
    }

    this.isSavingProfile = true;
    this.successMessage = '';
    this.errorMessage = '';

    const formData = new FormData();
    const uid = this.user?.id_usuario || this.user?.id;
    if (uid) {
      formData.append('user_id', String(uid));
    }
    formData.append('name', this.profileData.name);
    formData.append('email', this.profileData.email);
    if (this.profileData.password) {
      formData.append('password', this.profileData.password);
    }
    if (this.profilePhotoFile) {
      formData.append('foto', this.profilePhotoFile);
    }

    this.authService.updateProfile(formData).subscribe({
      next: (response: any) => {
        this.user = response.user;
        this.profileData.password = '';
        this.profilePhotoFile = null;
        this.profilePhotoFileName = '';

        const current = this.authService.obtenerUsuario() || {};
        const updated = { ...current, ...response.user };
        sessionStorage.setItem('usuario', JSON.stringify(updated));
        localStorage.setItem('usuario', JSON.stringify(updated));
        localStorage.setItem('eie_biometric_user', JSON.stringify(updated));

        this.successMessage = '¡Tu perfil ha sido actualizado con éxito!';
        this.isSavingProfile = false;

        // Limpiar mensaje tras 4 segundos
        setTimeout(() => {
          this.successMessage = '';
        }, 4000);
      },
      error: (err: any) => {
        console.error('Error al actualizar perfil', err);
        this.errorMessage = err.error?.message || 'Error al intentar guardar los cambios de tu perfil.';
        this.isSavingProfile = false;
      }
    });
  }

  // --- GESTIÓN DE ROLES Y MATRIZ DE PERMISOS DE MÓDULOS ---
  onSelectRolesTab() {
    this.activeTab = 'roles';
    this.loadRolesAndPermissions();
  }

  loadRolesAndPermissions() {
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

  formatRoleName(name: string): string {
    if (!name) return 'Rol';
    const lower = name.toLowerCase().trim();
    if (lower === 'admin') return 'Administrador General';
    if (lower === 'directivo') return 'Jefe de Unidad / Directivo';
    if (lower === 'secretaria') return 'Secretaría';
    return name.charAt(0).toUpperCase() + name.slice(1);
  }

  selectRole(roleId: number) {
    this.selectedRoleId = roleId;
    this.ensureRolePermisosStructure(roleId);
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
        this.successMessage = '¡Matriz de permisos guardada exitosamente! Se aplicará al menú de cada rol.';
        setTimeout(() => this.successMessage = '', 4000);
      },
      error: (err) => {
        this.isSavingPermisos = false;
        this.errorMessage = 'Error al guardar los permisos: ' + (err.error?.message || err.message);
        setTimeout(() => this.errorMessage = '', 4000);
      }
    });
  }

  openCreateRoleModal() {
    this.roleFormModel = { nombre_rol: '', descripcion: '' };
    this.showRoleModal = true;
  }

  openEditRoleModal(rol: any) {
    this.roleFormModel = { id: rol.id_rol, nombre_rol: rol.nombre_rol, descripcion: rol.descripcion || '' };
    this.showRoleModal = true;
  }

  closeRoleModal() {
    this.showRoleModal = false;
    this.roleFormModel = { nombre_rol: '', descripcion: '' };
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
          this.loadRolesAndPermissions();
        },
        error: (err) => alert('Error al actualizar rol: ' + (err.error?.message || err.message))
      });
    } else {
      this.roleService.createRole(this.roleFormModel).subscribe({
        next: (res) => {
          alert(`Rol "${this.roleFormModel.nombre_rol}" creado exitosamente.`);
          this.closeRoleModal();
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

  deleteRole(rol: any) {
    if ([1, 2, 3].includes(rol.id_rol)) {
      alert('Los roles base del sistema no pueden ser eliminados.');
      return;
    }

    if (!confirm(`¿Estás seguro de eliminar el rol "${rol.nombre_rol}"?`)) {
      return;
    }

    this.roleService.deleteRole(rol.id_rol).subscribe({
      next: () => {
        alert('Rol eliminado exitosamente.');
        this.loadRolesAndPermissions();
      },
      error: (err) => alert('Error al eliminar rol: ' + (err.error?.message || err.message))
    });
  }

  getSelectedRole() {
    return this.rolesList.find(r => r.id_rol === this.selectedRoleId) || { id_rol: this.selectedRoleId, nombre_rol: 'Rol Seleccionado' };
  }

  /**
   * Cierra la sesión del usuario actual y lo redirige a la pantalla de login.
   */
  onLogout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
