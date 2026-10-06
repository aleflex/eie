/**
 * Servidor WebSocket de Alta Concurrencia para Sincronización en Tiempo Real
 * Escuela de Idiomas del Ejército (EIE)
 * 
 * Permite que los clientes (Web y APK) reciban inscripciones, cambios de estado y
 * actualizaciones de estudiantes mediante WebSockets instantáneos sin polling continuo.
 */

const http = require('http');
const WebSocket = require('ws');

const PORT = parseInt(process.env.WS_PORT || process.env.PORT || '6001', 10);

// Crear servidor HTTP para admitir tanto WebSockets como la API de broadcast de Laravel
const server = http.createServer((req, res) => {
  // Configurar CORS para permitir comunicación desde Laravel y clientes
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // Endpoint de estado y salud
  if (req.method === 'GET' && (req.url === '/' || req.url === '/health' || req.url === '/status')) {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({
      status: 'ok',
      service: 'EIE-WebSocket-Server',
      clientsConnected: wss.clients.size,
      timestamp: new Date().toISOString()
    }));
    return;
  }

  // Endpoint POST /broadcast: Recibe eventos desde Laravel y los transmite a los clientes WebSocket
  if (req.method === 'POST' && req.url === '/broadcast') {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString();
    });

    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        const { channel, event, data } = payload;

        if (!channel || !event) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ error: 'channel y event son obligatorios' }));
          return;
        }

        const messageString = JSON.stringify({
          channel,
          event,
          data: data || {},
          timestamp: new Date().toISOString()
        });

        let recipients = 0;
        wss.clients.forEach(client => {
          if (client.readyState === WebSocket.OPEN) {
            // Si el cliente tiene canales suscritos o no tiene filtro específico
            if (!client.channels || client.channels.has('*') || client.channels.has(channel)) {
              client.send(messageString);
              recipients++;
            }
          }
        });

        console.log(`[WS Broadcast] Evento "${event}" en canal "${channel}" enviado a ${recipients} clientes.`);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, recipients }));
      } catch (err) {
        console.error('[WS Broadcast] Error al procesar payload:', err);
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Payload JSON inválido' }));
      }
    });
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ error: 'Ruta no encontrada' }));
});

// Inicializar WebSocket Server adjunto al servidor HTTP
const wss = new WebSocket.Server({ server });

wss.on('connection', (ws, req) => {
  ws.isAlive = true;
  ws.channels = new Set(['*']); // Por defecto escucha todos los eventos

  console.log(`[WS Conexión] Nuevo cliente conectado desde ${req.socket.remoteAddress}. Clientes activos: ${wss.clients.size}`);

  ws.on('pong', () => {
    ws.isAlive = true;
  });

  ws.on('message', message => {
    try {
      const data = JSON.parse(message.toString());
      
      // Suscripción a canales específicos (ej. { action: 'subscribe', channel: 'inscripciones' })
      if (data.action === 'subscribe' && data.channel) {
        ws.channels.add(data.channel);
        ws.send(JSON.stringify({ type: 'subscribed', channel: data.channel }));
      } else if (data.action === 'ping') {
        ws.send(JSON.stringify({ type: 'pong', timestamp: Date.now() }));
      }
    } catch (e) {
      // Ignorar mensajes con formato no JSON
    }
  });

  ws.on('close', () => {
    console.log(`[WS Desconexión] Cliente desconectado. Clientes restantes: ${wss.clients.size}`);
  });

  // Mensaje de bienvenida
  ws.send(JSON.stringify({
    type: 'welcome',
    message: 'Conectado exitosamente al servidor WebSocket EIE en tiempo real',
    timestamp: new Date().toISOString()
  }));
});

// Heartbeat cada 30 segundos para limpiar conexiones huérfanas
const heartbeatInterval = setInterval(() => {
  wss.clients.forEach(ws => {
    if (ws.isAlive === false) {
      return ws.terminate();
    }
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);

wss.on('close', () => {
  clearInterval(heartbeatInterval);
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`====================================================`);
  console.log(`🚀 Servidor WebSocket EIE iniciado en el puerto ${PORT}`);
  console.log(`   - WebSockets: ws://0.0.0.0:${PORT}/ws`);
  console.log(`   - HTTP Broadcast: http://0.0.0.0:${PORT}/broadcast`);
  console.log(`====================================================`);
});
