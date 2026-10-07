<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InscriptionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AccesoController;
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\ParaleloController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\NotaAsistenciaController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (Accesibles sin autenticación)
|--------------------------------------------------------------------------
*/
// Ping de salud para Railway / Render
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()]);
});

// Autenticación pública
Route::post('/login', [AuthController::class, 'login']);

// Catálogos públicos requeridos para el formulario de inscripción inicial
Route::get('/idiomas', [CourseController::class, 'getIdiomas']);
Route::get('/niveles', [CourseController::class, 'getNiveles']);
Route::get('/cursos', [CourseController::class, 'index']);
Route::get('/roles/permisos', [RoleController::class, 'getPermisos']);

// Formulario de inscripción pública (nuevos postulantes envían formulario y documentos)
Route::post('/inscripciones', [InscriptionController::class, 'store']);

// Servidor de imágenes públicas (fotos de estudiantes/documentos validados)
Route::get('/storage/{p1}/{p2}/{filename}', function ($p1, $p2, $filename) {
    $fullPath = storage_path("app/public/$p1/$p2/$filename");
    if (!\Illuminate\Support\Facades\File::exists($fullPath)) {
        $fullPath = storage_path("app/$p1/$p2/$filename");
    }
    if (!\Illuminate\Support\Facades\File::exists($fullPath)) {
        $fullPath = public_path("storage/$p1/$p2/$filename");
    }
    
    if (\Illuminate\Support\Facades\File::exists($fullPath) && !\Illuminate\Support\Facades\File::isDirectory($fullPath)) {
        $file = \Illuminate\Support\Facades\File::get($fullPath);
        $type = \Illuminate\Support\Facades\File::mimeType($fullPath) ?: 'image/jpeg';

        return \Illuminate\Support\Facades\Response::make($file, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    abort(404, 'Archivo no encontrado');
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren Bearer Token o Sesión Activa)
|--------------------------------------------------------------------------
*/
Route::middleware('api.auth')->group(function () {
    // Sesión y Perfil
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'getProfile']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);

    // Inscripciones (Gestión protegida para administradores y docentes)
    Route::get('/inscripciones', [InscriptionController::class, 'index']);
    Route::put('/inscripciones/{id}', [InscriptionController::class, 'update']);
    Route::delete('/inscripciones/{id}', [InscriptionController::class, 'destroy']);
    Route::get('/inscripciones/{id}/certificate', [PdfController::class, 'generateCertificate']);

    // Estudiantes (Gestión protegida)
    Route::get('/estudiantes/buscar', [StudentController::class, 'search']);
    Route::get('/estudiantes/{id}/historial', [StudentController::class, 'history']);
    Route::get('/estudiantes/{estudianteId}/documentos', [DocumentController::class, 'index']);
    Route::post('/documentos/subir', [DocumentController::class, 'store']);
    Route::delete('/documentos/{id}', [DocumentController::class, 'destroy']);
    Route::apiResource('estudiantes', StudentController::class)->except(['create', 'edit']);

    // Cursos (Administración protegida de cursos)
    Route::post('/cursos', [CourseController::class, 'store']);
    Route::get('/cursos/{id}', [CourseController::class, 'show']);
    Route::put('/cursos/{id}', [CourseController::class, 'update']);
    Route::delete('/cursos/{id}', [CourseController::class, 'destroy']);

    // Docentes (Instructores)
    Route::get('/docentes', [DocenteController::class, 'index']);
    Route::get('/docentes/mis-paralelos', [DocenteController::class, 'misParalelos']);
    Route::get('/docentes/{id}', [DocenteController::class, 'show']);
    Route::post('/docentes', [DocenteController::class, 'store']);
    Route::put('/docentes/{id}', [DocenteController::class, 'update']);
    Route::patch('/docentes/{id}/toggle-status', [DocenteController::class, 'toggleStatus']);
    Route::delete('/docentes/{id}', [DocenteController::class, 'destroy']);

    // Paralelos y Aulas
    Route::get('/paralelos', [ParaleloController::class, 'index']);
    Route::get('/paralelos/{id}', [ParaleloController::class, 'show']);
    Route::post('/paralelos', [ParaleloController::class, 'store']);
    Route::put('/paralelos/{id}', [ParaleloController::class, 'update']);
    Route::delete('/paralelos/{id}', [ParaleloController::class, 'destroy']);
    Route::get('/aulas', [ParaleloController::class, 'getAulas']);
    Route::post('/aulas', [ParaleloController::class, 'storeAula']);
    Route::put('/aulas/{id}', [ParaleloController::class, 'updateAula']);
    Route::delete('/aulas/{id}', [ParaleloController::class, 'destroyAula']);
    Route::get('/horarios', [ParaleloController::class, 'getHorarios']);

    // Notas y Asistencias (Docente / Rectificación)
    Route::post('/autorizar-modificacion', [NotaAsistenciaController::class, 'autorizarModificacion']);
    Route::get('/inscripciones/{id}/notas', [NotaAsistenciaController::class, 'getNotas']);
    Route::post('/inscripciones/{id}/notas', [NotaAsistenciaController::class, 'saveNota']);
    Route::delete('/notas/{id}', [NotaAsistenciaController::class, 'deleteNota']);
    Route::get('/inscripciones/{id}/asistencias', [NotaAsistenciaController::class, 'getAsistencias']);
    Route::post('/inscripciones/{id}/asistencias', [NotaAsistenciaController::class, 'saveAsistencia']);
    Route::get('/paralelos/{id}/autorizacion', [NotaAsistenciaController::class, 'getAutorizacionParalelo']);
    Route::post('/paralelos/{id}/autorizacion', [NotaAsistenciaController::class, 'setAutorizacionParalelo']);
    Route::get('/paralelos/{id}/asistencias', [NotaAsistenciaController::class, 'getAsistenciasParalelo']);

    // Reportes y Estadísticas
    Route::get('/reports/statistics-by-language', [ReportController::class, 'getLanguageStatistics']);
    Route::get('/reports/classroom-occupancy', [ReportController::class, 'getClassroomOccupancy']);
    Route::get('/reports/dashboard-summary', [ReportController::class, 'getDashboardSummary']);
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel']);
    Route::get('/reports/export/nominal-excel', [ReportController::class, 'exportNominalExcel']);
    Route::get('/reports/export/notas-excel', [ReportController::class, 'exportNotasExcel']);
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf']);
    Route::get('/reports/export/docente-pdf', [ReportController::class, 'exportDocentePdf']);
    Route::get('/reports/export/notas-pdf', [ReportController::class, 'exportNotasPdf']);
    Route::get('/reports/export/asistencias-pdf', [ReportController::class, 'exportAsistenciasPdf']);

    /*
    |--------------------------------------------------------------------------
    | RUTAS EXCLUSIVAS PARA ADMINISTRADORES (Role: Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('api.auth:admin')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/inscripciones/admin-assign', [InscriptionController::class, 'adminAssign']);
        Route::post('/estudiantes/{id}/rehabilitar', [StudentController::class, 'rehabilitar']);
        Route::post('/estudiantes/{id}/baja', [StudentController::class, 'destroy']);

        // Gestión de Accesos (Credenciales)
        Route::get('/accesos', [AccesoController::class, 'index']);
        Route::post('/accesos/asignar', [AccesoController::class, 'asignar']);
        Route::put('/accesos/actualizar/{userId}', [AccesoController::class, 'actualizar']);
        Route::delete('/accesos/desvincular/{userId}', [AccesoController::class, 'desvincular']);

        // Roles y Permisos
        Route::post('/roles/permisos', [RoleController::class, 'savePermisos']);
        Route::apiResource('roles', RoleController::class);

        // Configuración General
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
    });
});
