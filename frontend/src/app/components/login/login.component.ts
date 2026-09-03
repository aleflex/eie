import { Component, OnInit, NgZone } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';
import { environment } from '../../../environments/environment';
import { NativeBiometric } from '@capgo/capacitor-native-biometric';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent implements OnInit {
  /** Objeto que almacena las credenciales principales (Nombre de Usuario y Contraseña) */
  credenciales = {
    usuario: '',
    password: ''
  };
  
  /** Mensaje de error mostrado si el inicio de sesión falla */
  mensajeError: string = '';

  // Modal y formulario para Cambio Obligatorio de Contraseña
  showMustChangePasswordModal: boolean = false;
  nuevaPassword = {
    password: '',
    password_confirmation: ''
  };
  passwordError: string = '';
  pendingUserResponse: any = null;

  // Biometría Nativa (Huella Digital / Reconocimiento Facial Android)
  biometricsAvailable: boolean = false;
  isMobileDevice: boolean = false;
  hasSavedBiometricToken: boolean = false;

  // Configuración de API dinámica
  showApiConfigModal: boolean = false;
  customApiUrl: string = '';
  currentApiUrl: string = '';

  constructor(
    private servicioAutenticacion: AuthService,
    private enrutador: Router,
    private http: HttpClient,
    private ngZone: NgZone
  ) {}

  async ngOnInit() {
    const custom = localStorage.getItem('custom_api_url');
    if (custom && custom.includes('railway.app')) {
      localStorage.removeItem('custom_api_url');
    }
    this.customApiUrl = localStorage.getItem('custom_api_url') || '';
    this.currentApiUrl = environment.apiUrl;

    // Pre-calentar servidor backend Render en segundo plano mientras el usuario escribe sus credenciales
    try {
      this.http.get(`${this.currentApiUrl}/api/cursos`).subscribe({ error: () => {} });
    } catch (e) {}

    // Detectar si es dispositivo móvil o contenedor Capacitor
    this.isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
      || (window as any).Capacitor !== undefined;
    
    // Verificar si el sensor biométrico del celular Android está disponible (Solo en APK Nativa)
    if ((window as any).Capacitor && (window as any).Capacitor.isNativePlatform()) {
      try {
        const result = await NativeBiometric.isAvailable();
        if (result.isAvailable) {
          this.biometricsAvailable = true;
        }
      } catch (e) {
        console.log('Biometría no disponible en este dispositivo nativo.');
      }
    }

    // Verificar si el usuario habilitó activamente el acceso biométrico desde los ajustes de su cuenta
    const isBiometricEnabled = localStorage.getItem('eie_biometric_enabled');
    const savedToken = localStorage.getItem('eie_biometric_token');
    const savedUser = localStorage.getItem('eie_biometric_user');
    if ((isBiometricEnabled === 'true' || savedToken) && savedUser) {
      this.hasSavedBiometricToken = true;
      // Disparar diálogo nativo de huella automáticamente al abrir la app estilo banco
      setTimeout(() => {
        this.loginConBiometria();
      }, 500);
    } else {
      this.hasSavedBiometricToken = false;
    }
  }

  openApiConfig() {
    this.customApiUrl = localStorage.getItem('custom_api_url') || '';
    this.showApiConfigModal = true;
  }

  closeApiConfig() {
    this.showApiConfigModal = false;
  }

  saveApiConfig() {
    if (this.customApiUrl && this.customApiUrl.trim() !== '') {
      let url = this.customApiUrl.trim();
      if (url.endsWith('/')) {
        url = url.slice(0, -1);
      }
      if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'http://' + url;
      }
      localStorage.setItem('custom_api_url', url);
    } else {
      localStorage.removeItem('custom_api_url');
    }
    this.showApiConfigModal = false;
    window.location.reload();
  }

  resetApiConfig() {
    localStorage.removeItem('custom_api_url');
    this.showApiConfigModal = false;
    window.location.reload();
  }

  /**
   * Proceso principal de Inicio de Sesión por Nombre de Usuario y Contraseña
   */
  iniciarSesion() {
    this.mensajeError = '';
    const custom = localStorage.getItem('custom_api_url');
    if (custom && custom.includes('railway.app')) {
      localStorage.removeItem('custom_api_url');
    }
    this.currentApiUrl = environment.apiUrl;
    const payload = {
      usuario: (this.credenciales.usuario || '').trim().toLowerCase(),
      password: this.credenciales.password
    };

    this.servicioAutenticacion.iniciarSesion(payload).subscribe({
      next: (respuesta) => {
        console.log('✅ Inicio de sesión exitoso', respuesta);
        const usuario = respuesta.user;
        this.pendingUserResponse = respuesta;

        // Guardar credenciales para permitir acceso por Biometría Nativa en los siguientes ingresos
        if (usuario) {
          localStorage.setItem('eie_biometric_enabled', 'true');
          localStorage.setItem('eie_biometric_token', respuesta.token || 'token_valid');
          localStorage.setItem('eie_biometric_user', JSON.stringify(usuario));
          this.hasSavedBiometricToken = true;
        }

        // Verificar si debe cambiar su contraseña obligatoriamente
        if (usuario?.debe_cambiar_password) {
          this.showMustChangePasswordModal = true;
        } else {
          this.redireccionarSegunRol(usuario?.rol);
        }
      },
      error: (error) => {
        console.error('❌ Error de inicio de sesión', error);
        if (error.status === 0) {
          const apiIsHttp = (this.currentApiUrl || '').startsWith('http://');
          const pageIsHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
          if (pageIsHttps && apiIsHttp) {
            this.mensajeError = `⚠️ Error de seguridad (Mixed Content): Tu web en Vercel (HTTPS) no puede conectar al servidor HTTP (${this.currentApiUrl}). Configura una URL de servidor HTTPS (ej. Ngrok o servidor desplegado) en "Configurar Servidor".`;
          } else {
            this.mensajeError = `❌ No se pudo conectar al servidor API (${this.currentApiUrl}). Verifique que el servidor backend esté encendido y accesible.`;
          }
        } else {
          this.mensajeError = error.error?.message || 'Nombre de usuario o contraseña incorrectos. Por favor verifique sus datos.';
        }
      }
    });
  }

  /**
   * Inicia sesión activando el Diálogo Nativo del Sistema Android (BiometricPrompt)
   * Despliega directamente el sensor de Huella Dactilar o el Reconocimiento Facial del celular (estilo banca móvil).
   */
  async loginConBiometria() {
    const savedUser = localStorage.getItem('eie_biometric_user');

    if (!savedUser) {
      alert('Para activar la Huella Digital o Rostro en tu celular, ingresa con tu Nombre de Usuario y Contraseña una primera vez.');
      return;
    }

    try {
      // Invocar BiometricPrompt Nativo de Android (Ventana oficial del SO para huella / cara)
      await NativeBiometric.verifyIdentity({
        title: 'Fingerprint ID',
        subtitle: 'Escuela de Idiomas del Ejército',
        description: 'Coloca tu dedo en el lector de huella digital para ingresar',
        reason: 'Verificación biométrica de identidad',
        fallbackTitle: 'USAR CONTRASEÑA'
      });

      // Si el SO Android confirma la huella/rostro correctamente:
      const usuario = typeof savedUser === 'string' ? JSON.parse(savedUser) : savedUser;
      sessionStorage.setItem('usuario', JSON.stringify(usuario));
      localStorage.setItem('usuario', JSON.stringify(usuario));

      // Importante: Ejecutar la navegación dentro de NgZone para que Angular actualice la vista al instante
      this.ngZone.run(() => {
        this.redireccionarSegunRol(usuario.rol, usuario.id_rol);
      });

      // Refrescar perfil en tiempo real desde el servidor para sincronizar foto y datos
      this.servicioAutenticacion.cargarPerfilActualizado().subscribe({ error: () => {} });

    } catch (error: any) {
      console.warn('Biometría nativa cancelada o error:', error);
    }
  }

  /**
   * Guarda la nueva contraseña cuando el cambio es obligatorio
   */
  guardarNuevaPassword() {
    this.passwordError = '';
    if (this.nuevaPassword.password.length < 8) {
      this.passwordError = 'La contraseña debe tener al menos 8 caracteres.';
      return;
    }
    if (this.nuevaPassword.password !== this.nuevaPassword.password_confirmation) {
      this.passwordError = 'Las contraseñas no coinciden.';
      return;
    }

    this.servicioAutenticacion.cambiarPassword(this.nuevaPassword).subscribe({
      next: (res) => {
        alert('✅ Contraseña cambiada con éxito.');
        this.showMustChangePasswordModal = false;
        const usuario = this.pendingUserResponse?.user || this.servicioAutenticacion.obtenerUsuario();
        this.ngZone.run(() => {
          this.redireccionarSegunRol(usuario?.rol, usuario?.id_rol);
        });
      },
      error: (err) => {
        console.error('Error al cambiar contraseña', err);
        this.passwordError = err.error?.message || 'Error al actualizar contraseña. Intente nuevamente.';
      }
    });
  }

  /**
   * Redirecciona al panel según el rol del usuario
   */
  redireccionarSegunRol(rol?: string, idRol?: number) {
    if (rol === 'docente' || idRol === 3) {
      this.enrutador.navigate(['/docente-dashboard']);
    } else if (rol === 'estudiante' || idRol === 2) {
      this.enrutador.navigate(['/student-dashboard']);
    } else {
      this.enrutador.navigate(['/admin']);
    }
  }

  setPresetUrl(url: string) {
    this.customApiUrl = url;
  }

  onLogin() {
    this.iniciarSesion();
  }
}
