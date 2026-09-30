import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe, SlicePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DocenteService } from '../../services/docente.service';
import { AuthService } from '../../services/auth.service';
import { HttpClient } from '@angular/common/http';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { ReportService } from '../../services/report.service';
import { downloadFile } from '../../utils/file-downloader';

import { ImageCompressorService } from '../../services/image-compressor.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-docente-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './docente-dashboard.component.html',
  styleUrls: ['./docente-dashboard.component.css']
})
export class DocenteDashboardComponent implements OnInit {
  private apiUrl = environment.apiUrl + '/api';

  user: any = null;
  docente: any = null;
  paralelos: any[] = [];
  paraleloActivo: any = null;
  isMobileMenuOpen = false;

  // Variables para Modificación con Permiso / Autorización del Administrador
  modoEdicionConPermisoAsistencia = false;
  justificativoAsistenciaPermiso = '';
  adminPasswordAsistencia = '';
  adminNombreAsistencia = '';

  modoEdicionConPermisoNotas = false;
  justificativoNotasPermiso = '';
  adminPasswordNotas = '';
  adminNombreNotas = '';
  isLoading = true;
  isRefreshing = false;

  photoFile: File | null = null;
  photoFileName: string = '';
  uploadingPhoto: boolean = false;

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }
  errorMsg = '';
  today = new Date();

  // Tabs
  tabActivo: 'perfil' | 'lista' | 'notas' | 'asistencia' = 'perfil';

  // Notas
  periodoSeleccionado = 'Parcial 1';
  notasForm: { [key: string]: number | null } = {};
  observacionesForm: { [key: string]: string } = {};
  notasBloqueadas: { [key: string]: boolean } = {};
  savingNotas = false;
  notasMsg = '';
  notasError = false;

  // Asistencia
  fechaAsistencia: string = new Date().toISOString().split('T')[0];
  asistenciaForm: { [inscripcionId: number]: string } = {};
  observacionAsistenciaForm: { [inscripcionId: number]: string } = {};
  asistenciaBloqueada: boolean = false;
  asistenciaYaGuardada: boolean = false;
  esFechaPasada: boolean = false;
  savingAsistencia = false;
  asistenciaMsg = '';
  asistenciaError = false;

  isDownloadingExcel = false;

  constructor(
    private docenteService: DocenteService,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient,
    private reportService: ReportService,
    private imageCompressor: ImageCompressorService
  ) {}

  onPhotoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      alert('Por favor, selecciona una imagen de perfil válida.');
      return;
    }
    this.imageCompressor.compressImage(file, 800, 800, 0.82).then(compressed => {
      this.photoFile = compressed;
      this.photoFileName = compressed.name;
    });
  }

  uploadPhoto() {
    if (!this.photoFile) return;

    this.uploadingPhoto = true;
    const formData = new FormData();
    formData.append('foto', this.photoFile);

    this.authService.actualizarPerfil(formData).subscribe({
      next: (res: any) => {
        alert('Fotografía de perfil actualizada con éxito');
        this.photoFile = null;
        this.photoFileName = '';
        this.uploadingPhoto = false;
        if (res.user) {
          if (this.docente) this.docente.foto_url = res.user.foto_url;
          if (this.user) this.user.foto_url = res.user.foto_url;
        }
        this.authService.cargarPerfilActualizado().subscribe();
      },
      error: (err) => {
        console.error('Error subiendo foto de docente', err);
        alert('Error al actualizar la fotografía: ' + (err.error?.message || err.message));
        this.uploadingPhoto = false;
      }
    });
  }

  refreshData() {
    this.isRefreshing = true;
    this.cargarMisParalelos();
    this.authService.cargarPerfilActualizado().subscribe({
      next: () => this.isRefreshing = false,
      error: () => this.isRefreshing = false
    });
  }

  ngOnInit(): void {
    this.user = this.authService.getUser();
    if (!this.user) { this.router.navigate(['/login']); return; }
    if (this.user.rol === 'admin') { this.router.navigate(['/admin']); return; }
    if (this.user.rol === 'estudiante') { this.router.navigate(['/student-dashboard']); return; }
    
    // Suscripción reactiva para mantener perfil y foto de docente sincronizados
    this.authService.usuario$.subscribe(u => {
      if (u) {
        this.user = u;
        if (this.docente && u.foto_url) {
          this.docente.foto_url = u.foto_url;
        }
      }
    });

    this.biometricEnabled = this.authService.isBiometricEnabled();
    this.cargarMisParalelos();
  }

  biometricEnabled: boolean = false;

  async toggleBiometric(event: any) {
    const isChecked = event.target.checked;
    if (isChecked) {
      const res = await this.authService.enableBiometricForCurrentDevice();
      if (res.success) {
        this.biometricEnabled = true;
        alert(res.message);
      } else {
        this.biometricEnabled = false;
        event.target.checked = false;
        alert(res.message);
      }
    } else {
      this.authService.disableBiometricForCurrentDevice();
      this.biometricEnabled = false;
      alert('Acceso biométrico (Huella/Rostro) desactivado en este celular.');
    }
  }

  cargarMisParalelos() {
    this.isLoading = true;
    this.docenteService.getMisParalelos(this.user.id || this.user.id_usuario).subscribe({
      next: (res) => {
        this.docente = res.docente;
        const rawParalelos = res.paralelos || [];
        const seen = new Set();
        this.paralelos = rawParalelos.filter((p: any) => {
          const key = p.id || p.id_paralelo;
          if (seen.has(key)) return false;
          seen.add(key);
          return true;
        });
        this.isLoading = false;
        if (this.paralelos.length > 0) this.seleccionarParalelo(this.paralelos[0]);
      },
      error: () => {
        this.errorMsg = 'No se pudieron cargar los datos. Por favor intente nuevamente.';
        this.isLoading = false;
      }
    });
  }

  seleccionarParalelo(paralelo: any) {
    this.paraleloActivo = paralelo;
    this.tabActivo = 'lista';
    this.resetForms();
    const periodos = this.getPeriodos();
    if (periodos.length > 0) {
      this.periodoSeleccionado = periodos[0];
    }
    this.consultarAutorizacionParalelo();
  }

  consultarAutorizacionParalelo() {
    const id = this.paraleloActivo?.id || this.paraleloActivo?.id_paralelo;
    if (!id) return;
    this.http.get<any>(`${this.apiUrl}/paralelos/${id}/autorizacion`).subscribe({
      next: (res) => {
        if (res?.notas?.autorizado) {
          this.modoEdicionConPermisoNotas = true;
          this.adminNombreNotas = res.notas.admin_nombre || 'Administrador';
          this.justificativoNotasPermiso = res.notas.justificativo || 'Autorizado formalmente por Dirección';
        }
        if (res?.asistencias?.autorizado) {
          this.modoEdicionConPermisoAsistencia = true;
          this.adminNombreAsistencia = res.asistencias.admin_nombre || 'Administrador';
          this.justificativoAsistenciaPermiso = res.asistencias.justificativo || 'Autorizado formalmente por Dirección';
        }
      },
      error: () => {}
    });
  }

  setTab(tab: 'perfil' | 'lista' | 'notas' | 'asistencia') {
    this.tabActivo = tab;
    this.notasMsg = '';
    this.asistenciaMsg = '';

    if (tab === 'notas') {
      this.consultarAutorizacionParalelo();
      this.cargarNotasDelPeriodo();
    }
    if (tab === 'asistencia') {
      this.consultarAutorizacionParalelo();
      this.cargarAsistenciaDelDia();
    }
  }

  habilitarDocumentosEstudiante(estudianteId: number) {
    const horas = prompt('¿Por cuántas horas desea habilitar la subida de documentos al estudiante? (ej. 24, 48)', '24');
    if (!horas) return;
    const numHoras = parseInt(horas, 10);
    if (isNaN(numHoras) || numHoras <= 0) {
      alert('Por favor ingrese un número válido de horas.');
      return;
    }

    const fechaLimite = new Date();
    fechaLimite.setHours(fechaLimite.getHours() + numHoras);
    const fechaStr = fechaLimite.toISOString().slice(0, 19).replace('T', ' ');

    this.http.put(`${this.apiUrl}/students/${estudianteId}`, {
      documentos_habilitados_hasta: fechaStr
    }).subscribe({
      next: () => {
        alert(`Subida de documentos habilitada exitosamente por ${numHoras} horas (hasta ${fechaLimite.toLocaleString('es-BO')}).`);
        this.cargarMisParalelos();
      },
      error: (err) => {
        alert('Error al habilitar documentos: ' + (err.error?.message || err.message));
      }
    });
  }

  getPhotoUrl(url: string | null): string {
    if (!url) return '';
    const cleanUrl = url.trim().replace(/[\r\n\s]+/g, '');
    if (cleanUrl.startsWith('data:')) {
      if (!cleanUrl.includes(';base64,') || cleanUrl.length < 50) return '';
      return cleanUrl;
    }
    if (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://')) return cleanUrl;
    const apiBase = environment.apiUrl.replace(/\/+$/, '');
    if (cleanUrl.startsWith('/storage/')) return apiBase + cleanUrl;
    if (cleanUrl.startsWith('storage/')) return apiBase + '/' + cleanUrl;
    return apiBase + '/storage/' + cleanUrl.replace(/^\/+/, '');
  }

  onImgError(event: any) {
    if (event && event.target) {
      const src = event.target.src || '';
      if (!src.includes('default-avatar.svg') && !src.includes('default-avatar.png')) {
        event.target.src = '/assets/default-avatar.svg';
      }
    }
  }

  resetForms() {
    this.notasForm = {};
    this.observacionesForm = {};
    this.asistenciaForm = {};
    this.observacionAsistenciaForm = {};
    this.notasMsg = '';
    this.asistenciaMsg = '';
    this.modoEdicionConPermisoAsistencia = false;
    this.justificativoAsistenciaPermiso = '';
    this.adminPasswordAsistencia = '';
    this.adminNombreAsistencia = '';
    this.modoEdicionConPermisoNotas = false;
    this.justificativoNotasPermiso = '';
    this.adminPasswordNotas = '';
    this.adminNombreNotas = '';
  }

  get estudiantesActivos(): any[] {
    if (!this.paraleloActivo?.inscripciones) return [];
    return this.paraleloActivo.inscripciones.filter((i: any) => i.estudiante);
  }

  get totalEstudiantes(): number {
    return this.paralelos.reduce((sum, p) => sum + (p.inscripciones?.length || 0), 0);
  }

  // Verificar si el contrato vence pronto (en 7 días o menos)
  get contratoPorVencer(): boolean {
    if (this.docente?.tipo_contrato === 'Contrato' && this.docente?.fecha_contrato) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);
      const fechaContrato = new Date(this.docente.fecha_contrato);
      fechaContrato.setHours(0, 0, 0, 0);

      // Si ya expiró, el login no debería dejarlo entrar, pero por si acaso.
      if (fechaContrato < hoy) return false;

      const diffTime = Math.abs(fechaContrato.getTime() - hoy.getTime());
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
      return diffDays <= 7;
    }
    return false;
  }

  // ==================== NOTAS ====================

  isNotaBloqueada(inscripcionId: number): boolean {
    if (this.modoEdicionConPermisoNotas) return false;
    const key = `${inscripcionId}_${this.periodoSeleccionado}`;
    return !!this.notasBloqueadas[key];
  }

  get hayNotasBloqueadas(): boolean {
    return this.estudiantesActivos.some(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return !!this.notasBloqueadas[key];
    });
  }

  get todasNotasBloqueadas(): boolean {
    if (this.modoEdicionConPermisoNotas) return false;
    if (this.estudiantesActivos.length === 0) return false;
    return this.estudiantesActivos.every(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return !!this.notasBloqueadas[key];
    });
  }

  get hayNotasNuevasPorGuardar(): boolean {
    return this.estudiantesActivos.some(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return !this.notasBloqueadas[key] && this.notasForm[key] !== null && this.notasForm[key] !== undefined && this.notasForm[key] !== ('' as any);
    });
  }

  cargarNotasDelPeriodo() {
    this.notasBloqueadas = {};
    const requests = this.estudiantesActivos.map(insc =>
      this.http.get<any[]>(`${this.apiUrl}/inscripciones/${insc.id}/notas`).pipe(catchError(() => of([])))
    );

    forkJoin(requests).subscribe(resultados => {
      resultados.forEach((notas: any[], idx: number) => {
        const insc = this.estudiantesActivos[idx];
        const key = `${insc.id}_${this.periodoSeleccionado}`;
        const notaDePeriodo = notas.find((n: any) => n.periodo === this.periodoSeleccionado);
        if (notaDePeriodo && notaDePeriodo.nota !== null && notaDePeriodo.nota !== undefined) {
          this.notasForm[key] = Number(notaDePeriodo.nota);
          this.observacionesForm[key] = notaDePeriodo.observacion || '';
          this.notasBloqueadas[key] = true; // Bloqueada para el docente
        } else {
          this.notasForm[key] = null;
          this.observacionesForm[key] = '';
          this.notasBloqueadas[key] = false;
        }
      });
    });
  }

  onPeriodoChange() {
    this.modoEdicionConPermisoNotas = false;
    this.justificativoNotasPermiso = '';
    this.adminPasswordNotas = '';
    this.adminNombreNotas = '';
    this.cargarNotasDelPeriodo();
  }

  activarModoPermisoNotas() {
    Swal.fire({
      title: 'Autorización de Administrador Requerida',
      html: `
        <div style="text-align: left; font-size: 14px;">
          <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px 12px; margin-bottom: 16px; border-radius: 6px; color: #92400e; font-size: 13px; line-height: 1.4;">
            <i class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">security</i>
            <strong>Control de Dirección:</strong> Solo el Administrador puede autorizar la modificación de calificaciones ya asentadas.
          </div>
          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Usuario / Correo de Administrador (Opcional):</label>
            <input id="swal-admin-user-notas" class="swal2-input" placeholder="admin (o dejar en blanco)" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; height: 38px;">
          </div>
          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Contraseña del Administrador: <span style="color: #dc2626;">*</span></label>
            <input id="swal-admin-pass-notas" type="password" class="swal2-input" placeholder="Ingrese contraseña de Administrador" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; height: 38px;">
          </div>
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Motivo / Justificativo de Rectificación: <span style="color: #dc2626;">*</span></label>
            <textarea id="swal-admin-just-notas" class="swal2-textarea" placeholder="Ej: Rectificación por revisión de examen formal / Memorando de Dirección Académica..." style="margin: 0; width: 100%; box-sizing: border-box; font-size: 13px; height: 75px;"></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Verificar y Habilitar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#003B71',
      focusConfirm: false,
      showLoaderOnConfirm: true,
      preConfirm: () => {
        const adminUser = (document.getElementById('swal-admin-user-notas') as HTMLInputElement)?.value?.trim() || '';
        const adminPass = (document.getElementById('swal-admin-pass-notas') as HTMLInputElement)?.value || '';
        const justificativo = (document.getElementById('swal-admin-just-notas') as HTMLTextAreaElement)?.value?.trim() || '';

        if (!adminPass) {
          Swal.showValidationMessage('Debe ingresar la contraseña del Administrador.');
          return false;
        }
        if (!justificativo || justificativo.length < 5) {
          Swal.showValidationMessage('Debe ingresar un justificativo válido de al menos 5 caracteres.');
          return false;
        }

        return this.http.post<any>(`${this.apiUrl}/autorizar-modificacion`, {
          admin_user: adminUser,
          admin_password: adminPass,
          justificativo: justificativo,
          tipo: 'notas'
        }).toPromise().then(res => {
          return {
            admin_user: adminUser,
            admin_password: adminPass,
            justificativo: justificativo,
            admin_name: res?.admin_name || 'Administrador'
          };
        }).catch(err => {
          const msg = err?.error?.message || 'Contraseña de Administrador incorrecta o no autorizada.';
          Swal.showValidationMessage(msg);
          return false;
        });
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        this.justificativoNotasPermiso = result.value.justificativo;
        this.adminPasswordNotas = result.value.admin_password;
        this.adminNombreNotas = result.value.admin_name;
        this.modoEdicionConPermisoNotas = true;

        Swal.fire({
          icon: 'success',
          title: 'Autorización Concedida',
          html: `El Administrador <strong>${this.adminNombreNotas}</strong> ha otorgado autorización formal.<br><br>Ahora puede editar las calificaciones y presionar <strong>"Guardar Notas Rectificadas"</strong> al finalizar.`,
          confirmButtonColor: '#003B71'
        });
      }
    });
  }

  cancelarModoPermisoNotas() {
    this.modoEdicionConPermisoNotas = false;
    this.justificativoNotasPermiso = '';
    this.adminPasswordNotas = '';
    this.adminNombreNotas = '';
    this.cargarNotasDelPeriodo();
  }

  guardarNotasRectificadas() {
    if (!this.justificativoNotasPermiso) {
      this.activarModoPermisoNotas();
      return;
    }

    this.savingNotas = true;
    this.notasMsg = '';
    this.notasError = false;

    const modificados = this.estudiantesActivos.filter(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return this.notasForm[key] !== null && this.notasForm[key] !== undefined && this.notasForm[key] !== ('' as any);
    });

    if (modificados.length === 0) {
      this.notasMsg = 'No hay calificaciones ingresadas.';
      this.notasError = true;
      this.savingNotas = false;
      return;
    }

    const requests = modificados.map(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return this.http.post(`${this.apiUrl}/inscripciones/${insc.id}/notas`, {
        nota: this.notasForm[key],
        periodo: this.periodoSeleccionado,
        observacion: this.observacionesForm[key] || null,
        justificativo: this.justificativoNotasPermiso,
        admin_password: this.adminPasswordNotas
      }).pipe(catchError(err => {
        console.error('Error guardando nota rectificada', err);
        return of({ error: true, message: err?.error?.message || 'Error al guardar calificación' });
      }));
    });

    forkJoin(requests).subscribe({
      next: (responses: any[]) => {
        const hasErrors = responses.some(r => r && r.error);
        if (hasErrors) {
          const errItem = responses.find(r => r && r.error);
          this.notasMsg = errItem?.message || 'Error al guardar algunas calificaciones.';
          this.notasError = true;
        } else {
          this.notasMsg = `✓ Calificaciones rectificadas exitosamente con autorización de ${this.adminNombreNotas}.`;
          this.notasError = false;
          this.modoEdicionConPermisoNotas = false;
          this.justificativoNotasPermiso = '';
          this.adminPasswordNotas = '';
          this.adminNombreNotas = '';
          Swal.fire({
            icon: 'success',
            title: '¡Calificaciones Rectificadas!',
            text: 'Las calificaciones han sido actualizadas y auditadas con éxito con la autorización del Administrador.',
            confirmButtonColor: '#003B71'
          });
          this.cargarNotasDelPeriodo();
        }
        this.savingNotas = false;
      },
      error: (err) => {
        this.notasMsg = err?.error?.message || 'Error al guardar notas.';
        this.notasError = true;
        this.savingNotas = false;
      }
    });
  }

  getNotaValor(inscripcionId: number): number {
    const key = `${inscripcionId}_${this.periodoSeleccionado}`;
    return Number(this.notasForm[key]) || 0;
  }

  guardarTodasLasNotas() {
    this.savingNotas = true;
    this.notasMsg = '';
    this.notasError = false;

    // Solo guardar las notas que NO están bloqueadas
    const nuevos = this.estudiantesActivos.filter(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return !this.notasBloqueadas[key] && this.notasForm[key] !== null && this.notasForm[key] !== undefined && this.notasForm[key] !== ('' as any);
    });

    if (nuevos.length === 0) {
      if (this.todasNotasBloqueadas) {
        this.notasMsg = 'Todas las notas de este periodo ya están asentadas y bloqueadas. Contacte a Administración si requiere rectificaciones.';
      } else {
        this.notasMsg = 'No hay notas nuevas para guardar. Ingrese al menos una calificación en los campos habilitados.';
      }
      this.notasError = true;
      this.savingNotas = false;
      return;
    }

    const requests = nuevos.map(insc => {
      const key = `${insc.id}_${this.periodoSeleccionado}`;
      return this.http.post(`${this.apiUrl}/inscripciones/${insc.id}/notas`, {
        nota: this.notasForm[key],
        periodo: this.periodoSeleccionado,
        observacion: this.observacionesForm[key] || null
      }).pipe(catchError(err => {
        console.error('Error guardando nota', err);
        return of({ error: true, message: err?.error?.message || 'Error al guardar calificación' });
      }));
    });

    forkJoin(requests).subscribe({
      next: (responses: any[]) => {
        const hasErrors = responses.some(r => r && r.error);
        if (hasErrors) {
          const errItem = responses.find(r => r && r.error);
          this.notasMsg = errItem?.message || 'Error al guardar algunas calificaciones.';
          this.notasError = true;
        } else {
          this.notasMsg = `✓ Calificaciones del ${this.periodoSeleccionado} guardadas y bloqueadas correctamente.`;
          this.notasError = false;
          nuevos.forEach(insc => {
            this.notasBloqueadas[`${insc.id}_${this.periodoSeleccionado}`] = true;
          });
          setTimeout(() => this.notasMsg = '', 6000);
        }
        this.savingNotas = false;
        this.cargarNotasDelPeriodo();
      },
      error: (err) => {
        this.notasMsg = err?.error?.message || 'Error al guardar notas.';
        this.notasError = true;
        this.savingNotas = false;
      }
    });
  }

  // ==================== ASISTENCIA ====================

  cargarAsistenciaDelDia() {
    const requests = this.estudiantesActivos.map(insc =>
      this.http.get<any[]>(`${this.apiUrl}/inscripciones/${insc.id}/asistencias`).pipe(catchError(() => of([])))
    );

    const hoyStr = new Date().toISOString().split('T')[0];
    this.esFechaPasada = this.fechaAsistencia < hoyStr;

    forkJoin(requests).subscribe(resultados => {
      let yaRegistrado = false;
      resultados.forEach((asistencias: any[], idx: number) => {
        const insc = this.estudiantesActivos[idx];
        const asistDia = asistencias.find((a: any) => a.fecha?.startsWith(this.fechaAsistencia));
        if (asistDia) {
          this.asistenciaForm[insc.id] = asistDia.estado;
          this.observacionAsistenciaForm[insc.id] = asistDia.observacion || '';
          yaRegistrado = true;
        } else {
          this.asistenciaForm[insc.id] = 'presente'; // default
          this.observacionAsistenciaForm[insc.id] = '';
        }
      });
      this.asistenciaYaGuardada = yaRegistrado;
      // Bloqueada si ya fue guardada O si es una fecha pasada
      this.asistenciaBloqueada = yaRegistrado || this.esFechaPasada;
    });
  }

  onFechaAsistenciaChange() {
    this.modoEdicionConPermisoAsistencia = false;
    this.justificativoAsistenciaPermiso = '';
    this.adminPasswordAsistencia = '';
    this.adminNombreAsistencia = '';
    this.cargarAsistenciaDelDia();
  }

  setAsistencia(inscripcionId: number, estado: string) {
    if (this.asistenciaBloqueada && !this.modoEdicionConPermisoAsistencia) return;
    this.asistenciaForm[inscripcionId] = estado;
  }

  activarModoPermisoAsistencia() {
    Swal.fire({
      title: 'Autorización de Administrador Requerida',
      html: `
        <div style="text-align: left; font-size: 14px;">
          <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px 12px; margin-bottom: 16px; border-radius: 6px; color: #92400e; font-size: 13px; line-height: 1.4;">
            <i class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">security</i>
            <strong>Control de Dirección:</strong> Solo el Administrador puede autorizar la modificación o registro de asistencia en fechas consolidadas o pasadas.
          </div>
          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Usuario / Correo de Administrador (Opcional):</label>
            <input id="swal-admin-user-asist" class="swal2-input" placeholder="admin (o dejar en blanco)" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; height: 38px;">
          </div>
          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Contraseña del Administrador: <span style="color: #dc2626;">*</span></label>
            <input id="swal-admin-pass-asist" type="password" class="swal2-input" placeholder="Ingrese contraseña de Administrador" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; height: 38px;">
          </div>
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155; font-size: 13px;">Motivo / Justificativo de Asistencia: <span style="color: #dc2626;">*</span></label>
            <textarea id="swal-admin-just-asist" class="swal2-textarea" placeholder="Ej: Licencia médica presentada por el estudiante / Autorización de Dirección Académica..." style="margin: 0; width: 100%; box-sizing: border-box; font-size: 13px; height: 75px;"></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Verificar y Habilitar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#003B71',
      focusConfirm: false,
      showLoaderOnConfirm: true,
      preConfirm: () => {
        const adminUser = (document.getElementById('swal-admin-user-asist') as HTMLInputElement)?.value?.trim() || '';
        const adminPass = (document.getElementById('swal-admin-pass-asist') as HTMLInputElement)?.value || '';
        const justificativo = (document.getElementById('swal-admin-just-asist') as HTMLTextAreaElement)?.value?.trim() || '';

        if (!adminPass) {
          Swal.showValidationMessage('Debe ingresar la contraseña del Administrador.');
          return false;
        }
        if (!justificativo || justificativo.length < 5) {
          Swal.showValidationMessage('Debe ingresar un justificativo válido de al menos 5 caracteres.');
          return false;
        }

        return this.http.post<any>(`${this.apiUrl}/autorizar-modificacion`, {
          admin_user: adminUser,
          admin_password: adminPass,
          justificativo: justificativo,
          tipo: 'asistencia'
        }).toPromise().then(res => {
          return {
            admin_user: adminUser,
            admin_password: adminPass,
            justificativo: justificativo,
            admin_name: res?.admin_name || 'Administrador'
          };
        }).catch(err => {
          const msg = err?.error?.message || 'Contraseña de Administrador incorrecta o no autorizada.';
          Swal.showValidationMessage(msg);
          return false;
        });
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        this.justificativoAsistenciaPermiso = result.value.justificativo;
        this.adminPasswordAsistencia = result.value.admin_password;
        this.adminNombreAsistencia = result.value.admin_name;
        this.modoEdicionConPermisoAsistencia = true;

        Swal.fire({
          icon: 'success',
          title: 'Autorización Concedida',
          html: `El Administrador <strong>${this.adminNombreAsistencia}</strong> ha otorgado autorización formal.<br><br>Ahora puede rectificar la asistencia y presionar <strong>"Guardar Asistencia Rectificada"</strong>.`,
          confirmButtonColor: '#003B71'
        });
      }
    });
  }

  cancelarModoPermisoAsistencia() {
    this.modoEdicionConPermisoAsistencia = false;
    this.justificativoAsistenciaPermiso = '';
    this.adminPasswordAsistencia = '';
    this.adminNombreAsistencia = '';
    this.cargarAsistenciaDelDia();
  }

  guardarAsistenciaRectificada() {
    if (!this.justificativoAsistenciaPermiso) {
      this.activarModoPermisoAsistencia();
      return;
    }

    this.savingAsistencia = true;
    this.asistenciaMsg = '';
    this.asistenciaError = false;

    const requests = this.estudiantesActivos.map(insc =>
      this.http.post(`${this.apiUrl}/inscripciones/${insc.id}/asistencias`, {
        fecha: this.fechaAsistencia,
        estado: this.asistenciaForm[insc.id] || 'presente',
        observacion: this.observacionAsistenciaForm[insc.id] || null,
        justificativo: this.justificativoAsistenciaPermiso,
        admin_password: this.adminPasswordAsistencia
      }).pipe(catchError(err => {
        console.error('Error asistencia rectificada', err);
        return of({ error: true, message: err?.error?.message || 'Error al rectificar' });
      }))
    );

    forkJoin(requests).subscribe({
      next: (responses: any[]) => {
        const hasErr = responses.some(r => r && r.error);
        if (hasErr) {
          const errObj = responses.find(r => r && r.error);
          this.asistenciaMsg = errObj?.message || 'Error al rectificar la asistencia.';
          this.asistenciaError = true;
        } else {
          this.asistenciaMsg = `✓ Asistencia del ${this.fechaAsistencia} rectificada con autorización de ${this.adminNombreAsistencia}.`;
          this.asistenciaError = false;
          this.modoEdicionConPermisoAsistencia = false;
          this.justificativoAsistenciaPermiso = '';
          this.adminPasswordAsistencia = '';
          this.adminNombreAsistencia = '';
          Swal.fire({
            icon: 'success',
            title: '¡Asistencia Rectificada!',
            text: 'Los cambios y la auditoría con autorización de Administrador han sido guardados correctamente.',
            confirmButtonColor: '#003B71'
          });
          this.cargarAsistenciaDelDia();
        }
        this.savingAsistencia = false;
      },
      error: (err) => {
        this.asistenciaMsg = err?.error?.message || 'Error al rectificar asistencia.';
        this.asistenciaError = true;
        this.savingAsistencia = false;
      }
    });
  }

  guardarAsistencia() {
    if (this.asistenciaBloqueada && !this.modoEdicionConPermisoAsistencia) {
      this.activarModoPermisoAsistencia();
      return;
    }

    if (!this.fechaAsistencia) {
      this.asistenciaMsg = 'Seleccione una fecha primero.';
      this.asistenciaError = true;
      return;
    }

    this.savingAsistencia = true;
    this.asistenciaMsg = '';
    this.asistenciaError = false;

    const requests = this.estudiantesActivos.map(insc =>
      this.http.post(`${this.apiUrl}/inscripciones/${insc.id}/asistencias`, {
        fecha: this.fechaAsistencia,
        estado: this.asistenciaForm[insc.id] || 'presente',
        observacion: this.observacionAsistenciaForm[insc.id] || null
      }).pipe(catchError(err => {
        console.error('Error asistencia', err);
        return of({ error: true, message: err?.error?.message || 'Error al guardar' });
      }))
    );

    forkJoin(requests).subscribe({
      next: (responses: any[]) => {
        const hasErr = responses.some(r => r && r.error);
        if (hasErr) {
          const errObj = responses.find(r => r && r.error);
          this.asistenciaMsg = errObj?.message || 'Error al guardar la asistencia.';
          this.asistenciaError = true;
        } else {
          this.asistenciaMsg = `✓ Asistencia del ${this.fechaAsistencia} registrada y bloqueada correctamente.`;
          this.asistenciaError = false;
          this.asistenciaYaGuardada = true;
          this.asistenciaBloqueada = true;
          setTimeout(() => this.asistenciaMsg = '', 6000);
        }
        this.savingAsistencia = false;
      },
      error: (err) => {
        this.asistenciaMsg = err?.error?.message || 'Error al guardar asistencia.';
        this.asistenciaError = true;
        this.savingAsistencia = false;
      }
    });
  }

  getPeriodos(): string[] {
    if (!this.paraleloActivo?.curso?.nivel) {
      return ['Book 1', 'Book 2', 'Book 3', 'Book 4', 'Book 5', 'Book 6', 'Examen Final'];
    }
    const levelStr = this.paraleloActivo.curso.nivel;
    const match = levelStr.match(/BOOK\s+(\d+)-(\d+)/i);
    if (match) {
      const start = parseInt(match[1], 10);
      const end = parseInt(match[2], 10);
      const books: string[] = [];
      for (let i = start; i <= end; i++) {
        books.push(`Book ${i}`);
      }
      books.push('Examen Final');
      return books;
    }
    return ['Parcial 1', 'Parcial 2', 'Parcial 3', 'Final'];
  }

  exportarExcelLista() {
    if (!this.paraleloActivo) return;
    this.isDownloadingExcel = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId, tipo: 'lista' };
    this.reportService.downloadNominalExcel(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Nomina_Alumnos_${nombre}_${new Date().toISOString().slice(0,10)}.xlsx`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando lista nominal en Excel', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar la lista de alumnos en Excel');
      }
    });
  }

  exportarExcelAsistencias() {
    if (!this.paraleloActivo) return;
    this.isDownloadingExcel = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId, tipo: 'asistencia' };
    this.reportService.downloadNominalExcel(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Planilla_Asistencias_${nombre}_${new Date().toISOString().slice(0,10)}.xlsx`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando asistencias en Excel', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar el reporte de asistencias en Excel');
      }
    });
  }

  exportarExcelNotas() {
    if (!this.paraleloActivo) return;
    this.isDownloadingExcel = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId };
    this.reportService.downloadNotasExcel(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Planilla_Calificaciones_${nombre}_${new Date().toISOString().slice(0,10)}.xlsx`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando calificaciones en Excel', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar la planilla de calificaciones en Excel');
      }
    });
  }

  isDownloadingPdf = false;

  imprimirSegunTab() {
    if (this.tabActivo === 'notas') {
      this.imprimirNotasPdf();
    } else if (this.tabActivo === 'asistencia') {
      this.imprimirAsistenciasPdf();
    } else {
      this.imprimirLista();
    }
  }

  imprimirLista() {
    if (!this.paraleloActivo) {
      window.print();
      return;
    }
    this.isDownloadingPdf = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId, tipo: 'lista' };
    this.reportService.downloadDocentePdf(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingPdf = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Nomina_Alumnos_${nombre}_${new Date().toISOString().slice(0,10)}.pdf`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error generando PDF de nómina docente', err);
        this.isDownloadingPdf = false;
        alert('No se pudo generar el PDF de la lista. Por favor intente nuevamente.');
      }
    });
  }

  imprimirNotasPdf() {
    if (!this.paraleloActivo) return;
    this.isDownloadingPdf = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId, tipo: 'notas' };
    this.reportService.downloadNotasPdf(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingPdf = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Planilla_Calificaciones_${nombre}_${new Date().toISOString().slice(0,10)}.pdf`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error generando PDF de calificaciones', err);
        this.isDownloadingPdf = false;
        alert('No se pudo generar el PDF de calificaciones. Por favor intente nuevamente.');
      }
    });
  }

  imprimirAsistenciasPdf() {
    if (!this.paraleloActivo) return;
    this.isDownloadingPdf = true;
    const paraleloId = this.paraleloActivo.id || this.paraleloActivo.id_paralelo;
    const filters = { id_paralelo: paraleloId, tipo: 'asistencia' };
    this.reportService.downloadAsistenciasPdf(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingPdf = false;
        const nombre = this.paraleloActivo.nombre || this.paraleloActivo.nombre_paralelo || 'Paralelo';
        const filename = `Planilla_Asistencias_${nombre}_${new Date().toISOString().slice(0,10)}.pdf`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error generando PDF de asistencias', err);
        this.isDownloadingPdf = false;
        alert('No se pudo generar el PDF de asistencias. Por favor intente nuevamente.');
      }
    });
  }

  onLogout() {
    this.authService.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
