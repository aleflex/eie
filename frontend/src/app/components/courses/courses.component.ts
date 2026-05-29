import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CourseService, Course } from '../../services/course.service';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-courses',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './courses.component.html',
  styleUrls: ['./courses.component.css']
})
export class CoursesComponent implements OnInit {
  courses: Course[] = [];
  
  // Paginación y Filtros
  pagedCourses: Course[] = [];
  currentPage: number = 1;
  pageSize: number = 6;
  totalPages: number = 1;
  searchTerm: string = '';
  searchType: string = 'all';

  showForm: boolean = false;
  isEditing: boolean = false;
  
  currentCourse: Course = {
    idioma: 'Inglés',
    nivel: 'NIVEL I (BOOK 1-6)',
    modalidad: 'Presencial',
    horario: '08:00 - 10:00',
    cupo_minimo: 10,
    cupo_maximo: 30
  };

  nivelesDisponibles = [
    'NIVEL I (BOOK 1-6)',
    'NIVEL II (BOOK 7-12)',
    'NIVEL III (BOOK 13-18)',
    'NIVEL IV (BOOK 19-24)',
    'NIVEL V (BOOK 25-30)'
  ];

  constructor(private courseService: CourseService) {}

  ngOnInit(): void {
    this.loadCourses();
  }

  loadCourses() {
    this.courseService.getCourses().subscribe({
      next: (data) => {
        this.courses = data;
        this.applyFilters();
      },
      error: (err) => console.error('Error al cargar cursos', err)
    });
  }

  applyFilters() {
    let filtered = this.courses;
    
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      if (this.searchType === 'idioma') {
        filtered = this.courses.filter(c => c.idioma.toLowerCase().includes(term));
      } else if (this.searchType === 'nivel') {
        filtered = this.courses.filter(c => c.nivel.toLowerCase().includes(term));
      } else if (this.searchType === 'modalidad') {
        filtered = this.courses.filter(c => c.modalidad.toLowerCase().includes(term));
      } else {
        filtered = this.courses.filter(c => 
          c.idioma.toLowerCase().includes(term) || 
          c.nivel.toLowerCase().includes(term) ||
          c.horario.toLowerCase().includes(term) ||
          c.modalidad.toLowerCase().includes(term)
        );
      }
    }

    this.totalPages = Math.ceil(filtered.length / this.pageSize);
    
    // Asegurar que la página actual sea válida tras filtrar
    if (this.currentPage > this.totalPages && this.totalPages > 0) {
      this.currentPage = this.totalPages;
    }

    const start = (this.currentPage - 1) * this.pageSize;
    this.pagedCourses = filtered.slice(start, start + this.pageSize);
  }

  onSearch() {
    this.applyFilters();
  }

  onSearchInput() {
    this.applyFilters();
  }

  setPage(page: number) {
    if (page < 1 || page > this.totalPages) return;
    this.currentPage = page;
    this.applyFilters();
  }

  openCreateForm() {
    this.isEditing = false;
    this.currentCourse = { 
      idioma: 'Inglés', 
      nivel: 'NIVEL I (BOOK 1-6)', 
      modalidad: 'Presencial', 
      horario: '08:00 - 10:00', 
      cupo_minimo: 10,
      cupo_maximo: 30 
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  editCourse(course: Course) {
    this.isEditing = true;
    this.currentCourse = { ...course };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  saveCourse() {
    this.currentCourse.cupo_minimo = Number(this.currentCourse.cupo_minimo);
    this.currentCourse.cupo_maximo = Number(this.currentCourse.cupo_maximo);

    if (this.isEditing && this.currentCourse.id) {
      this.courseService.updateCourse(this.currentCourse.id, this.currentCourse).subscribe({
        next: () => {
          this.loadCourses();
          this.showForm = false;
        },
        error: (err) => alert('Error al actualizar: ' + JSON.stringify(err.error?.errors || err.message))
      });
    } else {
      this.courseService.createCourse(this.currentCourse).subscribe({
        next: () => {
          this.loadCourses();
          this.showForm = false;
        },
        error: (err) => alert('Error al crear: ' + JSON.stringify(err.error?.errors || err.message))
      });
    }
  }

  deleteCourse(id: number | undefined) {
    if (id && confirm('¿Está seguro de eliminar este curso?')) {
      this.courseService.deleteCourse(id).subscribe({
        next: () => this.loadCourses(),
        error: (err) => alert('No se puede eliminar el curso porque tiene inscripciones activas.')
      });
    }
  }

  closeForm() {
    this.showForm = false;
  }
}
