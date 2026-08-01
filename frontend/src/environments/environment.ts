/**
 * Configuración del Servidor Backend API (Laravel).
 * Coloca aquí la URL de tu servidor backend en la nube si tienes una (Ej: https://eie-backend.com o URL Ngrok).
 * Si está vacío '', se conectará automáticamente a tu servidor Laravel (IP Local / Localhost).
 */
const DOMINIO_SERVIDOR_BACKEND: string = ''; // Ej: 'https://mi-backend.com'

const getApiUrl = () => {
  if (typeof window !== 'undefined') {
    const customUrl = localStorage.getItem('custom_api_url');
    if (customUrl) {
      return customUrl;
    }

    // Si se definió una URL de Backend API explícita
    if (DOMINIO_SERVIDOR_BACKEND && DOMINIO_SERVIDOR_BACKEND.trim() !== '') {
      return DOMINIO_SERVIDOR_BACKEND.replace(/\/+$/, '');
    }

    // Si la web se abre desde localhost / 127.0.0.1
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      return 'http://localhost:8000';
    }

    // Si es aplicación nativa móvil (Capacitor / Cordova)
    const isNative = (window as any).Capacitor || (window as any).cordova || /Capacitor|Cordova/i.test(navigator.userAgent);
    if (isNative) {
      return 'http://10.10.11.222:8000';
    }

    // Si está desplegado en Vercel u otro hosting web sin backend local propio
    if (window.location.hostname.includes('vercel.app')) {
      return 'http://10.10.11.222:8000';
    }

    return `${window.location.protocol}//${window.location.hostname}:8000`;
  }
  return DOMINIO_SERVIDOR_BACKEND || 'http://10.10.11.222:8000';
};

export const environment = {
  production: false,
  apiUrl: getApiUrl()
};
