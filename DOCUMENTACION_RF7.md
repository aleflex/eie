# Reporte de Implementación: RF7 - Registro de Instructores (Docentes)

## Resumen del Trabajo Realizado
Se ha iniciado el desarrollo del módulo de Gestión de Docentes. Primero, se estableció la base de datos estructural creando la tabla `docentes` vinculada a la tabla `users`. Posteriormente, se desarrolló la API para permitir el registro de instructores, garantizando la seguridad de las contraseñas mediante encriptación Bcrypt e implementando transacciones de base de datos seguras. Finalmente, se diseñó e implementó la interfaz gráfica en Angular para el registro de docentes mediante un formulario reactivo con estética Glassmorphism.

### Tareas Completadas (HU7 - T1, T2 y T3)
1. **Creación de Migración (T1)**: Se estructuró la tabla `docentes` con campos de perfil profesional.
2. **Relación de Base de Datos (T1)**: Se estableció una clave foránea (`user_id`) conectando cada registro docente con una cuenta de usuario existente.
3. **Modelos Eloquent (T1)**: Se configuraron los modelos `Docente` y `User` para manejar la relación uno a uno nativa.
4. **Desarrollo de API REST (T2)**: Se creó el `DocenteController` con un método `store` para la gestión de registros.
5. **Encriptación Bcrypt (T2)**: Se implementó la encriptación segura de la contraseña obligatoria del instructor al momento del registro.
6. **Maquetación Frontend (T3)**: Se desarrolló el componente `DocentesComponent` en Angular utilizando validación reactiva y conectividad HTTP.
7. **Diseño Visual (T3)**: Se aplicó un diseño moderno (Glassmorphism) para el panel de registro en el frontend.
---

## Detalles Técnicos de Implementación

A continuación se muestra el código y los pasos aplicados:

### 1. Estructura de la Base de Datos (Migración)
La tabla `docentes` fue diseñada para extender los datos del usuario base. Se incluyeron campos específicos como `especialidad` y `telefono`.

```php
// database/migrations/2026_05_13_195810_create_docentes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('especialidad')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
```

### 2. Configuración del Modelo `Docente`
Se configuró el modelo para permitir la asignación masiva de campos y establecer la relación inversa hacia el usuario.

```php
// app/Models/Docente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $fillable = [
        'user_id',
        'especialidad',
        'telefono'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3. Actualización del Modelo `User`
Se modificó el modelo base de autenticación para que reconozca si un usuario tiene un perfil de docente asociado.

```php
// app/Models/User.php

class User extends Authenticatable
{
    // ... código preexistente ...

    public function docente()
    {
        return $this->hasOne(Docente::class);
    }
}
```

### 4. Ejecución
Para aplicar estos cambios en la base de datos MySQL, se ejecutó con éxito el comando:
```bash
php artisan migrate
```

### 4. Controlador de Registro (API)
Se construyó el `DocenteController` para procesar las peticiones HTTP de registro. Incorpora validación de datos, uso de transacciones de base de datos (`DB::beginTransaction`) para asegurar integridad, y encriptación de contraseña.

```php
// app/Http/Controllers/Api/DocenteController.php

public function store(Request $request)
{
    // Validación estricta
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'especialidad' => 'nullable|string|max:255',
        'telefono' => 'nullable|string|max:50',
    ]);

    try {
        DB::beginTransaction();

        // Creación del usuario con encriptación Bcrypt
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Creación del perfil Docente
        $docente = Docente::create([
            'user_id' => $user->id,
            'especialidad' => $request->especialidad,
            'telefono' => $request->telefono,
        ]);

        DB::commit();
        // Retorna JSON exitoso 201
        // ...
    } catch (\Exception $e) {
        DB::rollBack();
        // Retorna JSON con error 500
        // ...
    }
}
```

### 5. Definición de Rutas API
Se habilitó el endpoint correspondiente para la comunicación desde el Frontend.

```php
// routes/api.php

// Docentes (Instructores)
Route::post('/docentes', [\App\Http\Controllers\Api\DocenteController::class, 'store']);
```

### 6. Interfaz Frontend (Angular)
Se desarrolló el componente standalone `DocentesComponent` en el panel administrativo para registrar instructores.

#### 6.1. Integración de Servicios HTTP
Se implementó `DocenteService` para establecer comunicación con la API de Laravel desarrollada en la Tarea 2.

```typescript
// frontend/src/app/services/docente.service.ts
@Injectable({ providedIn: 'root' })
export class DocenteService {
  private apiUrl = 'http://localhost:8000/api/docentes';

  constructor(private http: HttpClient) { }

  registerDocente(docenteData: any): Observable<any> {
    return this.http.post(this.apiUrl, docenteData);
  }
}
```

#### 6.2. Lógica del Componente (Formularios Reactivos)
Se utilizó `ReactiveFormsModule` para la validación en tiempo real del formulario antes de enviar la información al servidor.

```typescript
// frontend/src/app/components/docentes/docentes.component.ts
export class DocentesComponent implements OnInit {
  docenteForm: FormGroup;
  // ... variables de estado ...

  constructor(private fb: FormBuilder, private docenteService: DocenteService) {
    this.docenteForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      especialidad: ['', [Validators.maxLength(255)]],
      telefono: ['', [Validators.maxLength(50)]]
    });
  }

  onSubmit(): void {
    if (this.docenteForm.invalid) return;

    this.docenteService.registerDocente(this.docenteForm.value).subscribe({
      next: (res) => this.successMessage = 'Instructor registrado exitosamente.',
      error: (err) => this.errorMessage = 'Error al registrar el instructor.'
    });
  }
}
```

#### 6.3. Diseño Visual Premium (Glassmorphism)
Se aplicó un diseño moderno basado en efectos de cristal o "Glassmorphism", brindando una experiencia "Premium" dentro del panel administrativo.
- Fondos difuminados (`backdrop-filter: blur()`).
- Estilos de alerta y estados de carga en los botones.
- Iconos de FontAwesome para cada campo.

---
**Desarrollado por:** Antigravity AI
**Requerimientos:** RF7 - HU7 (T1, T2, T3)
**Estado:** [COMPLETADO]
