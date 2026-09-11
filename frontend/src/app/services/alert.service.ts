import { Injectable } from '@angular/core';
import Swal, { SweetAlertIcon } from 'sweetalert2';

@Injectable({
  providedIn: 'root'
})
export class AlertService {
  /**
   * Muestra un diálogo modal moderno y formateado
   */
  show(title: string, message: string, icon: SweetAlertIcon = 'info') {
    return Swal.fire({
      title: title,
      html: message.replace(/\n/g, '<br>'),
      icon: icon,
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#003B71',
      buttonsStyling: true
    });
  }

  success(message: string, title: string = '¡Operación Exitosa!') {
    return this.show(title, message, 'success');
  }

  warning(message: string, title: string = 'Aviso Importante') {
    return this.show(title, message, 'warning');
  }

  error(message: string, title: string = 'Atención / Error') {
    return this.show(title, message, 'error');
  }

  info(message: string, title: string = 'Notificación Institucional') {
    return this.show(title, message, 'info');
  }

  /**
   * Diálogo de confirmación estilizado con botones Aceptar / Cancelar
   */
  confirm(message: string, title: string = '¿Confirmar Acción?'): Promise<boolean> {
    return Swal.fire({
      title: title,
      html: message.replace(/\n/g, '<br>'),
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#003B71',
      cancelButtonColor: '#64748b',
      reverseButtons: true
    }).then(result => result.isConfirmed);
  }

  /**
   * Notificación pequeña flotante (Toast)
   */
  toast(message: string, icon: SweetAlertIcon = 'success') {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });

    return Toast.fire({
      icon: icon,
      title: message
    });
  }
}
