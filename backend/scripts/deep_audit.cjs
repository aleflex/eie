const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

async function runDeepAudit() {
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

  console.log('--- CONEXIÓN EXITOSA A AIVEN MYSQL ---');

  // 1. Listar todas las tablas y conteo de filas
  const [tables] = await conn.execute(`
    SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = ?
    ORDER BY TABLE_NAME
  `, [getEnv('DB_DATABASE')]);

  console.log(`\n=== 1. TABLAS EXISTENTES (${tables.length}) ===`);
  const actualCounts = {};
  for (const t of tables) {
    const [cnt] = await conn.execute(`SELECT COUNT(*) as c FROM \`${t.TABLE_NAME}\``);
    const count = cnt[0].c;
    actualCounts[t.TABLE_NAME] = count;
    console.log(`- ${t.TABLE_NAME.padEnd(35)}: ${count} registros`);
  }

  // 2. Analizar columnas vacías / 100% NULL en cada tabla
  console.log(`\n=== 2. ANÁLISIS DE COLUMNAS 100% VACÍAS O NULAS ===`);
  const deadColumns = [];
  for (const t of tables) {
    const tableName = t.TABLE_NAME;
    const rowCount = actualCounts[tableName];
    if (rowCount === 0) continue;

    const [cols] = await conn.execute(`
      SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
      ORDER BY ORDINAL_POSITION
    `, [getEnv('DB_DATABASE'), tableName]);

    for (const c of cols) {
      const colName = c.COLUMN_NAME;
      // Contar no nulos y no vacios
      const [stats] = await conn.execute(`
        SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN \`${colName}\` IS NOT NULL AND CAST(\`${colName}\` AS CHAR) != '' THEN 1 ELSE 0 END) as filled
        FROM \`${tableName}\`
      `);
      const total = stats[0].total;
      const filled = Number(stats[0].filled || 0);
      const emptyPct = total > 0 ? (((total - filled) / total) * 100).toFixed(1) : 0;

      if (filled === 0) {
        deadColumns.push({
          table: tableName,
          column: colName,
          type: c.DATA_TYPE,
          total,
          emptyPct: '100%'
        });
        console.log(`  [VACÍA 100%] ${tableName}.${colName} (${c.DATA_TYPE}) -> 0/${total} con datos`);
      } else if (emptyPct >= 80 && total > 5) {
        console.log(`  [CASI VACÍA ${emptyPct}%] ${tableName}.${colName} (${c.DATA_TYPE}) -> ${filled}/${total} con datos`);
      }
    }
  }

  // 3. Huérfanos e inconsistencias relacionales
  console.log(`\n=== 3. ANÁLISIS DE INTEGRIDAD REFERENCIAL Y HUÉRFANOS ===`);

  // Estudiantes sin usuario
  const [estSinUser] = await conn.execute(`
    SELECT e.id_estudiante, e.id_usuario 
    FROM estudiantes e 
    LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario 
    WHERE u.id_usuario IS NULL
  `);
  console.log(`- Estudiantes sin usuario válido: ${estSinUser.length}`);

  // Docentes sin usuario
  const [docSinUser] = await conn.execute(`
    SELECT d.id_docente, d.id_usuario 
    FROM docentes d 
    LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario 
    WHERE u.id_usuario IS NULL
  `);
  console.log(`- Docentes sin usuario válido: ${docSinUser.length}`);

  // Inscripciones sin estudiante o sin curso
  const [inscHuerfanas] = await conn.execute(`
    SELECT i.id_inscripcion, i.id_estudiante, i.id_curso
    FROM inscripciones i
    LEFT JOIN estudiantes e ON i.id_estudiante = e.id_estudiante
    LEFT JOIN cursos c ON i.id_curso = c.id_curso
    WHERE e.id_estudiante IS NULL OR c.id_curso IS NULL
  `);
  console.log(`- Inscripciones huérfanas (sin estudiante o curso): ${inscHuerfanas.length}`);

  // Contactos de emergencia huérfanos
  const [contHuerfanos] = await conn.execute(`
    SELECT c.id_contacto, c.id_estudiante
    FROM contactos_emergencia c
    LEFT JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
    WHERE e.id_estudiante IS NULL
  `);
  console.log(`- Contactos de emergencia huérfanos: ${contHuerfanos.length}`);

  // Responsables huérfanos
  const [respHuerfanos] = await conn.execute(`
    SELECT r.id_responsable, r.id_estudiante
    FROM responsables r
    LEFT JOIN estudiantes e ON r.id_estudiante = e.id_estudiante
    WHERE e.id_estudiante IS NULL
  `);
  console.log(`- Responsables huérfanos: ${respHuerfanos.length}`);

  // Cursos huérfanos (docente, idioma, nivel, horario, aula)
  const [cursosHuerfanos] = await conn.execute(`
    SELECT c.id_curso
    FROM cursos c
    LEFT JOIN idiomas idm ON c.id_idioma = idm.id_idioma
    WHERE idm.id_idioma IS NULL
  `);
  console.log(`- Cursos con idioma inexistente: ${cursosHuerfanos.length}`);

  // 4. Chequeo de duplicados
  console.log(`\n=== 4. ANÁLISIS DE REGISTROS DUPLICADOS ===`);
  const [dupCi] = await conn.execute(`
    SELECT ci, COUNT(*) as qty, GROUP_CONCAT(id_usuario) as ids
    FROM usuarios
    WHERE ci IS NOT NULL AND ci != ''
    GROUP BY ci
    HAVING qty > 1
  `);
  console.log(`- Usuarios con CI duplicado: ${dupCi.length}`);
  dupCi.forEach(d => console.log(`    CI: ${d.ci} (Veces: ${d.qty}, IDs: ${d.ids})`));

  const [dupEmail] = await conn.execute(`
    SELECT correo_institucional, COUNT(*) as qty, GROUP_CONCAT(id_usuario) as ids
    FROM usuarios
    WHERE correo_institucional IS NOT NULL AND correo_institucional != ''
    GROUP BY correo_institucional
    HAVING qty > 1
  `);
  console.log(`- Usuarios con correo institucional duplicado: ${dupEmail.length}`);
  dupEmail.forEach(d => console.log(`    Correo: ${d.correo_institucional} (Veces: ${d.qty}, IDs: ${d.ids})`));

  const [dupInsc] = await conn.execute(`
    SELECT id_estudiante, id_curso, COUNT(*) as qty, GROUP_CONCAT(id_inscripcion) as ids
    FROM inscripciones
    GROUP BY id_estudiante, id_curso
    HAVING qty > 1
  `);
  console.log(`- Inscripciones duplicadas (mismo estudiante y mismo curso): ${dupInsc.length}`);
  dupInsc.forEach(d => console.log(`    Estudiante: ${d.id_estudiante}, Curso: ${d.id_curso} (IDs: ${d.ids})`));

  // 5. Análisis de valores Enum y Estados
  console.log(`\n=== 5. ANÁLISIS DE VALORES DE ESTADO (CONSISTENCIA DE DOMINIO) ===`);
  const [estadosUsuarios] = await conn.execute(`SELECT DISTINCT estado FROM usuarios`);
  console.log(`- Estados distintos en usuarios:`, estadosUsuarios.map(e => e.estado));

  const [estadosEstudiantes] = await conn.execute(`SELECT DISTINCT estado FROM estudiantes`);
  console.log(`- Estados distintos en estudiantes:`, estadosEstudiantes.map(e => e.estado));

  const [estadosInscripciones] = await conn.execute(`SELECT DISTINCT estado FROM inscripciones`);
  console.log(`- Estados distintos en inscripciones:`, estadosInscripciones.map(e => e.estado));

  await conn.end();
  console.log('\n--- AUDITORÍA PROFUNDA COMPLETADA ---');
}

runDeepAudit().catch(console.error);
