import { ApplicationConfig } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { IMAGE_CONFIG } from '@angular/common';
import { ServiceWorkerModule } from '@angular/service-worker';

import { routes } from './app.routes';
import { ngrokInterceptor } from './interceptors/ngrok.interceptor';

/**
 * Configuración de la Aplicación Angular
 * Define los proveedores y configuraciones globales de la aplicación.
 *
 * Incluye:
 * - Proveedor de enrutamiento
 * - Configuración de cliente HTTP con interceptores
 * - Configuración de imágenes
 */
export const appConfig: ApplicationConfig = {
  providers: [
    // Proveedor de enrutamiento con las rutas definidas
    provideRouter(routes),

    // Proveedor HTTP con interceptor de ngrok para desarrollo
    provideHttpClient(
      withInterceptors([ngrokInterceptor])
    ),

    // Configuración de imágenes de Angular
    {
      provide: IMAGE_CONFIG,
      useValue: {
        disableImageSizeWarning: true,    // Desactivar advertencias de tamaño
        disableImageLazyLoadWarning: true // Desactivar advertencias de lazy loading
      }
    }
  ]
};

