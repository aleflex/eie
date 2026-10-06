<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSocketService
{
    /**
     * Emite un evento en tiempo real hacia el servidor de WebSockets.
     *
     * @param string $channel El canal del evento ('inscripciones', 'estudiantes', 'usuarios').
     * @param string $event El nombre del evento ('nueva_inscripcion', 'estado_actualizado', etc.).
     * @param mixed $data Los datos payload a transmitir a los clientes conectados.
     * @return bool
     */
    public static function broadcast(string $channel, string $event, $data = []): bool
    {
        try {
            $wsUrl = env('WS_SERVER_URL', 'http://127.0.0.1:6001');

            // Envío con timeout ultra-corto (500ms) para no retrasar la petición principal
            $response = Http::timeout(0.8)->post("{$wsUrl}/broadcast", [
                'channel' => $channel,
                'event' => $event,
                'data' => $data,
                'timestamp' => now()->toIso8601String()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            // Silencioso para que nunca interrumpa el flujo si el servidor WS está reiniciando
            Log::debug("WebSocket Broadcast Notice: " . $e->getMessage());
            return false;
        }
    }
}
