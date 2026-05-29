import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ParaleloService } from '../../services/paralelo.service';
import { CourseService } from '../../services/course.service';
import { DocenteService } from '../../services/docente.service';

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

  constructor(
    private paraleloService: ParaleloService,
    private courseService: CourseService,
    private docenteService: DocenteService
  ) {}

  ngOnInit(): void {
    this.loadData();
  }

  loadData() {
    this.isLoading = true;
    this.paraleloService.getParalelos().subscribe({
      next: (data) => {
        this.paralelos = data;
        this.isLoading = false;
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
}
