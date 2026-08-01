<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Calendario Académico
        Configuracion::set('fecha_inicio_inscripcion', date('Y-m-d\T08:00'), 'string', 'academic');
        Configuracion::set('fecha_fin_inscripcion', date('Y-m-d\T18:00', strtotime('+3 months')), 'string', 'academic');

        // 2. Control de Archivos y Límites
        Configuracion::set('limite_pdf_mb', 5, 'int', 'files');
        Configuracion::set('comprimir_imagenes', true, 'bool', 'files');

        // 3. Datos Institucionales
        Configuracion::set('nombre_institucion', 'Escuela de Idiomas del Ejército', 'string', 'institution');
        Configuracion::set('nombre_director', 'Cnl. DAEN Juan Pérez López', 'string', 'institution');
        Configuracion::set('grado_director', 'Coronel DAEN', 'string', 'institution');

        // 4. Parámetros Generales
        Configuracion::set('cupo_defecto_paralelo', 25, 'int', 'general');
    }
}
