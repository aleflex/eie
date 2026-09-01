/**
 * Configuración del Servidor Backend API (Laravel).
 */
const DOMINIO_SERVIDOR_BACKEND: string = 'https://eie-backend-9n36.onrender.com';

const getApiUrl = () => {
  if (typeof window !== 'undefined') {
    // Si la app corre dentro de Capacitor en Celular Android o Genymotion
    const isCapacitor = (window as any).Capacitor !== undefined || (window.location && window.location.protocol === 'file:');
    if (isCapacitor) {
      return DOMINIO_SERVIDOR_BACKEND;
    }

    // Si se especificó un puerto local explícito en navegador PC
    if (window.location.hostname === '127.0.0.1' && window.location.port === '4200' && (window as any).useLocalBackend) {
      return 'http://localhost:8000';
    }

    try {
      const custom = localStorage.getItem('custom_api_url');
      if (custom && custom.includes('railway.app')) {
        localStorage.removeItem('custom_api_url');
      } else if (custom) {
        return custom;
      }
    } catch (e) {}
  }
  return DOMINIO_SERVIDOR_BACKEND;
};

export const environment = {
  production: false,
  apiUrl: getApiUrl(),
  storageUrl: getApiUrl() + '/storage',
  backendServerUrl: getApiUrl()
};
