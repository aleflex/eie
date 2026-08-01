-- ============================================================================
-- POBLACIÓN DE CATÁLOGOS NORMALIZADOS
-- ============================================================================
-- Ejecutar DESPUÉS de: php artisan migrate
-- ============================================================================

-- PASO 1: Roles
-- ============================================================================
INSERT INTO roles (nombre_rol, descripcion, created_at, updated_at) VALUES
('admin', 'Administrador del sistema', NOW(), NOW()),
('estudiante', 'Estudiante de la institución', NOW(), NOW()),
('docente', 'Docente/Profesor', NOW(), NOW()),
('directivo', 'Directivo/Rector', NOW(), NOW()),
('secretaria', 'Personal administrativo', NOW(), NOW());

-- PASO 2: Estados Civiles
-- ============================================================================
INSERT INTO estados_civil (nombre_estado_civil, created_at, updated_at) VALUES
('Soltero', NOW(), NOW()),
('Casado', NOW(), NOW()),
('Divorciado', NOW(), NOW()),
('Viudo', NOW(), NOW()),
('Unión Libre', NOW(), NOW()),
('Separado', NOW(), NOW());

-- PASO 3: Grupos Sanguíneos
-- ============================================================================
INSERT INTO grupos_sanguineo (nombre_grupo_sanguineo, created_at, updated_at) VALUES
('O+', NOW(), NOW()),
('O-', NOW(), NOW()),
('A+', NOW(), NOW()),
('A-', NOW(), NOW()),
('B+', NOW(), NOW()),
('B-', NOW(), NOW()),
('AB+', NOW(), NOW()),
('AB-', NOW(), NOW());

-- PASO 4: Tipos de Documentos
-- ============================================================================
INSERT INTO tipos_documentos (nombre_tipo_documento, codigo, created_at, updated_at) VALUES
('Cédula de Identidad', 'CI', NOW(), NOW()),
('Pasaporte', 'PASS', NOW(), NOW()),
('Licencia de Conducir', 'LDER', NOW(), NOW()),
('Carnet de Extranjería', 'CE', NOW(), NOW()),
('Documento de Identidad', 'DI', NOW(), NOW()),
('Carnet Militar', 'CM', NOW(), NOW()),
('Certificado de Nacimiento', 'CN', NOW(), NOW());

-- PASO 5: Tipos de Contrato Docente
-- ============================================================================
INSERT INTO tipos_contrato_docente (nombre_tipo_contrato, descripcion, created_at, updated_at) VALUES
('Contratado', 'Docente contratado por período', NOW(), NOW()),
('Titular', 'Docente en plantilla permanente', NOW(), NOW()),
('Interino', 'Docente interino/temporal', NOW(), NOW()),
('Practicante', 'Docente en período de prácticas', NOW(), NOW()),
('Becario', 'Docente becario', NOW(), NOW());

-- PASO 6: Estados Generales (para otras tablas)
-- ============================================================================
-- Ya deberían existir en tablas como grados, armas, etc.
-- Pero aquí documentamos los valores esperados:
-- Estado en estudiantes: Activo, Inactivo, Suspendido, Graduado
-- Estado en cursos: Activo, Inactivo, Archivado
-- Estado en inscripciones: Activo, Completado, Cancelado, Suspendido

-- PASO 7: Verificación
-- ============================================================================
SELECT 'VERIFICACIÓN DE CATÁLOGOS POBLADOS' as status;

SELECT 'Roles:' as item, COUNT(*) as cantidad FROM roles;
SELECT 'Estados Civiles:' as item, COUNT(*) as cantidad FROM estados_civil;
SELECT 'Grupos Sanguíneos:' as item, COUNT(*) as cantidad FROM grupos_sanguineo;
SELECT 'Tipos de Documento:' as item, COUNT(*) as cantidad FROM tipos_documentos;
SELECT 'Tipos de Contrato Docente:' as item, COUNT(*) as cantidad FROM tipos_contrato_docente;

-- PASO 8: Lista de valores para referencia
-- ============================================================================
SELECT '=== ROLES DISPONIBLES ===' as lista;
SELECT CONCAT(id_rol, '. ', nombre_rol, ' - ', COALESCE(descripcion, 'N/A')) as valor
FROM roles ORDER BY id_rol;

SELECT '=== ESTADOS CIVILES DISPONIBLES ===' as lista;
SELECT CONCAT(id_estado_civil, '. ', nombre_estado_civil) as valor
FROM estados_civil ORDER BY id_estado_civil;

SELECT '=== GRUPOS SANGUÍNEOS DISPONIBLES ===' as lista;
SELECT CONCAT(id_grupo_sanguineo, '. ', nombre_grupo_sanguineo) as valor
FROM grupos_sanguineo ORDER BY id_grupo_sanguineo;

SELECT '=== TIPOS DE DOCUMENTO DISPONIBLES ===' as lista;
SELECT CONCAT(id_tipo_documento, '. ', nombre_tipo_documento, ' (', codigo, ')') as valor
FROM tipos_documentos ORDER BY id_tipo_documento;

SELECT '=== TIPOS DE CONTRATO DOCENTE DISPONIBLES ===' as lista;
SELECT CONCAT(id_tipo_contrato, '. ', nombre_tipo_contrato, ' - ', COALESCE(descripcion, 'N/A')) as valor
FROM tipos_contrato_docente ORDER BY id_tipo_contrato;

-- PASO 9: Actualizar registros existentes
-- ============================================================================
-- IMPORTANTE: Ejecutar DESPUÉS de tener los catálogos poblados

-- Actualizar usuarios con tipos válidos (ejemplo - AJUSTAR SEGÚN TU LÓGICA)
-- UPDATE usuarios SET id_tipo_usuario = 1 WHERE id_tipo_usuario IS NULL AND tipo_usuario = 'admin';
-- UPDATE usuarios SET id_tipo_usuario = 2 WHERE id_tipo_usuario IS NULL AND tipo_usuario = 'estudiante';
-- UPDATE usuarios SET id_tipo_usuario = 3 WHERE id_tipo_usuario IS NULL AND tipo_usuario = 'docente';

SELECT 'POBLACIÓN DE CATÁLOGOS COMPLETADA' as status;
