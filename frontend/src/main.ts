import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app.component';
import { ServiceWorkerModule } from '@angular/service-worker';
import { environment } from './environments/environment';

bootstrapApplication(AppComponent, appConfig)
  .catch((err) => console.error(err));

// Registrar Service Worker solo en web produccion, NO en app nativa Capacitor
const isNative = typeof window !== 'undefined' && ((window as any).Capacitor || (window as any).cordova);
if (!isNative && environment.production && 'serviceWorker' in navigator) {
  navigator.serviceWorker.register('/ngsw-worker.js', { scope: '/' })
    .then(registration => console.log('✅ SW registered:', registration))
    .catch(err => console.error('❌ SW registration failed:', err));
}
