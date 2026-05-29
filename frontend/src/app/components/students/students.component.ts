import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { environment } from '../../../environments/environment';
import { StudentService } from '../../services/student.service';
import { CourseService } from '../../services/course.service';
import { InscriptionService } from '../../services/inscription.service';
import { ParaleloService } from '../../services/paralelo.service';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ImageCompressorService } from '../../services/image-compressor.service';

@Component({
  selector: 'app-students',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './students.component.html',
  styleUrl: './students.component.css'
})
export class StudentsComponent implements OnInit {
  students: any[] = [];
  courses: any[] = [];
  paralelos: any[] = [];
  filteredParalelos: any[] = [];
  selectedStudent: any = null;
  apiBase: string = environment.apiUrl.replace('/api', '');
  isLoading: boolean = true;
  
  // Variables para Historial Académico
  showHistoryModal: boolean = false;
  academicHistory: any = null;
  historyLoading: boolean = false;

  // Validación de Cupos
  quotaError: string | null = null;

  // Variables para el Modal de Confirmación
  showDeleteModal: boolean = false;
  studentToDeleteId: number | null = null;

  // Variables de Búsqueda
  searchTerm: string = '';
  searchType: string = 'nombre'; // 'nombre' o 'ci'
  private searchSubject = new Subject<string>();

  // Variables para Gestión de Documentos
  showDocumentsModal: boolean = false;
  studentDocuments: any[] = [];
  docsLoading: boolean = false;
  selectedFile: File | null = null;
  uploading: boolean = false;
  docType: string = 'Carnet de Identidad';
  currentStudentForDocs: any = null;
  docWarning: string = '';

  // Variables para Visor de Documentos (RF 15)
  showPreviewModal: boolean = false;
  activePreviewDoc: any = null;
  activePreviewUrl: SafeResourceUrl | null = null;
  isPreviewPdf: boolean = false;
  isPreviewImage: boolean = false;

  constructor(
    private studentService: StudentService,
    private courseService: CourseService,
    private inscriptionService: InscriptionService,
    private paraleloService: ParaleloService,
    private sanitizer: DomSanitizer,
    private imageCompressor: ImageCompressorService
  ) {}

  ngOnInit() {
    this.loadStudents();
    this.loadCourses();
    this.loadParalelos();

    // Configurar el debounce para la búsqueda
    this.searchSubject.pipe(
      debounceTime(400), // Esperar 400ms después de la última tecla
      distinctUntilChanged() // Solo si el término cambió
    ).subscribe(() => {
      this.onSearch();
    });
  }

  loadStudents() {
    this.isLoading = true;
    this.studentService.getStudents().subscribe({
      next: (data) => {
        this.students = data;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading students', err);
        this.isLoading = false;
      }
    });
  }

  loadCourses() {
    this.courseService.getCourses().subscribe({
      next: (data) => this.courses = data,
      error: (err) => console.error('Error loading courses', err)
    });
  }

  loadParalelos() {
    this.paraleloService.getParalelos().subscribe({
      next: (data) => {
        this.paralelos = data;
        this.updateFilteredParalelos();
      },
      error: (err) => console.error('Error loading paralelos', err)
    });
  }

  updateFilteredParalelos() {
    if (this.selectedStudent && this.selectedStudent.curso_id) {
      const selectedCourseId = Number(this.selectedStudent.curso_id);
      this.filteredParalelos = this.paralelos.filter(p => Number(p.curso_id) === selectedCourseId);
    } else {
      this.filteredParalelos = [];
    }
  }

  viewHistory(studentId: number) {
    this.showHistoryModal = true;
    this.historyLoading = true;
    this.academicHistory = null;

    this.studentService.getStudentHistory(studentId).subscribe({
      next: (data) => {
        this.academicHistory = data;
        this.historyLoading = false;
      },
      error: (err) => {
        console.error('Error loading history', err);
        alert('No se pudo cargar el historial académico');
        this.closeHistoryModal();
      }
    });
  }

  closeHistoryModal() {
    this.showHistoryModal = false;
    this.academicHistory = null;
    this.historyLoading = false;
  }

  adminPhotoFile: File | null = null;
  adminPhotoFileName: string = '';

  onAdminPhotoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      alert('Por favor, selecciona un archivo de imagen válido.');
      return;
    }
    // Comprimir foto de estudiante capturada por cámara (RF16 - T2)
    this.imageCompressor.compressImage(file, 800, 800, 0.82).then(compressed => {
      this.adminPhotoFile = compressed;
      this.adminPhotoFileName = compressed.name;
      console.log(`[RF16] Foto alumno: ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
    });
  }

  editStudent(student: any) {
    this.selectedStudent = { ...student };
    this.quotaError = null;
    this.adminPhotoFile = null;
    this.adminPhotoFileName = '';
    
    if (student.inscripciones && student.inscripciones.length > 0) {
      const ins = student.inscripciones[0];
      this.selectedStudent.inscripcion_id = ins.id;
      this.selectedStudent.estado_inscripcion = ins.estado;
      this.selectedStudent.curso_id = ins.curso_id;
      this.selectedStudent.paralelo_id = ins.paralelo_id || '';
    } else {
      this.selectedStudent.paralelo_id = '';
    }

    this.updateFilteredParalelos();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  onCourseChange() {
    const selectedCourseId = Number(this.selectedStudent.curso_id);
    const course = this.courses.find(c => c.id === selectedCourseId);
    const originalStudent = this.students.find(s => s.id === this.selectedStudent.id);
    const originalCourseId = originalStudent?.inscripciones[0]?.curso_id;

    if (course) {
      if (selectedCourseId !== originalCourseId && course.inscripciones_count >= course.cupo_maximo) {
        this.quotaError = `¡ATENCIÓN! El curso ${course.idioma} - ${course.nivel} está LLENO (${course.inscripciones_count}/${course.cupo_maximo}). Elige otro paralelo.`;
      } else {
        this.quotaError = null;
      }
    }

    // Resetear el paralelo seleccionado al cambiar el curso
    this.selectedStudent.paralelo_id = '';
    this.updateFilteredParalelos();
  }

  updateStudent() {
    if (!this.selectedStudent || this.quotaError) return;

    const formData = new FormData();
    Object.keys(this.selectedStudent).forEach(key => {
      if (key !== 'user' && key !== 'inscripciones' && this.selectedStudent[key] !== null && this.selectedStudent[key] !== undefined) {
        formData.append(key, this.selectedStudent[key]);
      }
    });

    if (this.adminPhotoFile) {
      formData.append('foto', this.adminPhotoFile);
    }

    this.studentService.updateStudentWithPhoto(this.selectedStudent.id, formData).subscribe({
      next: () => {
        this.adminPhotoFile = null;
        this.adminPhotoFileName = '';

        if (this.selectedStudent.inscripcion_id) {
          const insData = {
            estado: this.selectedStudent.estado_inscripcion,
            curso_id: this.selectedStudent.curso_id,
            paralelo_id: this.selectedStudent.paralelo_id || null
          };
          this.inscriptionService.updateInscription(this.selectedStudent.inscripcion_id, insData).subscribe({
            next: () => {
              alert('Datos, Curso y Paralelo de Inscripción actualizados correctamente');
              this.selectedStudent = null;
              this.loadStudents();
              this.loadCourses();
            },
            error: (err: any) => alert('Error al actualizar inscripción: ' + (err.error?.message || err.message))
          });
        } else {
          alert('Estudiante actualizado correctamente');
          this.selectedStudent = null;
          this.loadStudents();
        }
      },
      error: (err: any) => alert('Error al actualizar estudiante: ' + (err.error?.message || err.message || err))
    });
  }

  openDeleteModal(id: number) {
    this.studentToDeleteId = id;
    this.showDeleteModal = true;
  }

  closeDeleteModal() {
    this.showDeleteModal = false;
    this.studentToDeleteId = null;
  }

  executeDelete() {
    if (this.studentToDeleteId) {
      this.studentService.deleteStudent(this.studentToDeleteId).subscribe({
        next: () => {
          this.loadStudents();
          this.loadCourses(); //se recarga la lista de cursos para que se actualice el cupo maximo
          this.closeDeleteModal();
        },
        error: (err) => {
          const msg = err.error?.message || err.message || 'Error desconocido';
          alert('No se pudo eliminar: ' + msg);
          this.closeDeleteModal();
        }
      });
    }
  }

  cancelEdit() {
    this.selectedStudent = null;
    this.quotaError = null;
  }

  onSearch() {
    // Si no hay término, recargar todos
    if (!this.searchTerm || this.searchTerm.trim() === '') {
      this.loadStudents();
      return;
    }

    this.isLoading = true;
    this.studentService.searchStudents(this.searchTerm, this.searchType).subscribe({
      next: (data) => {
        this.students = data;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error searching students', err);
        this.isLoading = false;
      }
    });
  }

  // Método que se dispara en cada tecla
  onSearchInput() {
    this.searchSubject.next(this.searchTerm);
  }

  // --- GESTIÓN DE DOCUMENTOS ---
  openDocumentsModal(student: any) {
    this.currentStudentForDocs = student;
    this.showDocumentsModal = true;
    this.loadStudentDocuments();
  }

  closeDocumentsModal() {
    this.showDocumentsModal = false;
    this.currentStudentForDocs = null;
    this.studentDocuments = [];
    this.selectedFile = null;
    this.docWarning = '';
  }

  loadStudentDocuments() {
    if (!this.currentStudentForDocs) return;
    this.docsLoading = true;
    this.studentService.getDocuments(this.currentStudentForDocs.id).subscribe({
      next: (docs) => {
        this.studentDocuments = docs;
        this.docsLoading = false;
      },
      error: (err) => {
        console.error('Error cargando documentos', err);
        this.docsLoading = false;
      }
    });
  }

  onFileSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    // Validación de extensión
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const extension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(extension)) {
      alert('Solo se permiten archivos PDF, JPG o PNG');
      event.target.value = '';
      return;
    }

    // Validación de peso (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('El archivo excede el límite de 5MB');
      event.target.value = '';
      return;
    }

    // Reiniciar advertencia
    this.docWarning = '';

    // Validación inteligente de coherencia de nombre de archivo (Soft Warning)
    const nameLower = file.name.toLowerCase();
    const typeLower = this.docType.toLowerCase();

    if (typeLower === 'carnet de identidad') {
      if (nameLower.includes('certificado') || nameLower.includes('nacimiento') || nameLower.includes('titulo') || nameLower.includes('bachiller') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante')) {
        this.docWarning = `El archivo "${file.name}" parece ser un Certificado, Título o Depósito, y no un Carnet de Identidad. Asegúrate de estar subiendo el archivo correcto.`;
      }
    }

    if (typeLower === 'título de bachiller') {
      if (nameLower.includes('carnet') || nameLower.includes('ci') || nameLower.includes('nacimiento') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante') || nameLower.includes('certificado')) {
        this.docWarning = `El archivo "${file.name}" parece ser una Identificación, Certificado o Depósito, y no un Título de Bachiller. Asegúrate de estar subiendo el archivo correcto.`;
      }
    }

    if (typeLower === 'certificado de nacimiento') {
      if (nameLower.includes('carnet') || nameLower.includes('ci') || nameLower.includes('titulo') || nameLower.includes('bachiller') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante')) {
        this.docWarning = `El archivo "${file.name}" parece ser una Identificación, Título o Depósito, y no un Certificado de Nacimiento. Asegúrate de estar subiendo el archivo correcto.`;
      }
    }

    if (typeLower === 'boleta de depósito') {
      if (nameLower.includes('carnet') || nameLower.includes('ci') || nameLower.includes('titulo') || nameLower.includes('bachiller') || nameLower.includes('nacimiento')) {
        this.docWarning = `El archivo "${file.name}" parece ser una Identificación, Título o Certificado, y no un Comprobante de Depósito. Asegúrate de estar subiendo el archivo correcto.`;
      }
    }

    this.selectedFile = file;
  }

  uploadFile() {
    if (!this.selectedFile || !this.currentStudentForDocs) return;

    this.uploading = true;
    const formData = new FormData();
    formData.append('archivo', this.selectedFile);
    formData.append('estudiante_id', this.currentStudentForDocs.id.toString());
    formData.append('tipo_documento', this.docType);

    this.studentService.uploadDocument(formData).subscribe({
      next: () => {
        alert('Documento subido correctamente');
        this.selectedFile = null;
        this.uploading = false;
        this.docWarning = ''; // Limpiar advertencia en éxito
        this.loadStudentDocuments();
      },
      error: (err) => {
        console.error('Error subiendo archivo', err);
        alert('Error al subir: ' + (err.error?.message || err.message));
        this.uploading = false;
      }
    });
  }

  removeDocument(id: number) {
    if (!confirm('¿Estás seguro de eliminar este documento?')) return;

    this.studentService.deleteDocument(id).subscribe({
      next: () => {
        this.loadStudentDocuments();
      },
      error: (err) => alert('No se pudo eliminar el documento')
    });
  }

  downloadCertificate(student: any) {
    if (!student.inscripciones || student.inscripciones.length === 0) {
      alert('El estudiante no tiene inscripciones activas');
      return;
    }

    const inscriptionId = student.inscripciones[0].id;
    
    this.inscriptionService.downloadCertificate(inscriptionId).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `constancia_${student.ci}.pdf`;
        link.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        console.error('Error al descargar el PDF', err);
        alert('No se pudo generar la constancia en este momento');
      }
    });
  }

  previewDocument(doc: any) {
    this.activePreviewDoc = doc;
    const apiBase = environment.apiUrl.replace('/api', '');
    const url = apiBase + '/storage/documentos/' + doc.ruta_archivo;
    this.activePreviewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);

    const ext = doc.ruta_archivo.split('.').pop().toLowerCase();
    this.isPreviewPdf = ext === 'pdf';
    this.isPreviewImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);

    this.showPreviewModal = true;
  }

  closePreviewModal() {
    this.showPreviewModal = false;
    this.activePreviewDoc = null;
    this.activePreviewUrl = null;
    this.isPreviewPdf = false;
    this.isPreviewImage = false;
  }
}



