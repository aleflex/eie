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
    
    if (File::exists($fullPath) && !is_dir($fullPath)) {
        $file = File::get($fullPath);
        $type = File::mimeType($fullPath) ?: 'application/octet-stream';

        return Response::make($file, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    // Fallback inteligente para imágenes: Si la foto no existe en el disco efímero, devolver un Avatar SVG oficial (HTTP 200 OK) sin error 404
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) || str_contains($path, 'foto') || str_contains($path, 'estudiante')) {
        $svgAvatar = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="120" height="120">
            <rect width="120" height="120" rx="60" fill="#1e3a8a"/>
            <path d="M60 25 C45 25 35 37 35 52 C35 67 45 77 60 77 C75 77 85 67 85 52 C85 37 75 25 60 25 Z" fill="#ffffff"/>
            <path d="M22 102 C22 82 38 74 60 74 C82 74 98 82 98 102 Z" fill="#ffffff"/>
        </svg>';

        return Response::make($svgAvatar, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    abort(404, 'Archivo no encontrado');
})->where('path', '.*');
