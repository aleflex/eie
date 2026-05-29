# Reporte de Implementación: RF5 - Búsqueda de Inscritos

## Resumen del Trabajo Realizado
Se ha implementado la funcionalidad de búsqueda en el backend para permitir la localización rápida de estudiantes inscritos mediante su número de cédula (CI) o su nombre/apellido. Esta funcionalidad es fundamental para la gestión administrativa eficiente.

### Cambios en el Backend (Laravel)

1. **Nuevo Endpoint de Búsqueda**:
   - Se creó una ruta dedicada `GET /api/estudiantes/buscar`.
   - Soporta parámetros opcionales `ci` y `nombre`.
   - Utiliza comparaciones parciales (`LIKE`) para mejorar la experiencia de búsqueda.

---

## Detalles Técnicos de Implementación

A continuación se muestra el código y la lógica aplicada en el backend:

### 1. Definición de la Ruta (API Routes)
A continuación se muestra la ruta registrada en el archivo de rutas de la API. Se colocó antes de los recursos genéricos para asegurar su correcto funcionamiento.

```php
// backend/routes/api.php

// Ruta de búsqueda para estudiantes
Route::get('/estudiantes/buscar', [StudentController::class, 'search']);
```

### 2. Lógica del Controlador (StudentController)
A continuación se muestra el método `search` implementado en el controlador. Este método construye una consulta dinámica que se adapta a los filtros enviados desde el frontend.

```php
// backend/app/Http/Controllers/Api/StudentController.php

public function search(Request $request)
{
    // Iniciamos la consulta cargando las relaciones de inscripciones y cursos
    $query = Estudiante::with('inscripciones.curso');

    // Filtro por Carnet de Identidad (Búsqueda parcial con LIKE)
    if ($request->has('ci') && !empty($request->ci)) {
        $query->where('ci', 'LIKE', '%' . $request->ci . '%');
    }

    // Filtro por Nombre o Apellido (Búsqueda parcial)
    if ($request->has('nombre') && !empty($request->nombre)) {
        $nombre = $request->nombre;
        $query->where(function($q) use ($nombre) {
            $q->where('nombres', 'LIKE', '%' . $nombre . '%')
              ->orWhere('apellidos', 'LIKE', '%' . $nombre . '%');
        });
    }

    // Retorna los resultados en formato JSON
    return response()->json($query->get());
}
```

### 3. Ejemplo de Uso de la API
Para consumir este nuevo endpoint, a continuación se muestran ejemplos de URLs de consulta:

- **Búsqueda por CI**: `GET /api/estudiantes/buscar?ci=12345`
- **Búsqueda por Nombre**: `GET /api/estudiantes/buscar?nombre=Juan`

---

## Implementación en el Frontend (Angular)

A continuación se muestra la integración realizada en la interfaz de usuario:

### 1. Servicio de Datos (Student Service)
Se añadió el método para realizar la petición HTTP al backend enviando los parámetros de búsqueda.

```typescript
// frontend/src/app/services/student.service.ts
searchStudents(termino: string, tipo: string = 'nombre'): Observable<any[]> {
  const url = `${this.apiUrl}/buscar?${tipo}=${termino}`;
  return this.http.get<any[]>(url);
}
```

### 2. Lógica de Búsqueda (Componente)
A continuación se muestra el método `onSearch` que gestiona el estado de carga y la actualización de la lista de estudiantes.

```typescript
// frontend/src/app/components/students/students.component.ts
onSearch() {
  if (!this.searchTerm || this.searchTerm.trim() === '') {
    this.loadStudents(); // Si está vacío, carga todos
    return;
  }

  this.isLoading = true;
  this.studentService.searchStudents(this.searchTerm, this.searchType).subscribe({
    next: (data) => {
      this.students = data;
      this.isLoading = false;
    },
    error: (err) => {
      console.error('Error en búsqueda', err);
      this.isLoading = false;
    }
  });
}
```

### 3. Interfaz de Usuario y Accesibilidad
A continuación se muestra el código HTML de la barra de búsqueda. Se han incluido atributos `aria-label` para cumplir con los estándares de accesibilidad y eventos para búsqueda en tiempo real.

```html
<!-- frontend/src/app/components/students/students.component.html -->
<div class="search-box">
  <input 
    type="text" 
    [(ngModel)]="searchTerm" 
    (input)="onSearchInput()"
    (keyup.enter)="onSearch()"
    [placeholder]="searchType === 'nombre' ? 'Buscar...' : 'CI...'"
    aria-label="Término de búsqueda"
    class="search-input"
  >
</div>
```

### 4. Optimización de Rendimiento (DebounceTime)
A continuación se muestra la lógica aplicada para evitar saturar el servidor con peticiones innecesarias. Se utiliza un `Subject` de RxJS para emitir los términos de búsqueda con un retraso controlado.

```typescript
// frontend/src/app/components/students/students.component.ts

// Configuración en ngOnInit
this.searchSubject.pipe(
  debounceTime(400),       // Espera 400ms tras la última pulsación
  distinctUntilChanged()   // No busca si el término es igual al anterior
).subscribe(() => {
  this.onSearch();
});

// Método vinculado al evento (input)
onSearchInput() {
  this.searchSubject.next(this.searchTerm);
}
```

---
**Desarrollado por:** Antigravity AI
**Requerimientos:** RF5 - HU5 (T1, T2, T3)
**Estado:** [COMPLETADO]


