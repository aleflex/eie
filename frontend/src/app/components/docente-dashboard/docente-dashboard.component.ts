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
  isLoading = true;

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }
  errorMsg = '';
  today = new Date();

  // Tabs
  tabActivo: 'lista' | 'notas' | 'asistencia' = 'lista';

  // Notas
  periodoSeleccionado = 'Parcial 1';
  notasForm: { [key: string]: number | null } = {};
  observacionesForm: { [key: string]: string } = {};
  savingNotas = false;
  notasMsg = '';
  notasError = false;

  // Asistencia
  fechaAsistencia: string = new Date().toISOString().split('T')[0];
  asistenciaForm: { [inscripcionId: number]: string } = {};
  observacionAsistenciaForm: { [inscripcionId: number]: string } = {};
  savingAsistencia = false;
  asistenciaMsg = '';
  asistenciaError = false;

  isDownloadingExcel = false;

  constructor(
    private docenteService: DocenteService,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient,
    private reportService: ReportService
  ) {}

  ngOnInit(): void {
    this.user = this.authService.getUser();
    if (!this.user) { this.router.navigate(['/login']); return; }
    if (this.user.rol === 'admin') { this.router.navigate(['/admin']); return; }
    if (this.user.rol === 'estudiante') { this.router.navigate(['/student-dashboard']); return; }
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
  }

  setTab(tab: 'lista' | 'notas' | 'asistencia') {
    this.tabActivo = tab;
    this.notasMsg = '';
    this.asistenciaMsg = '';

    if (tab === 'notas') this.cargarNotasDelPeriodo();
    if (tab === 'asistencia') this.cargarAsistenciaDelDia();
  }

  resetForms() {
    this.notasForm = {};
    this.observacionesForm = {};
    this.asistenciaForm = {};
    this.observacionAsistenciaForm = {};
    this.notasMsg = '';
    this.asistenciaMsg = '';
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

  cargarNotasDelPeriodo() {
    const requests = this.estudiantesActivos.map(insc =>
      this.http.get<any[]>(`${this.apiUrl}/inscripciones/${insc.id}/notas`).pipe(catchError(() => of([])))
    );

    forkJoin(requests).subscribe(resultados => {
      resultados.forEach((notas: any[], idx: number) => {
        const insc = this.estudiantesActivos[idx];
        const key = `${insc.id}_${this.periodoSeleccionado}`;
        const notaDePeriodo = notas.find((n: any) => n.periodo === this.periodoSeleccionado);
        if (notaDePeriodo) {
          this.notasForm[key] = notaDePeriodo.nota;
          this.observacionesForm[key] = notaDePeriodo.observacion || '';
        } else {
          this.notasForm[key] = null;
          this.observacionesForm[key] = '';
        }
      });
    });
  }

  onPeriodoChange() {
    this.cargarNotasDelPeriodo();
  }

  getNotaValor(inscripcionId: number): number {
    const key = `${inscripcionId}_${this.periodoSeleccionado}`;
    return Number(this.notasForm[key]) || 0;
  }

  guardarTodasLasNotas() {
    this.savingNotas = true;
    this.notasMsg = '';
    this.notasError = false;

    const requests = this.estudiantesActivos
      .filter(insc => {
        const key = `${insc.id}_${this.periodoSeleccionado}`;
        return this.notasForm[key] !== null && this.notasForm[key] !== undefined;
      })
      .map(insc => {
        const key = `${insc.id}_${this.periodoSeleccionado}`;
        return this.http.post(`${this.apiUrl}/inscripciones/${insc.id}/notas`, {
          nota: this.notasForm[key],
          periodo: this.periodoSeleccionado,
          observacion: this.observacionesForm[key] || null
        }).pipe(catchError(err => {
          console.error('Error guardando nota', err);
          return of(null);
        }));
      });

    if (requests.length === 0) {
      this.notasMsg = 'No hay notas para guardar. Ingrese al menos una nota.';
      this.notasError = true;
      this.savingNotas = false;
      return;
    }

    forkJoin(requests).subscribe({
      next: () => {
        this.notasMsg = `✓ Notas del ${this.periodoSeleccionado} guardadas correctamente.`;
        this.notasError = false;
        this.savingNotas = false;
        setTimeout(() => this.notasMsg = '', 4000);
      },
      error: () => {
        this.notasMsg = 'Error al guardar algunas notas.';
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

    forkJoin(requests).subscribe(resultados => {
      resultados.forEach((asistencias: any[], idx: number) => {
        const insc = this.estudiantesActivos[idx];
        const asistDia = asistencias.find((a: any) => a.fecha?.startsWith(this.fechaAsistencia));
        if (asistDia) {
          this.asistenciaForm[insc.id] = asistDia.estado;
          this.observacionAsistenciaForm[insc.id] = asistDia.observacion || '';
        } else {
          this.asistenciaForm[insc.id] = 'presente'; // default
          this.observacionAsistenciaForm[insc.id] = '';
        }
      });
    });
  }

  setAsistencia(inscripcionId: number, estado: string) {
    this.asistenciaForm[inscripcionId] = estado;
  }

  guardarAsistencia() {
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
      }).pipe(catchError(err => { console.error('Error asistencia', err); return of(null); }))
    );

    forkJoin(requests).subscribe({
      next: () => {
        this.asistenciaMsg = `✓ Asistencia del ${this.fechaAsistencia} guardada correctamente.`;
        this.asistenciaError = false;
        this.savingAsistencia = false;
        setTimeout(() => this.asistenciaMsg = '', 4000);
      },
      error: () => {
        this.asistenciaMsg = 'Error al guardar asistencia.';
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

  exportarExcelAsistencias() {
    if (!this.paraleloActivo) return;
    this.isDownloadingExcel = true;
    const filters = { id_paralelo: this.paraleloActivo.id };
    this.reportService.downloadNominalExcel(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const filename = `Planilla_Asistencias_${this.paraleloActivo.nombre || 'Paralelo'}_${new Date().toISOString().slice(0,10)}.xls`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando asistencias', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar el reporte de asistencias en Excel');
      }
    });
  }

  exportarExcelNotas() {
    if (!this.paraleloActivo) return;
    this.isDownloadingExcel = true;
    const filters = { id_paralelo: this.paraleloActivo.id };
    this.reportService.downloadNotasExcel(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const filename = `Planilla_Calificaciones_${this.paraleloActivo.nombre || 'Paralelo'}_${new Date().toISOString().slice(0,10)}.xls`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando calificaciones', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar la planilla de calificaciones en Excel');
      }
    });
  }

  isDownloadingPdf = false;

  imprimirLista() {
    if (!this.paraleloActivo) {
      window.print();
      return;
    }
    this.isDownloadingPdf = true;
    const filters = { id_paralelo: this.paraleloActivo.id };
    this.reportService.downloadDocentePdf(filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingPdf = false;
        const filename = `Nomina_Alumnos_${this.paraleloActivo.nombre || 'Paralelo'}_${new Date().toISOString().slice(0,10)}.pdf`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error generando PDF de nómina docente', err);
        this.isDownloadingPdf = false;
        window.print();
      }
    });
  }

  onLogout() {
    this.authService.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
