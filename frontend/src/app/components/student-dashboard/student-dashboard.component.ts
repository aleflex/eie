import { Component, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl, Title } from '@angular/platform-browser';
import { Subscription } from 'rxjs';
import { AuthService } from '../../services/auth.service';
import { StudentService } from '../../services/student.service';
import { InscriptionService } from '../../services/inscription.service';
import { WebSocketService } from '../../services/websocket.service';
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
export class StudentDashboardComponent implements OnInit, OnDestroy {
  apiUrl = environment.apiUrl;
  user: any = null;
  student: any = null;
  isLoading: boolean = true;
  isRefreshing: boolean = false;
  activeTab: string = 'profile'; // 'profile', 'history', 'documents'

  private wsSubs: Subscription[] = [];
  private userSub: Subscription | null = null;

  // Mobile menu sidebar drawer
  isMobileMenuOpen: boolean = false;
  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  setTab(tab: string) {
    this.activeTab = tab;
    this.closeMobileMenu();
    if (this.user && this.user.estudiante_id) {
      this.loadStudentProfile(this.user.estudiante_id, true);
    }
  }

  refreshData() {
    if (this.user && this.user.estudiante_id) {
      this.isRefreshing = true;
      this.loadStudentProfile(this.user.estudiante_id, true, () => {
        this.isRefreshing = false;
      });
      this.authService.cargarPerfilActualizado().subscribe();
    }
  }

  onImgError(event: any) {
    if (event && event.target) {
      const src = event.target.src || '';
      if (!src.includes('default-avatar.svg') && !src.includes('default-avatar.png')) {
        event.target.src = '/assets/default-avatar.svg';
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
  rawPreviewUrl: string = '';
  isPreviewPdf: boolean = false;
  isPreviewImage: boolean = false;
  previewError: boolean = false;

  constructor(
    private authService: AuthService,
    private studentService: StudentService,
    private inscriptionService: InscriptionService,
    private wsService: WebSocketService,
    private router: Router,
    private sanitizer: DomSanitizer,
    private titleService: Title,
    private imageCompressor: ImageCompressorService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.titleService.setTitle('Estudiante - Escuela de Idiomas del Ejército');

    if (!this.authService.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }

    this.user = this.authService.getUser();
    if (this.user?.rol === 'admin') { this.router.navigate(['/admin']); return; }
    if (this.user?.rol === 'docente') { this.router.navigate(['/docente-dashboard']); return; }

    // Pre-cargar datos del estudiante de inmediato si ya existen en la sesión del usuario
    if (this.user?.estudiante) {
      this.student = this.user.estudiante;
      try {
        this.updateDocTypeDefault();
      } catch (e) {}
      this.isLoading = false;
    }

    this.biometricEnabled = this.authService.isBiometricEnabled();

    // Suscripción reactiva para mantener foto y perfil sincronizados en tiempo real entre Web y APK
    this.userSub = this.authService.usuario$.subscribe(u => {
      if (u) {
        this.user = u;
        if (this.student) {
          if (u.foto_url) this.student.foto_4x4_url = u.foto_url;
          if (u.nombres) this.student.nombres = u.nombres;
          if (u.apellidos) this.student.apellidos = u.apellidos;
        } else if (u.estudiante) {
          this.student = u.estudiante;
          this.isLoading = false;
        }
        if (u.estudiante_id && !this.uploading) {
          this.loadStudentProfile(u.estudiante_id, true);
        }
      }
    });

    if (this.user && this.user.estudiante_id) {
      this.loadStudentProfile(this.user.estudiante_id);
    } else if (!this.student) {
      this.errorMessage = 'No se encontró un perfil de estudiante asociado a esta cuenta.';
      this.isLoading = false;
    }

    // Sincronización instantánea mediante WebSockets (cero llamadas HTTP repetidas)
    this.wsSubs.push(
      this.wsService.onStudentUpdated().subscribe((updatedStudent) => {
        if (!updatedStudent) return;
        const currentId = this.user?.estudiante_id || this.student?.id_estudiante || this.student?.id;
        const updatedId = updatedStudent.id || updatedStudent.id_estudiante;
        if (currentId && updatedId && currentId == updatedId) {
          if (!this.uploading) {
            this.loadStudentProfile(currentId, true);
            if (this.activeTab === 'documents') {
              this.loadStudentDocuments(true);
            }
          }
        }
      }),
      this.wsService.onProfileUpdated().subscribe(() => {
        if (this.user?.estudiante_id && !this.uploading) {
          this.loadStudentProfile(this.user.estudiante_id, true);
        }
      }),
      this.wsService.onInscriptionUpdated().subscribe((ins) => {
        if (ins && this.user?.estudiante_id && !this.uploading) {
          this.loadStudentProfile(this.user.estudiante_id, true);
        }
      })
    );
  }

  ngOnDestroy() {
    this.stopCamera();
    this.wsSubs.forEach(s => s.unsubscribe());
    this.wsSubs = [];
    if (this.userSub) {
      this.userSub.unsubscribe();
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

  loadStudentProfile(id: number, silent: boolean = false, callback?: () => void) {
    if (!silent && !this.student) {
      this.isLoading = true;
      this.cdr.detectChanges();
    }
    this.studentService.getStudent(id).subscribe({
      next: (data) => {
        if (!data.foto_4x4_url && (this.user?.foto_url || this.student?.foto_4x4_url)) {
          data.foto_4x4_url = this.user?.foto_url || this.student?.foto_4x4_url;
        }
        this.student = data;
        if (data.foto_4x4_url && this.user) {
          this.user.foto_url = data.foto_4x4_url;
        }
        try {
          this.updateDocTypeDefault();
        } catch (e) {
          console.warn('Advertencia al configurar tipos de documentos:', e);
        }
        this.isLoading = false;
        this.cdr.detectChanges();
        if (!silent) {
          this.loadStudentDocuments();
        }
        if (callback) callback();
      },
      error: (err) => {
        console.error('Error loading student profile', err);
        this.isLoading = false;
        if (!silent) {
          this.errorMessage = 'No se pudo cargar tu información de perfil. Por favor, reintenta.';
        }
        this.cdr.detectChanges();
        if (callback) callback();
      }
    });
  }

  getCategoriaEstudiante(): string {
    const tipo = String(this.student?.tipo_usuario || '').toLowerCase();
    const rawGrado = this.student?.grado_academico || (typeof this.student?.grado === 'object' ? (this.student?.grado?.nombre || this.student?.grado?.nombre_grado) : this.student?.grado) || '';
    const grado = String(rawGrado || '').toLowerCase();
    const carnetMilitar = this.student?.carnet_militar;
    const carnetCossmil = this.student?.carnet_cossmil;

    if (tipo === 'militar' || !!carnetMilitar || (grado && !['civil', 'premilitar', 'bachiller'].includes(grado))) {
      return 'militar';
    }
    if (tipo === 'hijo_militar' || (!!carnetCossmil && !carnetMilitar)) {
      return 'hijo_militar';
    }
    if (tipo === 'emi') {
      return 'emi';
    }
    return 'normal';
  }

  getCategoriaEstudianteNombre(): string {
    const cat = this.getCategoriaEstudiante();
    switch (cat) {
      case 'militar':
        return 'Personal Militar';
      case 'hijo_militar':
        return 'Hijo de Militar';
      case 'emi':
        return 'Convenio EMI';
      default:
        return 'Estudiante Civil / Particular';
    }
  }

  getTiposDocumentosPermitidos(): string[] {
    const cat = this.getCategoriaEstudiante();
    if (cat === 'militar') {
      return [
        'Carnet Militar',
        'Carnet de COSSMIL',
        'Carnet de Identidad',
        'Certificado de Nacimiento'
      ];
    }
    if (cat === 'hijo_militar') {
      return [
        'Carnet de Identidad',
        'Carnet de COSSMIL',
        'Título de Bachiller',
        'Certificado de Nacimiento'
      ];
    }
    if (cat === 'emi') {
      return [
        'Carnet de Identidad',
        'Credencial o Factura EMI',
        'Boleta de Inscripción EMI',
        'Título de Bachiller',
        'Certificado de Nacimiento'
      ];
    }
    // Estudiante Civil / Particular (normal)
    return [
      'Carnet de Identidad',
      'Título de Bachiller',
      'Certificado de Nacimiento'
    ];
  }

  updateDocTypeDefault() {
    const permitidos = this.getTiposDocumentosPermitidos();
    if (!permitidos.includes(this.docType)) {
      this.docType = permitidos[0];
    }
  }

  getEstadoClass(estado: string): string {
    const est = (estado || '').toLowerCase().trim();
    if (est === 'activo' || est === 'habilitado' || est === 'inscrito') {
      return 'bg-success text-white shadow-sm';
    }
    if (est === 'pendiente' || est === 'en revision' || est === 'en revisión') {
      return 'bg-warning text-dark shadow-sm';
    }
    if (est === 'retirado' || est === 'baja' || est === 'inactivo' || est === 'reprobado') {
      return 'bg-danger text-white shadow-sm';
    }
    if (est === 'finalizado' || est === 'concluido' || est === 'aprobado') {
      return 'bg-primary text-white shadow-sm';
    }
    return 'bg-secondary text-white shadow-sm';
  }

  getEstadoText(estado: string): string {
    const est = (estado || '').toLowerCase().trim();
    if (est === 'activo' || est === 'habilitado' || est === 'inscrito') return 'Habilitado';
    if (est === 'pendiente') return 'Pendiente';
    if (est === 'retirado' || est === 'baja') return 'Retirado';
    if (est === 'finalizado') return 'Finalizado';
    return estado || 'Registrado';
  }

  getHorarioInscripcion(item: any): string {
    if (!item) return 'Pendiente de asignación';
    if (item.horario && typeof item.horario === 'string' && item.horario.trim() !== '') {
      return item.horario;
    }
    if (item.curso?.horario && typeof item.curso.horario === 'string' && item.curso.horario.trim() !== '') {
      return item.curso.horario;
    }
    if (item.paralelo?.horarios && Array.isArray(item.paralelo.horarios) && item.paralelo.horarios.length > 0) {
      return item.paralelo.horarios.map((h: any) => {
        const ini = (h.hora_inicio || '').substring(0, 5);
        const fin = (h.hora_fin || '').substring(0, 5);
        return `${h.dia_semana || ''} (${ini} - ${fin})`;
      }).join(', ');
    }
    return 'Pendiente de asignación';
  }

  loadStudentDocuments(silent: boolean = false) {
    if (!this.student) return;
    if (!silent) this.docsLoading = true;
    this.studentService.getDocuments(this.student.id).subscribe({
      next: (docs) => {
        if (!docs || !Array.isArray(docs)) {
          this.docsLoading = false;
          return;
        }
        const currentFingerprint = (this.studentDocuments || []).map(d => `${d.id_documento}_${d.nombre_archivo}`).join('|');
        const newFingerprint = docs.map(d => `${d.id_documento}_${d.nombre_archivo}`).join('|');
        if (currentFingerprint !== newFingerprint) {
          this.studentDocuments = docs;
        }
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
        if (res && res.estudiante) {
          this.student = res.estudiante;
          if (res.estudiante.foto_4x4_url && this.user) {
            this.user.foto_url = res.estudiante.foto_4x4_url;
          }
        }
        this.authService.cargarPerfilActualizado().subscribe();
        this.loadStudentProfile(this.student.id, true);
        this.cdr.detectChanges();
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
    if (!this.puedeSubirDocumentos()) {
      alert('Tu plazo para subir o eliminar documentos ha finalizado. La documentación se encuentra bloqueada.');
      return;
    }

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

    this.rawPreviewUrl = url;
    this.previewError = false;
    this.activePreviewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);

    const isDataPdf = path.startsWith('data:application/pdf');
    const isDataImg = path.startsWith('data:image');
    const ext = path.split('?')[0].split('.').pop().toLowerCase();

    this.isPreviewPdf = isDataPdf || ext === 'pdf';
    this.isPreviewImage = isDataImg || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);

    this.showPreviewModal = true;
  }

  onPreviewImgError() {
    this.previewError = true;
  }

  closePreviewModal() {
    this.showPreviewModal = false;
    this.activePreviewDoc = null;
    this.activePreviewUrl = null;
    this.rawPreviewUrl = '';
    this.isPreviewPdf = false;
    this.isPreviewImage = false;
    this.previewError = false;
  }

  // --- ESCÁNER INTELIGENTE DE DOCUMENTOS (KYC / 2 CARAS) ---
  isScannerOpen: boolean = false;
  scannerFacingMode: 'environment' | 'user' = 'environment';
  scannerTorchOn: boolean = false;
  scannerHasTorch: boolean = false;
  scannerStep: number = 1; // 1: Frente, 2: Reverso, 3: Revisión
  scannerFrontImage: string | null = null;
  scannerBackImage: string | null = null;
  scannerMediaStream: MediaStream | null = null;
  scannerError: string = '';

  get isCarnetMode(): boolean {
    return (this.docType || '').toLowerCase().includes('carnet');
  }

  openScanner() {
    this.isScannerOpen = true;
    this.scannerStep = 1;
    this.scannerFrontImage = null;
    this.scannerBackImage = null;
    this.scannerError = '';
    setTimeout(() => {
      this.initCamera();
    }, 150);
  }

  closeScanner() {
    this.stopCamera();
    this.isScannerOpen = false;
    this.scannerStep = 1;
    this.scannerFrontImage = null;
    this.scannerBackImage = null;
    this.scannerError = '';
  }

  async initCamera() {
    this.stopCamera();
    this.scannerError = '';
    try {
      const constraints: MediaStreamConstraints = {
        video: {
          facingMode: { ideal: this.scannerFacingMode },
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        },
        audio: false
      };
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      this.scannerMediaStream = stream;
      const videoEl = document.getElementById('scannerVideoFeed') as HTMLVideoElement;
      if (videoEl) {
        videoEl.srcObject = stream;
        videoEl.play().catch(e => console.warn('Video play error', e));
      }

      const track = stream.getVideoTracks()[0];
      const capabilities: any = track && track.getCapabilities ? track.getCapabilities() : {};
      this.scannerHasTorch = !!capabilities.torch;
      this.scannerTorchOn = false;
    } catch (err: any) {
      console.error('Error accediendo a la cámara:', err);
      this.scannerError = 'No se pudo acceder a la cámara. Por favor autoriza los permisos en tu navegador o celular.';
    }
  }

  stopCamera() {
    if (this.scannerMediaStream) {
      this.scannerMediaStream.getTracks().forEach(track => {
        try { track.stop(); } catch (e) {}
      });
      this.scannerMediaStream = null;
    }
  }

  toggleCameraFacing() {
    this.scannerFacingMode = this.scannerFacingMode === 'environment' ? 'user' : 'environment';
    this.initCamera();
  }

  async toggleTorch() {
    if (!this.scannerMediaStream) return;
    const track = this.scannerMediaStream.getVideoTracks()[0];
    if (track && this.scannerHasTorch) {
      this.scannerTorchOn = !this.scannerTorchOn;
      try {
        await (track as any).applyConstraints({
          advanced: [{ torch: this.scannerTorchOn }]
        });
      } catch (e) {
        console.warn('Torch toggle failed', e);
      }
    }
  }

  captureFrame() {
    const videoEl = document.getElementById('scannerVideoFeed') as HTMLVideoElement;
    if (!videoEl || !videoEl.videoWidth) return;

    const vW = videoEl.videoWidth;
    const vH = videoEl.videoHeight;
    const targetAspect = this.isCarnetMode ? 1.58 : 0.71;

    let cropW = vW * 0.85;
    let cropH = cropW / targetAspect;

    if (cropH > vH * 0.85) {
      cropH = vH * 0.85;
      cropW = cropH * targetAspect;
    }

    const startX = (vW - cropW) / 2;
    const startY = (vH - cropH) / 2;

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(cropW);
    canvas.height = Math.round(cropH);
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.drawImage(videoEl, startX, startY, cropW, cropH, 0, 0, canvas.width, canvas.height);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

    if (typeof navigator !== 'undefined' && navigator.vibrate) {
      try { navigator.vibrate(60); } catch (e) {}
    }

    if (this.isCarnetMode) {
      if (this.scannerStep === 1) {
        this.scannerFrontImage = dataUrl;
        this.scannerStep = 2; // Pasar al reverso
      } else if (this.scannerStep === 2) {
        this.scannerBackImage = dataUrl;
        this.scannerStep = 3; // Pasar a revisión
        this.stopCamera();
      }
    } else {
      this.scannerFrontImage = dataUrl;
      this.scannerStep = 3;
      this.stopCamera();
    }
  }

  retakeCurrentStep() {
    if (this.scannerStep === 2) {
      this.scannerStep = 1;
      this.scannerFrontImage = null;
    } else if (this.scannerStep === 3) {
      this.scannerStep = 1;
      this.scannerFrontImage = null;
      this.scannerBackImage = null;
      this.initCamera();
    }
  }

  async confirmScannedDocument() {
    if (this.isCarnetMode && this.scannerFrontImage && this.scannerBackImage) {
      const combinedBlob = await this.stitchTwoSides(this.scannerFrontImage, this.scannerBackImage);
      if (combinedBlob) {
        const file = new File([combinedBlob], `Carnet_Identidad_${this.student?.ci || 'doc'}.jpg`, { type: 'image/jpeg' });
        this.selectedFile = file;
        this.closeScanner();
      }
    } else if (this.scannerFrontImage) {
      const blob = this.dataURItoBlob(this.scannerFrontImage);
      const cleanType = (this.docType || 'Documento').replace(/\s+/g, '_');
      const file = new File([blob], `${cleanType}_${this.student?.ci || 'doc'}.jpg`, { type: 'image/jpeg' });
      this.selectedFile = file;
      this.closeScanner();
    }
  }

  private stitchTwoSides(frontDataUrl: string, backDataUrl: string): Promise<Blob | null> {
    return new Promise((resolve) => {
      const imgFront = new Image();
      const imgBack = new Image();

      imgFront.onload = () => {
        imgBack.onload = () => {
          const canvasW = 1200;
          const cardW = 1060;
          const cardH = Math.round(cardW / 1.58);
          const gap = 70;
          const headerH = 120;
          const footerH = 50;
          const canvasH = headerH + cardH + gap + cardH + footerH;

          const canvas = document.createElement('canvas');
          canvas.width = canvasW;
          canvas.height = canvasH;
          const ctx = canvas.getContext('2d');
          if (!ctx) { resolve(null); return; }

          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, canvasW, canvasH);

          // Encabezado institucional
          ctx.fillStyle = '#003b71';
          ctx.font = 'bold 34px sans-serif';
          ctx.textAlign = 'center';
          ctx.fillText('DOCUMENTO OFICIAL DE IDENTIFICACIÓN — EIE', canvasW / 2, 55);

          ctx.fillStyle = '#64748b';
          ctx.font = '20px sans-serif';
          ctx.fillText('Cédula de Identidad (Anverso y Reverso)', canvasW / 2, 90);

          const cardX = (canvasW - cardW) / 2;

          // Etiqueta Anverso
          ctx.fillStyle = '#1e293b';
          ctx.font = 'bold 22px sans-serif';
          ctx.textAlign = 'left';
          ctx.fillText('1. PARTE FRONTAL (ANVERSO):', cardX, headerH + 20);

          const cardY1 = headerH + 30;
          ctx.save();
          this.roundedRect(ctx, cardX, cardY1, cardW, cardH, 20);
          ctx.clip();
          ctx.drawImage(imgFront, cardX, cardY1, cardW, cardH);
          ctx.restore();

          ctx.strokeStyle = '#94a3b8';
          ctx.lineWidth = 2;
          this.roundedRect(ctx, cardX, cardY1, cardW, cardH, 20);
          ctx.stroke();

          // Etiqueta Reverso
          const cardY2 = cardY1 + cardH + gap;
          ctx.fillStyle = '#1e293b';
          ctx.font = 'bold 22px sans-serif';
          ctx.fillText('2. PARTE POSTERIOR (REVERSO):', cardX, cardY2 - 10);

          ctx.save();
          this.roundedRect(ctx, cardX, cardY2, cardW, cardH, 20);
          ctx.clip();
          ctx.drawImage(imgBack, cardX, cardY2, cardW, cardH);
          ctx.restore();

          this.roundedRect(ctx, cardX, cardY2, cardW, cardH, 20);
          ctx.stroke();

          canvas.toBlob((blob) => {
            resolve(blob);
          }, 'image/jpeg', 0.90);
        };
        imgBack.src = backDataUrl;
      };
      imgFront.src = frontDataUrl;
    });
  }

  private roundedRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
  }

  private dataURItoBlob(dataURI: string): Blob {
    const byteString = atob(dataURI.split(',')[1]);
    const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeString });
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
