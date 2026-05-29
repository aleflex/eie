import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/auth.service';

/**
 * Componente de Inicio de Sesión
 * Permite a los usuarios autenticarse en el sistema.
 * Redirige al usuario al panel correspondiente según su rol:
 * - Docente: /docente-dashboard
 * - Estudiante: /student-dashboard
 * - Admin: /admin
 */
@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  /** Objeto que almacena las credenciales (email y contraseña) */
  credenciales = {
    email: '',
    password: ''
  };
  
  /** Mensaje de error mostrado si el inicio de sesión falla */
  mensajeError: string = '';

  constructor(
    private servicioAutenticacion: AuthService,
    private enrutador: Router
  ) {}

  /**
   * Ejecuta el proceso de inicio de sesión
   * - Envía las credenciales al AuthService
   * - Si es exitoso, redirige al usuario a su panel según su rol
   * - Si falla, muestra un mensaje de error
   * 
   * Roles soportados:
   * - 'docente': Redirecciona a /docente-dashboard
   * - 'estudiante': Redirecciona a /student-dashboard
   * - 'admin': Redirecciona a /admin
   */
  iniciarSesion() {
    this.servicioAutenticacion.iniciarSesion(this.credenciales).subscribe({
      next: (respuesta) => {
        console.log('✅ Inicio de sesión exitoso', respuesta);
        const usuario = respuesta.user;

        // Redirigir según el rol del usuario
        if (usuario?.rol === 'docente') {
          this.enrutador.navigate(['/docente-dashboard']);
        } else if (usuario?.rol === 'estudiante') {
          this.enrutador.navigate(['/student-dashboard']);
        } else {
          // Por defecto, admin
          this.enrutador.navigate(['/admin']);
        }
      },
      error: (error) => {
        console.error('❌ Error de inicio de sesión', error);
        this.mensajeError = error.error?.message || 'Credenciales inválidas. Por favor intente de nuevo.';
      }
    });
  }

  // Método heredado para compatibilidad
  onLogin() {
    this.iniciarSesion();
  }
}
