<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});

// Ruta Fallback para Servir Archivos /storage/ (Fotos, PDFs y Documentos)
Route::get('/storage/{path}', function ($path) {
    // Buscar en storage/app/public/
    $fullPath = storage_path('app/public/' . $path);
    if (!File::exists($fullPath)) {
        $fullPath = storage_path('app/' . $path);
    }
    if (!File::exists($fullPath)) {
        $fullPath = public_path('storage/' . $path);
    }
    
    if (!File::exists($fullPath)) {
        abort(404, 'Archivo no encontrado');
    }

    $file = File::get($fullPath);
    $type = File::mimeType($fullPath) ?: 'application/octet-stream';

    return Response::make($file, 200, [
        'Content-Type' => $type,
        'Cache-Control' => 'public, max-age=86400',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->where('path', '.*');
