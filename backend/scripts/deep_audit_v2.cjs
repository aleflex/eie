const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

async function runSuperAudit() {
  const envPath = path.resolve(__dirname, '../.env');
  const env = fs.readFileSync(envPath, 'utf8');
  const getEnv = (key) => {
    const m = env.match(new RegExp('^' + key + '=(.*)$', 'm'));
    return m ? m[1].trim() : null;
  };

  const caPath = path.resolve(__dirname, '../storage/ca.pem');

  const conn = await mysql.createConnection({
    host: getEnv('DB_HOST'),
    port: parseInt(getEnv('DB_PORT')),
    user: getEnv('DB_USERNAME'),
    password: getEnv('DB_PASSWORD'),
    database: getEnv('DB_DATABASE'),
    ssl: { ca: fs.readFileSync(caPath) }
  });

  const dbName = getEnv('DB_DATABASE');
  console.log('================================================================');
  console.log(` AUDITORÍA TÉCNICA PROFUNDA (FASE 2) - BASE DE DATOS: ${dbName}`);
  console.log(' Servidor: ' + getEnv('DB_HOST') + ':' + getEnv('DB_PORT'));
  console.log(' Fecha/Hora Auditoría: ' + new Date().toISOString());
  console.log('================================================================\n');

  const results = {
    metadata: {
      database: dbName,
      timestamp: new Date().toISOString(),
      server: getEnv('DB_HOST')
    }
  };

  // 1. CONFIGURACIÓN Y VARIABLES DEL SERVIDOR MYSQL
  console.log('--- 1. PARÁMETROS DEL MOTOR Y CONFIGURACIÓN ---');
  const [serverVars] = await conn.execute(`
    SELECT @@version as version, @@version_comment as comment, 
           @@sql_mode as sql_mode, @@character_set_database as charset_db, 
           @@collation_database as collation_db, @@time_zone as time_zone,
           @@system_time_zone as system_time_zone, @@max_connections as max_connections
  `);
  console.log('Versión MySQL:', serverVars[0].version, `(${serverVars[0].comment})`);
  console.log('Charset DB:', serverVars[0].charset_db, '| Collation:', serverVars[0].collation_db);
  console.log('Timezone:', serverVars[0].time_zone, `(System: ${serverVars[0].system_time_zone})`);
  console.log('Max Connections:', serverVars[0].max_connections);
  console.log('SQL Mode:', serverVars[0].sql_mode);
  results.serverConfig = serverVars[0];

  // 2. MÉTRICAS DE ALMACENAMIENTO Y TABLAS
  console.log('\n--- 2. DIMENSIÓN Y ALMACENAMIENTO DE TABLAS ---');
  const [tables] = await conn.execute(`
    SELECT 
      TABLE_NAME, 
      ENGINE, 
      TABLE_COLLATION, 
      TABLE_ROWS, 
      ROUND((DATA_LENGTH) / 1024, 2) AS data_kb,
      ROUND((INDEX_LENGTH) / 1024, 2) AS index_kb,
      ROUND((DATA_FREE) / 1024, 2) AS data_free_kb,
      AUTO_INCREMENT
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = ?
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
  `, [dbName]);

  let totalDataKb = 0;
  let totalIndexKb = 0;
  const tableCounts = {};

  for (const t of tables) {
    const [cnt] = await conn.execute(`SELECT COUNT(*) as c FROM \`${t.TABLE_NAME}\``);
    const realCount = cnt[0].c;
    tableCounts[t.TABLE_NAME] = realCount;
    totalDataKb += parseFloat(t.data_kb || 0);
    totalIndexKb += parseFloat(t.index_kb || 0);
    console.log(
      `- ${t.TABLE_NAME.padEnd(30)} | Rows: ${String(realCount).padStart(5)} | Data: ${String(t.data_kb).padStart(7)} KB | Index: ${String(t.index_kb).padStart(7)} KB | Collation: ${t.TABLE_COLLATION}`
    );
  }
  console.log(`\n>> TOTAL ESPACIO BD: Data: ${(totalDataKb/1024).toFixed(2)} MB | Índices: ${(totalIndexKb/1024).toFixed(2)} MB | Total: ${((totalDataKb + totalIndexKb)/1024).toFixed(2)} MB`);
  results.tableStorage = tables;

  // 3. CLAVES PRIMARIAS Y FOREIGN KEYS
  console.log('\n--- 3. REVISIÓN DE INTEGRIDAD ESTRUCTURAL (PK & FKs) ---');
  // Tablas sin Primary Key
  const [noPk] = await conn.execute(`
    SELECT t.TABLE_NAME 
    FROM information_schema.TABLES t
    LEFT JOIN information_schema.STATISTICS s 
      ON t.TABLE_SCHEMA = s.TABLE_SCHEMA 
      AND t.TABLE_NAME = s.TABLE_NAME 
      AND s.INDEX_NAME = 'PRIMARY'
    WHERE t.TABLE_SCHEMA = ? 
      AND t.TABLE_TYPE = 'BASE TABLE'
      AND s.INDEX_NAME IS NULL
  `, [dbName]);
  if (noPk.length > 0) {
    console.log('ALERTA: Tablas SIN Clave Primaria (PK):', noPk.map(x => x.TABLE_NAME).join(', '));
  } else {
    console.log('OK: Todas las tablas tienen Clave Primaria (PK) definida.');
  }
  results.tablasSinPk = noPk;

  // Lista de Foreign Keys y sus reglas ON DELETE / ON UPDATE
  const [fks] = await conn.execute(`
    SELECT 
      kcu.TABLE_NAME, 
      kcu.COLUMN_NAME, 
      kcu.CONSTRAINT_NAME, 
      kcu.REFERENCED_TABLE_NAME, 
      kcu.REFERENCED_COLUMN_NAME,
      rc.DELETE_RULE,
      rc.UPDATE_RULE
    FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
      ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA 
      AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
    WHERE kcu.TABLE_SCHEMA = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
  `, [dbName]);
  console.log(`Total Foreign Keys formales activas en schema: ${fks.length}`);
  results.foreignKeys = fks;

  // 4. ANÁLISIS DE COLUMNAS VACÍAS / DENSIDAD DE DATOS (BATCH AGGREGATION RÁPIDO)
  console.log('\n--- 4. DENSIDAD DE DATOS Y COLUMNAS 100% NULAS ---');
  const emptyColumns = [];
  const sparseColumns = [];

  for (const t of tables) {
    const tableName = t.TABLE_NAME;
    const count = tableCounts[tableName];
    if (count === 0) continue;

    const [cols] = await conn.execute(`
      SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
      ORDER BY ORDINAL_POSITION
    `, [dbName, tableName]);

    const selects = cols.map(c => 
      `SUM(CASE WHEN \`${c.COLUMN_NAME}\` IS NOT NULL AND CAST(\`${c.COLUMN_NAME}\` AS CHAR) != '' THEN 1 ELSE 0 END) AS \`col_${c.COLUMN_NAME}\``
    ).join(', ');

    const [aggResult] = await conn.execute(`SELECT ${selects} FROM \`${tableName}\``);
    const row = aggResult[0];

    for (const c of cols) {
      const filled = Number(row[`col_${c.COLUMN_NAME}`] || 0);
      const emptyPct = count > 0 ? (((count - filled) / count) * 100).toFixed(1) : 0;
      if (filled === 0) {
        emptyColumns.push({ table: tableName, column: c.COLUMN_NAME, type: c.DATA_TYPE, count });
        console.log(`  [VACÍA 100%] ${tableName}.${c.COLUMN_NAME} (${c.DATA_TYPE}) -> 0/${count}`);
      } else if (emptyPct >= 80 && count > 5) {
        sparseColumns.push({ table: tableName, column: c.COLUMN_NAME, type: c.DATA_TYPE, filled, count, emptyPct });
        console.log(`  [CASI VACÍA ${emptyPct}%] ${tableName}.${c.COLUMN_NAME} (${c.DATA_TYPE}) -> ${filled}/${count}`);
      }
    }
  }
  results.columnasVacias = emptyColumns;
  results.columnasCasiVacias = sparseColumns;

  // 5. REVISIÓN DE INTEGRIDAD REFERENCIAL SOBRE TODAS LAS FKs EXISTENTES Y RELACIONES CLAVE
  console.log('\n--- 5. HUÉRFANOS E INCONSISTENCIAS REFERENCIALES ---');
  const orphanChecks = [];

  for (const fk of fks) {
    const query = `
      SELECT COUNT(*) as orphans
      FROM \`${fk.TABLE_NAME}\` child
      LEFT JOIN \`${fk.REFERENCED_TABLE_NAME}\` parent
        ON child.\`${fk.COLUMN_NAME}\` = parent.\`${fk.REFERENCED_COLUMN_NAME}\`
      WHERE child.\`${fk.COLUMN_NAME}\` IS NOT NULL 
        AND parent.\`${fk.REFERENCED_COLUMN_NAME}\` IS NULL
    `;
    const [orphanRes] = await conn.execute(query);
    const orphanCount = orphanRes[0].orphans;
    if (orphanCount > 0) {
      console.log(`  [ALERTA HUÉRFANOS FK] ${fk.TABLE_NAME}.${fk.COLUMN_NAME} -> ${fk.REFERENCED_TABLE_NAME}: ${orphanCount} registros huérfanos!`);
      orphanChecks.push({
        table: fk.TABLE_NAME,
        column: fk.COLUMN_NAME,
        referencedTable: fk.REFERENCED_TABLE_NAME,
        orphanCount
      });
    }
  }
  if (orphanChecks.length === 0) {
    console.log('OK: 0 registros huérfanos en todas las 24 Foreign Keys formales.');
  }
  results.huerfanosFk = orphanChecks;

  // Chequeos específicos adicionales
  // a) Usuarios sin rol
  const [usrSinRol] = await conn.execute(`
    SELECT u.id_usuario, u.ci, u.nombres, u.apellidos, u.id_rol
    FROM usuarios u
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    WHERE r.id_rol IS NULL
  `);
  console.log(`- Usuarios sin rol asignado o con id_rol inválido: ${usrSinRol.length}`);
  results.usuariosSinRol = usrSinRol;

  // b) Estudiantes sin registro en usuarios
  const [estSinUser] = await conn.execute(`
    SELECT e.id_estudiante, e.id_usuario
    FROM estudiantes e
    LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
    WHERE u.id_usuario IS NULL
  `);
  console.log(`- Estudiantes sin usuario asociado: ${estSinUser.length}`);
  results.estudiantesSinUsuario = estSinUser;

  // c) Docentes sin registro en usuarios
  const [docSinUser] = await conn.execute(`
    SELECT d.id_docente, d.id_usuario
    FROM docentes d
    LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario
    WHERE u.id_usuario IS NULL
  `);
  console.log(`- Docentes sin usuario asociado: ${docSinUser.length}`);
  results.docentesSinUsuario = docSinUser;

  // d) Responsables sin ningún estudiante asignado
  const [respSinEst] = await conn.execute(`
    SELECT r.id_responsable, r.nombres_responsable, r.apellido_paterno_responsable
    FROM responsables r
    LEFT JOIN estudiante_responsable er ON r.id_responsable = er.id_responsable
    WHERE er.id_responsable IS NULL
  `);
  console.log(`- Responsables registrados sin ningún estudiante asignado: ${respSinEst.length}`);
  respSinEst.forEach(r => console.log(`    Responsable ID ${r.id_responsable}: ${r.nombres_responsable} ${r.apellido_paterno_responsable || ''}`));
  results.responsablesSinEstudiante = respSinEst;

  // e) Contactos de emergencia sin estudiante
  const [contSinEst] = await conn.execute(`
    SELECT c.id_contacto_emergencia, c.id_estudiante
    FROM contactos_emergencia c
    LEFT JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
    WHERE e.id_estudiante IS NULL
  `);
  console.log(`- Contactos de emergencia huérfanos: ${contSinEst.length}`);
  results.contactosSinEstudiante = contSinEst;

  // f) Documentos sin estudiante
  const [docSinEst] = await conn.execute(`
    SELECT d.id_documento, d.id_estudiante, d.tipo_documento
    FROM documentos d
    LEFT JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
    WHERE e.id_estudiante IS NULL
  `);
  console.log(`- Documentos huérfanos de estudiante: ${docSinEst.length}`);
  results.documentosSinEstudiante = docSinEst;

  // g) Cursos con relaciones inválidas
  const [cursosRefsInval] = await conn.execute(`
    SELECT c.id_curso,
      CASE WHEN idm.id_idioma IS NULL THEN 'idioma' ELSE NULL END as miss_idioma,
      CASE WHEN n.id_nivel IS NULL THEN 'nivel' ELSE NULL END as miss_nivel,
      CASE WHEN m.id_modalidad IS NULL THEN 'modalidad' ELSE NULL END as miss_modalidad
    FROM cursos c
    LEFT JOIN idiomas idm ON c.id_idioma = idm.id_idioma
    LEFT JOIN niveles n ON c.id_nivel = n.id_nivel
    LEFT JOIN modalidades m ON c.id_modalidad = m.id_modalidad
    HAVING miss_idioma IS NOT NULL OR miss_nivel IS NOT NULL OR miss_modalidad IS NOT NULL
  `);
  console.log(`- Cursos con idioma, nivel o modalidad inexistente: ${cursosRefsInval.length}`);
  results.cursosRefsInval = cursosRefsInval;

  // h) Tokens de Sanctum huérfanos
  const [tokensHuerf] = await conn.execute(`
    SELECT pat.id, pat.tokenable_id, pat.tokenable_type
    FROM personal_access_tokens pat
    LEFT JOIN usuarios u ON pat.tokenable_id = u.id_usuario
    WHERE u.id_usuario IS NULL
  `);
  console.log(`- Tokens Sanctum huérfanos (usuarios eliminados): ${tokensHuerf.length}`);
  results.tokensHuerf = tokensHuerf;

  // 6. CALIDAD DE DATOS, DUPLICADOS Y SEGURIDAD
  console.log('\n--- 6. CALIDAD DE DATOS, DUPLICADOS Y SEGURIDAD ---');

  // a) Contraseñas no hasheadas (en texto plano o débiles)
  const [badPass] = await conn.execute(`
    SELECT id_usuario, correo_institucional, password
    FROM usuarios
    WHERE password NOT LIKE '$2y$%' AND password NOT LIKE '$2a$%' AND password NOT LIKE '$argon2%'
  `);
  console.log(`- Contraseñas inseguras o sin hash Bcrypt/Argon2: ${badPass.length}`);
  if (badPass.length > 0) {
    badPass.forEach(bp => console.log(`    ALERTA: Usuario ID ${bp.id_usuario} (${bp.correo_institucional}) tiene password no hasheada!`));
  }
  results.passwordsInseguras = badPass;

  // b) CI duplicados
  const [dupCi] = await conn.execute(`
    SELECT ci, COUNT(*) as qty, GROUP_CONCAT(id_usuario) as ids
    FROM usuarios
    WHERE ci IS NOT NULL AND ci != ''
    GROUP BY ci
    HAVING qty > 1
  `);
  console.log(`- CIs duplicados en usuarios: ${dupCi.length}`);
  dupCi.forEach(d => console.log(`    CI ${d.ci} aparece ${d.qty} veces en IDs: ${d.ids}`));
  results.ciDuplicados = dupCi;

  // c) Correos institucionales duplicados
  const [dupCorreoInst] = await conn.execute(`
    SELECT correo_institucional, COUNT(*) as qty, GROUP_CONCAT(id_usuario) as ids
    FROM usuarios
    WHERE correo_institucional IS NOT NULL AND correo_institucional != ''
    GROUP BY correo_institucional
    HAVING qty > 1
  `);
  console.log(`- Correos institucionales duplicados: ${dupCorreoInst.length}`);
  dupCorreoInst.forEach(d => console.log(`    Correo ${d.correo_institucional} en IDs: ${d.ids}`));
  results.correosInstitucionalesDuplicados = dupCorreoInst;

  // d) Inscripciones duplicadas (mismo estudiante en el mismo curso)
  const [dupInsc] = await conn.execute(`
    SELECT id_estudiante, id_curso, COUNT(*) as qty, GROUP_CONCAT(id_inscripcion) as ids
    FROM inscripciones
    GROUP BY id_estudiante, id_curso
    HAVING qty > 1
  `);
  console.log(`- Inscripciones duplicadas (mismo estudiante y curso): ${dupInsc.length}`);
  dupInsc.forEach(d => console.log(`    Estudiante ${d.id_estudiante}, Curso ${d.id_curso} -> IDs: ${d.ids}`));
  results.inscripcionesDuplicadas = dupInsc;

  // e) Fechas de nacimiento anómalas en estudiantes
  const [badFechasNac] = await conn.execute(`
    SELECT e.id_estudiante, e.id_usuario, u.nombres, u.apellidos, e.fecha_nacimiento,
      TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) as edad
    FROM estudiantes e
    JOIN usuarios u ON e.id_usuario = u.id_usuario
    WHERE e.fecha_nacimiento IS NOT NULL 
      AND (e.fecha_nacimiento > CURDATE() OR e.fecha_nacimiento < '1920-01-01' OR TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) < 3)
  `);
  console.log(`- Estudiantes con fecha de nacimiento anómala (<3 años, >104 años o futura): ${badFechasNac.length}`);
  badFechasNac.forEach(b => console.log(`    Estudiante ID ${b.id_estudiante}: ${b.nombres} ${b.apellidos} (${b.fecha_nacimiento}, Edad: ${b.edad})`));
  results.fechasNacimientoAnomalas = badFechasNac;

  // f) Rango de notas
  const [colsNotas] = await conn.execute(`
    SELECT COLUMN_NAME FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'notas'
  `, [dbName]);
  const notaCol = colsNotas.find(c => ['nota', 'calificacion_final', 'nota_final'].includes(c.COLUMN_NAME));
  if (notaCol) {
    const [badNotas] = await conn.execute(`
      SELECT id_nota, id_inscripcion, \`${notaCol.COLUMN_NAME}\` as nota
      FROM notas
      WHERE \`${notaCol.COLUMN_NAME}\` < 0 OR \`${notaCol.COLUMN_NAME}\` > 100
    `);
    console.log(`- Notas fuera de rango (0-100) en col '${notaCol.COLUMN_NAME}': ${badNotas.length}`);
    results.notasFueraDeRango = badNotas;
  }

  // 7. ANÁLISIS DE TIMESTAMPS Y ANOMALÍAS TEMPORALES
  console.log('\n--- 7. ANÁLISIS DE TIMESTAMPS Y ANOMALÍAS TEMPORALES ---');
  results.timestampAnomalies = [];
  for (const t of tables) {
    const tableName = t.TABLE_NAME;
    const [hasCols] = await conn.execute(`
      SELECT COLUMN_NAME FROM information_schema.COLUMNS 
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN ('created_at', 'updated_at')
    `, [dbName, tableName]);
    if (hasCols.length === 2 && tableCounts[tableName] > 0) {
      const [badTs] = await conn.execute(`
        SELECT COUNT(*) as c FROM \`${tableName}\`
        WHERE created_at IS NOT NULL AND updated_at IS NOT NULL AND created_at > updated_at
      `);
      if (badTs[0].c > 0) {
        console.log(`  [ANOMALÍA] ${tableName}: ${badTs[0].c} filas tienen created_at > updated_at`);
        results.timestampAnomalies.push({ table: tableName, count: badTs[0].c });
      }
    }
  }

  // 8. ESTADOS Y DOMINIOS ENUM
  console.log('\n--- 8. CONSISTENCIA DE ESTADOS ENUM EN TODA LA BD ---');
  results.estadosPorTabla = {};
  const tablesWithStatus = ['usuarios', 'estudiantes', 'docentes', 'cursos', 'inscripciones', 'notas', 'documentos'];
  for (const tw of tablesWithStatus) {
    const [hasCol] = await conn.execute(`
      SELECT COLUMN_NAME FROM information_schema.COLUMNS 
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'estado'
    `, [dbName, tw]);
    if (hasCol.length > 0) {
      const [distincts] = await conn.execute(`
        SELECT estado, COUNT(*) as qty FROM \`${tw}\` GROUP BY estado
      `);
      results.estadosPorTabla[tw] = distincts;
      console.log(`- Estados en tabla \`${tw}\`:`, distincts.map(d => `${d.estado || 'NULL'}: ${d.qty}`).join(', '));
    }
  }

  // 9. REVISIÓN DE SESIONES Y TOKENS ACTIVOS
  console.log('\n--- 9. HUELLA DE SESIONES Y TOKENS DE SEGURIDAD ---');
  const [tokenStats] = await conn.execute(`
    SELECT 
      COUNT(*) as total_tokens,
      SUM(CASE WHEN last_used_at IS NULL THEN 1 ELSE 0 END) as never_used,
      SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END) as expired,
      MAX(last_used_at) as last_activity
    FROM personal_access_tokens
  `);
  console.log('- Tokens Sanctum:', tokenStats[0]);
  results.tokensSanctumStats = tokenStats[0];

  const [sessionStats] = await conn.execute(`
    SELECT 
      COUNT(*) as total_sessions,
      COUNT(DISTINCT user_id) as users_with_session,
      MIN(FROM_UNIXTIME(last_activity)) as oldest_session,
      MAX(FROM_UNIXTIME(last_activity)) as newest_session
    FROM sessions
  `);
  console.log('- Sesiones en base de datos:', sessionStats[0]);
  results.sessionStats = sessionStats[0];

  // Escribir reporte JSON
  const reportPath = path.resolve(__dirname, 'audit_report_v2.json');
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
  console.log(`\nReporte JSON exportado exitosamente a: ${reportPath}`);

  await conn.end();
  console.log('\n================================================================');
  console.log(' AUDITORÍA PROFUNDA FASE 2 FINALIZADA EXITOSAMENTE (100%)');
  console.log('================================================================');
}

runSuperAudit().catch(err => {
  console.error('ERROR EN AUDITORÍA:', err);
  process.exit(1);
});
