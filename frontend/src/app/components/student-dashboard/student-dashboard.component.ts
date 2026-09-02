import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { AuthService } from '../../services/auth.service';
import { StudentService } from '../../services/student.service';
import { InscriptionService } from '../../services/inscription.service';
import { downloadFile } from '../../utils/file-downloader';
import { ImageCompressorService } from '../../services/image-compressor.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-student-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './student-dashboard.component.html',
  styleUrl: './student-dashboard.component.css'
})
export class StudentDashboardComponent implements OnInit {
  apiUrl = environment.apiUrl;
  user: any = null;
  student: any = null;
  isLoading: boolean = true;
  activeTab: string = 'profile'; // 'profile', 'history', 'documents'

  // Mobile menu sidebar drawer
  isMobileMenuOpen: boolean = false;
  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  onImgError(event: any) {
    if (event && event.target) {
      event.target.style.display = 'none';
      if (event.target.nextElementSibling) {
        event.target.nextElementSibling.style.display = 'flex';
      }
    }
  }

  // Digital Documents Upload
  studentDocuments: any[] = [];
  docsLoading: boolean = false;
  selectedFile: File | null = null;
  uploading: boolean = false;
  docType: string = 'Carnet de Identidad';
  docWarning: string = '';

  // 4x4 Photo Upload
  photoFile: File | null = null;
  photoFileName: string = '';

  // Messages
  successMessage: string = '';
  errorMessage: string = '';

  // Variables para Visor de Documentos (RF 15)
  showPreviewModal: boolean = false;
  activePreviewDoc: any = null;
  activePreviewUrl: SafeResourceUrl | null = null;
  isPreviewPdf: boolean = false;
  isPreviewImage: boolean = false;

  constructor(
    private authService: AuthService,
    private studentService: StudentService,
    private inscriptionService: InscriptionService,
    private router: Router,
    private sanitizer: DomSanitizer,
    private imageCompressor: ImageCompressorService
  ) {}

  ngOnInit() {
    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }

    this.user = this.authService.getUser();
    if (this.user.rol === 'admin') { this.router.navigate(['/admin']); return; }
    if (this.user.rol === 'docente') { this.router.navigate(['/docente-dashboard']); return; }

    this.biometricEnabled = this.authService.isBiometricEnabled();
    if (this.user && this.user.estudiante_id) {
      this.loadStudentProfile(this.user.estudiante_id);
    } else {
      this.errorMessage = 'No se encontró un perfil de estudiante asociado a esta cuenta.';
      this.isLoading = false;
    }
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

  loadStudentProfile(id: number) {
    this.isLoading = true;
    this.studentService.getStudent(id).subscribe({
      next: (data) => {
        this.student = data;
        this.isLoading = false;
        this.loadStudentDocuments();
      },
      error: (err) => {
        console.error('Error loading student profile', err);
        this.errorMessage = 'No se pudo cargar tu información de perfil. Por favor, reintenta.';
        this.isLoading = false;
      }
    });
  }

  loadStudentDocuments() {
    if (!this.student) return;
    this.docsLoading = true;
    this.studentService.getDocuments(this.student.id).subscribe({
      next: (docs) => {
        this.studentDocuments = docs;
        this.docsLoading = false;
      },
      error: (err) => {
        console.error('Error loading documents', err);
        this.docsLoading = false;
      }
    });
  }

  onPhotoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      alert('Por favor, selecciona una imagen de perfil válida.');
      return;
    }
    // Comprimir foto 4x4 capturada por cámara (RF16 - T2)
    this.imageCompressor.compressImage(file, 800, 800, 0.82).then(compressed => {
      this.photoFile = compressed;
      this.photoFileName = compressed.name;
      console.log(`[RF16] Foto perfil: ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
    });
  }

  uploadPhoto() {
    if (!this.photoFile || !this.student) return;

    this.uploading = true;
    const formData = new FormData();
    // Reutilizar update student con foto
    formData.append('foto', this.photoFile);
    formData.append('nombres', this.student.nombres);
    formData.append('apellidos', this.student.apellidos);

    this.studentService.updateStudentWithPhoto(this.student.id, formData).subscribe({
      next: (res: any) => {
        alert('Fotografía 4x4 actualizada con éxito');
        this.photoFile = null;
        this.photoFileName = '';
        this.uploading = false;
        this.loadStudentProfile(this.student.id);
      },
      error: (err) => {
        console.error('Error subiendo foto', err);
        alert('Error al actualizar la fotografía: ' + (err.error?.message || err.message));
        this.uploading = false;
      }
    });
  }

  onFileSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
    if (!allowedExtensions.includes(extension)) {
      alert('Solo se permiten archivos PDF, JPG o PNG');
      event.target.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      alert('El archivo excede el límite de 5MB');
      event.target.value = '';
      return;
    }

    this.docWarning = '';
    const nameLower = file.name.toLowerCase();
    const typeLower = this.docType.toLowerCase();

    if (typeLower === 'carnet de identidad' && (nameLower.includes('certificado') || nameLower.includes('titulo') || nameLower.includes('deposito'))) {
      this.docWarning = `El archivo "${file.name}" parece no ser un Carnet de Identidad. Confirma que es el correcto.`;
    }

    // Comprimir imágenes; PDFs pasan sin cambios (RF16 - T2)
    if (file.type.startsWith('image/')) {
      this.imageCompressor.compressImage(file, 1200, 1200, 0.78).then(compressed => {
        this.selectedFile = compressed;
        console.log(`[RF16] Doc: ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
      });
    } else {
      this.selectedFile = file;
    }
  }

  uploadFile() {
    if (!this.selectedFile || !this.student) return;

    this.uploading = true;
    const formData = new FormData();
    formData.append('archivo', this.selectedFile);
    formData.append('estudiante_id', this.student.id.toString());
    formData.append('tipo_documento', this.docType);

    this.studentService.uploadDocument(formData).subscribe({
      next: () => {
        alert('Documento subido correctamente');
        this.selectedFile = null;
        this.uploading = false;
        this.docWarning = '';
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

    if (!confirm('¿Estás seguro de eliminar este documento de tu expediente?')) return;

    this.studentService.deleteDocument(docId).subscribe({
      next: () => {
        this.loadStudentDocuments();
      },
      error: (err) => alert('No se pudo eliminar el documento.')
    });
  }

  downloadCertificate() {
    if (!this.student || !this.student.inscripciones || this.student.inscripciones.length === 0) {
      alert('No cuentas con inscripciones activas para emitir constancia.');
      return;
    }

    const inscriptionId = this.student.inscripciones[0].id_inscripcion || this.student.inscripciones[0].id;
    this.inscriptionService.downloadCertificate(inscriptionId).subscribe({
      next: (blob) => {
        downloadFile(blob, `constancia_${this.student.ci}.pdf`);
      },
      error: (err) => {
        console.error('Error generating PDF', err);
        alert('No se pudo descargar la constancia académica en este momento.');
      }
    });
  }

  onLogout() {
    this.authService.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: () => this.router.navigate(['/login'])
    });
  }

  getPhotoUrl(url: string | null): string {
    if (!url) return '';
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

  previewDocument(doc: any) {
    this.activePreviewDoc = doc;
    const apiBase = (this.apiUrl || environment.apiUrl).replace(/\/api\/?$/, '');
    let path = doc.ruta_archivo || doc.archivo || '';

    let url = '';
    if (path.startsWith('data:')) {
      url = path;
    } else if (path.startsWith('http://') || path.startsWith('https://')) {
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

    const isDataPdf = path.startsWith('data:application/pdf');
    const isDataImg = path.startsWith('data:image');
    const ext = path.split('.').pop().toLowerCase();

    this.isPreviewPdf = isDataPdf || ext === 'pdf';
    this.isPreviewImage = isDataImg || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);

    this.showPreviewModal = true;
  }

  closePreviewModal() {
    this.showPreviewModal = false;
    this.activePreviewDoc = null;
    this.activePreviewUrl = null;
    this.isPreviewPdf = false;
    this.isPreviewImage = false;
  }

  puedeSubirDocumentos(): boolean {
    if (!this.student || !this.student.documentos_habilitados_hasta) {
      return false;
    }
    const hasta = new Date(this.student.documentos_habilitados_hasta);
    const ahora = new Date();
    return ahora <= hasta;
  }

  obtenerFechaLimiteFormatted(): string {
    if (!this.student || !this.student.documentos_habilitados_hasta) {
      return '';
    }
    const date = new Date(this.student.documentos_habilitados_hasta);
    return date.toLocaleString('es-BO', { 
      day: '2-digit', 
      month: '2-digit', 
      year: 'numeric', 
      hour: '2-digit', 
      minute: '2-digit' 
    });
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
