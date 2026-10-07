import { Component, OnInit, NgZone } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';
import { environment } from '../../../environments/environment';
import { Capacitor } from '@capacitor/core';
import { NativeBiometric } from '@capgo/capacitor-native-biometric';
import Swal from 'sweetalert2';

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

  /** Control de visualización de contraseña */
  mostrarPassword: boolean = false;
  
  /** Mensaje de error mostrado si el inicio de sesión falla */
  mensajeError: string = '';

  // Barra de progreso y etapas del inicio de sesión
  isLoggingIn: boolean = false;
  loginProgress: number = 0;
  loginStatusText: string = 'Verificando credenciales...';
  loginSuccess: boolean = false;
  loginSuccessTitle: string = '';
  loginSuccessSubtitle: string = '';
  loginUserRoleBadge: string = '';
  loginUserName: string = '';
  private loginProgressTimer: any = null;

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
  savedBiometricUserName: string = '';
  savedBiometricUserEmail: string = '';

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

    // Detectar si es dispositivo móvil o contenedor Capacitor nativo
    this.isMobileDevice = Capacitor.isNativePlatform() || 
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Verificar si el usuario habilitó activamente el acceso biométrico desde configuraciones
    const isBiometricEnabled = localStorage.getItem('eie_biometric_enabled');
    const savedUserStr = localStorage.getItem('eie_biometric_user');
    const savedToken = localStorage.getItem('eie_biometric_token');

    if (savedUserStr) {
      try {
        const u = JSON.parse(savedUserStr);
        this.savedBiometricUserName = u.name || u.nombre || u.usuario || '';
        this.savedBiometricUserEmail = u.email || u.usuario || '';
        if (!this.credenciales.usuario && this.savedBiometricUserEmail) {
          this.credenciales.usuario = this.savedBiometricUserEmail;
        }
      } catch (e) {
        console.warn('Error parseando eie_biometric_user:', e);
      }
    }

    // Disparar diálogo nativo de huella de inmediato apenas se entra a la app en el APK de Android
    const isBiometricDisabled = localStorage.getItem('eie_biometric_enabled') === 'false';
    this.hasSavedBiometricToken = !!savedUserStr && !isBiometricDisabled;

    if (Capacitor.isNativePlatform() && !isBiometricDisabled) {
      setTimeout(() => {
        this.loginConBiometria(true);
      }, 800);
    }
  }

  toggleMostrarPassword() {
    this.mostrarPassword = !this.mostrarPassword;
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

    // Iniciar barra de carga con porcentaje
    this.isLoggingIn = true;
    this.loginSuccess = false;
    this.loginProgress = 15;
    this.loginStatusText = 'Verificando credenciales...';

    if (this.loginProgressTimer) clearInterval(this.loginProgressTimer);
    this.loginProgressTimer = setInterval(() => {
      if (this.loginProgress < 40) {
        this.loginProgress += 6;
        this.loginStatusText = 'Comprobando seguridad y permisos...';
      } else if (this.loginProgress < 75) {
        this.loginProgress += 4;
        this.loginStatusText = 'Autenticando en el servidor...';
      } else if (this.loginProgress < 90) {
        this.loginProgress += 2;
        this.loginStatusText = 'Cargando información del usuario...';
      }
    }, 200);

    this.servicioAutenticacion.iniciarSesion(payload).subscribe({
      next: (respuesta) => {
        console.log('✅ Inicio de sesión exitoso', respuesta);
        if (this.loginProgressTimer) {
          clearInterval(this.loginProgressTimer);
          this.loginProgressTimer = null;
        }

        const usuario = respuesta.user;
        this.pendingUserResponse = respuesta;

        // Guardar credenciales para permitir acceso por Biometría Nativa
        if (usuario && Capacitor.isNativePlatform()) {
          usuario.token = respuesta.token;
          localStorage.setItem('eie_biometric_user', JSON.stringify(usuario));
          localStorage.setItem('eie_biometric_token', respuesta.token || 'token_valid');
          if (localStorage.getItem('eie_biometric_enabled') !== 'false') {
            localStorage.setItem('eie_biometric_enabled', 'true');
            this.hasSavedBiometricToken = true;
          }
        }

        // Configurar mensaje de bienvenida con el nombre del admin / usuario
        this.loginProgress = 100;
        this.loginSuccess = true;
        this.loginStatusText = '¡Acceso concedido!';

        const rawName = usuario?.name || usuario?.nombres || usuario?.usuario || 'Administrador';
        const nombreLimpio = this.servicioAutenticacion.cleanDisplayName(rawName);
        this.loginUserName = nombreLimpio;

        const rol = (usuario?.rol || '').toLowerCase();
        const idRol = usuario?.id_rol ? Number(usuario?.id_rol) : (rol === 'admin' ? 1 : null);
        const esAdmin = rol === 'admin' || idRol === 1 || (!rol && !usuario?.docente_id && !usuario?.estudiante_id);

        if (esAdmin) {
          this.loginUserRoleBadge = 'ADMINISTRADOR';
          this.loginSuccessTitle = `¡Bienvenido(a), Administrador(a) ${nombreLimpio}!`;
        } else if (rol === 'docente' || idRol === 3) {
          this.loginUserRoleBadge = 'DOCENTE';
          this.loginSuccessTitle = `¡Bienvenido(a), Docente ${nombreLimpio}!`;
        } else if (rol === 'estudiante' || idRol === 2) {
          this.loginUserRoleBadge = 'ESTUDIANTE';
          this.loginSuccessTitle = `¡Bienvenido(a), ${nombreLimpio}!`;
        } else {
          this.loginUserRoleBadge = 'USUARIO';
          this.loginSuccessTitle = `¡Bienvenido(a), ${nombreLimpio}!`;
        }

        this.loginSuccessSubtitle = 'Inicio de sesión exitoso. Redirigiendo a tu panel de control...';

        setTimeout(() => {
          this.isLoggingIn = false;
          // Verificar si debe cambiar su contraseña obligatoriamente
          if (usuario?.debe_cambiar_password) {
            this.showMustChangePasswordModal = true;
          } else {
            this.redireccionarSegunRol(usuario?.rol, usuario?.id_rol);
          }
        }, 1500);
      },
      error: (error) => {
        console.error('❌ Error de inicio de sesión', error);
        if (this.loginProgressTimer) {
          clearInterval(this.loginProgressTimer);
          this.loginProgressTimer = null;
        }
        this.isLoggingIn = false;
        this.loginSuccess = false;
        this.loginProgress = 0;

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
   * Despliega la ventana oficial del sensor de Huella Dactilar estilo banca móvil.
   */
  async loginConBiometria(autoTrigger: boolean = false) {
    if (!Capacitor.isNativePlatform()) {
      if (!autoTrigger) {
        Swal.fire({
          title: 'Sensor de Huella Móvil',
          text: 'La autenticación con huella digital está disponible en la app APK instalada en tu celular Android.',
          icon: 'info',
          confirmButtonColor: '#003B71'
        });
      }
      return;
    }

    // Si es llamada automática y está explícitamente deshabilitado, omitir
    if (autoTrigger && localStorage.getItem('eie_biometric_enabled') === 'false') {
      return;
    }

    try {
      // Invocar BiometricPrompt Nativo de Android directamente (Ventana oficial del SO para huella dactilar)
      await NativeBiometric.verifyIdentity({
        title: 'Fingerprint ID',
        subtitle: 'Ingrese su huella digital para iniciar sesión',
        description: 'Coloque su dedo en el sensor',
        negativeButtonText: 'INGRESAR CONTRASEÑA',
        maxAttempts: 5
      });

      // Si el SO Android confirma la huella correctamente:
      const savedUserStr = localStorage.getItem('eie_biometric_user') || localStorage.getItem('usuario');
      if (savedUserStr) {
        const usuario = typeof savedUserStr === 'string' ? JSON.parse(savedUserStr) : savedUserStr;
        const savedToken = localStorage.getItem('eie_biometric_token') || usuario.token;
        if (savedToken && (!usuario.token || usuario.token === 'auth_token_active')) {
          usuario.token = savedToken;
        }
        sessionStorage.setItem('usuario', JSON.stringify(usuario));
        localStorage.setItem('usuario', JSON.stringify(usuario));
        this.servicioAutenticacion.establecerSesionBiometrica(usuario);

        const rawName = usuario.name || usuario.nombres || usuario.usuario || 'Administrador';
        const nombreLimpio = this.servicioAutenticacion.cleanDisplayName(rawName);
        this.loginUserName = nombreLimpio;
        this.isLoggingIn = true;
        this.loginSuccess = true;
        this.loginProgress = 100;
        this.loginStatusText = '¡Huella digital reconocida!';
        const rol = (usuario.rol || '').toLowerCase();
        const idRol = usuario.id_rol ? Number(usuario.id_rol) : (rol === 'admin' ? 1 : null);
        const esAdmin = rol === 'admin' || idRol === 1 || (!rol && !usuario.docente_id && !usuario.estudiante_id);
        if (esAdmin) {
          this.loginUserRoleBadge = 'ADMINISTRADOR';
          this.loginSuccessTitle = `¡Bienvenido(a), Administrador(a) ${nombreLimpio}!`;
        } else if (rol === 'docente' || idRol === 3) {
          this.loginUserRoleBadge = 'DOCENTE';
          this.loginSuccessTitle = `¡Bienvenido(a), Docente ${nombreLimpio}!`;
        } else {
          this.loginUserRoleBadge = 'ESTUDIANTE';
          this.loginSuccessTitle = `¡Bienvenido(a), ${nombreLimpio}!`;
        }
        this.loginSuccessSubtitle = 'Acceso biométrico autorizado. Ingresando al panel...';

        setTimeout(() => {
          this.isLoggingIn = false;
          // Importante: Ejecutar la navegación dentro de NgZone para que Angular actualice la vista al instante
          this.ngZone.run(() => {
            this.redireccionarSegunRol(usuario.rol, usuario.id_rol);
          });
        }, 1200);

        // Refrescar perfil en tiempo real desde el servidor para sincronizar foto y datos
        this.servicioAutenticacion.cargarPerfilActualizado().subscribe({ error: () => {} });
      } else {
        // La huella fue validada por Android pero es el primer inicio en este APK y aún no hay cuenta guardada
        Swal.fire({
          title: '¡Huella Reconocida!',
          text: 'Inicia sesión con tu usuario y contraseña una sola vez para vincular tu cuenta a este celular.',
          icon: 'success',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#003B71'
        });
      }

    } catch (error: any) {
      console.warn('Biometría nativa retorno:', error);
      const errStr = (error?.message || error?.error || error?.toString() || '').toLowerCase();
      const code = error?.code || '';

      // Si el usuario canceló voluntariamente o presionó "INGRESAR CONTRASEÑA", no molestar con alertas
      if (errStr.includes('cancel') || errStr.includes('negative') || code == 15 || code == 16 || code == 10 || code == '15' || code == '16') {
        return;
      }

      // Si fue pulsado manualmente por el usuario, mostrar el estado real
      if (!autoTrigger) {
        if (errStr.includes('none_enrolled') || errStr.includes('not enrolled') || code == 3 || code == 11) {
          Swal.fire({
            title: 'Sin huella registrada',
            text: 'Debes registrar al menos una huella digital en los Ajustes de Seguridad y Bloqueo de tu celular Android para usar esta función.',
            icon: 'warning',
            confirmButtonColor: '#003B71'
          });
        } else {
          Swal.fire({
            title: 'Sensor Biométrico',
            text: `Aviso del sensor: ${error?.message || errStr || 'No se pudo abrir el lector biométrico'}`,
            icon: 'info',
            confirmButtonColor: '#003B71'
          });
        }
      }
    }
  }

  /**
   * Permite desvincular la huella guardada en el celular para cambiar de usuario
   */
  desvincularBiometria() {
    Swal.fire({
      title: '¿Cambiar de cuenta / Desvincular?',
      text: 'Se desvinculará la huella asociada a este dispositivo. Podrás volver a habilitarla cuando ingreses a tu cuenta.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, desvincular',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b'
    }).then((result) => {
      if (result.isConfirmed) {
        this.servicioAutenticacion.disableBiometricForCurrentDevice();
        this.hasSavedBiometricToken = false;
        this.savedBiometricUserName = '';
        this.savedBiometricUserEmail = '';
        this.credenciales.usuario = '';
        this.credenciales.password = '';
        Swal.fire({
          title: 'Huella Desvinculada',
          text: 'Ahora puedes ingresar con cualquier usuario y contraseña.',
          icon: 'success',
          confirmButtonColor: '#003B71'
        });
      }
    });
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
        this.showMustChangePasswordModal = false;
        Swal.fire({
          title: '¡Operación Exitosa!',
          html: '<div style="text-align: center; color: #334155; font-size: 1rem; line-height: 1.55;">Contraseña cambiada con éxito.</div>',
          icon: 'success',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#003B71',
          buttonsStyling: true,
          heightAuto: false
        }).then(() => {
          const usuario = this.pendingUserResponse?.user || this.servicioAutenticacion.obtenerUsuario();
          this.ngZone.run(() => {
            this.redireccionarSegunRol(usuario?.rol, usuario?.id_rol);
          });
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
