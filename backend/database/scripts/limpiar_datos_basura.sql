-- ============================================================================
-- LIMPIEZA DE DATOS BASURA - BASE DE DATOS EIE
-- ============================================================================
-- Este script limpia registros inválidos, duplicados y inconsistentes
-- EJECUTAR CON CUIDADO - CREAR BACKUP ANTES
-- ============================================================================

-- PASO 1: Identificar y documentar problemas
-- ============================================================================
SELECT 'DIAGNÓSTICO: Registros de estudiantes sin usuario válido' as diagnostico;
SELECT COUNT(*) as cantidad FROM estudiantes WHERE id_usuario IS NULL;

SELECT 'DIAGNÓSTICO: Usuarios sin tipo asignado' as diagnostico;
SELECT COUNT(*) as cantidad FROM usuarios WHERE tipo_usuario = '' OR tipo_usuario IS NULL;

SELECT 'DIAGNÓSTICO: Estudiantes con carnet duplicado' as diagnostico;
SELECT carnet_militar, COUNT(*) as cantidad 
FROM estudiantes 
WHERE carnet_militar IS NOT NULL AND carnet_militar != ''
GROUP BY carnet_militar 
HAVING COUNT(*) > 1;

SELECT 'DIAGNÓSTICO: Inscripciones sin paralelo en cursos con paralelos' as diagnostico;
SELECT COUNT(*) as cantidad 
FROM inscripciones i
WHERE i.id_paralelo IS NULL 
  AND i.id_curso IN (SELECT DISTINCT id_curso FROM paralelos);

-- PASO 2: Limpiar datos nulos innecesarios
-- ============================================================================

-- Eliminar estudiantes huérfanos (sin usuario válido)
-- ANTES DE EJECUTAR: Revisar qué registros se van a eliminar
SELECT id_estudiante, id_usuario FROM estudiantes WHERE id_usuario IS NULL LIMIT 10;

-- Comentar la siguiente línea si deseas ACTUALIZAR EN LUGAR DE ELIMINAR
-- DELETE FROM estudiantes WHERE id_usuario IS NULL;

-- PASO 3: Eliminar inscripciones inválidas
-- ============================================================================

-- Inscripciones con estudiante inexistente
DELETE FROM inscripciones 
WHERE id_estudiante NOT IN (SELECT id_estudiante FROM estudiantes);

-- Inscripciones con curso inexistente
DELETE FROM inscripciones 
WHERE id_curso NOT IN (SELECT id_curso FROM cursos);

-- Inscripciones con paralelo inexistente (pero que debería tener uno)
DELETE FROM inscripciones 
WHERE id_paralelo NOT IN (SELECT id_paralelo FROM paralelos) 
  AND id_paralelo IS NOT NULL;

-- PASO 4: Limpiar notas huérfanas
-- ============================================================================

-- Eliminar notas de inscripciones que ya no existen
DELETE FROM notas 
WHERE id_inscripcion NOT IN (SELECT id_inscripcion FROM inscripciones);

-- PASO 5: Limpiar asistencias huérfanas
-- ============================================================================

-- Eliminar asistencias de inscripciones que ya no existen
DELETE FROM asistencias 
WHERE id_inscripcion NOT IN (SELECT id_inscripcion FROM inscripciones);

-- PASO 6: Limpiar documentos huérfanos
-- ============================================================================

-- Eliminar documentos de estudiantes que ya no existen
DELETE FROM documentos 
WHERE id_estudiante NOT IN (SELECT id_estudiante FROM estudiantes);

-- PASO 7: Limpiar registros de auditoría del sistema
-- ============================================================================

-- Eliminar campos de string vacíos en usuarios (preparación para FK)
UPDATE usuarios SET tipo_usuario = 'estudiante' WHERE tipo_usuario = '' OR tipo_usuario IS NULL;

-- Normalizar estado a valores válidos
UPDATE usuarios SET estado = 'Activo' WHERE estado = '' OR estado IS NULL;

-- PASO 8: Verificar integridad
-- ============================================================================
SELECT 'DESPUÉS DE LIMPIEZA:' as status;

SELECT 'Estudiantes sin usuario:' as check_item, COUNT(*) as resultado 
FROM estudiantes WHERE id_usuario IS NULL;

SELECT 'Inscripciones huérfanas:' as check_item, COUNT(*) as resultado 
FROM inscripciones i
WHERE i.id_estudiante NOT IN (SELECT id_estudiante FROM estudiantes)
   OR i.id_curso NOT IN (SELECT id_curso FROM cursos);

SELECT 'Notas huérfanas:' as check_item, COUNT(*) as resultado 
FROM notas WHERE id_inscripcion NOT IN (SELECT id_inscripcion FROM inscripciones);

SELECT 'Asistencias huérfanas:' as check_item, COUNT(*) as resultado 
FROM asistencias WHERE id_inscripcion NOT IN (SELECT id_inscripcion FROM inscripciones);

SELECT 'Documentos huérfanos:' as check_item, COUNT(*) as resultado 
FROM documentos WHERE id_estudiante NOT IN (SELECT id_estudiante FROM estudiantes);

-- PASO 9: Reporte final
-- ============================================================================
SELECT 'REPORTE FINAL' as status;

SELECT 'Total usuarios:' as item, COUNT(*) as cantidad FROM usuarios;
SELECT 'Total estudiantes:' as item, COUNT(*) as cantidad FROM estudiantes;
SELECT 'Total docentes:' as item, COUNT(*) as cantidad FROM docentes;
SELECT 'Total inscripciones:' as item, COUNT(*) as cantidad FROM inscripciones;
SELECT 'Total notas:' as item, COUNT(*) as cantidad FROM notas;
SELECT 'Total asistencias:' as item, COUNT(*) as cantidad FROM asistencias;
SELECT 'Total documentos:' as item, COUNT(*) as cantidad FROM documentos;

-- PASO 10: Desfragmentar (opcional, solo MySQL)
-- ============================================================================
-- OPTIMIZE TABLE usuarios;
-- OPTIMIZE TABLE estudiantes;
-- OPTIMIZE TABLE inscripciones;
-- OPTIMIZE TABLE notas;
-- OPTIMIZE TABLE asistencias;

COMMIT;
SELECT 'LIMPIEZA COMPLETADA' as status;
