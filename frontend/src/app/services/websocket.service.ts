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
  private isDestroyed = false;
  private channels = new Set<string>(['inscripciones', 'estudiantes', 'usuarios']);

  constructor(private ngZone: NgZone) {
    if (typeof window !== 'undefined') {
      this.connect();
    }
  }

  /**
   * Establece la conexión única con el servidor WebSocket
   */
  public connect() {
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
        console.warn('⚠️ [WebSocket EIE] Advertencia de socket:', error);
      };

      this.socket.onclose = () => {
        this.ngZone.run(() => {
          this.isConnectedSubject.next(false);
          this.socket = null;
          if (!this.isDestroyed) {
            this.scheduleReconnect();
          }
        });
      };
    } catch (e) {
      console.warn('[WebSocket EIE] Error al inicializar conexión:', e);
      this.scheduleReconnect();
    }
  }

  /**
   * Programa reconexión automática inteligente con retroceso exponencial
   */
  private scheduleReconnect() {
    if (this.reconnectTimer || this.isDestroyed) return;

    this.reconnectAttempts++;
    if (this.reconnectAttempts > this.maxReconnectAttempts) {
      console.warn('[WebSocket EIE] Se alcanzó el límite de intentos de reconexión.');
      return;
    }

    const delay = Math.min(3000 * Math.pow(1.5, this.reconnectAttempts - 1), 30000);
    console.log(`[WebSocket EIE] Reintentando conexión en ${(delay / 1000).toFixed(1)} segundos (intento ${this.reconnectAttempts})...`);

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
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
    }
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
  }
}
