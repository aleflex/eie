const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

async function executeMigration() {
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
  console.log(' EJECUTANDO MIGRACIÓN: convert_estado_civil_and_grupo_sanguineo');
  console.log(` DB Target: ${dbName} @ ${getEnv('DB_HOST')}`);
  console.log('================================================================\n');

  // Helper para verificar existencia de columnas
  const hasColumn = async (table, column) => {
    const [cols] = await conn.execute(`
      SELECT COLUMN_NAME FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
    `, [dbName, table, column]);
    return cols.length > 0;
  };

  // Helper para verificar existencia de tablas
  const hasTable = async (table) => {
    const [tbls] = await conn.execute(`
      SELECT TABLE_NAME FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
    `, [dbName, table]);
    return tbls.length > 0;
  };

  // Helper para eliminar FK si existe
  const dropFkIfExists = async (table, fkColumn) => {
    const [fks] = await conn.execute(`
      SELECT CONSTRAINT_NAME 
      FROM information_schema.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
    `, [dbName, table, fkColumn]);

    for (const fk of fks) {
      console.log(`  - Eliminando FK '${fk.CONSTRAINT_NAME}' en tabla '${table}'...`);
      try {
        await conn.execute(`ALTER TABLE \`${table}\` DROP FOREIGN KEY \`${fk.CONSTRAINT_NAME}\``);
      } catch (err) {
        console.log(`    (Aviso: ${err.message})`);
      }
    }
  };

  // 1. Agregar columnas estado_civil y grupo_sanguineo a usuarios
  console.log('--- PASO 1: Agregando columnas varchar a usuarios ---');
  if (!(await hasColumn('usuarios', 'estado_civil'))) {
    console.log('  + Agregando columna `estado_civil` (VARCHAR 50)...');
    await conn.execute(`ALTER TABLE usuarios ADD COLUMN estado_civil VARCHAR(50) NULL AFTER ci`);
  } else {
    console.log('  = Columna `estado_civil` ya existe.');
  }

  if (!(await hasColumn('usuarios', 'grupo_sanguineo'))) {
    console.log('  + Agregando columna `grupo_sanguineo` (VARCHAR 10)...');
    await conn.execute(`ALTER TABLE usuarios ADD COLUMN grupo_sanguineo VARCHAR(10) NULL AFTER estado_civil`);
  } else {
    console.log('  = Columna `grupo_sanguineo` ya existe.');
  }

  // 2. Poblar datos desde las tablas catálogo o estudiantes
  console.log('\n--- PASO 2: Migrando datos existentes ---');
  const hasUserEstCivil = await hasColumn('usuarios', 'id_estado_civil');
  const hasEstEstCivil = (await hasTable('estudiantes')) && (await hasColumn('estudiantes', 'id_estado_civil'));

  if (await hasTable('estados_civil')) {
    console.log('  > Poblando `estado_civil` desde la tabla catalog `estados_civil`...');
    const joinConds = [];
    if (hasUserEstCivil) joinConds.push("u.id_estado_civil = ec.id_estado_civil");
    if (hasEstEstCivil) joinConds.push("e.id_estado_civil = ec.id_estado_civil");
    const joinOn = joinConds.length > 0 ? joinConds.join(' OR ') : '1=0';

    const [resEc] = await conn.execute(`
      UPDATE usuarios u
      LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
      LEFT JOIN estados_civil ec ON (${joinOn})
      SET u.estado_civil = COALESCE(ec.nombre_estado_civil, u.estado_civil, 'Soltero(a)')
      WHERE u.estado_civil IS NULL OR u.estado_civil = ''
    `);
    console.log(`    Filas actualizadas en estado_civil: ${resEc.affectedRows}`);
  }

  const hasUserGrupoSang = await hasColumn('usuarios', 'id_grupo_sanguineo');
  const hasEstGrupoSang = (await hasTable('estudiantes')) && (await hasColumn('estudiantes', 'id_grupo_sanguineo'));

  if (await hasTable('grupos_sanguineo')) {
    console.log('  > Poblando `grupo_sanguineo` desde la tabla catalog `grupos_sanguineo`...');
    const joinConds = [];
    if (hasUserGrupoSang) joinConds.push("u.id_grupo_sanguineo = gs.id_grupo_sanguineo");
    if (hasEstGrupoSang) joinConds.push("e.id_grupo_sanguineo = gs.id_grupo_sanguineo");
    const joinOn = joinConds.length > 0 ? joinConds.join(' OR ') : '1=0';

    const [resGs] = await conn.execute(`
      UPDATE usuarios u
      LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
      LEFT JOIN grupos_sanguineo gs ON (${joinOn})
      SET u.grupo_sanguineo = COALESCE(gs.nombre_grupo_sanguineo, u.grupo_sanguineo, 'O+')
      WHERE u.grupo_sanguineo IS NULL OR u.grupo_sanguineo = ''
    `);
    console.log(`    Filas actualizadas en grupo_sanguineo: ${resGs.affectedRows}`);
  }

  // Asegurar que ningún usuario quede nulo
  await conn.execute(`UPDATE usuarios SET estado_civil = 'Soltero(a)' WHERE estado_civil IS NULL OR estado_civil = ''`);
  await conn.execute(`UPDATE usuarios SET grupo_sanguineo = 'O+' WHERE grupo_sanguineo IS NULL OR grupo_sanguineo = ''`);

  // 3. Eliminar llaves foráneas y columnas en estudiantes
  console.log('\n--- PASO 3: Limpiando columnas antiguas en estudiantes ---');
  if (await hasTable('estudiantes')) {
    if (await hasColumn('estudiantes', 'id_estado_civil')) {
      await dropFkIfExists('estudiantes', 'id_estado_civil');
      console.log('  - Eliminando columna `id_estado_civil` de `estudiantes`...');
      await conn.execute(`ALTER TABLE estudiantes DROP COLUMN id_estado_civil`);
    }
    if (await hasColumn('estudiantes', 'id_grupo_sanguineo')) {
      await dropFkIfExists('estudiantes', 'id_grupo_sanguineo');
      console.log('  - Eliminando columna `id_grupo_sanguineo` de `estudiantes`...');
      await conn.execute(`ALTER TABLE estudiantes DROP COLUMN id_grupo_sanguineo`);
    }
  }

  // 4. Eliminar llaves foráneas y columnas en usuarios
  console.log('\n--- PASO 4: Limpiando columnas antiguas en usuarios ---');
  if (await hasColumn('usuarios', 'id_estado_civil')) {
    await dropFkIfExists('usuarios', 'id_estado_civil');
    console.log('  - Eliminando columna `id_estado_civil` de `usuarios`...');
    await conn.execute(`ALTER TABLE usuarios DROP COLUMN id_estado_civil`);
  }
  if (await hasColumn('usuarios', 'id_grupo_sanguineo')) {
    await dropFkIfExists('usuarios', 'id_grupo_sanguineo');
    console.log('  - Eliminando columna `id_grupo_sanguineo` de `usuarios`...');
    await conn.execute(`ALTER TABLE usuarios DROP COLUMN id_grupo_sanguineo`);
  }

  // 5. Eliminar tablas catálogo/satélite
  console.log('\n--- PASO 5: Eliminando tablas catálogo obsoletas ---');
  console.log('  - Eliminando tabla `estados_civil` si existe...');
  await conn.execute(`DROP TABLE IF EXISTS estados_civil`);
  console.log('  - Eliminando tabla `grupos_sanguineo` si existe...');
  await conn.execute(`DROP TABLE IF EXISTS grupos_sanguineo`);

  // 6. Registrar la migración en la tabla migrations de Laravel
  console.log('\n--- PASO 6: Registrando migración en la tabla `migrations` ---');
  const migrationName = '2026_09_08_010000_convert_estado_civil_and_grupo_sanguineo_to_attributes_in_usuarios';
  
  if (await hasTable('migrations')) {
    const [alreadyMigrated] = await conn.execute(
      `SELECT id FROM migrations WHERE migration = ?`,
      [migrationName]
    );

    if (alreadyMigrated.length === 0) {
      const [maxBatch] = await conn.execute(`SELECT MAX(batch) as max_b FROM migrations`);
      const nextBatch = (maxBatch[0].max_b || 0) + 1;
      await conn.execute(
        `INSERT INTO migrations (migration, batch) VALUES (?, ?)`,
        [migrationName, nextBatch]
      );
      console.log(`  + Migración registrada exitosamente en batch #${nextBatch}.`);
    } else {
      console.log('  = La migración ya figuraba registrada en la tabla `migrations`.');
    }
  }

  await conn.end();
  console.log('\n================================================================');
  console.log(' MIGRACIÓN FINALIZADA EXITOSAMENTE');
  console.log('================================================================');
}

executeMigration().catch(err => {
  console.error('\nERROR EN MIGRACIÓN:', err);
  process.exit(1);
});
