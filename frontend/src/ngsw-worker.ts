/// <reference lib="webworker" />
import { cleanupOutdatedCaches, precacheAndRoute, cleanupOldSwCaches } from '@angular/service-worker';

// Precache manifest será inyectado en tiempo de build
declare const self: any;

// Limpiar cachés antigos
cleanupOutdatedCaches();

// Precachear archivos del manifest
precacheAndRoute(self.__WB_MANIFEST || []);

// Listener para mensajes desde la app
self.addEventListener('message', (event: any) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
