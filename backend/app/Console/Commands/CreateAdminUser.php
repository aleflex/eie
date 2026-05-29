<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Docente;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Crea o verifica el usuario administrador del sistema';

    public function handle()
    {
        $email = 'admin@eie.edu.bo';

        $user = User::where('email', $email)->first();

        if ($user) {
            $docente = Docente::where('user_id', $user->id)->first();
            if ($docente) {
                $this->warn("El usuario $email está registrado como DOCENTE (docente_id: {$docente->id}).");
                $this->info("No se puede usar como admin puro. Crea uno con email diferente.");
            } else {
                $this->info("El usuario $email ya existe y NO es docente → es ADMIN. Contraseña: password123");
            }
        } else {
            User::create([
                'name'     => 'Administrador EIE',
                'email'    => $email,
                'password' => Hash::make('password123'),
            ]);
            $this->info("✅ Usuario admin creado: $email / password123");
        }

        // Siempre asegurarse de tener un admin puro con email alternativo si no existe
        $adminAlt = 'superadmin@eie.edu.bo';
        if (!User::where('email', $adminAlt)->exists()) {
            User::create([
                'name'     => 'Super Administrador',
                'email'    => $adminAlt,
                'password' => Hash::make('admin2024'),
            ]);
            $this->info("✅ Admin alternativo creado: $adminAlt / admin2024");
        } else {
            $this->line("Admin alternativo ($adminAlt) ya existe.");
        }
    }
}
