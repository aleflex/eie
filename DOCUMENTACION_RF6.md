# Reporte de Implementación: RF6 - Subida de Documentación Digital

## Resumen del Trabajo Realizado
Se ha configurado la infraestructura necesaria en el backend para permitir el almacenamiento seguro y organizado de documentos digitales (PDF) de los estudiantes. Se estableció un sistema de discos dedicado y se verificó la conectividad del enlace simbólico.

### Cambios en el Almacenamiento (Laravel)

1. **Configuración de Disco "documentos"**:
   - Se registró un nuevo disco en el sistema de archivos.
   - El almacenamiento físico se encuentra en `storage/app/public/documentos`.
   - El acceso público está gestionado a través de `public/storage/documentos`.

---

## Detalles Técnicos de Implementación

A continuación se muestra el código y los pasos aplicados:

### 1. Configuración de Filesystems
A continuación se muestra el fragmento de código añadido al archivo `config/filesystems.php`. Este disco permite separar los documentos de los estudiantes de otras imágenes o archivos temporales.

```php
// backend/config/filesystems.php

'documentos' => [
    'driver' => 'local',
    'root' => storage_path('app/public/documentos'),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/documentos',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

### 2. Vinculación del Almacenamiento (Storage Link)
A continuación se muestra el comando ejecutado para crear el enlace simbólico entre la carpeta de almacenamiento privado y la carpeta pública accesible desde la web.

```bash
php artisan storage:link
```
*Resultado:* `INFO The [public\storage] link has been connected to [storage\app/public].`

### 3. Estructura de Directorios
Se ha verificado la existencia de la siguiente estructura para garantizar que las subidas no fallen por falta de carpetas:
- `backend/storage/app/public/documentos/` (Creado con permisos de escritura).

---

## Implementación de la API de Documentos

A continuación se detallan los componentes técnicos desarrollados para el procesamiento de archivos:

### 1. Estructura de la Base de Datos (Migración)
A continuación se muestra la definición de la tabla `documentos`. Se ajustó el tipo de dato de `estudiante_id` a `integer` para mantener la integridad referencial con la tabla de estudiantes preexistente.

```php
// database/migrations/2026_05_11_185908_create_documentos_table.php

Schema::create('documentos', function (Blueprint $table) {
    $table->id();
    $table->integer('estudiante_id'); // FK vinculada a int(11)
    $table->string('tipo_documento'); // Ej: CI, Título, Depósito
    $table->string('nombre_archivo');
    $table->string('ruta_archivo');
    $table->string('extension', 10);
    $table->bigInteger('peso');
    $table->timestamps();

    $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
});
```

### 2. Controlador de Documentos (API)
A continuación se muestra la lógica principal del controlador para procesar peticiones `multipart/form-data`. El sistema valida el tipo de archivo (PDF, JPG, PNG) y su peso máximo (5MB).

```php
// app/Http/Controllers/Api/DocumentController.php

public function store(Request $request)
{
    // Validación de seguridad
    $request->validate([
        'estudiante_id' => 'required|exists:estudiantes,id',
        'archivo' => 'required|file|mimes:pdf,jpg,png|max:5120',
    ]);

    $file = $request->file('archivo');
    $fileName = time() . '_' . $file->getClientOriginalName();
    
    // Almacenamiento físico en el disco 'documentos'
    $path = $file->storeAs('estudiantes/' . $request->estudiante_id, $fileName, 'documentos');

    // Registro en base de datos
    return Documento::create([
        'estudiante_id' => $request->estudiante_id,
        'ruta_archivo' => $path,
        'peso' => $file->getSize(),
        // ... otros campos
    ]);
}
```

### 3. Definición de Rutas
A continuación se muestran los endpoints habilitados para la gestión de la documentación digital:

```php
// routes/api.php

Route::get('/estudiantes/{estudianteId}/documentos', [DocumentController::class, 'index']);
Route::post('/documentos/subir', [DocumentController::class, 'store']);
Route::delete('/documentos/{id}', [DocumentController::class, 'destroy']);
```

---
**Desarrollado por:** Antigravity AI
**Requerimientos:** RF6 - HU6 (T1, T2)
**Estado:** [COMPLETADO]

