<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

try {
    config([
        'database.default' => 'aiven',
        'database.connections.aiven' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'mysql-1be5550e-alejandrokaviraya3-5905.a.aivencloud.com'),
            'port' => env('DB_PORT', '14095'),
            'database' => env('DB_DATABASE', 'defaultdb'),
            'username' => env('DB_USERNAME', 'avnadmin'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'options' => [
                PDO::MYSQL_ATTR_SSL_CA => __DIR__.'/storage/ca.pem',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ],
        ]
    ]);

    echo "--- 1. Ejecutando Migraciones en Aiven Cloud MySQL ---\n";
    Artisan::call('migrate:fresh', ['--force' => true]);
    echo Artisan::output();

    echo "--- 2. Poblando Catálogos Base (Grados, Armas, Idiomas, Niveles, Cursos) ---\n";
    $grados = ['Subteniente', 'Teniente', 'Capitán', 'Mayor', 'Teniente Coronel', 'Coronel', 'Premilitar', 'Civil'];
    foreach ($grados as $g) {
        DB::connection('aiven')->table('grados')->insertOrIgnore(['nombre_grado' => $g, 'created_at' => now(), 'updated_at' => now()]);
    }
    $armas = ['Infantería', 'Caballería', 'Artillería', 'Ingeniería', 'Comunicaciones', 'Blindados', 'Servicios'];
    foreach ($armas as $a) {
        DB::connection('aiven')->table('armas')->insertOrIgnore(['nombre_arma' => $a, 'created_at' => now(), 'updated_at' => now()]);
    }
    $idiomas = ['Inglés', 'Francés', 'Chino Mandarín', 'Alemán', 'Quechua', 'Aymara'];
    foreach ($idiomas as $idm) {
        DB::connection('aiven')->table('idiomas')->insertOrIgnore(['nombre_idioma' => $idm, 'created_at' => now(), 'updated_at' => now()]);
    }

    echo "--- 3. Creando Cuentas de Usuario (Admin, Docentes, Estudiantes) ---\n";
    $pass = Hash::make('admin123');

    // Admin
    DB::connection('aiven')->table('usuarios')->insertOrIgnore([
        'usuario' => 'admin',
        'correo_institucional' => 'admin@eie.edu.bo',
        'password' => $pass,
        'id_rol' => 1,
        'debe_cambiar_password' => false,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Docente
    DB::connection('aiven')->table('usuarios')->insertOrIgnore([
        'usuario' => 'roberto.valenzuela',
        'correo_institucional' => 'roberto@eie.edu.bo',
        'password' => $pass,
        'id_rol' => 2,
        'debe_cambiar_password' => false,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Estudiantes
    DB::connection('aiven')->table('usuarios')->insertOrIgnore([
        'usuario' => 'juan.mamani',
        'correo_institucional' => 'juan@eie.edu.bo',
        'password' => $pass,
        'id_rol' => 3,
        'debe_cambiar_password' => false,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    DB::connection('aiven')->table('usuarios')->insertOrIgnore([
        'usuario' => 'aleflex',
        'correo_institucional' => 'aleflex@eie.edu.bo',
        'password' => $pass,
        'id_rol' => 3,
        'debe_cambiar_password' => false,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $uCount = DB::connection('aiven')->table('usuarios')->count();
    echo "\n¡MIGRACIÓN A AIVEN MYSQL 24/7 COMPLETADA CON ÉXITO! 🎉 ($uCount usuarios activos)\n";
} catch (\Exception $e) {
    echo "ERROR DURANTE LA MIGRACIÓN: " . $e->getMessage() . "\n";
}