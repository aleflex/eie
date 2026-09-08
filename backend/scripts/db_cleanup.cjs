const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

async function runCleanup() {
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

  console.log('================================================================');
  console.log(' SANEAMIENTO Y LIMPIEZA DE BASE DE DATOS (AIVEN MYSQL)');
  console.log('================================================================\n');

  // 1. Purgar tokens huérfanos en personal_access_tokens
  console.log('--- 1. PURGA DE TOKENS HUÉRFANOS ---');
  const [tokensBefore] = await conn.execute(`
    SELECT COUNT(*) as total FROM personal_access_tokens
  `);
  const [huerfanosBefore] = await conn.execute(`
    SELECT COUNT(*) as total
    FROM personal_access_tokens pat
    LEFT JOIN usuarios u ON pat.tokenable_id = u.id_usuario
    WHERE u.id_usuario IS NULL
  `);
  console.log(`Tokens totales antes: ${tokensBefore[0].total}`);
  console.log(`Tokens huérfanos a eliminar: ${huerfanosBefore[0].total}`);

  const [deleteTokens] = await conn.execute(`
    DELETE pat
    FROM personal_access_tokens pat
    LEFT JOIN usuarios u ON pat.tokenable_id = u.id_usuario
    WHERE u.id_usuario IS NULL
  `);
  console.log(`Filas eliminadas en personal_access_tokens: ${deleteTokens.affectedRows}`);

  // 2. Limpieza de sesiones anónimas y viejas en sessions
  console.log('\n--- 2. PURGA DE SESIONES ANÓNIMAS / OBSOLETAS ---');
  const [sessionsBefore] = await conn.execute(`
    SELECT COUNT(*) as total,
           SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as anonimas
    FROM sessions
  `);
  console.log(`Sesiones totales antes: ${sessionsBefore[0].total} (Anónimas: ${sessionsBefore[0].anonimas})`);

  // Eliminar sesiones que no tienen usuario vinculado o que tengan más de 7 días de antigüedad
  const [deleteSessions] = await conn.execute(`
    DELETE FROM sessions
    WHERE user_id IS NULL OR last_activity < UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)
  `);
  console.log(`Filas eliminadas en sessions: ${deleteSessions.affectedRows}`);

  // 3. Verificación de responsables sin estudiantes
  console.log('\n--- 3. REVISIÓN DE RESPONSABLES SIN ESTUDIANTES ---');
  const [respSinEst] = await conn.execute(`
    SELECT r.id_responsable, r.nombres_responsable, r.apellido_paterno_responsable
    FROM responsables r
    LEFT JOIN estudiante_responsable er ON r.id_responsable = er.id_responsable
    WHERE er.id_responsable IS NULL
  `);
  console.log(`Responsables sin estudiante vinculado actualmente: ${respSinEst.length}`);
  respSinEst.forEach(r => {
    console.log(`  - ID ${r.id_responsable}: ${r.nombres_responsable} ${r.apellido_paterno_responsable || ''}`);
  });
  // Por prudencia no los borramos automáticamente para no perder posibles datos de tutores precargados, salvo confirmación explícita.

  // 4. Verificación final de integridad
  console.log('\n--- 4. ESTADO FINAL POST-LIMPIEZA ---');
  const [tokensAfter] = await conn.execute(`SELECT COUNT(*) as total FROM personal_access_tokens`);
  const [sessionsAfter] = await conn.execute(`SELECT COUNT(*) as total FROM sessions`);
  console.log(`Tokens activos legítimos restantes: ${tokensAfter[0].total}`);
  console.log(`Sesiones activas restantes: ${sessionsAfter[0].total}`);

  await conn.end();
  console.log('\n================================================================');
  console.log(' SANEAMIENTO COMPLETADO EXITOSAMENTE');
  console.log('================================================================');
}

runCleanup().catch(err => {
  console.error('ERROR EN LIMPIEZA:', err);
  process.exit(1);
});
