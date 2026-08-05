<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Configurar conexión dinámica Aiven
    config([
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

    $res = DB::connection('aiven')->select('SELECT VERSION() as version');
    echo "¡CONEXIÓN EXITOSA A AIVEN MYSQL 24/7! Versión: " . $res[0]->version . "\n";
} catch (\Exception $e) {
    echo "ERROR DE CONEXIÓN AIVEN: " . $e->getMessage() . "\n";
}
