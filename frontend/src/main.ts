import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app.component';
import { ServiceWorkerModule } from '@angular/service-worker';
import { environment } from './environments/environment';
import Swal, { SweetAlertIcon } from 'sweetalert2';

// Reemplazo global del alert() cuadrado nativo por modal estilizado y redondeado de alta fidelidad
if (typeof window !== 'undefined') {
  const nativeAlert = window.alert;
  (window as any).__nativeAlert = nativeAlert;

  window.alert = (message?: any) => {
    const rawMsg = typeof message === 'object' ? JSON.stringify(message, null, 2) : String(message ?? '');
    
    let icon: SweetAlertIcon = 'info';
    let title = 'Notificación Institucional';
    const lower = rawMsg.toLowerCase();

    if (
      lower.includes('error') || 
      lower.includes('fallo') || 
      lower.includes('no se pudo') || 
      lower.includes('inválido') || 
      lower.includes('invalido') || 
      lower.includes('peligroso') || 
      lower.includes('malicioso') || 
      lower.includes('infracción') || 
      lower.includes('infraccion') ||
      lower.includes('rechazado')
    ) {
      icon = 'error';
      title = 'Atención / Error';
    } else if (
      lower.includes('éxito') || 
      lower.includes('exito') || 
      lower.includes('correctamente') || 
      lower.includes('exitosamente') || 
      lower.includes('guardado') || 
      lower.includes('actualizado') || 
      lower.includes('rehabilitado') || 
      lower.includes('registrado') ||
      lower.includes('¡estudiante')
    ) {
      icon = 'success';
      title = '¡Operación Exitosa!';
    } else if (
      lower.includes('advertencia') || 
      lower.includes('cuidado') || 
      lower.includes('debe') || 
      lower.includes('obligatorio') || 
      lower.includes('solo se permite') || 
      lower.includes('límite') || 
      lower.includes('limite') || 
      lower.includes('atención') || 
      lower.includes('atencion') ||
      lower.includes('selecciona')
    ) {
      icon = 'warning';
      title = 'Aviso Importante';
    }

    Swal.fire({
      title: title,
      html: `<div style="text-align: center; color: #334155; font-size: 1rem; line-height: 1.55;">${rawMsg.replace(/\n/g, '<br>')}</div>`,
      icon: icon,
      confirmButtonText: 'Entendido',
      confirmButtonColor: '#003B71',
      buttonsStyling: true,
      heightAuto: false
    });
  };
}

bootstrapApplication(AppComponent, appConfig)
  .catch((err) => console.error(err));

// Desregistrar Service Worker y limpiar cachés antiguas del navegador
if (typeof window !== 'undefined') {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
      for (const registration of registrations) {
        registration.unregister();
      }
    });
    if ('caches' in window) {
      caches.keys().then(keys => {
        keys.forEach(key => caches.delete(key));
      });
    }
  }

  // Purga profunda de cualquier elemento en localStorage que contenga railway.app
  try {
    const keysToRemove: string[] = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key) {
        const val = localStorage.getItem(key);
        if (val && val.includes('railway.app')) {
          keysToRemove.push(key);
        }
      }
    }
    keysToRemove.forEach(k => localStorage.removeItem(k));
  } catch (e) {}
}
