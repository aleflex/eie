<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

if (Schema::hasTable('notas')) {
    echo "TABLA NOTAS EXISTE\n";
    print_r(Schema::getColumnListing('notas'));
} else {
    echo "TABLA NOTAS NO EXISTE - Creándola para la prueba...\n";
    Schema::create('notas', function ($table) {
        $table->id();
        $table->unsignedBigInteger('inscripcion_id');
        $table->decimal('nota', 5, 2);
        $table->timestamps();
    });
    echo "Tabla NOTAS creada.\n";
}
