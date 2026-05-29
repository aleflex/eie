const getApiUrl = () => {
  if (typeof window !== 'undefined' && window.location.hostname !== 'localhost' && window.location.hostname !== '137.184.129.64') {
    // Si entran por navegador web, usa la IP que escribieron en la barra
    return `http://${window.location.hostname}:8000`;
  }
  // IP real del servidor Linux para la aplicación móvil (APK)
  return 'http://137.184.129.64:8000';
};

export const environment = {
  production: true,
  apiUrl: getApiUrl()
};
