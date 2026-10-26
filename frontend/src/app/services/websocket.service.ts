import { Injectable, NgZone, OnDestroy } from '@angular/core';
import { Observable, Subject, BehaviorSubject, filter } from 'rxjs';
import { environment } from '../../environments/environment';

export interface WebSocketEvent {
  channel: string;
  event: string;
  data: any;
  timestamp?: string;
}

@Injectable({
  providedIn: 'root'
})
export class WebSocketService implements OnDestroy {
  private socket: WebSocket | null = null;
  private eventSubject = new Subject<WebSocketEvent>();
  private isConnectedSubject = new BehaviorSubject<boolean>(false);
  
  public isConnected$ = this.isConnectedSubject.asObservable();
  
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 20;
  private reconnectTimer: any = null;
  private heartbeatTimer: any = null;
  private isDestroyed = false;
  private channels = new Set<string>(['inscripciones', 'estudiantes', 'usuarios']);

  constructor(private ngZone: NgZone) {
    if (typeof window !== 'undefined') {
      // Cerrar limpiamente al entrar en Back-Forward Cache (bfcache) o cambiar de página
      window.addEventListener('pagehide', () => {
        this.stopHeartbeat();
        if (this.reconnectTimer) {
          clearTimeout(this.reconnectTimer);
          this.reconnectTimer = null;
        }
        if (this.socket) {
          try {
            this.socket.close(1000, 'Page hidden / entered bfcache');
          } catch (e) {}
          this.socket = null;
        }
      });

      // Manejar restauración desde Back-Forward Cache (bfcache)
      window.addEventListener('pageshow', (event) => {
        if (event.persisted || !this.socket || this.socket.readyState !== WebSocket.OPEN) {
          this.reconnectAttempts = 0;
          this.connect();
        }
      });

      // Reconectar cuando la pestaña vuelve a ser visible tras suspensión
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          if (!this.socket || this.socket.readyState === WebSocket.CLOSED) {
            this.reconnectAttempts = 0;
            this.connect();
          }
        }
      });

      // Reconectar cuando se recupera la conexión a internet
      window.addEventListener('online', () => {
        this.reconnectAttempts = 0;
        this.connect();
      });

      this.connect();
    }
  }

  /**
   * Establece la conexión única con el servidor WebSocket
   */
  public connect() {
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
      return;
    }

    if (this.socket && (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING)) {
      return;
    }

    try {
      const wsUrl = environment.wsUrl || 'ws://localhost:6001/ws';
      console.log('[WebSocket EIE] Conectando a:', wsUrl);

      this.socket = new WebSocket(wsUrl);

      this.socket.onopen = () => {
        this.ngZone.run(() => {
          console.log('✅ [WebSocket EIE] Conectado exitosamente en tiempo real.');
          this.isConnectedSubject.next(true);
          this.reconnectAttempts = 0;

          // Iniciar Heartbeat cada 25 segundos para mantener activo el proxy reverso de Render / Apache
          this.stopHeartbeat();
          this.heartbeatTimer = setInterval(() => {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
              this.send({ action: 'ping' });
            }
          }, 25000);

          // Suscribirse a los canales registrados
          this.channels.forEach(ch => {
            this.send({ action: 'subscribe', channel: ch });
          });
        });
      };

      this.socket.onmessage = (event: MessageEvent) => {
        this.ngZone.run(() => {
          try {
            const parsed = JSON.parse(event.data);

            // Mensajes de bienvenida o pong de control interno
            if (parsed.type === 'welcome' || parsed.type === 'pong' || parsed.type === 'subscribed') {
              return;
            }

            if (parsed.channel && parsed.event) {
              console.log(`⚡ [WebSocket Evento Recibido] [${parsed.channel}:${parsed.event}]`, parsed.data);
              this.eventSubject.next(parsed);
            }
          } catch (err) {
            console.warn('[WebSocket] Error al analizar mensaje:', err);
          }
        });
      };

      this.socket.onerror = (error) => {
        // Silenciar advertencias consecutivas para evitar saturación de consola
        if (this.reconnectAttempts <= 1) {
          console.warn('⚠️ [WebSocket EIE] Conexión no disponible en este momento:', error);
        }
      };

      this.socket.onclose = () => {
        this.ngZone.run(() => {
          this.stopHeartbeat();
          this.isConnectedSubject.next(false);
          this.socket = null;
          if (!this.isDestroyed) {
            this.scheduleReconnect();
          }
        });
      };
    } catch (e) {
      this.stopHeartbeat();
      if (this.reconnectAttempts <= 1) {
        console.warn('[WebSocket EIE] Error al inicializar conexión:', e);
      }
      this.scheduleReconnect();
    }
  }

  private stopHeartbeat() {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }
  }

  /**
   * Programa reconexión automática inteligente con retroceso exponencial
   */
  private scheduleReconnect() {
    if (this.reconnectTimer || this.isDestroyed) return;

    this.reconnectAttempts++;
    if (this.reconnectAttempts > this.maxReconnectAttempts) {
      console.info('ℹ️ [WebSocket EIE] El servidor WebSocket no está disponible actualmente. El sistema continuará operando normalmente en modo REST estándar.');
      return;
    }

    const delay = Math.min(3000 * Math.pow(1.5, this.reconnectAttempts - 1), 30000);
    if (this.reconnectAttempts <= 3 || this.reconnectAttempts % 5 === 0) {
      console.log(`[WebSocket EIE] Reintentando conexión en ${(delay / 1000).toFixed(1)} segundos (intento ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
    }

    this.reconnectTimer = setTimeout(() => {
      this.reconnectTimer = null;
      this.connect();
    }, delay);
  }

  /**
   * Envía un mensaje JSON a través del WebSocket
   */
  public send(payload: any) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(payload));
    }
  }

  /**
   * Suscripción reactiva a un canal y evento específico
   */
  public onEvent(channel: string, eventName?: string): Observable<WebSocketEvent> {
    this.channels.add(channel);
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.send({ action: 'subscribe', channel });
    }

    return this.eventSubject.asObservable().pipe(
      filter(e => e.channel === channel && (!eventName || e.event === eventName))
    );
  }

  /**
   * Observable: Emite cuando llega una nueva inscripción en tiempo real
   */
  public onInscriptionCreated(): Observable<any> {
    return new Observable(observer => {
      const sub = this.onEvent('inscripciones', 'nueva_inscripcion').subscribe(e => observer.next(e.data));
      return () => sub.unsubscribe();
    });
  }

  /**
   * Observable: Emite cuando se actualiza el estado de una inscripción
   */
  public onInscriptionUpdated(): Observable<any> {
    return new Observable(observer => {
      const sub = this.onEvent('inscripciones', 'estado_actualizado').subscribe(e => observer.next(e.data));
      return () => sub.unsubscribe();
    });
  }

  /**
   * Observable: Emite cuando se elimina una inscripción
   */
  public onInscriptionDeleted(): Observable<any> {
    return new Observable(observer => {
      const sub = this.onEvent('inscripciones', 'inscripcion_eliminada').subscribe(e => observer.next(e.data));
      return () => sub.unsubscribe();
    });
  }

  /**
   * Observable: Emite cuando se actualizan los datos de un estudiante
   */
  public onStudentUpdated(): Observable<any> {
    return new Observable(observer => {
      const sub = this.onEvent('estudiantes', 'estudiante_actualizado').subscribe(e => observer.next(e.data));
      return () => sub.unsubscribe();
    });
  }

  /**
   * Observable: Emite cuando se actualiza el perfil del usuario autenticado
   */
  public onProfileUpdated(): Observable<any> {
    return new Observable(observer => {
      const sub = this.onEvent('usuarios', 'perfil_actualizado').subscribe(e => observer.next(e.data));
      return () => sub.unsubscribe();
    });
  }

  ngOnDestroy() {
    this.isDestroyed = true;
    this.stopHeartbeat();
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
    }
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
  }
}
