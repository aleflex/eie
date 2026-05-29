<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aula;
use App\Models\Horario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear usuario administrador si no existe
        if (!User::where('email', 'admin@eie.edu.bo')->exists()) {
            User::create([
                'name' => 'Administrador EIE',
                'email' => 'admin@eie.edu.bo',
                'password' => bcrypt('password123'),
            ]);
        }

        // 1.5 Crear un curso por defecto para que las inscripciones iniciales no fallen
        if (\App\Models\Curso::count() === 0) {
            \App\Models\Curso::create([
                'id' => 1,
                'idioma' => 'Inglés',
                'nivel' => 'Inicial (Básico)',
                'modalidad' => 'Regular',
                'horario' => '08:00 - 10:00',
                'cupo_minimo' => 5,
                'cupo_maximo' => 30,
            ]);
        }

        // 2. Sembrar Aulas (Clasrooms)
        if (Aula::count() === 0) {
            Aula::create(['nombre' => 'Aula 101', 'capacidad' => 30]);
            Aula::create(['nombre' => 'Aula 102', 'capacidad' => 30]);
            Aula::create(['nombre' => 'Aula 201', 'capacidad' => 35]);
            Aula::create(['nombre' => 'Aula 202', 'capacidad' => 35]);
            Aula::create(['nombre' => 'Laboratorio de Idiomas A', 'capacidad' => 25]);
            Aula::create(['nombre' => 'Laboratorio de Idiomas B', 'capacidad' => 25]);
            Aula::create(['nombre' => 'Auditorio Principal', 'capacidad' => 100]);
        }

        // 3. Sembrar Horarios (Schedules)
        if (Horario::count() === 0) {
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:00:00']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '14:00:00', 'hora_fin' => '16:00:00']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '16:00:00', 'hora_fin' => '18:00:00']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '18:30:00', 'hora_fin' => '20:30:00']);
            Horario::create(['dia_semana' => 'Lunes a Viernes', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00']);
            Horario::create(['dia_semana' => 'Sábado', 'hora_inicio' => '08:00:00', 'hora_fin' => '13:00:00']);
            Horario::create(['dia_semana' => 'Sábado', 'hora_inicio' => '14:00:00', 'hora_fin' => '19:00:00']);
        }

        // 4. Sembrar Configuraciones por defecto
        $this->call(SettingsTableSeeder::class);
    }
}
