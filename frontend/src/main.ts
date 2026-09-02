import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app.component';
import { ServiceWorkerModule } from '@angular/service-worker';
import { environment } from './environments/environment';

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
