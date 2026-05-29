import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DocenteService } from '../../services/docente.service';
import { ImageCompressorService } from '../../services/image-compressor.service';

@Component({
  selector: 'app-docentes-list',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './docentes-list.component.html',
  styleUrls: ['./docentes-list.component.css']
})
export class DocentesListComponent implements OnInit {
  docentes: any[] = [];
  filteredDocentes: any[] = [];
  isLoading = true;
  selectedDocente: any = null;
  isEditing = false;
  showForm = false;

  searchTerm: string = '';

  constructor(
    private docenteService: DocenteService,
    private imageCompressor: ImageCompressorService
  ) {}

  ngOnInit(): void {
    this.loadDocentes();
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
    this.selectedDocente = {
      nombres: '',
      apellidos: '',
      correo_electronico: '',
      ci: '',
      especialidad: '',
      telefono: '',
      estado: 'Activo',
      tipo_contrato: '',
      fecha_contrato: ''
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  editDocente(docente: any) {
    this.isEditing = true;
    this.docentePhotoFile = null;
    this.docentePhotoFileName = '';
    // Make a deep copy to avoid modifying the table row directly before saving
    this.selectedDocente = {
      id: docente.id,
      nombres: docente.nombres || '',
      apellidos: docente.apellidos || '',
      correo_electronico: docente.correo_electronico || '',
      foto_url: docente.foto_url || '',
      ci: docente.ci || '',
      especialidad: docente.especialidad,
      telefono: docente.telefono,
      meta_foto: docente.foto_url,
      estado: docente.estado,
      tipo_contrato: docente.tipo_contrato || '',
      fecha_contrato: docente.fecha_contrato || '',
      user_id: docente.user_id
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  cancelEdit() {
    this.selectedDocente = null;
    this.showForm = false;
  }

  saveDocente() {
    if (!this.selectedDocente) return;

    if (this.isEditing) {
      this.updateDocente();
    } else {
      if (!this.selectedDocente.nombres) {
        alert('El campo Nombres es obligatorio.');
        return;
      }

      const formData = new FormData();
      formData.append('nombres', this.selectedDocente.nombres);
      formData.append('apellidos', this.selectedDocente.apellidos || '');
      formData.append('correo_electronico', this.selectedDocente.correo_electronico || '');
      if (this.selectedDocente.ci) {
        formData.append('ci', this.selectedDocente.ci);
      }
      formData.append('especialidad', this.selectedDocente.especialidad || 'General');
      formData.append('telefono', this.selectedDocente.telefono || '');
      if (this.selectedDocente.tipo_contrato) {
        formData.append('tipo_contrato', this.selectedDocente.tipo_contrato);
      }
      if (this.selectedDocente.tipo_contrato === 'Contrato' && this.selectedDocente.fecha_contrato) {
        formData.append('fecha_contrato', this.selectedDocente.fecha_contrato);
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
          this.showForm = false;
          this.loadDocentes();
        },
        error: (err) => {
          alert('Error al registrar instructor: ' + (err.error?.message || err.message));
        }
      });
    }
  }

  updateDocente() {
    if (!this.selectedDocente) return;

    const formData = new FormData();
    formData.append('nombres', this.selectedDocente.nombres);
    formData.append('apellidos', this.selectedDocente.apellidos || '');
    formData.append('correo_electronico', this.selectedDocente.correo_electronico || '');
    if (this.selectedDocente.ci) {
      formData.append('ci', this.selectedDocente.ci);
    }
    formData.append('especialidad', this.selectedDocente.especialidad || '');
    formData.append('telefono', this.selectedDocente.telefono || '');
    formData.append('estado', this.selectedDocente.estado || 'Activo');
    if (this.selectedDocente.tipo_contrato) {
      formData.append('tipo_contrato', this.selectedDocente.tipo_contrato);
    }
    if (this.selectedDocente.tipo_contrato === 'Contrato' && this.selectedDocente.fecha_contrato) {
      formData.append('fecha_contrato', this.selectedDocente.fecha_contrato);
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
        alert('Error al actualizar instructor: ' + (err.error?.message || err.message));
      }
    });
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
}
