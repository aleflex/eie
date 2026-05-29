# Sistema Híbrido para Gestión Documental de Inscripciones - EIE

**Objetivo General:**
Desarrollar un sistema web híbrido para la gestión documental de inscripciones, garantizando la disponibilidad de la información de estudiantes y docentes en la Escuela de Idiomas del Ejército, Cochabamba.

---

## EPIC 1 - Gestión de Inscripciones y Habilitación de Materias
*Objetivo:* Automatizar la habilitación de materias, inscripciones, bajas y modificaciones.

### Requerimientos Funcionales (RF) e Historias de Usuario (HU)

#### RF 1 - HU 1: Registro de Inscripción y Habilitación
**Historia:** Como estudiante, quiero registrar mi inscripción en el sistema web, para solicitar mi habilitación formal en las materias y paralelos correspondientes.
* **Escenario Bueno:** Dado que el estudiante ingresa al sistema web, cuando completa el formulario de inscripción con datos válidos, entonces el sistema registra la inscripción y muestra un mensaje confirmando la habilitación en la materia.
* **Escenario Malo Bajo:** Dado que el estudiante intenta inscribirse, cuando deja el campo de "Idioma" vacío, entonces el sistema no procesa la solicitud y resalta el campo indicando que es obligatorio.
* **Escenario Malo Alto:** Dado que el estudiante envía el formulario, cuando la base de datos pierde conexión, entonces el sistema cancela la operación y muestra un error 500 pidiendo contactar al administrador.

**Tareas (Frontend: Angular, Backend: Laravel):**
* `RF1 - HU1 - T1`: Crear migración, modelo y controlador (CRUD) para Inscripciones en Laravel.
* `RF1 - HU1 - T2`: Desarrollar la API REST (POST) para recibir y validar los datos de inscripción.
* `RF1 - HU1 - T3`: Diseñar el formulario reactivo de inscripción en Angular con validaciones de campos obligatorios.
* `RF1 - HU1 - T4`: Integrar el servicio HTTP en Angular para enviar los datos al backend y gestionar la respuesta de confirmación o error.

#### RF 2 - HU 2: Eliminación de Inscripción
**Historia:** Como administrador/estudiante, quiero dar de baja una inscripción errónea, para liberar el cupo en el paralelo y mantener actualizado el registro académico.
* **Escenario Bueno:** Dado que el administrador selecciona una inscripción errónea, cuando confirma la eliminación, entonces el sistema da de baja el registro y libera automáticamente el cupo en ese paralelo.
* **Escenario Malo Bajo:** Dado que el administrador hace clic en eliminar, cuando cancela la ventana de confirmación, entonces el sistema aborta la acción y mantiene el registro intacto.
* **Escenario Malo Alto:** Dado que se intenta eliminar una inscripción, cuando el estudiante ya tiene notas o asistencias vinculadas, entonces el sistema bloquea la eliminación y muestra un error de integridad de datos.

**Tareas:**
* `RF2 - HU2 - T1`: Implementar el método de eliminación (DELETE) en Laravel, añadiendo lógica para verificar que la inscripción no tenga notas asociadas (integridad referencial).
* `RF2 - HU2 - T2`: Diseñar un modal de confirmación en Angular (SweetAlert o Bootstrap Modal) para prevenir borrados accidentales.
* `RF2 - HU2 - T3`: Conectar la acción de eliminar con la API y programar la liberación automática de cupos en la base de datos.

#### RF 3 - HU 3: Actualización de Datos de Inscripción
**Historia:** Como estudiante, quiero modificar los datos de mi inscripción (como el paralelo o turno), para corregir errores antes del cierre de gestión.
* **Escenario Bueno:** Dado que el estudiante necesita cambiar de turno, cuando selecciona un nuevo paralelo con cupo y guarda, entonces el sistema actualiza la inscripción y confirma el cambio exitoso.
* **Escenario Malo Bajo:** Dado que el estudiante abre la edición, cuando presiona guardar sin haber alterado ningún dato, entonces el sistema no hace consultas a la base de datos y avisa que no hubo cambios.
* **Escenario Malo Alto:** Dado que el estudiante confirma el cambio de paralelo, cuando otro usuario ocupa el último cupo en ese mismo milisegundo, entonces el sistema revierte la actualización y notifica que el paralelo ya está lleno.

**Tareas:**
* `RF3 - HU3 - T1`: Crear el endpoint PUT/PATCH en Laravel para actualizar atributos específicos de la inscripción.
* `RF3 - HU3 - T2`: Desarrollar el componente de edición en Angular, cargando previamente los datos actuales del estudiante en el formulario.
* `RF3 - HU3 - T3`: Implementar validación en tiempo real de disponibilidad de cupos si el estudiante intenta cambiar de paralelo.

#### RF 4 - HU 4: Listado de Inscripciones
**Historia:** Como estudiante, quiero visualizar el histórico de mis inscripciones, para confirmar los cursos en los que me encuentro legalmente habilitado.
* **Escenario Bueno:** Dado que el estudiante accede a su historial, cuando el sistema carga los datos, entonces muestra una tabla con todas sus materias cursadas y su estado de habilitación.
* **Escenario Malo Bajo:** Dado que un estudiante recién creado entra al módulo, cuando el sistema busca su historial, entonces muestra la tabla vacía y un mensaje indicando que no tiene registros.
* **Escenario Malo Alto:** Dado que se solicita el listado, cuando la API de Laravel demora más del tiempo límite de respuesta, entonces el sistema interrumpe la carga y muestra un error de "Timeout".

**Tareas:**
* `RF4 - HU4 - T1`: Escribir consulta (Query) usando Eloquent ORM en Laravel para obtener el historial académico filtrado por el ID del estudiante.
* `RF4 - HU4 - T2`: Diseñar e implementar una tabla interactiva en Angular (con paginación) para mostrar las materias.
* `RF4 - HU4 - T3`: Consumir la API desde el frontend y maquetar los estados (Habilitado, Pendiente) con badges de colores (Bootstrap). [COMPLETADO]

#### RF 5 - HU 5: Búsqueda de Inscritos
**Historia:** Como administrador, quiero buscar inscritos mediante filtros específicos, para gestionar rápidamente la información de los estudiantes por curso.
* **Escenario Bueno:** Dado que el administrador ingresa un carnet de identidad, cuando presiona el botón buscar, entonces el sistema filtra la base de datos y muestra únicamente el registro de ese estudiante.
* **Escenario Malo Bajo:** Dado que se ingresa un nombre con errores ortográficos, cuando se ejecuta la búsqueda, entonces el sistema no encuentra coincidencias y sugiere verificar los datos ingresados.
* **Escenario Malo Alto:** Dado que el administrador utiliza la barra de búsqueda, cuando ingresa comandos SQL maliciosos, entonces el sistema neutraliza la consulta y registra un intento de vulneración de seguridad.

**Tareas:**
* `RF5 - HU5 - T1`: [COMPLETADO] Implementar endpoint GET en Laravel que reciba parámetros de búsqueda (Carnet de Identidad, Nombre).
* `RF5 - HU5 - T2`: [COMPLETADO] Crear una barra de búsqueda en el panel de administrador en Angular.

* `RF5 - HU5 - T3`: [COMPLETADO] Aplicar un operador debounceTime en Angular para evitar saturar el servidor con peticiones por cada tecla presionada.


#### RF 6 - HU 6: Subida de Documentación Digital
**Historia:** Como estudiante, quiero cargar mis documentos requisitos en formato digital, para completar mi expediente sin necesidad de entregar papeles físicos.
* **Escenario Bueno:** Dado que el estudiante selecciona su cédula en formato PDF, cuando confirma la subida, entonces el sistema guarda el archivo en el servidor y lo vincula a su expediente digital.
* **Escenario Malo Bajo:** Dado que el estudiante intenta cargar un archivo, cuando selecciona una imagen .PNG que excede el límite de 5MB, entonces el sistema rechaza el archivo y pide subir un documento más ligero.
* **Escenario Malo Alto:** Dado que se está procesando la subida del documento, cuando el disco duro del servidor se queda sin espacio de almacenamiento, entonces el sistema interrumpe el proceso y lanza una alerta crítica al soporte técnico.

**Tareas:**
* `RF6 - HU6 - T1`: [COMPLETADO] Configurar el sistema de archivos (File Storage) en Laravel para guardar PDFs de manera segura.

* `RF6 - HU6 - T2`: [COMPLETADO] Desarrollar API para procesar peticiones multipart/form-data y guardar la ruta del archivo en la base de datos.

* `RF6 - HU6 - T3`: [COMPLETADO] Diseñar el componente de subida en Angular, con validación de extensión (.pdf) y peso máximo del archivo.


---

## EPIC 2 - Gestión de Docentes
*Objetivo:* Registrar e identificar a los instructores, gestionar su disponibilidad, asignarlos a paralelos y generar constancias digitales.

#### RF 7 - HU 7: Registro de Instructores
**Historia:** Como administrador, quiero registrar los datos de los instructores, para contar con una base de datos actualizada del personal docente disponible.
* **Escenario Bueno:** Dado que el administrador completa los datos del nuevo instructor, cuando guarda el formulario, entonces el sistema crea el perfil del docente y le asigna credenciales de acceso.
* **Escenario Malo Bajo:** Dado que se registran los datos, cuando se ingresa un correo electrónico que ya pertenece a otro usuario, entonces el sistema detiene el registro y solicita un correo único.
* **Escenario Malo Alto:** Dado que se guarda el perfil, cuando los permisos de escritura en la base de datos fallan, entonces el sistema no registra al docente y muestra un error interno de servidor.

**Tareas:**
* `RF7 - HU7 - T1`: Crear migración y tabla docentes vinculada a la tabla de users.
* `RF7 - HU7 - T2`: Desarrollar API para la creación de la cuenta docente y encriptación de contraseña (Bcrypt).
* `RF7 - HU7 - T3`: Maquetar el formulario de registro de personal en el panel administrativo de Angular.

#### RF 8 - HU 8: Actualizar Información del Docente
**Historia:** Como administrador, quiero actualizar el perfil y disponibilidad, asegurando que los cambios se reflejen en tiempo real en los módulos de asignación.
* **Escenario Bueno:** Dado que el docente cambia su número de teléfono, cuando el administrador actualiza el dato en el perfil, entonces el sistema guarda la información y muestra el perfil actualizado.
* **Escenario Malo Bajo:** Dado que se edita el perfil, cuando se ingresan letras en el campo de teléfono, entonces el sistema bloquea la acción y pide un formato numérico válido.
* **Escenario Malo Alto:** Dado que dos administradores editan al mismo docente a la vez, cuando ambos intentan guardar, entonces el sistema detecta colisión de datos y bloquea el segundo guardado pidiendo recargar la página.

**Tareas:**
* `RF8 - HU8 - T1`: Desarrollar endpoint de actualización en Laravel con validación de correos electrónicos únicos (exceptuando el actual).
* `RF8 - HU8 - T2`: Implementar la vista de edición de perfil docente en Angular.

#### RF 9 - HU 9: Listar Docentes y sus Estados
**Historia:** Como administrador, quiero ver los estados y visualización de plantilla.
* **Escenario Bueno:** Dado que el jefe de estudios entra al módulo, cuando solicita ver la nómina, entonces el sistema muestra la lista completa de docentes y su estado actual (Activo/Inactivo).
* **Escenario Malo Bajo:** Dado que se filtra la lista por "Idioma Ruso", cuando no hay instructores para ese idioma, entonces la tabla aparece vacía y muestra "Cero resultados".
* **Escenario Malo Alto:** Dado que se solicita la nómina, cuando el token de sesión del administrador ha expirado, entonces el sistema deniega el acceso a la lista y redirige al usuario a la pantalla de login.

**Tareas:**
* `RF9 - HU9 - T1`: Crear endpoint para listar docentes incluyendo relaciones (idioma de especialidad).
* `RF9 - HU9 - T2`: Construir una tabla en Angular con un botón o toggle para cambiar el estado del docente de Activo a Inactivo.

#### RF 10 - HU 10: Creación de Paralelos y Horarios
**Historia:** Como administrador, quiero definir los paralelos y sus respectivos horarios, para organizar la oferta académica del semestre.
* **Escenario Bueno:** Dado que el administrador configura un nuevo curso de Inglés, cuando asigna el horario de 08:00 a 10:00 y guarda, entonces el sistema crea el paralelo y lo habilita para recibir inscripciones.
* **Escenario Malo Bajo:** Dado que se crea el paralelo, cuando el administrador olvida establecer el cupo máximo, entonces el sistema detiene la creación y marca el campo de cupo en rojo.
* **Escenario Malo Alto:** Dado que se asigna el Aula 5, cuando el sistema detecta que esa aula ya está ocupada por otro curso en el mismo horario, entonces bloquea la operación y notifica el choque de ambientes.

**Tareas:**
* `RF10 - HU10 - T1`: Diseñar la estructura de base de datos para Paralelos, Aulas y Horarios.
* `RF10 - HU10 - T2`: Desarrollar CRUD de paralelos en Laravel.
* `RF10 - HU10 - T3`: Diseñar interfaz de Angular con selectores de fecha y hora para definir turnos académicos.

#### RF 11 - HU 11: Vinculación de Docente a Paralelo
**Historia:** Como administrador, quiero asignar un instructor a un paralelo específico, para establecer la responsabilidad académica de cada curso.
* **Escenario Bueno:** Dado que se selecciona al Instructor A y el Paralelo 1, cuando se confirma la vinculación, entonces el sistema relaciona ambas entidades y otorga permisos al docente sobre ese curso.
* **Escenario Malo Bajo:** Dado que se intenta asignar al Instructor A, cuando éste ya tiene otro paralelo a la misma hora, entonces el sistema lanza una advertencia y prohíbe la doble asignación.
* **Escenario Malo Alto:** Dado que se procesa la vinculación, cuando el registro del docente fue eliminado de la base de datos segundos antes, entonces el sistema detiene la transacción y lanza un error de "Entidad no encontrada".

**Tareas:**
* `RF11 - HU11 - T1`: Crear la tabla intermedia (Pivot) docente_paralelo en MySQL.
* `RF11 - HU11 - T2`: Desarrollar lógica en Laravel que cruce los horarios asignados para bloquear la asignación si el docente ya está ocupado a esa hora.
* `RF11 - HU11 - T3`: Crear un panel de asignación en Angular con listas desplegables dinámicas de docentes y cursos disponibles.

#### RF 12 - HU 12: Generación de Constancias
**Historia:** Como docente, quiero generar mi constancia de asignación académica en formato digital, para tener un respaldo oficial de los paralelos y horarios que tengo bajo mi responsabilidad.
* **Escenario Bueno:** Dado que el docente solicita su constancia de horarios, cuando el sistema recopila sus materias asignadas, entonces genera un archivo digital y permite su descarga para respaldo.
* **Escenario Malo Bajo:** Dado que un docente sin carga horaria solicita el documento, cuando el sistema procesa la solicitud, entonces aborta la generación y avisa que no hay paralelos asignados.
* **Escenario Malo Alto:** Dado que se solicita el documento, cuando la librería de generación PDF falla en el servidor, entonces el sistema no emite la constancia y muestra un mensaje de fallo de renderizado.

**Tareas:**
* `RF12 - HU12 - T1`: Instalar e integrar librería de renderizado PDF (como DomPDF o Snappy) en el proyecto Laravel.
* `RF12 - HU12 - T2`: Diseñar la plantilla (Blade view) del documento con el membrete institucional.
* `RF12 - HU12 - T3`: Programar el botón en Angular que solicite el PDF y fuerce la descarga en el navegador del docente.

---

## EPIC 3: Interfaz Móvil Responsiva
*Objetivo:* Desarrollar la interfaz móvil responsiva de la aplicación.

#### RF 13 - HU 13: Adaptar el diseño visual (Responsive Design)
**Historia:** Como usuario del sistema, quiero que la interfaz ajuste automáticamente su resolución y maquetación, para visualizar el sistema correctamente en celulares, tablets y laptops.
* **Escenario Bueno:** Dado que el usuario entra desde su celular, cuando la plataforma carga, entonces las columnas se apilan verticalmente y el contenido se ajusta perfectamente a la pantalla.
* **Escenario Malo Bajo:** Dado que el usuario cambia rápidamente su celular de vertical a horizontal, cuando ocurre la rotación, entonces el diseño parpadea un instante y luego se reajusta correctamente sin perder datos.
* **Escenario Malo Alto:** Dado que el usuario navega desde un dispositivo sin soporte moderno de CSS, cuando el sistema intenta aplicar las reglas visuales, entonces la interfaz colapsa y los botones quedan inaccesibles.

**Tareas:**
* `RF13 - HU13 - T1`: Implementar el sistema de grillas (Grid System) fluido de Bootstrap en los contenedores principales de Angular.
* `RF13 - HU13 - T2`: Escribir reglas CSS/SCSS con Media Queries (@media) para reajustar tablas y tarjetas en pantallas menores a 768px.

#### RF 14 - HU 14: Implementar navegación táctil
**Historia:** Como estudiante, quiero utilizar menús tipo hamburguesa y botones optimizados, para navegar de forma ágil mediante gestos táctiles.
* **Escenario Bueno:** Dado que el estudiante usa la app móvil, cuando toca el ícono de menú hamburguesa, el menú lateral se despliega suavemente.
* **Escenarios Malos:** (Tocar secciones accidentalmente, scripts que congelen el menú en dispositivos lentos).

**Tareas:**
* `RF14 - HU14 - T1`: Configurar el componente de Navbar en Angular para colapsar en un menú hamburguesa.
* `RF14 - HU14 - T2`: Aumentar el área táctil (padding) de los botones de acción para facilitar la interacción con los dedos.

#### RF 15 - HU 15: Visualización de documentación en móviles
**Historia:** Como estudiante, quiero abrir y descargar mis documentos PDF de inscripción, para verificar mi información de forma legible desde dispositivos pequeños.

**Tareas:**
* `RF15 - HU15 - T1`: Integrar un visor nativo o librería de PDF compatible con móviles en Angular (ej. ng2-pdf-viewer).
* `RF15 - HU15 - T2`: Configurar el contenedor del documento para ajustarse dinámicamente al ancho de la pantalla (width: 100vw).

#### RF 16 - HU 16: Carga de archivos mediante cámara o galería
**Historia:** Como estudiante, quiero subir fotos de mis requisitos directamente desde la cámara de mi dispositivo, para completar mi inscripción.

**Tareas:**
* `RF16 - HU16 - T1`: Configurar la etiqueta HTML de subida en Angular para sugerir el uso de cámara (`accept="image/*" capture="environment"`).
* `RF16 - HU16 - T2`: Implementar un script en frontend para comprimir la imagen capturada antes de enviarla a la API de Laravel.

#### RF 17 - HU 17: Optimización de formularios extensos (Steppers)
**Historia:** Como administrador, quiero que los formularios de inscripción y vinculación se dividan en pasos secuenciales.

**Tareas:**
* `RF17 - HU17 - T1`: Integrar e instalar un componente de Stepper en Angular.
* `RF17 - HU17 - T2`: Dividir el formulario extenso de inscripción en 3 vistas (Datos Personales, Académicos, Documentación).
* `RF17 - HU17 - T3`: Implementar lógica de validación por paso.

---

## EPIC 4: Reportes y Estadísticas Académicas
*Objetivo:* Desarrollar el módulo de Reportes para generar estadísticas académicas de la Escuela de Idiomas del Ejército.

#### RF 18 - HU 18: Generación de Estadísticas de Matriculación por Idioma
**Historia:** Como jefe de estudios, quiero generar reportes cuantitativos detallados de la cantidad de estudiantes inscritos desglosados por idioma.

**Tareas:**
* `RF18 - HU18 - T1`: Crear consulta SQL avanzada en Laravel usando groupBy y count para sumarizar estudiantes por idioma.
* `RF18 - HU18 - T2`: Desarrollar el endpoint de reporte para devolver los totales en formato JSON.
* `RF18 - HU18 - T3`: Diseñar una vista de panel de reportes numéricos en Angular.

#### RF 19 - HU 19: Visualización de Tableros (Dashboards) de Rendimiento y Ocupación
**Historia:** Como administrador académico, quiero visualizar una interfaz gráfica con diagramas de barras y pasteles en tiempo real.

**Tareas:**
* `RF19 - HU19 - T1`: Instalar librería gráfica en Angular (ej. Chart.js o ng2-charts).
* `RF19 - HU19 - T2`: Programar endpoints en Laravel que calculen porcentajes de ocupación de aulas.
* `RF19 - HU19 - T3`: Configurar y renderizar gráficos de torta y de barras vinculados a la API.

#### RF 20 - HU 20: Exportación de Documentación Oficial (PDF/Excel)
**Historia:** Como docente, quiero exportar las listas de asistencia en formatos PDF y Excel.

**Tareas:**
* `RF20 - HU20 - T1`: Instalar paquete Laravel Excel (maatwebsite/excel).
* `RF20 - HU20 - T2`: Crear las clases de exportación (Export Classes) en backend que darán formato al centralizador de asistencia y notas.
* `RF20 - HU20 - T3`: Implementar en Angular botones dedicados de descarga que manejen las respuestas Blob del servidor.

#### RF 21 - HU 21: Motor de Consultas con Filtrado Multi-criterio
**Historia:** Como administrador, quiero generar reportes personalizados aplicando filtros combinados (gestión, docente, nivel, turno).

**Tareas:**
* `RF21 - HU21 - T1`: Implementar Scopes en los modelos Eloquent de Laravel para manejar filtros dinámicos.
* `RF21 - HU21 - T2`: Crear un panel lateral de filtros colapsable en la interfaz de Angular.
* `RF21 - HU21 - T3`: Configurar Angular para recargar la tabla de datos y los gráficos automáticamente.
