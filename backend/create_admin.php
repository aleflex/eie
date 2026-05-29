<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

if (User::where('email', 'admin@eie.edu.bo')->exists()) {
    echo "El usuario ya existe.\n";
} else {
    User::create([
        'name' => 'Admin EIE',
        'email' => 'admin@eie.edu.bo',
        'password' => Hash::make('admin123'),
    ]);
    echo "Usuario admin@eie.edu.bo creado con éxito.\n";
}
