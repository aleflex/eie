import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { SettingsService } from '../../services/settings.service';
import { AuthService } from '../../services/auth.service';
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

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
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
    private router: Router,
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

  /**
   * Cierra la sesión del usuario actual y lo redirige a la pantalla de login.
   */
  onLogout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
