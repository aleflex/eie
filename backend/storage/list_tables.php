<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
try {
    $configs = DB::select('SELECT * FROM configuraciones');
    print_r($configs);
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
