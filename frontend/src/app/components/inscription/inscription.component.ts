import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NavbarComponent } from '../navbar/navbar.component';
import { FooterComponent } from '../footer/footer.component';
import { InscriptionService } from '../../services/inscription.service';
import { ImageCompressorService } from '../../services/image-compressor.service';

@Component({
  selector: 'app-inscription',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, NavbarComponent, FooterComponent],
  templateUrl: './inscription.component.html',
  styleUrl: './inscription.component.css'
})
export class InscriptionComponent implements OnInit {
  inscriptionForm!: FormGroup;
  isSubmitted = false;

  // Stepper state
  currentStep = 1;
  totalSteps = 3;
  isLoading = false;

  // Modern Alert Modals state
  showModal = false;
  modalType: 'success' | 'error' = 'success';
  modalMessage = '';

  // Almacenar nombres de archivo seleccionados para dropzones personalizados
  fileNames: { [key: string]: string } = {
    carnet: '',
    titulo: '',
    nacimiento: '',
    deposito: '',
    foto: '',
    credencialEmi: ''
  };

  // Warnings for mismatching filenames (Soft Warning - Option A)
  fileWarnings: { [key: string]: string } = {
    carnet: '',
    titulo: '',
    nacimiento: '',
    deposito: '',
    foto: '',
    credencialEmi: ''
  };

  constructor(
    private fb: FormBuilder,
    private inscriptionService: InscriptionService,
    private imageCompressor: ImageCompressorService
  ) {}

  /**
   * Se ejecuta al inicializar el componente.
   * Llama a las funciones para preparar el formulario y escuchar sus cambios.
   */
  ngOnInit(): void {
    this.initForm();
    this.setupFormSubscriptions();
  }

  /**
   * Inicializa el formulario reactivo con todos sus campos y reglas de validación (obligatorio, longitud, etc.).
   */
  initForm() {
    this.inscriptionForm = this.fb.group({
      userType: ['normal', Validators.required],
      nombres: ['', [Validators.required, Validators.minLength(3)]],
      apellidos: ['', [Validators.required, Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ']{2,}(?:\s+[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ']{2,})+$/)]],
      gradoAcademico: [''],
      armaEspecialidad: [''],
      lugarNacimiento: ['', Validators.required],
      fechaNacimiento: ['', Validators.required],
      ci: ['', [Validators.required, Validators.pattern(/^[0-9]+$/), Validators.minLength(6)]],
      carnetMilitar: [''],
      carnetCossmil: [''],
      estadoCivil: ['', Validators.required],
      grupoSanguineo: ['', Validators.required],
      celularPrefix: ['+591', Validators.required],
      celular: ['', [Validators.required, Validators.pattern(/^[0-9]{7,10}$/)]],
      anioBachiller: ['', [Validators.required, Validators.min(1950), Validators.max(2026)]],
      edad: ['', [Validators.required, Validators.min(5), Validators.max(60)]],
      email: ['', [Validators.required, Validators.email]],
      domicilio: ['', Validators.required],
      horario: ['', Validators.required],
      nivel: ['', Validators.required],
      idioma: ['Inglés', Validators.required],
      tipoCurso: ['regular', Validators.required],
      nombrePadres: ['', [Validators.required, Validators.minLength(6)]],
      ciTutor: ['', [Validators.required, Validators.pattern(/^[0-9]+$/), Validators.minLength(6)]],
      hermanosInscritos: [''],
      contactoEmergencia: ['', [Validators.required, Validators.minLength(4)]],
      archivos: this.fb.group({
        carnet: [null, Validators.required],
        titulo: [null, Validators.required],
        nacimiento: [null, Validators.required],
        deposito: [null, Validators.required],
        foto: [null, Validators.required],
        credencialEmi: [null]
      })
    });
  }

  /**
   * Escucha los cambios en tiempo real de campos específicos (como fecha de nacimiento o tipo de usuario)
   * para calcular valores automáticamente o cambiar las reglas de validación.
   */
  setupFormSubscriptions() {
    // 1. Calcular edad automáticamente desde la fecha de nacimiento
    this.inscriptionForm.get('fechaNacimiento')?.valueChanges.subscribe(value => {
      if (value) {
        const age = this.calculateAge(value);
        this.inscriptionForm.get('edad')?.patchValue(age, { emitEvent: false });
        this.inscriptionForm.get('edad')?.markAsTouched();
      }
    });

    // 2. Limpiar campos militares si se selecciona tipo de usuario civil o hijo de militar, o limpiar padres si es militar
    this.inscriptionForm.get('userType')?.valueChanges.subscribe(type => {
      const nombrePadresCtrl = this.inscriptionForm.get('nombrePadres');
      const ciTutorCtrl = this.inscriptionForm.get('ciTutor');

      if (type !== 'militar') {
        this.inscriptionForm.patchValue({
          gradoAcademico: '',
          armaEspecialidad: '',
          carnetMilitar: '',
          carnetCossmil: ''
        }, { emitEvent: false });
        
        // Re-add validators for non-military
        nombrePadresCtrl?.setValidators([Validators.required, Validators.minLength(6)]);
        ciTutorCtrl?.setValidators([Validators.required, Validators.pattern(/^[0-9]+$/), Validators.minLength(6)]);
      } else {
        // Remove validators for military
        nombrePadresCtrl?.clearValidators();
        ciTutorCtrl?.clearValidators();
      }
      
      nombrePadresCtrl?.updateValueAndValidity({ emitEvent: false });
      ciTutorCtrl?.updateValueAndValidity({ emitEvent: false });
    });
  }

  /**
   * Calcula la edad exacta de una persona basándose en su fecha de nacimiento proporcionada.
   * @param birthDateString Fecha de nacimiento en formato de texto.
   * @returns La edad en años (número entero).
   */
  calculateAge(birthDateString: string): number {
    if (!birthDateString) return 0;
    const today = new Date();
    const birthDate = new Date(birthDateString);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age >= 0 ? age : 0;
  }

  /**
   * Restringe la entrada del teclado para permitir exclusivamente números.
   * Útil para campos como número de celular o carnet.
   * @param event Evento del teclado.
   */
  onlyNumbers(event: KeyboardEvent) {
    const charCode = event.which ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      event.preventDefault();
    }
  }

  // Easy access to form fields
  get f() { return this.inscriptionForm.controls; }

  // Get current user category reactively
  get userType(): string {
    return this.inscriptionForm.get('userType')?.value || '';
  }

  // Stepper navigation and validation
  /**
   * Verifica si todos los campos de un "paso" (step) específico del formulario son válidos.
   * @param step Número del paso a validar (1, 2 o 3).
   * @returns true si el paso es válido, false si hay errores.
   */
  validateStep(step: number): boolean {
    this.isSubmitted = true;

    if (step === 1) {
      const step1Fields = ['userType', 'nombres', 'apellidos', 'ci', 'lugarNacimiento', 'fechaNacimiento', 'edad', 'estadoCivil', 'nombrePadres', 'ciTutor', 'contactoEmergencia'];
      let isValid = true;
      step1Fields.forEach(field => {
        const control = this.inscriptionForm.get(field);
        if (control) {
          control.markAsTouched();
          if (control.invalid) {
            isValid = false;
          }
        }
      });
      return isValid;
    }

    if (step === 2) {
      const step2Fields = ['email', 'celularPrefix', 'celular', 'domicilio', 'grupoSanguineo', 'anioBachiller', 'tipoCurso', 'idioma', 'nivel', 'horario'];
      let isValid = true;
      step2Fields.forEach(field => {
        const control = this.inscriptionForm.get(field);
        if (control) {
          control.markAsTouched();
          if (control.invalid) {
            isValid = false;
          }
        }
      });
      return isValid;
    }

    if (step === 3) {
      const archivosGroup = this.inscriptionForm.get('archivos') as FormGroup;
      if (archivosGroup) {
        // Establecer validación de credencialEmi dinámicamente
        const credencialCtrl = archivosGroup.get('credencialEmi');
        if (this.userType === 'emi') {
          credencialCtrl?.setValidators(Validators.required);
        } else {
          credencialCtrl?.clearValidators();
        }
        credencialCtrl?.updateValueAndValidity({ emitEvent: false });

        let isValid = true;
        Object.keys(archivosGroup.controls).forEach(key => {
          if (key === 'credencialEmi' && this.userType !== 'emi') {
            return;
          }
          const control = archivosGroup.controls[key];
          control.markAsTouched();
          if (control.invalid) {
            isValid = false;
          }
        });
        return isValid;
      }
      return false;
    }

    return true;
  }

  /**
   * Avanza al siguiente paso del formulario de inscripción si el paso actual es completamente válido.
   */
  nextStep() {
    if (this.validateStep(this.currentStep)) {
      if (this.currentStep < this.totalSteps) {
        this.currentStep++;
        this.isSubmitted = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }
  }

  /**
   * Retrocede al paso anterior del formulario sin borrar los datos ingresados.
   */
  prevStep() {
    if (this.currentStep > 1) {
      this.currentStep--;
      this.isSubmitted = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  /**
   * Permite navegar a un paso específico directamente, siempre y cuando los pasos intermedios sean válidos.
   * @param step El paso al que se desea ir.
   */
  goToStep(step: number) {
    if (step < this.currentStep) {
      this.currentStep = step;
      this.isSubmitted = false;
    } else if (step > this.currentStep) {
      for (let s = this.currentStep; s < step; s++) {
        if (!this.validateStep(s)) {
          return;
        }
      }
      this.currentStep = step;
      this.isSubmitted = false;
    }
  }

  // Files operations — with client-side image compression (RF16 - HU16 - T2)
  /**
   * Se activa cuando el usuario selecciona un archivo para subir.
   * Realiza validaciones inteligentes de nombres, comprime imágenes y guarda el archivo en memoria.
   * @param event El evento input con el archivo.
   * @param fieldName El nombre del campo (carnet, titulo, etc.).
   */
  onFileSelected(event: any, fieldName: string) {
    const file = event.target.files[0];
    if (!file) return;

    const archivos = this.inscriptionForm.get('archivos') as FormGroup;

    // Reiniciar advertencia
    this.fileWarnings[fieldName] = '';

    // Validación inteligente de coherencia de nombre de archivo (Soft Warning)
    const nameLower = file.name.toLowerCase();

    if (fieldName === 'carnet') {
      if (nameLower.includes('certificado') || nameLower.includes('nacimiento') || nameLower.includes('titulo') || nameLower.includes('bachiller') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante')) {
        this.fileWarnings[fieldName] = 'El archivo seleccionado parece ser un Certificado, Título o Depósito bancario en lugar de tu Cédula de Identidad.';
      }
    }

    if (fieldName === 'titulo') {
      if (nameLower.includes('carnet') || nameLower.includes('ci') || nameLower.includes('nacimiento') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante') || nameLower.includes('certificado')) {
        this.fileWarnings[fieldName] = 'El archivo seleccionado parece ser una Identificación, Certificado o Depósito en lugar de tu Título de Bachiller.';
      }
    }

    if (fieldName === 'nacimiento') {
      if (nameLower.includes('carnet') || nameLower.includes('ci') || nameLower.includes('titulo') || nameLower.includes('bachiller') || nameLower.includes('deposito') || nameLower.includes('boleta') || nameLower.includes('comprobante')) {
        this.fileWarnings[fieldName] = 'El archivo seleccionado parece ser una Identificación, Título o Depósito en lugar de tu Certificado de Nacimiento.';
      }
    }

    if (fieldName === 'deposito') {
      if (nameLower.includes('ci') || nameLower.includes('carnet') || nameLower.includes('certificado') || nameLower.includes('nacimiento') || nameLower.includes('titulo') || nameLower.includes('foto')) {
        this.fileWarnings[fieldName] = 'El archivo seleccionado parece ser una Identificación, Fotografía o Certificado en lugar de tu comprobante de depósito bancario.';
      }
    }

    if (file.type.startsWith('image/')) {
      const maxDim = fieldName === 'foto' ? 800 : 1200; // fotos 4x4 a 800px; documentos a 1200px
      const quality = fieldName === 'foto' ? 0.82 : 0.78;
      this.imageCompressor.compressImage(file, maxDim, maxDim, quality).then(compressed => {
        archivos.patchValue({ [fieldName]: compressed });
        archivos.get(fieldName)?.markAsTouched();
        this.fileNames[fieldName] = compressed.name;
        console.log(`[RF16] ${fieldName}: comprimido ${(file.size/1024).toFixed(1)}KB → ${(compressed.size/1024).toFixed(1)}KB`);
        if (fieldName === 'foto') {
          this.analyzePhoto4x4(file);
        }
      });
    } else {
      // PDFs y otros formatos sin compresión
      archivos.patchValue({ [fieldName]: file });
      archivos.get(fieldName)?.markAsTouched();
      this.fileNames[fieldName] = file.name;
    }
  }

  /**
   * Analiza matemáticamente los píxeles de una foto 4x4 subida para advertir
   * si la imagen no es cuadrada o si el fondo no es de color rojo sólido.
   * @param file El archivo de imagen subido.
   */
  analyzePhoto4x4(file: File): Promise<void> {
    return new Promise((resolve) => {
      this.fileWarnings['foto'] = '';
      const reader = new FileReader();
      reader.onload = (e: any) => {
        const img = new Image();
        img.onload = () => {
          // 1. Validar relación de aspecto 4x4 (cuadrado)
          const aspectRatio = img.width / img.height;
          if (aspectRatio < 0.82 || aspectRatio > 1.18) {
            this.fileWarnings['foto'] = '⚠️ La imagen no tiene formato cuadrado (4x4). Se recomienda una foto cuadrada.';
          }

          // 2. Crear Canvas para analizar colores del fondo
          const canvas = document.createElement('canvas');
          canvas.width = 100;
          canvas.height = 100;
          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.drawImage(img, 0, 0, 100, 100);
            
            // Tomar muestras del fondo en la esquina superior izquierda (15, 15) y superior derecha (85, 15)
            const leftPixel = ctx.getImageData(15, 15, 1, 1).data;
            const rightPixel = ctx.getImageData(85, 15, 1, 1).data;

            const isRed = (pixel: Uint8ClampedArray) => {
              const r = pixel[0];
              const g = pixel[1];
              const b = pixel[2];
              // El rojo debe ser el canal dominante y tener una intensidad significativa
              return r > 115 && g < 95 && b < 95 && (r - g) > 40 && (r - b) > 40;
            };

            const leftRed = isRed(leftPixel);
            const rightRed = isRed(rightPixel);

            if (!leftRed && !rightRed) {
              if (this.fileWarnings['foto']) {
                this.fileWarnings['foto'] += ' Además, el color de fondo no parece ser ROJO. Recuerda que es obligatorio fondo rojo para la inscripción.';
              } else {
                this.fileWarnings['foto'] = '⚠️ El color de fondo no parece ser ROJO. Recuerda que es obligatorio subir una foto con fondo rojo.';
              }
            }
          }
          resolve();
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  /**
   * Elimina un archivo previamente seleccionado y limpia sus validaciones y variables asociadas.
   * @param fieldName El nombre del campo a vaciar.
   */
  removeFile(fieldName: string) {
    const archivos = this.inscriptionForm.get('archivos') as FormGroup;
    archivos.patchValue({ [fieldName]: null });
    archivos.get(fieldName)?.markAsTouched();
    this.fileNames[fieldName] = '';
    this.fileWarnings[fieldName] = ''; // Reiniciar advertencia

    const fileInput = document.getElementById('file_' + fieldName) as HTMLInputElement;
    if (fileInput) {
      fileInput.value = '';
    }
  }

  /**
   * Cierra el modal o ventana emergente de alertas (éxito o error).
   */
  closeModal() {
    this.showModal = false;
  }

  /**
   * Recolecta todos los datos del formulario (texto y archivos), los empaca en un FormData
   * y los envía al backend usando el InscriptionService.
   * Si es exitoso, muestra mensaje de éxito y reinicia el formulario.
   */
  onSubmit() {
    this.isSubmitted = true;
    console.log('Submitting final form...', this.inscriptionForm.value);

    if (!this.validateStep(3)) {
      this.modalType = 'error';
      this.modalMessage = 'Por favor, carga todos los documentos obligatorios en este paso para continuar.';
      this.showModal = true;
      return;
    }

    if (this.inscriptionForm.invalid) {
      this.modalType = 'error';
      this.modalMessage = 'Hay algunos campos inválidos en los pasos anteriores. Por favor, revísalos.';
      this.showModal = true;
      return;
    }

    this.isLoading = true;

    const formData = new FormData();
    Object.keys(this.inscriptionForm.value).forEach(key => {
      if (key !== 'archivos') {
        formData.append(key, this.inscriptionForm.value[key]);
      }
    });

    const archivos = this.inscriptionForm.value.archivos;
    Object.keys(archivos).forEach(key => {
      if (archivos[key]) {
        formData.append(key, archivos[key]);
      }
    });

    this.inscriptionService.enviarInscripcion(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.modalType = 'success';
        this.modalMessage = '¡Inscripción registrada con éxito! En breve recibirás un correo electrónico de confirmación con los siguientes pasos de tu proceso.';
        this.showModal = true;

        // Reiniciar formulario
        this.inscriptionForm.reset({
          userType: 'normal',
          tipoCurso: 'presencial',
          idioma: 'Inglés',
          archivos: { carnet: null, titulo: null, nacimiento: null, deposito: null, foto: null }
        });

        // Reiniciar nombres de archivos y valores reales de entrada
        Object.keys(this.fileNames).forEach(key => {
          this.fileNames[key] = '';
          const fileInput = document.getElementById('file_' + key) as HTMLInputElement;
          if (fileInput) {
            fileInput.value = '';
          }
        });

        this.isSubmitted = false;
        this.currentStep = 1;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      },
      error: (err) => {
        this.isLoading = false;
        console.error('Submission error:', err);
        this.modalType = 'error';
        if (err.status === 422 && err.error && err.error.errors) {
          const validationErrors = Object.values(err.error.errors).flat().join('\n');
          this.modalMessage = 'Errores de validación del servidor:\n' + validationErrors;
        } else {
          this.modalMessage = 'Hubo un problema al enviar tu formulario: ' + (err.error?.message || err.message || err);
        }
        this.showModal = true;
      }
    });
  }
}
