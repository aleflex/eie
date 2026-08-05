<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$pass = Hash::make('admin123');

// 1. Actualizar contraseña del admin existente (ID 16)
DB::table('usuarios')->where('usuario', 'admin')->update(['password' => $pass, 'debe_cambiar_password' => false]);

// 2. Asignar docentes y estudiantes
DB::table('usuarios')->where('id_usuario', 6)->update(['usuario' => 'roberto.valenzuela', 'password' => $pass, 'debe_cambiar_password' => false]);
DB::table('usuarios')->where('id_usuario', 11)->update(['usuario' => 'juan.mamani', 'password' => $pass, 'debe_cambiar_password' => false]);
DB::table('usuarios')->where('id_usuario', 12)->update(['usuario' => 'aleflex', 'password' => $pass, 'debe_cambiar_password' => false]);

echo "¡Credenciales activas en Docker MySQL!\n";
$usuarios = DB::table('usuarios')->select('id_usuario', 'usuario', 'id_rol')->get();
foreach ($usuarios as $u) {
    if (!empty($u->usuario)) {
        echo "ID: {$u->id_usuario} | Usuario: '{$u->usuario}' | Rol ID: {$u->id_rol}\n";
    }
}
