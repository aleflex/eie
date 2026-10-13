import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DocenteService } from '../../services/docente.service';
import { CourseService } from '../../services/course.service';
import { ImageCompressorService } from '../../services/image-compressor.service';
import { AuthService } from '../../services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-docentes-list',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './docentes-list.component.html',
  styleUrls: ['./docentes-list.component.css']
})
export class DocentesListComponent implements OnInit {
  apiUrl = environment.apiUrl;
  docentes: any[] = [];
  isSidebarCollapsed = false;
  isMobileMenuOpen = false;

  listaIdiomas: string[] = [
    'Inglés',
    'Francés',
    'Chino Mandarín',
    'Alemán',
    'Quechua',
    'Aymara'
  ];
  customEspecialidad: string = '';

  // Reglas de formato telefónico y límites por país
  phoneCountryRules: { [prefix: string]: { min: number; max: number; country: string; placeholder: string } } = {
    '+591': { min: 8, max: 8, country: 'Bolivia', placeholder: '70000000 (8 dígitos)' },
    '+54':  { min: 10, max: 10, country: 'Argentina', placeholder: '1123456789 (10 dígitos)' },
    '+55':  { min: 10, max: 11, country: 'Brasil', placeholder: '11987654321 (10 u 11 dígitos)' },
    '+56':  { min: 9, max: 9, country: 'Chile', placeholder: '912345678 (9 dígitos)' },
    '+51':  { min: 9, max: 9, country: 'Perú', placeholder: '912345678 (9 dígitos)' },
    '+57':  { min: 10, max: 10, country: 'Colombia', placeholder: '3001234567 (10 dígitos)' },
    '+34':  { min: 9, max: 9, country: 'España', placeholder: '612345678 (9 dígitos)' },
    '+1':   { min: 10, max: 10, country: 'EE.UU. / Canadá', placeholder: '2025550123 (10 dígitos)' },
    '+52':  { min: 10, max: 10, country: 'México', placeholder: '5512345678 (10 dígitos)' },
    '+595': { min: 9, max: 9, country: 'Paraguay', placeholder: '981234567 (9 dígitos)' },
    '+598': { min: 8, max: 8, country: 'Uruguay', placeholder: '91234567 (8 dígitos)' },
    '+593': { min: 9, max: 9, country: 'Ecuador', placeholder: '991234567 (9 dígitos)' },
    '+58':  { min: 10, max: 10, country: 'Venezuela', placeholder: '4121234567 (10 dígitos)' },
    '+86':  { min: 11, max: 11, country: 'China', placeholder: '13800138000 (11 dígitos)' },
    '+49':  { min: 10, max: 11, country: 'Alemania', placeholder: '15112345678 (10 u 11 dígitos)' },
    '+33':  { min: 9, max: 9, country: 'Francia', placeholder: '612345678 (9 dígitos)' },
    '+':    { min: 6, max: 15, country: 'Otro país', placeholder: 'Número telefónico' }
  };

  telefonoPrefix: string = '+591';
  telefonoNumero: string = '';

  get currentPhoneRule() {
    return this.phoneCountryRules[this.telefonoPrefix] || this.phoneCountryRules['+'];
  }

  onTelefonoPrefixChange() {
    if (this.telefonoNumero && this.telefonoNumero.length > this.currentPhoneRule.max) {
      this.telefonoNumero = this.telefonoNumero.substring(0, this.currentPhoneRule.max);
    }
  }

  parseTelefono(raw: string) {
    if (!raw) {
      this.telefonoPrefix = '+591';
      this.telefonoNumero = '';
      return;
    }
    const clean = (raw + '').trim();
    const prefixes = Object.keys(this.phoneCountryRules).filter(p => p !== '+').sort((a, b) => b.length - a.length);
    for (const p of prefixes) {
      if (clean.startsWith(p)) {
        this.telefonoPrefix = p;
        this.telefonoNumero = clean.substring(p.length).replace(/[^0-9]/g, '');
        return;
      }
    }
    this.telefonoPrefix = '+591';
    this.telefonoNumero = clean.replace(/[^0-9]/g, '');
  }

  buildTelefono(): string {
    const num = (this.telefonoNumero || '').trim().replace(/[^0-9]/g, '');
    if (!num) return '';
    return `${this.telefonoPrefix} ${num}`;
  }

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMobileMenu() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  closeMobileMenu() {
    this.isMobileMenuOpen = false;
  }

  onlyNumbers(event: KeyboardEvent) {
    const charCode = event.which ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      event.preventDefault();
    }
  }
  filteredDocentes: any[] = [];
  isLoading = true;
  selectedDocente: any = null;
  isEditing = false;
  showForm = false;

  searchTerm: string = '';

  constructor(
    private docenteService: DocenteService,
    private courseService: CourseService,
    private imageCompressor: ImageCompressorService,
    private authService: AuthService
  ) {}

  canAccess(module: string): boolean {
    return this.authService.canAccess(module);
  }

  ngOnInit(): void {
    this.loadDocentes();
    this.loadIdiomas();
  }

  loadIdiomas() {
    this.courseService.obtenerIdiomas().subscribe({
      next: (data) => {
        if (Array.isArray(data) && data.length > 0) {
          const nombres = data.map((i: any) => i.nombre_idioma || i.nombre).filter(Boolean);
          if (nombres.length > 0) {
            this.listaIdiomas = Array.from(new Set([...this.listaIdiomas, ...nombres]));
          }
        }
      },
      error: () => {}
    });
  }

  loadDocentes() {
    this.isLoading = true;
    this.docenteService.getDocentes().subscribe({
      next: (data) => {
        this.docentes = data;
        this.filteredDocentes = data;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading docentes', err);
        this.isLoading = false;
      }
    });
  }

  onSearch() {
    if (!this.searchTerm.trim()) {
      this.filteredDocentes = this.docentes;
      return;
    }

    const term = this.searchTerm.toLowerCase();
    this.filteredDocentes = this.docentes.filter(d => {
      const nameMatch = `${d.nombres} ${d.apellidos}`.toLowerCase().includes(term) || d.user?.name?.toLowerCase().includes(term);
      const emailMatch = d.correo_electronico?.toLowerCase().includes(term) || d.user?.email?.toLowerCase().includes(term);
      const espMatch = d.especialidad?.toLowerCase().includes(term);
      const ciMatch = d.ci?.toLowerCase().includes(term);
      return nameMatch || emailMatch || espMatch || ciMatch;
    });
  }

  onSearchInput() {
    if (this.searchTerm === '') {
      this.filteredDocentes = this.docentes;
    }
  }

  docentePhotoFile: File | null = null;
  docentePhotoFileName: string = '';

  onDocentePhotoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      alert('Por favor, selecciona una imagen válida.');
      return;
    }
    // Comprimir foto del instructor capturada por cámara o galería (RF16 - T2)
    this.imageCompressor.compressImage(file, 800, 800, 0.82).then(compressed => {
      this.docentePhotoFile = compressed;
      this.docentePhotoFileName = compressed.name;
      console.log(`[RF16] Foto docente: ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
    });
  }

  openCreateForm() {
    this.isEditing = false;
    this.docentePhotoFile = null;
    this.docentePhotoFileName = '';
    this.customEspecialidad = '';
    this.parseTelefono('');
    this.selectedDocente = {
      nombres: '',
      apellidos: '',
      correo_electronico: '',
      ci: '',
      expedido: '',
      fecha_nacimiento: '',
      lugar_nacimiento: '',
      especialidad: '',
      telefono: '',
      estado: 'Activo',
      tipo_contrato: '',
      fecha_contrato: '',
      fecha_inicio_contrato: '',
      fecha_fin_contrato: ''
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  editDocente(docente: any) {
    this.isEditing = true;
    this.docentePhotoFile = null;
    this.docentePhotoFileName = '';
    this.customEspecialidad = '';
    this.parseTelefono(docente.telefono || '');
    if (docente.especialidad && !this.listaIdiomas.includes(docente.especialidad)) {
      this.listaIdiomas.push(docente.especialidad);
    }
    // Make a deep copy to avoid modifying the table row directly before saving
    this.selectedDocente = {
      id: docente.id,
      nombres: docente.nombres || '',
      apellidos: docente.apellidos || '',
      correo_electronico: docente.correo_electronico || '',
      foto_url: docente.foto_url || '',
      ci: docente.ci || '',
      expedido: docente.expedido || docente.user?.expedido || '',
      fecha_nacimiento: docente.fecha_nacimiento || '',
      lugar_nacimiento: docente.lugar_nacimiento || '',
      especialidad: docente.especialidad || '',
      telefono: docente.telefono,
      meta_foto: docente.foto_url,
      estado: docente.estado,
      tipo_contrato: docente.tipo_contrato || '',
      fecha_contrato: docente.fecha_contrato || '',
      fecha_inicio_contrato: docente.fecha_inicio_contrato || '',
      fecha_fin_contrato: docente.fecha_fin_contrato || '',
      user_id: docente.user_id
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  cancelEdit() {
    this.selectedDocente = null;
    this.customEspecialidad = '';
    this.showForm = false;
  }

  saveDocente() {
    if (!this.selectedDocente) return;

    if (!this.selectedDocente.nombres || !this.selectedDocente.nombres.trim()) {
      alert('El campo Nombres es obligatorio.');
      return;
    }

    if (!this.selectedDocente.ci || !(this.selectedDocente.ci + '').trim()) {
      alert('El número de C.I. del instructor es obligatorio.');
      return;
    }

    const cleanCi = (this.selectedDocente.ci + '').trim();
    if (!/^[0-9]{7,8}$/.test(cleanCi)) {
      alert('El C.I. del instructor debe contener estrictamente 7 u 8 dígitos numéricos.');
      return;
    }

    const fullTel = this.buildTelefono();
    if (this.telefonoNumero && this.telefonoNumero.trim()) {
      const len = this.telefonoNumero.trim().length;
      if (len < this.currentPhoneRule.min || len > this.currentPhoneRule.max) {
        alert(`Para ${this.currentPhoneRule.country} (${this.telefonoPrefix}), el número telefónico debe tener ${this.currentPhoneRule.min === this.currentPhoneRule.max ? ('exactamente ' + this.currentPhoneRule.min) : ('entre ' + this.currentPhoneRule.min + ' y ' + this.currentPhoneRule.max)} dígitos.`);
        return;
      }
    }

    if (this.isEditing) {
      this.updateDocente();
    } else {
      const formData = new FormData();
      formData.append('nombres', this.selectedDocente.nombres.trim());
      if (this.selectedDocente.apellidos && this.selectedDocente.apellidos.trim()) {
        formData.append('apellidos', this.selectedDocente.apellidos.trim());
      }
      if (this.selectedDocente.correo_electronico && this.selectedDocente.correo_electronico.trim()) {
        formData.append('correo_electronico', this.selectedDocente.correo_electronico.trim());
      }
      formData.append('ci', cleanCi);
      if (this.selectedDocente.expedido) {
        formData.append('expedido', this.selectedDocente.expedido);
      }
      if (this.selectedDocente.fecha_nacimiento) {
        formData.append('fecha_nacimiento', this.selectedDocente.fecha_nacimiento);
      }
      if (this.selectedDocente.lugar_nacimiento && this.selectedDocente.lugar_nacimiento.trim()) {
        formData.append('lugar_nacimiento', this.selectedDocente.lugar_nacimiento.trim());
      }
      const espFinal = this.selectedDocente.especialidad === '__OTRO__'
        ? (this.customEspecialidad?.trim() || 'General')
        : (this.selectedDocente.especialidad?.trim() || 'General');
      formData.append('especialidad', espFinal);

      if (fullTel) {
        formData.append('telefono', fullTel);
      }
      if (this.selectedDocente.tipo_contrato) {
        formData.append('tipo_contrato', this.selectedDocente.tipo_contrato);
      }
      if (this.selectedDocente.fecha_inicio_contrato) {
        formData.append('fecha_inicio_contrato', this.selectedDocente.fecha_inicio_contrato);
      }
      if (this.selectedDocente.tipo_contrato === 'Contrato' && this.selectedDocente.fecha_fin_contrato) {
        formData.append('fecha_fin_contrato', this.selectedDocente.fecha_fin_contrato);
        formData.append('fecha_contrato', this.selectedDocente.fecha_fin_contrato);
      }
      formData.append('estado', 'Activo');

      if (this.docentePhotoFile) {
        formData.append('foto', this.docentePhotoFile);
      }

      this.docenteService.registerDocenteWithPhoto(formData).subscribe({
        next: () => {
          alert('Instructor registrado exitosamente');
          this.selectedDocente = null;
          this.docentePhotoFile = null;
          this.docentePhotoFileName = '';
          this.customEspecialidad = '';
          this.showForm = false;
          this.loadDocentes();
        },
        error: (err) => {
          alert('Error al registrar instructor:\n• ' + this.extractErrorMessage(err));
        }
      });
    }
  }

  updateDocente() {
    if (!this.selectedDocente) return;

    const fullTel = this.buildTelefono();
    if (this.telefonoNumero && this.telefonoNumero.trim()) {
      const len = this.telefonoNumero.trim().length;
      if (len < this.currentPhoneRule.min || len > this.currentPhoneRule.max) {
        alert(`Para ${this.currentPhoneRule.country} (${this.telefonoPrefix}), el número telefónico debe tener ${this.currentPhoneRule.min === this.currentPhoneRule.max ? ('exactamente ' + this.currentPhoneRule.min) : ('entre ' + this.currentPhoneRule.min + ' y ' + this.currentPhoneRule.max)} dígitos.`);
        return;
      }
    }

    const formData = new FormData();
    formData.append('nombres', this.selectedDocente.nombres.trim());
    if (this.selectedDocente.apellidos && this.selectedDocente.apellidos.trim()) {
      formData.append('apellidos', this.selectedDocente.apellidos.trim());
    }
    if (this.selectedDocente.correo_electronico && this.selectedDocente.correo_electronico.trim()) {
      formData.append('correo_electronico', this.selectedDocente.correo_electronico.trim());
    }
    if (this.selectedDocente.ci) {
      formData.append('ci', (this.selectedDocente.ci + '').trim());
    }
    if (this.selectedDocente.expedido) {
      formData.append('expedido', this.selectedDocente.expedido);
    }
    if (this.selectedDocente.fecha_nacimiento) {
      formData.append('fecha_nacimiento', this.selectedDocente.fecha_nacimiento);
    }
    if (this.selectedDocente.lugar_nacimiento && this.selectedDocente.lugar_nacimiento.trim()) {
      formData.append('lugar_nacimiento', this.selectedDocente.lugar_nacimiento.trim());
    }
    const espFinal = this.selectedDocente.especialidad === '__OTRO__'
      ? (this.customEspecialidad?.trim() || 'General')
      : (this.selectedDocente.especialidad?.trim() || 'General');
    formData.append('especialidad', espFinal);

    if (fullTel) {
      formData.append('telefono', fullTel);
    }
    formData.append('estado', this.selectedDocente.estado || 'Activo');
    if (this.selectedDocente.tipo_contrato) {
      formData.append('tipo_contrato', this.selectedDocente.tipo_contrato);
    }
    if (this.selectedDocente.fecha_inicio_contrato) {
      formData.append('fecha_inicio_contrato', this.selectedDocente.fecha_inicio_contrato);
    }
    if (this.selectedDocente.tipo_contrato === 'Contrato' && this.selectedDocente.fecha_fin_contrato) {
      formData.append('fecha_fin_contrato', this.selectedDocente.fecha_fin_contrato);
      formData.append('fecha_contrato', this.selectedDocente.fecha_fin_contrato);
    }

    if (this.docentePhotoFile) {
      formData.append('foto', this.docentePhotoFile);
    }

    this.docenteService.updateDocenteWithPhoto(this.selectedDocente.id, formData).subscribe({
      next: () => {
        alert('Instructor actualizado exitosamente');
        this.selectedDocente = null;
        this.docentePhotoFile = null;
        this.docentePhotoFileName = '';
        this.showForm = false;
        this.loadDocentes();
      },
      error: (err) => {
        alert('Error al actualizar instructor:\n• ' + this.extractErrorMessage(err));
      }
    });
  }

  private extractErrorMessage(err: any): string {
    if (err?.error?.errors && typeof err.error.errors === 'object') {
      const messages = Object.values(err.error.errors).flat() as string[];
      if (messages.length > 0) {
        return messages.join('\n• ');
      }
    }
    return err?.error?.message || err?.message || 'Error desconocido';
  }

  toggleStatus(docente: any) {
    this.docenteService.toggleStatus(docente.id).subscribe({
      next: (res) => {
        docente.estado = res.docente.estado;
      },
      error: (err) => {
        alert('Error al cambiar el estado: ' + (err.error?.message || err.message));
      }
    });
  }

  deleteDocente(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este instructor? Se eliminará también su cuenta de acceso.')) {
      this.docenteService.deleteDocente(id).subscribe({
        next: () => {
          this.loadDocentes();
        },
        error: (err) => {
          alert('Error al eliminar instructor: ' + (err.error?.message || err.message));
        }
      });
    }
  }

  onImageError(event: any) {
    if (event && event.target) {
      const src = event.target.src || '';
      if (!src.includes('default-avatar.svg') && !src.includes('default-avatar.png')) {
        event.target.src = '/assets/default-avatar.svg';
      }
    }
  }

  getPhotoUrl(url: string | null | undefined): string {
    if (!url) {
      return '';
    }
    if (url.startsWith('data:') || url.startsWith('http://') || url.startsWith('https://')) {
      return url;
    }
    const apiBase = this.apiUrl.replace(/\/api\/?$/, '');
    return apiBase + (url.startsWith('/') ? '' : '/') + url;
  }
}
