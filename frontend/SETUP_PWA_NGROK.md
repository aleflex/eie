# 🚀 Guía Rápida: PWA + ngrok

## 📱 Lo que hice:
✅ Convertí Angular a PWA (Progressive Web App)
✅ Creé Service Worker para funcionar offline
✅ Configuré ApiService para conectar al backend
✅ La app funciona en web y móvil

---

## 🏃 Pasos RÁPIDOS para Pruebas

### 1️⃣ **Compila la PWA para PRODUCCIÓN**
```bash
cd c:\Users\aleja\Desktop\eie\frontend
npm run build
```
Esto genera la carpeta `dist/frontend/` lista para servir.

### 2️⃣ **Instala ngrok en tu computadora**
```bash
# Descargalo desde: https://ngrok.com/download
# O en PowerShell:
choco install ngrok  # Si tienes Chocolatey
```

### 3️⃣ **Expone el backend Laravel con ngrok**
```bash
# En una terminal NUEVA (mientras Laravel sigue corriendo):
ngrok http 8000
```

Verás algo como:
```
Forwarding: https://abc123xyz.ngrok.io -> http://localhost:8000
```
**Copia esa URL** (ej: `https://abc123xyz.ngrok.io`)

### 4️⃣ **Actualiza el API URL en la app**
Opción A: Edita `environment.prod.ts`
```typescript
export const environment = {
  production: true,
  apiUrl: 'https://abc123xyz.ngrok.io'  // ← Tu URL de ngrok
};
```

Opción B: Dinámicamente desde el navegador (Consola DevTools)
```javascript
// En la consola del navegador:
// Abre Developer Tools (F12) y pega:
// ng.probe(document.querySelector('app-root')).injector.get(ApiService).setApiUrl('https://abc123xyz.ngrok.io')
```

### 5️⃣ **Sirve la PWA compilada**
```bash
# Desde la carpeta frontend:
npx http-server dist/frontend -p 4200 --cors
```

### 6️⃣ **Abre en tu móvil**
1. En el móvil, abre navegador
2. Ve a: `http://<tu-ip-computadora>:4200`
   - Encuentra tu IP con: `ipconfig` en PowerShell
   - Busca "IPv4 Address" (ej: `192.168.1.100`)

3. URL en móvil: `http://192.168.1.100:4200`

### 7️⃣ **Instala como app nativa**
- Chrome (Android): Click en el ⋮ menú → "Instalar aplicación"
- Safari (iOS): Click en el cuadro de compartir → "Añadir a pantalla de inicio"

---

## 👀 **Para que el Admin VEA lo que SE ENVÍA**

### Opción 1: Desde la Consola (F12)
```javascript
// Ver todas las peticiones HTTP
// En DevTools → Network → ver cada petición
```

### Opción 2: Backend con Logging
En `app/Http/Middleware/` crea:
```php
// LogApiRequests.php
namespace App\Http\Middleware;

class LogApiRequests {
    public function handle($request, $next) {
        \Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'data' => $request->all()
        ]);
        return $next($request);
    }
}
```

Mira los logs con:
```bash
tail -f storage/logs/laravel.log
```

### Opción 3: Panel de Admin en Real-Time
Si necesito crear un dashboard para ver peticiones en tiempo real, dímelo 📡

---

## ⚠️ **Problemas Comunes**

### "CORS Error" 
✅ Ya configuré CORS en `backend/config/cors.php`
Pero si se repite, el backend podría rechazar a ngrok.

**Solución:**
```php
// En backend/config/cors.php
'allowed_origins' => ['*'],  // Permitir todas durante pruebas
```

### "El Service Worker no se registra"
Limpia el navegador:
```javascript
// Consola del navegador (F12)
navigator.serviceWorker.getRegistrations().then(regs => {
  regs.forEach(r => r.unregister());
});
```

### "App no ve el backend"
1. Verifica que ngrok está corriendo
2. Verifica la URL en `environment.prod.ts`
3. Abre la Consola (F12) y ve los errores

---

## 📊 **Próximas Mejoras (Si Necesitas)**

- [ ] Dashboard admin para ver peticiones en tiempo real
- [ ] Almacenamiento local (LocalStorage) en la PWA
- [ ] Sincronización offline de datos
- [ ] Notificaciones push

¿Necesitas algo más? 🚀
