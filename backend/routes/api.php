<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InscriptionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AccesoController;

// Ping de salud para Railway / Render
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user/profile', [AuthController::class, 'getProfile']);
Route::post('/user/profile', [AuthController::class, 'updateProfile']);
Route::post('/user/change-password', [AuthController::class, 'changePassword']);

// Estudiantes (CRUD)
Route::get('/estudiantes/buscar', [StudentController::class, 'search']);
Route::post('/estudiantes/{id}/rehabilitar', [StudentController::class, 'rehabilitar']);
Route::post('/estudiantes/{id}/baja', [StudentController::class, 'destroy']);
Route::apiResource('estudiantes', StudentController::class);
Route::get('/estudiantes/{id}/historial', [StudentController::class, 'history']);


// Cursos (CRUD)
Route::apiResource('cursos', CourseController::class);

// Inscripciones
Route::get('/inscripciones', [InscriptionController::class, 'index']);
Route::post('/inscripciones', [InscriptionController::class, 'store']);
Route::post('/inscripciones/admin-assign', [InscriptionController::class, 'adminAssign']);
Route::put('/inscripciones/{id}', [InscriptionController::class, 'update']);
Route::delete('/inscripciones/{id}', [InscriptionController::class, 'destroy']);

// Documentos Digitales
Route::get('/estudiantes/{estudianteId}/documentos', [DocumentController::class, 'index']);
Route::post('/documentos/subir', [DocumentController::class, 'store']);
Route::delete('/documentos/{id}', [DocumentController::class, 'destroy']);

// Docentes (Instructores)
Route::get('/docentes', [\App\Http\Controllers\Api\DocenteController::class, 'index']);
Route::get('/docentes/mis-paralelos', [\App\Http\Controllers\Api\DocenteController::class, 'misParalelos']);
Route::get('/docentes/{id}', [\App\Http\Controllers\Api\DocenteController::class, 'show']);
Route::post('/docentes', [\App\Http\Controllers\Api\DocenteController::class, 'store']);
Route::put('/docentes/{id}', [\App\Http\Controllers\Api\DocenteController::class, 'update']);
Route::patch('/docentes/{id}/toggle-status', [\App\Http\Controllers\Api\DocenteController::class, 'toggleStatus']);
Route::delete('/docentes/{id}', [\App\Http\Controllers\Api\DocenteController::class, 'destroy']);

// Rutas para Paralelos
Route::get('/paralelos', [\App\Http\Controllers\Api\ParaleloController::class, 'index']);
Route::get('/paralelos/{id}', [\App\Http\Controllers\Api\ParaleloController::class, 'show']);
Route::post('/paralelos', [\App\Http\Controllers\Api\ParaleloController::class, 'store']);
Route::put('/paralelos/{id}', [\App\Http\Controllers\Api\ParaleloController::class, 'update']);
Route::delete('/paralelos/{id}', [\App\Http\Controllers\Api\ParaleloController::class, 'destroy']);

// Rutas auxiliares
Route::get('/aulas', [\App\Http\Controllers\Api\ParaleloController::class, 'getAulas']);
Route::post('/aulas', [\App\Http\Controllers\Api\ParaleloController::class, 'storeAula']);
Route::put('/aulas/{id}', [\App\Http\Controllers\Api\ParaleloController::class, 'updateAula']);
Route::delete('/aulas/{id}', [\App\Http\Controllers\Api\ParaleloController::class, 'destroyAula']);
Route::get('/horarios', [\App\Http\Controllers\Api\ParaleloController::class, 'getHorarios']);

// Rutas para PDF
Route::get('/inscripciones/{id}/certificate', [\App\Http\Controllers\Api\PdfController::class, 'generateCertificate']);

// Rutas para Notas y Asistencias (Docente)
Route::get('/inscripciones/{id}/notas', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'getNotas']);
Route::post('/inscripciones/{id}/notas', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'saveNota']);
Route::delete('/notas/{id}', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'deleteNota']);

Route::get('/inscripciones/{id}/asistencias', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'getAsistencias']);
Route::post('/inscripciones/{id}/asistencias', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'saveAsistencia']);

Route::get('/paralelos/{id}/asistencias', [\App\Http\Controllers\Api\NotaAsistenciaController::class, 'getAsistenciasParalelo']);

// Configuración General del Sistema
Route::get('/settings', [SettingController::class, 'index']);
Route::post('/settings', [SettingController::class, 'update']);

// Gestión de Accesos (Credenciales)
Route::get('/accesos', [AccesoController::class, 'index']);
Route::post('/accesos/asignar', [AccesoController::class, 'asignar']);
Route::put('/accesos/actualizar/{userId}', [AccesoController::class, 'actualizar']);
Route::delete('/accesos/desvincular/{userId}', [AccesoController::class, 'desvincular']);

// Roles y Matriz de Permisos de Módulos
use App\Http\Controllers\Api\RoleController;
Route::get('/roles/permisos', [RoleController::class, 'getPermisos']);
Route::post('/roles/permisos', [RoleController::class, 'savePermisos']);
Route::apiResource('roles', RoleController::class);

// Reportes y Estadísticas (EPIC 4: RF 18, RF 19, RF 20, RF 21)
use App\Http\Controllers\Api\ReportController;
Route::get('/reports/statistics-by-language', [ReportController::class, 'getLanguageStatistics']);
Route::get('/reports/classroom-occupancy', [ReportController::class, 'getClassroomOccupancy']);
Route::get('/reports/dashboard-summary', [ReportController::class, 'getDashboardSummary']);
Route::get('/reports/export/excel', [ReportController::class, 'exportExcel']);
Route::get('/reports/export/nominal-excel', [ReportController::class, 'exportNominalExcel']);
Route::get('/reports/export/notas-excel', [ReportController::class, 'exportNotasExcel']);
Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf']);
Route::get('/reports/export/docente-pdf', [ReportController::class, 'exportDocentePdf']);

// Ruta de imágenes /storage/ en API para responder siempre HTTP 200 OK
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
