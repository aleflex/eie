/**
 * Configuración del Servidor Backend API (Laravel).
 */
const DOMINIO_SERVIDOR_BACKEND: string = 'https://eie-production.up.railway.app';

const getApiUrl = () => {
  if (typeof window !== 'undefined') {
    // Si la web se abre desde localhost / 127.0.0.1 en modo dev local
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      return 'http://localhost:8000';
    }

    // Limpiar cualquier IP antigua guardada en localStorage
    try {
      localStorage.removeItem('custom_api_url');
    } catch (e) {}

    // Servidor de producción en Railway
    return DOMINIO_SERVIDOR_BACKEND;
  }
  return DOMINIO_SERVIDOR_BACKEND;
};

export const environment = {
  production: false,
  apiUrl: getApiUrl(),
  storageUrl: getApiUrl() + '/storage',
  backendServerUrl: getApiUrl()
};
