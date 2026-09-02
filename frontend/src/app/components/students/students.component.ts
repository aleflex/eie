import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { environment } from '../../../environments/environment';
import { StudentService } from '../../services/student.service';
import { CourseService } from '../../services/course.service';
import { InscriptionService } from '../../services/inscription.service';
import { downloadFile } from '../../utils/file-downloader';
import { ParaleloService } from '../../services/paralelo.service';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ImageCompressorService } from '../../services/image-compressor.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-students',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './students.component.html',
  styleUrl: './students.component.css'
})
export class StudentsComponent implements OnInit {
  students: any[] = [];
  allStudents: any[] = [];
  courses: any[] = [];
  paralelos: any[] = [];
  filteredParalelos: any[] = [];
  selectedStudent: any = null;
  apiBase: string = environment.apiUrl.replace('/api', '');
  isLoading: boolean = true;
  isSidebarCollapsed: boolean = false;
  isMobileMenuOpen: boolean = false;
  expandedStudentIds: Set<number> = new Set<number>();

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

  toggleRow(studentId: number) {
    if (this.expandedStudentIds.has(studentId)) {
      this.expandedStudentIds.delete(studentId);
    } else {
      this.expandedStudentIds.add(studentId);
    }
  }

  isRowExpanded(studentId: number): boolean {
    return this.expandedStudentIds.has(studentId);
  }

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
    private imageCompressor: ImageCompressorService,
    private http: HttpClient,
    private authService: AuthService
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
        this.allStudents = data || [];
        this.students = data || [];
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

  carnetMilitarNum: string = '';
  carnetMilitarSerie: string = '';

  onlyNumbers(event: KeyboardEvent) {
    const charCode = event.which ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      event.preventDefault();
    }
  }

  updateCarnetMilitarFull() {
    if (!this.selectedStudent) return;
    const num = (this.carnetMilitarNum || '').trim();
    const serie = (this.carnetMilitarSerie || '').trim();
    if (num && serie) {
      this.selectedStudent.carnet_militar = `${num}-${serie}`;
    } else if (num) {
      this.selectedStudent.carnet_militar = num;
    } else {
      this.selectedStudent.carnet_militar = '';
    }
  }

  autoGenerateCossmil() {
    if (!this.selectedStudent) return;
    const fecha = this.selectedStudent.fecha_nacimiento;
    const nombres = (this.selectedStudent.nombres || '').trim();
    const apellidos = (this.selectedStudent.apellidos || '').trim().split(/\s+/);

    let numPart = '';
    if (fecha) {
      const parts = fecha.split('-');
      if (parts.length === 3) {
        numPart = parts[0].substring(2, 4) + parts[1] + parts[2];
      }
    }

    let letPart = '';
    if (apellidos.length > 0 && apellidos[0]) {
      letPart += apellidos[0].charAt(0).toUpperCase();
    }
    if (apellidos.length > 1 && apellidos[1]) {
      letPart += apellidos[1].charAt(0).toUpperCase();
    } else if (apellidos.length > 0 && apellidos[0].length > 1) {
      letPart += apellidos[0].charAt(1).toUpperCase();
    }
    if (nombres.length > 0) {
      letPart += nombres.charAt(0).toUpperCase();
    }

    if (numPart || letPart) {
      this.selectedStudent.carnet_cossmil = (numPart + letPart).toUpperCase();
    }
  }

  editStudent(student: any) {
    this.selectedStudent = { ...student };
    this.quotaError = null;
    this.adminPhotoFile = null;
    this.adminPhotoFileName = '';

    // Cargar nombres, apellidos, ci, expedido desde el objeto user si no están directamente
    if (this.selectedStudent.user) {
      if (!this.selectedStudent.nombres) this.selectedStudent.nombres = this.selectedStudent.user.nombres || '';
      if (!this.selectedStudent.apellidos) this.selectedStudent.apellidos = this.selectedStudent.user.apellidos || '';
      if (!this.selectedStudent.ci) this.selectedStudent.ci = this.selectedStudent.user.ci || '';
      if (!this.selectedStudent.expedido) this.selectedStudent.expedido = this.selectedStudent.user.expedido || '';
    }

    // Auto-separar CI antiguo que incluye departamento (ej: '6190284 LP' -> ci='6190284', expedido='LP')
    if (this.selectedStudent.ci && /^[0-9]{7,8}\s+[A-Za-z]{2}$/.test((this.selectedStudent.ci + '').trim())) {
      const ciParts = (this.selectedStudent.ci + '').trim().split(/\s+/);
      this.selectedStudent.ci = ciParts[0];
      if (!this.selectedStudent.expedido) {
        this.selectedStudent.expedido = ciParts[1].toUpperCase();
      }
    }

    // Auto-separar CI Tutor antiguo que incluye departamento
    if (this.selectedStudent.ci_tutor && /^[0-9]{7,8}\s+[A-Za-z]{2}$/.test((this.selectedStudent.ci_tutor + '').trim())) {
      const tutorParts = (this.selectedStudent.ci_tutor + '').trim().split(/\s+/);
      this.selectedStudent.ci_tutor = tutorParts[0];
    }

    if (this.selectedStudent.carnet_militar) {
      if (this.selectedStudent.carnet_militar.includes('-')) {
        const parts = this.selectedStudent.carnet_militar.split('-');
        this.carnetMilitarNum = parts[0];
        this.carnetMilitarSerie = parts[1];
      } else {
        this.carnetMilitarNum = this.selectedStudent.carnet_militar;
        this.carnetMilitarSerie = '';
      }
    } else {
      this.carnetMilitarNum = '';
      this.carnetMilitarSerie = '';
    }

    if (this.selectedStudent.documentos_habilitados_hasta) {
      this.selectedStudent.documentos_habilitados_hasta = this.selectedStudent.documentos_habilitados_hasta.replace(' ', 'T').substring(0, 16);
    }

    if (student.inscripciones && student.inscripciones.length > 0) {
      const ins = student.inscripciones[0];
      this.selectedStudent.inscripcion_id = ins.id_inscripcion || ins.id;
      this.selectedStudent.estado_inscripcion = (ins.estado || 'activo').toLowerCase();
      this.selectedStudent.curso_id = ins.id_curso || ins.curso_id || (ins.curso ? (ins.curso.id_curso || ins.curso.id) : '');
      this.selectedStudent.paralelo_id = ins.id_paralelo || ins.paralelo_id || '';
    } else {
      this.selectedStudent.inscripcion_id = null;
      this.selectedStudent.estado_inscripcion = 'activo';
      this.selectedStudent.curso_id = '';
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

    if (this.selectedStudent.ci) {
      const cleanCi = (this.selectedStudent.ci + '').trim();
      // Permitir formato puro (7-8 dígitos) o formato antiguo (dígitos + espacio + depto)
      if (!/^[0-9]{7,8}(\s*[A-Za-z]{2})?$/.test(cleanCi)) {
        alert('El C.I. del estudiante debe contener 7 u 8 dígitos numéricos.');
        return;
      }
    }

    if (this.selectedStudent.ci_tutor) {
      const cleanCiTutor = (this.selectedStudent.ci_tutor + '').trim();
      // Permitir formato puro (7-8 dígitos) o formato antiguo (dígitos + espacio + depto)
      if (cleanCiTutor !== '' && !/^[0-9]{7,8}(\s*[A-Za-z]{2})?$/.test(cleanCiTutor)) {
        alert('El C.I. del tutor debe contener 7 u 8 dígitos numéricos.');
        return;
      }
    }

    const formData = new FormData();
    Object.keys(this.selectedStudent).forEach(key => {
      const val = this.selectedStudent[key];
      if (
        key !== 'user' &&
        key !== 'inscripciones' &&
        key !== 'gradoRel' &&
        key !== 'armaRel' &&
        key !== 'grado' &&
        key !== 'arma' &&
        key !== 'foto_4x4_url' &&
        val !== null &&
        val !== undefined &&
        typeof val !== 'object'
      ) {
        formData.append(key, val);
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
          // Actualizar inscripción existente
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
        } else if (this.selectedStudent.curso_id) {
          // Crear nueva inscripción para estudiante sin curso asignado
          const newInsData = {
            id_estudiante: this.selectedStudent.id,
            id_curso: this.selectedStudent.curso_id,
            id_paralelo: this.selectedStudent.paralelo_id || null,
            estado: this.selectedStudent.estado_inscripcion || 'activo',
            fecha_registro: new Date().toISOString().split('T')[0]
          };
          this.http.post(`${environment.apiUrl}/api/inscripciones/admin-assign`, newInsData).subscribe({
            next: () => {
              alert('Estudiante actualizado y curso asignado correctamente');
              this.selectedStudent = null;
              this.loadStudents();
              this.loadCourses();
            },
            error: (err: any) => alert('Error al asignar curso: ' + (err.error?.message || err.message))
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

  // Variables para Baja Lógica y Falta Grave con Pruebas
  selectedStudentForBaja: any = null;
  studentHasGrades: boolean = false;
  motivoBaja: string = '';
  archivoPruebaBaja: File | null = null;
  archivoPruebaBajaName: string = '';
  bajaLoading: boolean = false;

  hasStudentGrades(student: any): boolean {
    if (!student || !student.inscripciones || student.inscripciones.length === 0) return false;
    return student.inscripciones.some((ins: any) => ins.notas && ins.notas.length > 0);
  }

  isStudentDeBaja(student: any): boolean {
    if (!student) return false;
    const inscrip = student.inscripciones && student.inscripciones.length > 0 ? student.inscripciones[0] : null;
    const estadoInscrip = (inscrip?.estado || '').toLowerCase();
    const estadoUser = (student.user?.estado || '').toUpperCase();
    return estadoInscrip === 'baja' || estadoInscrip === 'retirado' || estadoUser === 'INACTIVO';
  }

  openDeleteModal(studentOrId: any) {
    let student = null;
    if (typeof studentOrId === 'object') {
      student = studentOrId;
      this.studentToDeleteId = student.id || student.id_estudiante;
    } else {
      this.studentToDeleteId = studentOrId;
      student = this.allStudents.find(s => s.id === studentOrId || s.id_estudiante === studentOrId);
    }

    this.selectedStudentForBaja = student;
    this.studentHasGrades = this.hasStudentGrades(student);
    this.motivoBaja = '';
    this.archivoPruebaBaja = null;
    this.archivoPruebaBajaName = '';
    this.bajaLoading = false;
    this.showDeleteModal = true;
  }

  onPruebaBajaSelected(event: any) {
    const file = event.target.files[0];
    if (file) {
      this.archivoPruebaBaja = file;
      this.archivoPruebaBajaName = file.name;
    }
  }

  closeDeleteModal() {
    this.showDeleteModal = false;
    this.studentToDeleteId = null;
    this.selectedStudentForBaja = null;
    this.studentHasGrades = false;
    this.motivoBaja = '';
    this.archivoPruebaBaja = null;
    this.archivoPruebaBajaName = '';
    this.bajaLoading = false;
  }

  executeDelete() {
    if (!this.studentToDeleteId) return;

    if (this.studentHasGrades) {
      if (!this.motivoBaja || this.motivoBaja.trim().length < 5) {
        alert('Este estudiante tiene notas registradas en el sistema. Es obligatorio detallar el motivo de la infracción grave al reglamento institucional.');
        return;
      }
    }

    this.bajaLoading = true;
    const formData = new FormData();
    formData.append('motivo_baja', this.motivoBaja || 'Baja solicitada por administración');
    formData.append('forzar_con_pruebas', this.studentHasGrades ? '1' : '0');
    if (this.archivoPruebaBaja) {
      formData.append('archivo_prueba', this.archivoPruebaBaja);
    }

    this.studentService.darDeBajaEstudiante(this.studentToDeleteId, formData).subscribe({
      next: () => {
        this.bajaLoading = false;
        alert('El estudiante ha sido dado de baja exitosamente. Su registro e historial permanecen protegidos.');
        this.loadStudents();
        this.loadCourses();
        this.closeDeleteModal();
      },
      error: (err) => {
        this.bajaLoading = false;
        const msg = err.error?.message || err.message || 'Error desconocido';
        alert('No se pudo procesar la baja: ' + msg);
      }
    });
  }

  rehabilitarStudent(student: any) {
    const id = student.id || student.id_estudiante;
    const nombre = `${student.nombres || student.user?.nombres || ''} ${student.apellidos || student.user?.apellidos || ''}`.trim();
    if (!confirm(`¿Deseas rehabilitar al estudiante "${nombre}" para que vuelva a estar habilitado en el sistema?`)) {
      return;
    }

    this.studentService.rehabilitarEstudiante(id).subscribe({
      next: () => {
        alert(`¡Estudiante "${nombre}" rehabilitado exitosamente!`);
        this.loadStudents();
        this.loadCourses();
      },
      error: (err) => {
        const msg = err.error?.message || err.message || 'Error desconocido';
        alert('No se pudo rehabilitar al estudiante: ' + msg);
      }
    });
  }

  cancelEdit() {
    this.selectedStudent = null;
    this.quotaError = null;
  }

  onSearch() {
    if (!this.searchTerm || this.searchTerm.trim() === '') {
      this.students = [...this.allStudents];
      return;
    }

    const term = this.searchTerm.trim().toLowerCase();
    const wordBoundaryRegex = new RegExp(`\\b${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'i');

    this.students = this.allStudents.filter(st => {
      const nombres = st.nombres || st.user?.nombres || '';
      const apellidos = st.apellidos || st.user?.apellidos || '';
      const fullName = `${nombres} ${apellidos}`.trim();
      const ci = `${st.ci || st.user?.ci || ''}`;

      if (this.searchType === 'ci') {
        return ci.toLowerCase().includes(term);
      }

      return wordBoundaryRegex.test(nombres) ||
             wordBoundaryRegex.test(apellidos) ||
             wordBoundaryRegex.test(fullName) ||
             ci.toLowerCase().includes(term);
    });
  }

  // Método que se dispara en cada tecla
  onSearchInput() {
    this.onSearch();
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

  removeDocument(doc: any) {
    const docId = typeof doc === 'object' ? (doc.id_documento || doc.id_expediente || doc.id) : doc;
    if (!docId) {
      alert('No se pudo identificar el código único del documento.');
      return;
    }

    if (!confirm('¿Estás seguro de eliminar este documento?')) return;

    this.studentService.deleteDocument(docId).subscribe({
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

    const inscriptionId = student.inscripciones[0].id_inscripcion || student.inscripciones[0].id;

    this.inscriptionService.downloadCertificate(inscriptionId).subscribe({
      next: (blob) => {
        downloadFile(blob, `constancia_${student.ci}.pdf`);
      },
      error: (err) => {
        console.error('Error al descargar el PDF', err);
        alert('No se pudo generar la constancia en este momento');
      }
    });
  }

  previewDocument(doc: any) {
    this.activePreviewDoc = doc;
    const apiBase = environment.apiUrl.replace(/\/api\/?$/, '');
    let path = doc.ruta_archivo || doc.archivo || '';

    let url = '';
    if (path.startsWith('http://') || path.startsWith('https://')) {
      url = path;
    } else if (path.startsWith('/storage/documentos/')) {
      url = apiBase + path;
    } else if (path.startsWith('/storage/')) {
      url = apiBase + path;
    } else if (path.startsWith('storage/')) {
      url = apiBase + '/' + path;
    } else {
      url = apiBase + '/storage/documentos/' + path.replace(/^\/+/, '');
    }

    this.activePreviewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);

    const ext = path.split('.').pop().toLowerCase();
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

  onImageError(event: any) {
    if (event && event.target) {
      event.target.style.display = 'none';
      if (event.target.nextElementSibling) {
        event.target.nextElementSibling.style.display = 'flex';
      }
    }
  }

  getPhotoUrl(url: string | null): string {
    if (!url) {
      return '';
    }
    const cleanUrl = url.trim().replace(/[\r\n\s]+/g, '');
    if (cleanUrl.startsWith('data:')) {
      if (!cleanUrl.includes(';base64,') || cleanUrl.length < 50) {
        return '';
      }
      return cleanUrl;
    }
    if (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://')) {
      return cleanUrl;
    }
    const apiUrl = environment.apiUrl.replace(/\/+$/, '');
    if (cleanUrl.startsWith('/storage/')) {
      return apiUrl + cleanUrl;
    }
    if (cleanUrl.startsWith('storage/')) {
      return apiUrl + '/' + cleanUrl;
    }
    return apiUrl + '/storage/' + cleanUrl.replace(/^\/+/, '');
  }

  formatDocSize(peso: any): string {
    if (peso === null || peso === undefined || isNaN(Number(peso)) || Number(peso) <= 0) {
      return 'Archivo adjunto';
    }
    const bytes = Number(peso);
    if (bytes >= 1048576) {
      return (bytes / 1048576).toFixed(1) + ' MB';
    }
    return (bytes / 1024).toFixed(1) + ' KB';
  }
}



