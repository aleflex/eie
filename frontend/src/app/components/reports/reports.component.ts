import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ReportService } from '../../services/report.service';
import { CourseService } from '../../services/course.service';
import { ParaleloService } from '../../services/paralelo.service';
import { DocenteService } from '../../services/docente.service';
import { AuthService } from '../../services/auth.service';
import { downloadFile } from '../../utils/file-downloader';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './reports.component.html',
  styleUrl: './reports.component.css'
})
export class ReportsComponent implements OnInit, AfterViewInit {
  @ViewChild('languageCanvas') languageCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('classroomCanvas') classroomCanvas!: ElementRef<HTMLCanvasElement>;

  user: any = null;
  isLoading: boolean = true;
  isDownloadingExcel: boolean = false;
  isDownloadingPdf: boolean = false;

  // Control de la barra lateral (Sidebar)
  isSidebarCollapsed: boolean = false;
  isMobileMenuOpen: boolean = false;

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  // Control del panel lateral de filtros colapsable (RF 21)
  isFilterSidebarOpen: boolean = false;

  // Filtros Multi-criterio Combinados (Gestión, Docente, Nivel, Turno, etc.)
  filters: any = {
    gestion: '',
    id_docente: '',
    id_nivel: '',
    turno: '',
    id_idioma: '',
    id_curso: '',
    id_paralelo: '',
    estado: '',
    fecha_desde: '',
    fecha_hasta: ''
  };

  // Selector de vista de tabla principal: 'aulas', 'estudiantes' o 'pendientes'
  activeTableView: 'aulas' | 'estudiantes' | 'pendientes' = 'aulas';
  searchTermStudent: string = '';
  searchTermPending: string = '';

  setActiveTableView(view: 'aulas' | 'estudiantes' | 'pendientes') {
    this.activeTableView = view;
  }

  filterByKpi(type: string) {
    if (type === 'pendientes') {
      this.activeTableView = 'pendientes';
    } else if (type === 'activos') {
      this.activeTableView = 'estudiantes';
      this.searchTermStudent = '';
    } else if (type === 'retirados') {
      this.filters.estado = 'retirado';
      this.activeTableView = 'estudiantes';
      this.onFilterChange();
      return;
    } else {
      this.activeTableView = 'aulas';
    }

    setTimeout(() => {
      const tableSection = document.querySelector('.table-container');
      if (tableSection) {
        tableSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 50);
  }

  // Catálogos para los selectores de filtro
  cursosList: any[] = [];
  paralelosList: any[] = [];
  docentesList: any[] = [];
  gestionesList: number[] = [2026, 2025, 2024, 2023];
  turnosList: any[] = [
    { id: 'manana', nombre: 'Mañana (08:00 - 12:00)' },
    { id: 'tarde', nombre: 'Tarde (14:00 - 18:00)' },
    { id: 'noche', nombre: 'Noche (18:30 - 21:00)' },
    { id: 'sabado', nombre: 'Sábados' }
  ];

  idiomasList: any[] = [
    { id: 1, nombre: 'Inglés' },
    { id: 2, nombre: 'Francés' },
    { id: 3, nombre: 'Alemán' },
    { id: 4, nombre: 'Ruso' },
    { id: 5, nombre: 'Chino' },
    { id: 6, nombre: 'Aymara' },
    { id: 7, nombre: 'Quechua' }
  ];
  nivelesList: any[] = [
    { id: 1, nombre: 'Básico' },
    { id: 2, nombre: 'Intermedio' },
    { id: 3, nombre: 'Avanzado' },
    { id: 4, nombre: 'Especializado' }
  ];

  // Datos Resumen KPIs (RF 18 & RF 19)
  summaryData: any = {
    total_inscritos: 0,
    habilitados: 0,
    pendientes: 0,
    retirados: 0,
    promedio_notas: 0,
    porcentaje_habilitados: 0,
    idioma_top: 'N/A',
    ocupacion_promedio: 0
  };

  // Datos de Estadísticas por Idioma
  languageStats: any[] = [];

  // Datos de Ocupación de Aulas
  classroomStats: any[] = [];

  // Control de expansión de estudiantes por Paralelo/Aula
  expandedParaleloId: number | null = null;
  studentFilterByParalelo: { [id: number]: string } = {};

  // Lista consolidada plana de todos los estudiantes filtrados de todas las aulas/paralelos
  get allFilteredStudents(): any[] {
    const list: any[] = [];
    if (!this.classroomStats) return list;
    for (const aula of this.classroomStats) {
      if (aula.estudiantes && Array.isArray(aula.estudiantes)) {
        for (const est of aula.estudiantes) {
          list.push({
            ...est,
            aula_nombre: aula.aula || 'Sin Aula',
            paralelo_nombre: aula.nombre_paralelo || 'N/A',
            curso_nombre: aula.curso || 'N/A',
            docente_nombre: aula.docente || 'Sin Asignar',
            horario_desc: aula.horario || 'Regular'
          });
        }
      }
    }

    if (!this.searchTermStudent || !this.searchTermStudent.trim()) {
      return list;
    }

    const term = this.searchTermStudent.toLowerCase().trim();
    return list.filter(e =>
      (e.nombre_completo && e.nombre_completo.toLowerCase().includes(term)) ||
      (e.ci && e.ci.toLowerCase().includes(term)) ||
      (e.paralelo_nombre && e.paralelo_nombre.toLowerCase().includes(term)) ||
      (e.curso_nombre && e.curso_nombre.toLowerCase().includes(term)) ||
      (e.docente_nombre && e.docente_nombre.toLowerCase().includes(term))
    );
  }

  // Lista consolidada de estudiantes en estado pendiente (con o sin paralelo)
  get pendingStudents(): any[] {
    const list: any[] = [];
    if (!this.classroomStats) return list;
    for (const aula of this.classroomStats) {
      if (aula.estudiantes && Array.isArray(aula.estudiantes)) {
        for (const est of aula.estudiantes) {
          const st = (est.estado || '').toLowerCase();
          if (st.includes('pen')) {
            list.push({
              ...est,
              aula_nombre: aula.aula || 'Sin Aula Asignada',
              paralelo_nombre: aula.nombre_paralelo || 'Sin Paralelo (Por Asignar)',
              curso_nombre: aula.curso || 'N/A',
              docente_nombre: aula.docente || 'Sin Asignar',
              horario_desc: aula.horario || 'Por Definir'
            });
          }
        }
      }
    }

    if (!this.searchTermPending || !this.searchTermPending.trim()) {
      return list;
    }

    const term = this.searchTermPending.toLowerCase().trim();
    return list.filter(e =>
      (e.nombre_completo && e.nombre_completo.toLowerCase().includes(term)) ||
      (e.ci && e.ci.toLowerCase().includes(term)) ||
      (e.paralelo_nombre && e.paralelo_nombre.toLowerCase().includes(term)) ||
      (e.curso_nombre && e.curso_nombre.toLowerCase().includes(term)) ||
      (e.docente_nombre && e.docente_nombre.toLowerCase().includes(term))
    );
  }

  toggleParaleloExpand(id_paralelo: number) {
    if (this.expandedParaleloId === id_paralelo) {
      this.expandedParaleloId = null;
    } else {
      this.expandedParaleloId = id_paralelo;
      if (!this.studentFilterByParalelo[id_paralelo]) {
        this.studentFilterByParalelo[id_paralelo] = 'todos';
      }
    }
  }

  setStudentFilter(idParalelo: number, estado: string) {
    this.studentFilterByParalelo[idParalelo] = estado;
  }

  getFilteredStudents(item: any): any[] {
    if (!item || !item.estudiantes) return [];
    const filter = this.studentFilterByParalelo[item.id_paralelo] || 'todos';
    if (filter === 'todos') return item.estudiantes;
    return item.estudiantes.filter((e: any) => {
      const st = (e.estado || '').toLowerCase();
      if (filter === 'activo') return st.includes('act') || st.includes('hab');
      if (filter === 'pendiente') return st.includes('pen');
      if (filter === 'baja') return st.includes('ret') || st.includes('baj') || st.includes('inac');
      return true;
    });
  }

  calculateStudentCounts(estudiantes: any[]) {
    let activos = 0, pendientes = 0, bajas = 0;
    if (!estudiantes) return { activos, pendientes, bajas };
    estudiantes.forEach(e => {
      const st = (e.estado || '').toLowerCase();
      if (st.includes('act') || st.includes('hab')) {
        activos++;
      } else if (st.includes('ret') || st.includes('baj') || st.includes('inac')) {
        bajas++;
      } else {
        pendientes++;
      }
    });
    return { activos, pendientes, bajas };
  }

  constructor(
    private reportService: ReportService,
    private courseService: CourseService,
    private paraleloService: ParaleloService,
    private docenteService: DocenteService,
    private authService: AuthService
  ) {}

  ngOnInit() {
    this.user = this.authService.getUser();
    this.cargarCatalogos();
    this.cargarReportes();
  }

  ngAfterViewInit() {
    setTimeout(() => {
      this.renderCharts();
    }, 400);
  }

  toggleFilterSidebar() {
    this.isFilterSidebarOpen = !this.isFilterSidebarOpen;
  }

  cargarCatalogos() {
    this.courseService.obtenerCursos().subscribe({
      next: (data: any[]) => this.cursosList = data,
      error: (err: any) => console.error('Error cargando cursos', err)
    });

    this.paraleloService.obtenerParalelos().subscribe({
      next: (data: any[]) => this.paralelosList = data,
      error: (err: any) => console.error('Error cargando paralelos', err)
    });

    this.docenteService.getDocentes().subscribe({
      next: (data: any[]) => this.docentesList = data,
      error: (err: any) => console.error('Error cargando docentes', err)
    });
  }

  /**
   * Carga sincrónica y reactiva de reportes al cambiar filtros (RF 21 - HU 21 - T3)
   */
  cargarReportes() {
    this.isLoading = true;

    this.reportService.getDashboardSummary(this.filters).subscribe({
      next: (data: any) => {
        this.summaryData = data;
      },
      error: (err: any) => console.error('Error cargando KPI summary', err)
    });

    this.reportService.getLanguageStatistics(this.filters).subscribe({
      next: (res: any) => {
        this.languageStats = res.estadisticas || [];
        this.renderLanguageChart();
      },
      error: (err: any) => console.error('Error cargando estadísticas por idioma', err)
    });

    this.reportService.getClassroomOccupancy(this.filters).subscribe({
      next: (res: any) => {
        this.classroomStats = res.aulas || [];
        this.classroomStats.forEach(item => {
          if (item.estudiantes && item.estudiantes.length > 0) {
            const counts = this.calculateStudentCounts(item.estudiantes);
            item.activos_count = counts.activos;
            item.pendientes_count = counts.pendientes;
            item.bajas_count = counts.bajas;
          }
        });
        this.isLoading = false;
        this.renderClassroomChart();
      },
      error: (err: any) => {
        console.error('Error cargando ocupación de aulas', err);
        this.isLoading = false;
      }
    });
  }

  onFilterChange() {
    this.cargarReportes();
  }

  resetFilters() {
    this.filters = {
      gestion: '',
      id_docente: '',
      id_nivel: '',
      turno: '',
      id_idioma: '',
      id_curso: '',
      id_paralelo: '',
      estado: '',
      fecha_desde: '',
      fecha_hasta: ''
    };
    this.searchTermStudent = '';
    this.searchTermPending = '';
    this.cargarReportes();
  }

  /**
   * RF 20 - HU 20: Exportación dedicada a Excel (Blob)
   */
  exportExcel() {
    this.isDownloadingExcel = true;
    this.reportService.downloadExcel(this.filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingExcel = false;
        const filename = `Relacion_Nominal_EIE_${new Date().toISOString().slice(0,10)}.xlsx`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando Excel', err);
        this.isDownloadingExcel = false;
        alert('No se pudo generar el archivo Excel en este momento');
      }
    });
  }

  /**
   * RF 20 - HU 20: Exportación dedicada a PDF (Blob)
   */
  exportPdf() {
    this.isDownloadingPdf = true;
    this.reportService.downloadPdf(this.filters).subscribe({
      next: (blob: Blob) => {
        this.isDownloadingPdf = false;
        const filename = `Reporte_Oficial_EIE_${new Date().toISOString().slice(0,10)}.pdf`;
        downloadFile(blob, filename);
      },
      error: (err: any) => {
        console.error('Error exportando PDF', err);
        this.isDownloadingPdf = false;
        alert('No se pudo generar el reporte PDF en este momento');
      }
    });
  }

  renderCharts() {
    this.renderLanguageChart();
    this.renderClassroomChart();
  }

  /**
   * Renderizado de Gráfico de Torta / Pastel (Doughnut) para Idiomas (RF 19)
   */
  renderLanguageChart() {
    if (!this.languageCanvas) return;
    const canvas = this.languageCanvas.nativeElement;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (this.languageStats.length === 0) {
      ctx.fillStyle = '#94a3b8';
      ctx.font = '14px Inter, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('Sin datos para el gráfico', canvas.width / 2, canvas.height / 2);
      return;
    }

    const colors = ['#003B71', '#F9B233', '#10B981', '#6366F1', '#EC4899', '#8B5CF6', '#14B8A6'];
    const centerX = canvas.width / 2 - 40;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) - 20;

    let startAngle = 0;
    const total = this.languageStats.reduce((sum, item) => sum + item.total_estudiantes, 0);

    this.languageStats.forEach((item, index) => {
      const sliceAngle = total > 0 ? (item.total_estudiantes / total) * 2 * Math.PI : 0;
      const color = colors[index % colors.length];

      // Rebanada
      ctx.beginPath();
      ctx.moveTo(centerX, centerY);
      ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
      ctx.closePath();
      ctx.fillStyle = color;
      ctx.fill();
      ctx.lineWidth = 2;
      ctx.strokeStyle = '#ffffff';
      ctx.stroke();

      startAngle += sliceAngle;
    });

    // Centro Donut
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius * 0.5, 0, 2 * Math.PI);
    ctx.fillStyle = '#ffffff';
    ctx.fill();

    // Texto central
    ctx.fillStyle = '#003B71';
    ctx.font = 'bold 18px Outfit, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(`${total}`, centerX, centerY - 6);
    ctx.font = '10px Inter, sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.fillText('Estudiantes', centerX, centerY + 12);

    // Leyenda lateral
    let legendY = 30;
    const legendX = canvas.width - 120;

    this.languageStats.forEach((item, index) => {
      const color = colors[index % colors.length];
      ctx.fillStyle = color;
      ctx.fillRect(legendX, legendY, 12, 12);

      ctx.fillStyle = '#1e293b';
      ctx.font = '12px Inter, sans-serif';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'top';
      ctx.fillText(`${item.idioma}: ${item.total_estudiantes}`, legendX + 18, legendY);

      legendY += 22;
    });
  }

  /**
   * Renderizado de Gráfico de Barras para Ocupación de Aulas (RF 19)
   */
  renderClassroomChart() {
    if (!this.classroomCanvas) return;
    const canvas = this.classroomCanvas.nativeElement;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (this.classroomStats.length === 0) {
      ctx.fillStyle = '#94a3b8';
      ctx.font = '14px Inter, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('Sin datos de aulas', canvas.width / 2, canvas.height / 2);
      return;
    }

    const paddingLeft = 60;
    const paddingBottom = 40;
    const chartWidth = canvas.width - paddingLeft - 20;
    const chartHeight = canvas.height - paddingBottom - 30;

    // Eje Y (0% a 100%)
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;

    for (let i = 0; i <= 5; i++) {
      const y = canvas.height - paddingBottom - (i * (chartHeight / 5));
      const val = i * 20;

      ctx.beginPath();
      ctx.moveTo(paddingLeft, y);
      ctx.lineTo(canvas.width - 20, y);
      ctx.stroke();

      ctx.fillStyle = '#64748b';
      ctx.font = '10px Inter, sans-serif';
      ctx.textAlign = 'right';
      ctx.fillText(`${val}%`, paddingLeft - 8, y + 3);
    }

    // Dibujar Barras
    const barCount = this.classroomStats.length;
    const barWidth = Math.min(45, (chartWidth / barCount) - 15);

    this.classroomStats.forEach((item, index) => {
      const x = paddingLeft + (index * (chartWidth / barCount)) + 10;
      const pct = Math.min(item.porcentaje_ocupacion, 100);
      const h = (pct / 100) * chartHeight;
      const y = canvas.height - paddingBottom - h;

      // Color barra según ocupación
      let barColor = '#003B71';
      if (pct >= 90) barColor = '#EF4444';
      else if (pct >= 70) barColor = '#F59E0B';
      else if (pct >= 40) barColor = '#10B981';

      // Barra
      ctx.fillStyle = barColor;
      ctx.beginPath();
      if (typeof ctx.roundRect === 'function') {
        ctx.roundRect(x, y, barWidth, h, [4, 4, 0, 0]);
      } else {
        ctx.rect(x, y, barWidth, h);
      }
      ctx.fill();

      // Valor encima de la barra
      ctx.fillStyle = '#1e293b';
      ctx.font = 'bold 10px Inter, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(`${pct}%`, x + (barWidth / 2), y - 6);

      // Label Eje X (Aula/Paralelo)
      ctx.fillStyle = '#475569';
      ctx.font = '10px Inter, sans-serif';
      const label = item.nombre_paralelo ? `${item.aula} (${item.nombre_paralelo})` : item.aula;
      ctx.fillText(label.slice(0, 10), x + (barWidth / 2), canvas.height - paddingBottom + 15);
    });
  }
}
