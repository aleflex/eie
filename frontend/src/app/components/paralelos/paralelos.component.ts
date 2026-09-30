import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { ParaleloService } from '../../services/paralelo.service';
import { CourseService } from '../../services/course.service';
import { DocenteService } from '../../services/docente.service';
import { AuthService } from '../../services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-paralelos',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './paralelos.component.html',
  styleUrls: ['./paralelos.component.css']
})
export class ParalelosComponent implements OnInit {
  paralelos: any[] = [];
  courses: any[] = [];
  aulas: any[] = [];
  docentes: any[] = [];
  horarios: any[] = [];
  isSidebarCollapsed = false;
  isMobileMenuOpen = false;

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }
  
  isLoading = true;
  showForm = false;
  showAulaForm = false;
  isEditingAula = false;
  errorMsg = ''; // ← mensaje de error de conflicto
  
  selectedParalelo: any = {
    nombre: '',
    curso_id: '',
    aula_id: '',
    docentes: [],
    horarios: []
  };

  newAula: any = {
    id: null,
    nombre: '',
    capacidad: null
  };

  openAcademicOnLoad = false;

  constructor(
    private paraleloService: ParaleloService,
    private courseService: CourseService,
    private docenteService: DocenteService,
    private authService: AuthService,
    private http: HttpClient,
    private route: ActivatedRoute
  ) {}

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  ngOnInit(): void {
    this.route.queryParams.subscribe(params => {
      if (params['open'] === 'academic') {
        this.openAcademicOnLoad = true;
      }
    });
    this.loadData();
  }

  loadData() {
    this.isLoading = true;
    this.paraleloService.getParalelos().subscribe({
      next: (data) => {
        this.paralelos = (data || []).map((p: any) => {
          if (p.docentes && Array.isArray(p.docentes)) {
            const seen = new Set();
            p.docentes = p.docentes.filter((d: any) => {
              const key = d.id_docente || d.id;
              if (seen.has(key)) return false;
              seen.add(key);
              return true;
            });
          }
          return p;
        });
        this.isLoading = false;

        if (this.openAcademicOnLoad && this.paralelos.length > 0) {
          this.abrirModalAdminCalificaciones(this.paralelos[0]);
          this.openAcademicOnLoad = false;
        }
      },
      error: () => this.isLoading = false
    });
    
    this.courseService.getCourses().subscribe(data => this.courses = data);
    this.paraleloService.getAulas().subscribe(data => this.aulas = data);
    this.paraleloService.getHorarios().subscribe(data => this.horarios = data);
    this.docenteService.getDocentes().subscribe(data => this.docentes = data);
  }

  toggleHorario(id: number) {
    const index = this.selectedParalelo.horarios.indexOf(id);
    if (index > -1) {
      this.selectedParalelo.horarios.splice(index, 1);
    } else {
      this.selectedParalelo.horarios.push(id);
    }
  }

  toggleDocente(id: number) {
    // Si el docente tiene conflicto de horario, bloquear selección
    if (this.docenteTieneConflicto(id) && !this.isDocenteSelected(id)) {
      this.errorMsg = `⚠️ Este instructor ya tiene un paralelo asignado en el mismo horario seleccionado.`;
      setTimeout(() => this.errorMsg = '', 4000);
      return;
    }
    const index = this.selectedParalelo.docentes.indexOf(id);
    if (index > -1) {
      this.selectedParalelo.docentes.splice(index, 1);
    } else {
      this.selectedParalelo.docentes.push(id);
    }
  }

  isHorarioSelected(id: number): boolean {
    return this.selectedParalelo.horarios.includes(id);
  }

  isDocenteSelected(id: number): boolean {
    return this.selectedParalelo.docentes.includes(id);
  }

  /**
   * Detecta si un docente ya tiene un paralelo con alguno de los horarios seleccionados.
   * Excluye el paralelo actual (edición).
   */
  docenteTieneConflicto(docenteId: number): boolean {
    const horariosSeleccionados: number[] = this.selectedParalelo.horarios;
    if (!horariosSeleccionados || horariosSeleccionados.length === 0) return false;

    return this.paralelos.some(p => {
      // Excluir el paralelo que se está editando
      if (this.selectedParalelo.id && p.id === this.selectedParalelo.id) return false;

      // ¿Este paralelo tiene al docente?
      const tieneDocente = p.docentes?.some((d: any) => d.id === docenteId);
      if (!tieneDocente) return false;

      // ¿Comparte algún horario con los seleccionados?
      const horariosParalelo = p.horarios?.map((h: any) => h.id) || [];
      return horariosSeleccionados.some(hId => horariosParalelo.includes(hId));
    });
  }

  /**
   * Obtiene el nombre del paralelo conflictivo para mostrar en el tooltip
   */
  getNombreConflicto(docenteId: number): string {
    const horariosSeleccionados: number[] = this.selectedParalelo.horarios;
    const conflicto = this.paralelos.find(p => {
      if (this.selectedParalelo.id && p.id === this.selectedParalelo.id) return false;
      const tieneDocente = p.docentes?.some((d: any) => d.id === docenteId);
      if (!tieneDocente) return false;
      const horariosParalelo = p.horarios?.map((h: any) => h.id) || [];
      return horariosSeleccionados.some(hId => horariosParalelo.includes(hId));
    });
    return conflicto ? `Ocupado en: ${conflicto.nombre} (${conflicto.curso?.idioma})` : '';
  }

  editParalelo(paralelo: any) {
    this.selectedParalelo = {
      id: paralelo.id,
      nombre: paralelo.nombre,
      curso_id: paralelo.curso_id,
      aula_id: paralelo.aula_id,
      docentes: paralelo.docentes.map((d: any) => d.id),
      horarios: paralelo.horarios.map((h: any) => h.id)
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  resetForm() {
    this.selectedParalelo = {
      nombre: '',
      curso_id: '',
      aula_id: '',
      docentes: [],
      horarios: []
    };
    this.showForm = false;
  }

  saveParalelo() {
    this.errorMsg = '';
    this.paraleloService.saveParalelo(this.selectedParalelo).subscribe({
      next: () => {
        alert('Paralelo guardado exitosamente');
        this.resetForm();
        this.loadData();
      },
      error: (err) => {
        // El backend devuelve 422 con mensaje descriptivo de conflicto de horario
        const msg = err.error?.message || err.message || 'Error al guardar el paralelo.';
        this.errorMsg = '⚠️ ' + msg;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  deleteParalelo(id: number) {
    if (confirm('¿Estás seguro de eliminar este paralelo?')) {
      this.paraleloService.deleteParalelo(id).subscribe({
        next: () => this.loadData(),
        error: (err) => alert('Error al eliminar: ' + err.message)
      });
    }
  }

  saveAula() {
    if (!this.newAula.nombre) {
      alert('El nombre del aula es obligatorio.');
      return;
    }

    const payload: any = {
      nombre: this.newAula.nombre,
      capacidad: this.newAula.capacidad ? Number(this.newAula.capacidad) : null
    };

    if (this.isEditingAula && this.newAula.id) {
      payload.id = this.newAula.id;
    }

    this.paraleloService.saveAula(payload).subscribe({
      next: () => {
        alert(this.isEditingAula ? 'Aula actualizada exitosamente' : 'Aula creada exitosamente');
        this.resetAulaForm();
        // Recargar aulas
        this.paraleloService.getAulas().subscribe(data => this.aulas = data);
      },
      error: (err) => alert('Error al guardar aula: ' + (err.error?.message || err.message))
    });
  }

  editAula(aula: any) {
    this.isEditingAula = true;
    this.newAula = {
      id: aula.id,
      nombre: aula.nombre,
      capacidad: aula.capacidad
    };
    this.showAulaForm = true;
    
    // Desplazar la pantalla suavemente al formulario de aulas
    const el = document.getElementById('gestion-aulas-header');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  }

  resetAulaForm() {
    this.newAula = {
      id: null,
      nombre: '',
      capacidad: null
    };
    this.isEditingAula = false;
    this.showAulaForm = false;
  }

  deleteAula(id: number) {
    if (confirm('¿Estás seguro de eliminar esta aula? Se validará que no esté asignada a ningún paralelo.')) {
      this.paraleloService.deleteAula(id).subscribe({
        next: () => {
          alert('Aula eliminada correctamente');
          // Recargar aulas
          this.paraleloService.getAulas().subscribe(data => this.aulas = data);
        },
        error: (err) => alert('Error al eliminar aula: ' + (err.error?.message || err.message))
      });
    }
  }

  // =========================================================================
  // GESTIÓN Y RECTIFICACIÓN ACADÉMICA (EXCLUSIVO ADMINISTRADOR CON JUSTIFICATIVO)
  // =========================================================================
  showAcademicModal = false;
  selectedParaleloAdmin: any = null;
  adminTab: 'notas' | 'asistencia' = 'notas';
  adminEstudiantes: any[] = [];
  loadingAdminStudents = false;
  adminPeriodo = 'Book 1';
  adminFechaAsistencia: string = new Date().toISOString().split('T')[0];
  
  adminNotasForm: { [inscId: number]: number | null } = {};
  adminObsNotasForm: { [inscId: number]: string } = {};
  adminJustificativoNotas: { [inscId: number]: string } = {};

  adminAsistenciaForm: { [inscId: number]: string } = {};
  adminObsAsistenciaForm: { [inscId: number]: string } = {};
  adminJustificativoAsistencia: { [inscId: number]: string } = {};

  savingAdminItem: { [key: string]: boolean } = {};
  adminModalMsg = '';
  adminModalError = false;

  abrirModalAdminCalificacionesDirecto() {
    if (this.paralelos && this.paralelos.length > 0) {
      this.abrirModalAdminCalificaciones(this.paralelos[0]);
    } else {
      alert('No hay paralelos configurados en el sistema.');
    }
  }

  onAdminParaleloChange(paraleloId: any) {
    const found = this.paralelos.find(p => (p.id == paraleloId || p.id_paralelo == paraleloId));
    if (found) {
      this.abrirModalAdminCalificaciones(found);
    }
  }

  abrirModalAdminCalificaciones(p: any) {
    this.selectedParaleloAdmin = p;
    this.showAcademicModal = true;
    this.adminTab = 'notas';
    this.adminModalMsg = '';
    const periodos = this.getAdminPeriodos();
    if (periodos.length > 0) this.adminPeriodo = periodos[0];
    this.cargarEstudiantesParaleloAdmin();
  }

  cerrarModalAdminCalificaciones() {
    this.showAcademicModal = false;
    this.selectedParaleloAdmin = null;
    this.adminEstudiantes = [];
  }

  getAdminPeriodos(): string[] {
    if (!this.selectedParaleloAdmin?.curso?.nivel) {
      return ['Book 1', 'Book 2', 'Book 3', 'Book 4', 'Book 5', 'Book 6', 'Examen Final'];
    }
    const levelStr = this.selectedParaleloAdmin.curso.nivel;
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

  cargarEstudiantesParaleloAdmin() {
    if (!this.selectedParaleloAdmin) return;
    this.loadingAdminStudents = true;
    const id = this.selectedParaleloAdmin.id || this.selectedParaleloAdmin.id_paralelo;
    this.http.get<any[]>(`${environment.apiUrl}/api/paralelos/${id}/asistencias`).subscribe({
      next: (data) => {
        this.adminEstudiantes = data || [];
        this.cargarNotasAdminPeriodo();
        this.cargarAsistenciaAdminFecha();
        this.loadingAdminStudents = false;
      },
      error: () => {
        this.loadingAdminStudents = false;
      }
    });
  }

  cargarNotasAdminPeriodo() {
    this.adminEstudiantes.forEach(ins => {
      const inscId = ins.id || ins.id_inscripcion;
      this.http.get<any[]>(`${environment.apiUrl}/api/inscripciones/${inscId}/notas`).pipe(catchError(() => of([]))).subscribe(notas => {
        const notaItem = notas.find((n: any) => n.periodo === this.adminPeriodo);
        if (notaItem && notaItem.nota !== null && notaItem.nota !== undefined) {
          this.adminNotasForm[inscId] = Number(notaItem.nota);
          this.adminObsNotasForm[inscId] = notaItem.observacion || '';
        } else {
          this.adminNotasForm[inscId] = null;
          this.adminObsNotasForm[inscId] = '';
        }
      });
    });
  }

  cargarAsistenciaAdminFecha() {
    this.adminEstudiantes.forEach(ins => {
      const inscId = ins.id || ins.id_inscripcion;
      this.http.get<any[]>(`${environment.apiUrl}/api/inscripciones/${inscId}/asistencias`).pipe(catchError(() => of([]))).subscribe(asists => {
        const asistItem = asists.find((a: any) => a.fecha?.startsWith(this.adminFechaAsistencia));
        if (asistItem) {
          this.adminAsistenciaForm[inscId] = asistItem.estado;
          this.adminObsAsistenciaForm[inscId] = asistItem.observacion || '';
        } else {
          this.adminAsistenciaForm[inscId] = 'presente';
          this.adminObsAsistenciaForm[inscId] = '';
        }
      });
    });
  }

  guardarNotaAdmin(ins: any) {
    const inscId = ins.id || ins.id_inscripcion;
    const notaVal = this.adminNotasForm[inscId];
    if (notaVal === null || notaVal === undefined || isNaN(notaVal)) {
      alert('Por favor ingrese una calificación válida (0-100).');
      return;
    }
    const justificativo = (this.adminJustificativoNotas[inscId] || '').trim();
    if (!justificativo) {
      alert('Como Administrador, debe ingresar obligatoriamente el Justificativo del cambio de calificación.');
      return;
    }

    const key = `nota_${inscId}`;
    this.savingAdminItem[key] = true;

    this.http.post(`${environment.apiUrl}/api/inscripciones/${inscId}/notas`, {
      nota: notaVal,
      periodo: this.adminPeriodo,
      observacion: this.adminObsNotasForm[inscId] || null,
      justificativo: justificativo
    }).subscribe({
      next: () => {
        this.savingAdminItem[key] = false;
        alert(`✓ Calificación de ${ins.estudiante?.nombres} rectificada con justificativo asentado.`);
        this.adminJustificativoNotas[inscId] = '';
        this.cargarNotasAdminPeriodo();
      },
      error: (err) => {
        this.savingAdminItem[key] = false;
        alert('Error: ' + (err.error?.message || err.message));
      }
    });
  }

  guardarAsistenciaAdmin(ins: any) {
    const inscId = ins.id || ins.id_inscripcion;
    const estado = this.adminAsistenciaForm[inscId] || 'presente';
    const justificativo = (this.adminJustificativoAsistencia[inscId] || '').trim();
    if (!justificativo) {
      alert('Como Administrador, debe ingresar obligatoriamente el Justificativo de la modificación o falta justificada.');
      return;
    }

    const key = `asist_${inscId}`;
    this.savingAdminItem[key] = true;

    this.http.post(`${environment.apiUrl}/api/inscripciones/${inscId}/asistencias`, {
      fecha: this.adminFechaAsistencia,
      estado: estado,
      observacion: this.adminObsAsistenciaForm[inscId] || null,
      justificativo: justificativo
    }).subscribe({
      next: () => {
        this.savingAdminItem[key] = false;
        alert(`✓ Asistencia de ${ins.estudiante?.nombres} actualizada con justificativo asentado.`);
        this.adminJustificativoAsistencia[inscId] = '';
        this.cargarAsistenciaAdminFecha();
      },
      error: (err) => {
        this.savingAdminItem[key] = false;
        alert('Error: ' + (err.error?.message || err.message));
      }
    });
  }
}
