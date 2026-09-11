import { Component, OnInit, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NavbarComponent } from '../navbar/navbar.component';
import { FooterComponent } from '../footer/footer.component';
import { InscriptionService } from '../../services/inscription.service';
import { ImageCompressorService } from '../../services/image-compressor.service';
import { environment } from '../../../environments/environment';
import { Capacitor } from '@capacitor/core';
import { Geolocation } from '@capacitor/geolocation';

declare var L: any;

@Component({
  selector: 'app-inscription',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, NavbarComponent, FooterComponent],
  templateUrl: './inscription.component.html',
  styleUrl: './inscription.component.css'
})
export class InscriptionComponent implements OnInit, AfterViewInit {
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

  // Configuración de API dinámica (para APK móvil y pruebas)
  showApiConfigModal: boolean = false;
  customApiUrl: string = '';
  currentApiUrl: string = '';

  // Mapa interactivo para selección de Domicilio
  map: any = null;
  marker: any = null;
  isGeolocating: boolean = false;
  mapSearchQuery: string = '';
  isSearchingAddress: boolean = false;
  detectedLocationInfo: string = '';

  // Almacenar nombres de archivo seleccionados para dropzones personalizados
  fileNames: { [key: string]: string } = {
    carnet: '',
    titulo: '',
    nacimiento: '',
    deposito: '',
    foto: '',
    credencialEmi: '',
    carnetCossmil: '',
    carnetMilitarDoc: ''
  };

  // Warnings for mismatching filenames (Soft Warning - Option A)
  fileWarnings: { [key: string]: string } = {
    carnet: '',
    titulo: '',
    nacimiento: '',
    deposito: '',
    foto: '',
    credencialEmi: '',
    carnetCossmil: '',
    carnetMilitarDoc: ''
  };

  // Reglas de longitud y formato de celular por país
  phoneCountryRules: { [prefix: string]: { min: number; max: number; country: string; placeholder: string } } = {
    '+591': { min: 8, max: 8, country: 'Bolivia', placeholder: 'Ej. 70000000 (8 dígitos)' },
    '+54':  { min: 10, max: 10, country: 'Argentina', placeholder: 'Ej. 1123456789 (10 dígitos)' },
    '+55':  { min: 10, max: 11, country: 'Brasil', placeholder: 'Ej. 11987654321 (10 u 11 dígitos)' },
    '+56':  { min: 9, max: 9, country: 'Chile', placeholder: 'Ej. 912345678 (9 dígitos)' },
    '+51':  { min: 9, max: 9, country: 'Perú', placeholder: 'Ej. 912345678 (9 dígitos)' },
    '+57':  { min: 10, max: 10, country: 'Colombia', placeholder: 'Ej. 3001234567 (10 dígitos)' },
    '+34':  { min: 9, max: 9, country: 'España', placeholder: 'Ej. 612345678 (9 dígitos)' },
    '+1':   { min: 10, max: 10, country: 'EE.UU. / Canadá', placeholder: 'Ej. 2025550123 (10 dígitos)' }
  };

  currentPhoneRule = this.phoneCountryRules['+591'];
  isPhotoFondoRojoValid: boolean = true;

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

    // Pre-calentar servidor backend Render en segundo plano mientras el estudiante llena los datos
    try {
      this.inscriptionService.ping().subscribe({ error: () => {} });
    } catch (e) {}

    this.customApiUrl = localStorage.getItem('custom_api_url') || '';
    this.currentApiUrl = environment.apiUrl;
  }

  ngAfterViewInit() {
    if (this.currentStep === 2) {
      setTimeout(() => this.initMap(), 500);
    }
  }

  /**
   * Inicializa el mapa interactivo de OpenStreetMap con Leaflet
   */
  initMap() {
    if (typeof L === 'undefined') {
      console.warn('Leaflet aún no está cargado');
      return;
    }
    const mapEl = document.getElementById('map-domicilio');
    if (!mapEl) return;

    if (this.map) {
      setTimeout(() => {
        if (this.map) this.map.invalidateSize();
      }, 100);
      return;
    }

    // Coordenadas por defecto (La Paz, Bolivia)
    const defaultLat = -16.5000;
    const defaultLng = -68.1500;

    this.map = L.map('map-domicilio').setView([defaultLat, defaultLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(this.map);

    this.marker = L.marker([defaultLat, defaultLng], {
      draggable: true
    }).addTo(this.map);

    // Al hacer clic en cualquier punto del mapa
    this.map.on('click', (e: any) => {
      const { lat, lng } = e.latlng;
      this.marker.setLatLng([lat, lng]);
      this.reverseGeocode(lat, lng);
    });

    // Al arrastrar el pin
    this.marker.on('dragend', () => {
      const pos = this.marker.getLatLng();
      this.reverseGeocode(pos.lat, pos.lng);
    });

    setTimeout(() => {
      if (this.map) this.map.invalidateSize();
    }, 300);
  }

  /**
   * Obtiene la ubicación GPS precisa del estudiante (Nativo móvil y Web)
   */
  async usarUbicacionGps() {
    this.isGeolocating = true;
    try {
      if (Capacitor.isNativePlatform()) {
        const permStatus = await Geolocation.checkPermissions();
        if (permStatus.location !== 'granted') {
          await Geolocation.requestPermissions();
        }

        const position = await Geolocation.getCurrentPosition({
          enableHighAccuracy: true,
          timeout: 10000
        });

        this.isGeolocating = false;
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        if (!this.map) this.initMap();
        if (this.map && this.marker) {
          this.map.setView([lat, lng], 17);
          this.marker.setLatLng([lat, lng]);
          this.reverseGeocode(lat, lng);
        }
        return;
      }

      // En la web del navegador, usar directamente navigator.geolocation
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            this.isGeolocating = false;
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            if (!this.map) this.initMap();
            if (this.map && this.marker) {
              this.map.setView([lat, lng], 17);
              this.marker.setLatLng([lat, lng]);
              this.reverseGeocode(lat, lng);
            }
          },
          (err) => {
            this.isGeolocating = false;
            console.warn('Aviso geolocalización web:', err.message || err);
            alert('Asegúrate de permitir el acceso de ubicación a la página en tu navegador.');
          },
          { enableHighAccuracy: true, timeout: 10000 }
        );
      } else {
        this.isGeolocating = false;
        alert('Tu navegador no soporta geolocalización GPS.');
      }
    } catch (e: any) {
      this.isGeolocating = false;
      console.warn('Aviso geolocalización:', e.message || e);
    }
  }

  /**
   * Geocodificación inversa: Convierte latitud/longitud a Nombre de Calle y Zona
   */
  reverseGeocode(lat: number, lng: number) {
    this.isSearchingAddress = true;
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
    fetch(url)
      .then(res => res.json())
      .then(data => {
        this.isSearchingAddress = false;
        if (data && data.address) {
          const addr = data.address;
          const road = addr.road || addr.pedestrian || addr.street || '';
          const suburb = addr.suburb || addr.neighbourhood || addr.quarter || addr.residential || addr.city_district || '';
          const city = addr.city || addr.town || addr.village || '';

          let direccionFinal = '';
          if (suburb && road) {
            direccionFinal = `Zona ${suburb}, ${road}`;
          } else if (road) {
            direccionFinal = road + (city ? `, ${city}` : '');
          } else if (suburb) {
            direccionFinal = `Zona ${suburb}` + (city ? `, ${city}` : '');
          } else if (data.display_name) {
            const parts = data.display_name.split(',');
            direccionFinal = parts.slice(0, 3).join(',').trim();
          } else {
            direccionFinal = `Zona Central (Ubicación fijada en mapa)`;
          }

          this.detectedLocationInfo = direccionFinal;
          this.inscriptionForm.get('domicilio')?.patchValue(direccionFinal);
          this.inscriptionForm.get('domicilio')?.markAsDirty();
          this.inscriptionForm.get('domicilio')?.markAsTouched();
        }
      })
      .catch(err => {
        this.isSearchingAddress = false;
        console.error('Error reverse geocoding:', err);
      });
  }

  /**
   * Busca una zona o calle escrita por el usuario y centra el mapa
   */
  buscarEnMapa() {
    if (!this.mapSearchQuery || !this.mapSearchQuery.trim()) return;
    this.isSearchingAddress = true;
    const query = encodeURIComponent(this.mapSearchQuery.trim() + ', Bolivia');
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`)
      .then(res => res.json())
      .then(results => {
        this.isSearchingAddress = false;
        if (results && results.length > 0) {
          const first = results[0];
          const lat = parseFloat(first.lat);
          const lng = parseFloat(first.lon);
          if (!this.map) {
            this.initMap();
          }
          if (this.map && this.marker) {
            this.map.setView([lat, lng], 16);
            this.marker.setLatLng([lat, lng]);
            this.reverseGeocode(lat, lng);
          }
        } else {
          alert('No encontramos esa zona en el mapa. Intenta hacer clic manualmente sobre el mapa.');
        }
      })
      .catch(() => {
        this.isSearchingAddress = false;
      });
  }

  /**
   * Abre el modal de configuración de conexión
   */
  openApiConfig() {
    this.customApiUrl = localStorage.getItem('custom_api_url') || '';
    this.showApiConfigModal = true;
  }

  /**
   * Cierra el modal de configuración de conexión
   */
  closeApiConfig() {
    this.showApiConfigModal = false;
  }

  /**
   * Guarda la URL personalizada de la API y recarga la aplicación
   */
  saveApiConfig() {
    if (this.customApiUrl && this.customApiUrl.trim() !== '') {
      let url = this.customApiUrl.trim();
      // Eliminar barra diagonal final si existe
      if (url.endsWith('/')) {
        url = url.slice(0, -1);
      }
      // Asegurar protocolo
      if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'http://' + url;
      }
      localStorage.setItem('custom_api_url', url);
    } else {
      localStorage.removeItem('custom_api_url');
    }
    this.showApiConfigModal = false;
    window.location.reload();
  }

  /**
   * Restablece la URL a los valores de fábrica
   */
  resetApiConfig() {
    localStorage.removeItem('custom_api_url');
    this.showApiConfigModal = false;
    window.location.reload();
  }

  /**
   * Inicializa el formulario reactivo con todos sus campos y reglas de validación (obligatorio, longitud, etc.).
   */
  autoGenerateCossmil() {
    // Si no es militar ni hijo_militar, NO generar Cossmil y mantenerlo vacío
    if (this.userType !== 'militar' && this.userType !== 'hijo_militar') {
      this.inscriptionForm.get('carnetCossmil')?.patchValue('', { emitEvent: false });
      return;
    }

    const fecha = this.inscriptionForm.get('fechaNacimiento')?.value;
    const nombres = (this.inscriptionForm.get('nombres')?.value || '').trim();
    const apellidosStr = (this.inscriptionForm.get('apellidos')?.value || '').trim();
    const apellidos = apellidosStr ? apellidosStr.split(/\s+/) : [];

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
      this.inscriptionForm.get('carnetCossmil')?.patchValue((numPart + letPart).toUpperCase(), { emitEvent: false });
    }
  }

  initForm() {
    this.inscriptionForm = this.fb.group({
      userType: ['normal', Validators.required],
      nombres: ['', [Validators.required, Validators.minLength(3), Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/)]],
      apellidos: ['', [Validators.required, Validators.minLength(2), Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/)]],
      gradoAcademico: [''],
      armaEspecialidad: [''],
      lugarNacimiento: ['', Validators.required],
      fechaNacimiento: ['', Validators.required],
      ci: ['', [Validators.required, Validators.pattern(/^[0-9]{7,8}$/)]],
      expedido: ['', Validators.required],
      carnetMilitar: [''],
      carnetMilitarSerie: [''],
      carnetCossmil: [''],
      estadoCivil: ['', Validators.required],
      grupoSanguineo: ['', Validators.required],
      celularPrefix: ['+591', Validators.required],
      celular: ['', [Validators.required, Validators.pattern(/^[0-9]{8}$/)]],
      anioBachiller: ['', [Validators.required, Validators.pattern(/^(19[5-9]\d|20[0-2]\d)$/)]],
      edad: ['', [Validators.required, Validators.min(5), Validators.max(60)]],
      email: ['', [Validators.required, Validators.email]],
      domicilio: ['', Validators.required],
      horario: ['', Validators.required],
      nivel: ['', Validators.required],
      idioma: ['Inglés', Validators.required],
      tipoCurso: ['regular', Validators.required],
      nombrePadres: ['', [Validators.required, Validators.minLength(6), Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/)]],
      ciTutor: ['', [Validators.required, Validators.pattern(/^[0-9]{7,8}$/)]],
      hermanosInscritos: [''],
      contactoEmergencia: ['', [Validators.required, Validators.minLength(4)]],
      archivos: this.fb.group({
        carnet: [null, Validators.required],
        titulo: [null, Validators.required],
        nacimiento: [null, Validators.required],
        deposito: [null], // Obligatorio SOLO para Estudiantes EMI
        foto: [null, Validators.required],
        credencialEmi: [null],
        carnetCossmil: [null],
        carnetMilitarDoc: [null]
      })
    });
  }

  /**
   * Escucha los cambios en tiempo real de campos específicos (como fecha de nacimiento o tipo de usuario)
   * para calcular valores automáticamente o cambiar las reglas de validación.
   */
  setupFormSubscriptions() {
    // 1. Calcular edad automáticamente desde la fecha de nacimiento y autogenerar Cossmil
    this.inscriptionForm.get('fechaNacimiento')?.valueChanges.subscribe(value => {
      if (value) {
        const age = this.calculateAge(value);
        this.inscriptionForm.get('edad')?.patchValue(age, { emitEvent: false });
        this.inscriptionForm.get('edad')?.markAsTouched();
        this.autoGenerateCossmil();
      }
    });

    // Escuchar cambios de nombres y apellidos para COSSMIL
    this.inscriptionForm.get('nombres')?.valueChanges.subscribe(() => {
      this.autoGenerateCossmil();
    });

    this.inscriptionForm.get('apellidos')?.valueChanges.subscribe(() => {
      this.autoGenerateCossmil();
    });

    // Escuchar cambio de país para adaptar el límite y validación del número de celular
    this.inscriptionForm.get('celularPrefix')?.valueChanges.subscribe(prefix => {
      this.currentPhoneRule = this.phoneCountryRules[prefix] || { min: 7, max: 12, country: 'Internacional', placeholder: 'Número de celular' };
      const celularCtrl = this.inscriptionForm.get('celular');
      if (celularCtrl) {
        celularCtrl.setValidators([
          Validators.required,
          Validators.pattern(new RegExp(`^[0-9]{${this.currentPhoneRule.min},${this.currentPhoneRule.max}}$`))
        ]);
        celularCtrl.updateValueAndValidity();
      }
    });

    // 2. Limpiar campos militares si se selecciona tipo de usuario civil o hijo de militar, o limpiar padres si es militar
    this.inscriptionForm.get('userType')?.valueChanges.subscribe(type => {
      const nombrePadresCtrl = this.inscriptionForm.get('nombrePadres');
      const ciTutorCtrl = this.inscriptionForm.get('ciTutor');
      const carnetCossmilFileCtrl = this.inscriptionForm.get('archivos.carnetCossmil');
      const depositoFileCtrl = this.inscriptionForm.get('archivos.deposito');

      if (type !== 'militar') {
        this.inscriptionForm.patchValue({
          gradoAcademico: '',
          armaEspecialidad: '',
          carnetMilitar: '',
          carnetCossmil: ''
        }, { emitEvent: false });
        
        // Re-add validators for non-military
        nombrePadresCtrl?.setValidators([Validators.required, Validators.minLength(6), Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/)]);
        ciTutorCtrl?.setValidators([Validators.required, Validators.pattern(/^[0-9]{7,8}$/)]);
      } else {
        // Remove validators for military
        nombrePadresCtrl?.clearValidators();
        ciTutorCtrl?.clearValidators();
      }

      // Carnet COSSMIL es obligatorio para militar e hijo_militar
      if (type === 'militar' || type === 'hijo_militar') {
        carnetCossmilFileCtrl?.setValidators([Validators.required]);
        this.fileNames['carnetCossmil'] = '';
      } else {
        carnetCossmilFileCtrl?.clearValidators();
        carnetCossmilFileCtrl?.reset(null);
        this.fileNames['carnetCossmil'] = '';
      }

      // Boleta de Depósito / Inscripción EMI: SOLO requerida para Estudiantes EMI
      if (type === 'emi') {
        depositoFileCtrl?.setValidators([Validators.required]);
      } else {
        depositoFileCtrl?.clearValidators();
        depositoFileCtrl?.reset(null);
        this.fileNames['deposito'] = '';
      }

      nombrePadresCtrl?.updateValueAndValidity({ emitEvent: false });
      ciTutorCtrl?.updateValueAndValidity({ emitEvent: false });
      carnetCossmilFileCtrl?.updateValueAndValidity({ emitEvent: false });
      depositoFileCtrl?.updateValueAndValidity({ emitEvent: false });
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

  /**
   * Bloquea terminantemente números, guiones, símbolos y caracteres especiales, permitiendo ÚNICAMENTE letras y espacios
   */
  blockNumbers(event: KeyboardEvent) {
    this.onlyLetters(event);
  }

  onlyLetters(event: KeyboardEvent) {
    if (event.ctrlKey || event.altKey || event.metaKey) {
      return;
    }
    if (['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Delete', 'Home', 'End', 'Escape'].includes(event.key)) {
      return;
    }
    // Permitir estrictamente letras mayúsculas, minúsculas, tildes, diéresis, ñ y espacios
    if (event.key && event.key.length === 1 && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(event.key)) {
      event.preventDefault();
      event.stopPropagation();
    }
  }

  /**
   * Bloquea en Android / teclados móviles virtuales la inserción de cualquier carácter no alfabético
   */
  blockNumbersBeforeInput(event: any) {
    this.onlyLettersBeforeInput(event);
  }

  onlyLettersBeforeInput(event: any) {
    if (event.data && /[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/.test(event.data)) {
      event.preventDefault();
      event.stopPropagation();
    }
  }

  /**
   * Limita el año de bachiller a exactamente 4 dígitos numéricos en tiempo real
   */
  onYearInput(event: any) {
    const input = event.target as HTMLInputElement;
    if (input && input.value) {
      const clean = input.value.replace(/[^0-9]/g, '').slice(0, 4);
      if (input.value !== clean) {
        input.value = clean;
      }
      this.inscriptionForm.get('anioBachiller')?.setValue(clean);
      this.inscriptionForm.get('anioBachiller')?.markAsDirty();
    }
  }

  /**
   * Limita el número de celular a la cantidad máxima permitida por el país seleccionado
   */
  onPhoneInput(event: any) {
    const input = event.target as HTMLInputElement;
    if (input && input.value) {
      const max = this.currentPhoneRule.max || 10;
      const clean = input.value.replace(/[^0-9]/g, '').slice(0, max);
      if (input.value !== clean) {
        input.value = clean;
      }
      this.inscriptionForm.get('celular')?.setValue(clean);
      this.inscriptionForm.get('celular')?.markAsDirty();
    }
  }

  /**
   * Sanitiza el texto al pegar desde el portapapeles permitiendo ÚNICAMENTE letras y espacios
   */
  onPasteSanitize(event: ClipboardEvent, controlName: string) {
    const pasted = event.clipboardData?.getData('text') || '';
    if (/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/.test(pasted)) {
      event.preventDefault();
      const clean = pasted.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
      const input = event.target as HTMLInputElement;
      const start = input.selectionStart || 0;
      const end = input.selectionEnd || 0;
      const current = input.value || '';
      const updated = current.substring(0, start) + clean + current.substring(end);
      input.value = updated;
      this.inscriptionForm.get(controlName)?.setValue(updated);
      this.inscriptionForm.get(controlName)?.markAsDirty();
      this.inscriptionForm.get(controlName)?.markAsTouched();
    }
  }

  /**
   * Filtra en tiempo real (evento input) para asegurar que el valor contenga ÚNICAMENTE letras y espacios
   */
  filterLetters(event: Event, controlName: string) {
    const input = event.target as HTMLInputElement;
    if (!input) return;
    const clean = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
    if (input.value !== clean) {
      input.value = clean;
      this.inscriptionForm.get(controlName)?.setValue(clean);
      this.inscriptionForm.get(controlName)?.markAsDirty();
    }
  }

  onLetterInput(event: any, controlName: string) {
    this.filterLetters(event, controlName);
  }

  onInputSanitize(event: any, controlName: string) {
    this.filterLetters(event, controlName);
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
        const credencialCtrl = archivosGroup.get('credencialEmi');
        const depositoCtrl = archivosGroup.get('deposito');
        const cossmilCtrl = archivosGroup.get('carnetCossmil');
        const militarDocCtrl = archivosGroup.get('carnetMilitarDoc');

        if (this.userType === 'emi') {
          credencialCtrl?.setValidators(Validators.required);
          depositoCtrl?.setValidators(Validators.required);
          cossmilCtrl?.clearValidators();
          militarDocCtrl?.clearValidators();
          cossmilCtrl?.reset(null);
          militarDocCtrl?.reset(null);
        } else if (this.userType === 'militar') {
          cossmilCtrl?.setValidators(Validators.required);
          militarDocCtrl?.setValidators(Validators.required);
          credencialCtrl?.clearValidators();
          depositoCtrl?.clearValidators();
          credencialCtrl?.reset(null);
          depositoCtrl?.reset(null);
        } else if (this.userType === 'hijo_militar') {
          cossmilCtrl?.setValidators(Validators.required);
          militarDocCtrl?.clearValidators();
          credencialCtrl?.clearValidators();
          depositoCtrl?.clearValidators();
          militarDocCtrl?.reset(null);
          credencialCtrl?.reset(null);
          depositoCtrl?.reset(null);
        } else {
          // Usuario normal / civil: Ningún documento militar ni de EMI
          credencialCtrl?.clearValidators();
          depositoCtrl?.clearValidators();
          cossmilCtrl?.clearValidators();
          militarDocCtrl?.clearValidators();
          credencialCtrl?.reset(null);
          depositoCtrl?.reset(null);
          cossmilCtrl?.reset(null);
          militarDocCtrl?.reset(null);
          this.fileNames['carnetCossmil'] = '';
          this.fileNames['carnetMilitarDoc'] = '';
          this.fileNames['credencialEmi'] = '';
          this.fileNames['deposito'] = '';
        }

        credencialCtrl?.updateValueAndValidity({ emitEvent: false });
        depositoCtrl?.updateValueAndValidity({ emitEvent: false });
        cossmilCtrl?.updateValueAndValidity({ emitEvent: false });
        militarDocCtrl?.updateValueAndValidity({ emitEvent: false });

        let isValid = true;
        Object.keys(archivosGroup.controls).forEach(key => {
          if ((key === 'credencialEmi' || key === 'deposito') && this.userType !== 'emi') return;
          if (key === 'carnetCossmil' && this.userType !== 'militar' && this.userType !== 'hijo_militar') return;
          if (key === 'carnetMilitarDoc' && this.userType !== 'militar') return;

          const control = archivosGroup.controls[key];
          control.markAsTouched();
          if (control.invalid) {
            isValid = false;
          }
        });

        // La foto debe tener fondo rojo
        if (!this.isPhotoFondoRojoValid) {
          isValid = false;
        }

        return isValid;
      }
      return false;
    }

    return true;
  }

  /**
   * Obtiene la lista descriptiva de errores y campos vacíos del paso indicado
   */
  getStepErrors(step: number): string[] {
    const errors: string[] = [];
    const f = this.inscriptionForm.controls;

    if (step === 1) {
      if (f['nombres'].errors) {
        if (f['nombres'].errors['required']) errors.push('• Nombres: Campo obligatorio.');
        else if (f['nombres'].errors['minlength']) errors.push('• Nombres: Mínimo 3 letras.');
        else if (f['nombres'].errors['pattern']) errors.push('• Nombres: Solo se permiten letras (no se admiten números).');
      }
      if (f['apellidos'].errors) {
        if (f['apellidos'].errors['required']) errors.push('• Apellidos: Campo obligatorio.');
        else if (f['apellidos'].errors['minlength']) errors.push('• Apellidos: Mínimo 2 letras.');
        else if (f['apellidos'].errors['pattern']) errors.push('• Apellidos: Solo se permiten letras (no se admiten números).');
      }
      if (f['ci'].errors) {
        errors.push('• Cédula de Identidad: Debe contener entre 7 y 8 dígitos numéricos.');
      }
      if (f['expedido'].errors) {
        errors.push('• Lugar de Expedición: Debes seleccionar el departamento emisor.');
      }
      if (f['lugarNacimiento'].errors) {
        errors.push('• Lugar de Nacimiento: Debes seleccionar tu lugar de nacimiento.');
      }
      if (f['fechaNacimiento'].errors) {
        errors.push('• Fecha de Nacimiento: Campo obligatorio.');
      }
      if (f['estadoCivil'].errors) {
        errors.push('• Estado Civil: Debes seleccionar una opción.');
      }
      if (this.userType !== 'militar') {
        if (f['nombrePadres'].errors) {
          if (f['nombrePadres'].errors['required']) errors.push('• Nombres de los Padres / Tutor: Campo obligatorio.');
          else if (f['nombrePadres'].errors['pattern']) errors.push('• Nombres de los Padres / Tutor: Solo se permiten letras (no números).');
          else if (f['nombrePadres'].errors['minlength']) errors.push('• Nombres de los Padres / Tutor: Mínimo 6 caracteres.');
        }
        if (f['ciTutor'].errors) {
          errors.push('• C.I. del Tutor: Debe contener 7 u 8 dígitos numéricos.');
        }
      }
      if (f['contactoEmergencia'].errors) {
        errors.push('• Contacto de Emergencia: Campo obligatorio (mínimo 4 caracteres).');
      }
    } else if (step === 2) {
      if (f['email'].errors) {
        errors.push('• Correo Electrónico: Ingresa un correo válido (ej. usuario@ejemplo.com).');
      }
      if (f['celular'].errors) {
        const rule = this.currentPhoneRule;
        errors.push(`• Celular: Para ${rule.country} (${f['celularPrefix'].value}), debe tener ${rule.min === rule.max ? ('exactamente ' + rule.min) : ('entre ' + rule.min + ' y ' + rule.max)} dígitos numéricos.`);
      }
      if (f['domicilio'].errors) {
        errors.push('• Domicilio (Zona y Calle): Campo obligatorio (puedes seleccionarlo en el mapa).');
      }
      if (f['grupoSanguineo'].errors) {
        errors.push('• Grupo Sanguíneo: Debes seleccionar una opción.');
      }
      if (f['anioBachiller'].errors) {
        errors.push('• Año Bachiller: Debe ser un año de 4 dígitos entre 1950 y 2026 (Ej. 2020).');
      }
      if (f['tipoCurso'].errors) {
        errors.push('• Modalidad del Curso: Campo obligatorio.');
      }
      if (f['idioma'].errors) {
        errors.push('• Idioma: Campo obligatorio.');
      }
      if (f['nivel'].errors) {
        errors.push('• Nivel: Debes seleccionar un nivel de estudio.');
      }
      if (f['horario'].errors) {
        errors.push('• Horario: Debes seleccionar un horario.');
      }
    }
    return errors;
  }

  /**
   * Obtiene la lista de documentos que aún no se han subido en el paso 3
   */
  getMissingDocuments(): string[] {
    const missing: string[] = [];
    const archivosGroup = this.inscriptionForm.get('archivos') as FormGroup;
    if (!archivosGroup) return missing;

    if (this.userType === 'normal') {
      // Validación estricta de los 4 requisitos de Usuario Normal
      if (archivosGroup.get('carnet')?.invalid || !this.fileNames['carnet']) {
        missing.push('• Carnet de Identidad * (Requisito Obligatorio: PDF o Foto)');
      }
      if (archivosGroup.get('titulo')?.invalid || !this.fileNames['titulo']) {
        missing.push('• Título de Bachiller * (Requisito Obligatorio: PDF o Foto)');
      }
      if (archivosGroup.get('nacimiento')?.invalid || !this.fileNames['nacimiento']) {
        missing.push('• Certificado de Nacimiento * (Requisito Obligatorio: PDF o Foto)');
      }
      if (archivosGroup.get('foto')?.invalid || !this.fileNames['foto']) {
        missing.push('• Fotografía Personal 4x4 * (Requisito Obligatorio: Foto 4x4 con fondo rojo)');
      } else if (!this.isPhotoFondoRojoValid) {
        missing.push('• Fotografía Personal 4x4 * (No cumple con el requisito de fondo ROJO obligatorio)');
      }
      return missing;
    }

    if (archivosGroup.get('foto')?.invalid || !this.fileNames['foto']) {
      missing.push('• Fotografía Personal 4x4: Obligatorio (Solo imagen JPG o PNG, fondo rojo)');
    } else if (!this.isPhotoFondoRojoValid) {
      missing.push('• Fotografía Personal 4x4: No cumple con el fondo ROJO obligatorio');
    }
    if (archivosGroup.get('carnet')?.invalid || !this.fileNames['carnet']) {
      missing.push('• Carnet de Identidad: Obligatorio (PDF o Foto)');
    }
    if (archivosGroup.get('titulo')?.invalid || !this.fileNames['titulo']) {
      missing.push('• Título de Bachiller: Obligatorio (PDF o Foto)');
    }
    if (archivosGroup.get('nacimiento')?.invalid || !this.fileNames['nacimiento']) {
      missing.push('• Certificado de Nacimiento: Obligatorio (PDF o Foto)');
    }
    if (this.userType === 'emi') {
      if (archivosGroup.get('credencialEmi')?.invalid || !this.fileNames['credencialEmi']) {
        missing.push('• Credencial o Factura EMI: Obligatorio para estudiantes EMI');
      }
      if (archivosGroup.get('deposito')?.invalid || !this.fileNames['deposito']) {
        missing.push('• Boleta de Pago / Depósito EMI: Obligatorio');
      }
    }
    if (this.userType === 'militar') {
      if (archivosGroup.get('carnetCossmil')?.invalid || !this.fileNames['carnetCossmil']) {
        missing.push('• Carnet de COSSMIL: Obligatorio para postulante militar');
      }
      if (archivosGroup.get('carnetMilitarDoc')?.invalid || !this.fileNames['carnetMilitarDoc']) {
        missing.push('• Carnet Militar / Credencial: Obligatorio para postulante militar');
      }
    }
    if (this.userType === 'hijo_militar') {
      if (archivosGroup.get('carnetCossmil')?.invalid || !this.fileNames['carnetCossmil']) {
        missing.push('• Carnet de COSSMIL: Obligatorio para hijo de militar');
      }
    }
    return missing;
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
        if (this.currentStep === 2) {
          setTimeout(() => this.initMap(), 400);
        }
      }
    } else {
      const stepErrors = this.getStepErrors(this.currentStep);
      this.modalType = 'error';
      this.modalMessage = `Por favor revisa y completa los siguientes campos del Paso ${this.currentStep}:\n\n` + stepErrors.join('\n');
      this.showModal = true;
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
      if (this.currentStep === 2) {
        setTimeout(() => this.initMap(), 400);
      }
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
      if (this.currentStep === 2) {
        setTimeout(() => this.initMap(), 400);
      }
    } else if (step > this.currentStep) {
      for (let s = this.currentStep; s < step; s++) {
        if (!this.validateStep(s)) {
          const stepErrors = this.getStepErrors(s);
          this.modalType = 'error';
          this.modalMessage = `No puedes avanzar al Paso ${step} sin completar correctamente el Paso ${s}:\n\n` + stepErrors.join('\n');
          this.showModal = true;
          return;
        }
      }
      this.currentStep = step;
      this.isSubmitted = false;
      if (this.currentStep === 2) {
        setTimeout(() => this.initMap(), 400);
      }
    }
  }

  /**
   * Valida la seguridad y autenticidad del archivo (Magic Bytes, extensión y detección de scripts maliciosos en PDFs).
   */
  private async validateFileSecurity(file: File, fieldName: string): Promise<{ valid: boolean; errorMsg?: string }> {
    const ext = (file.name.split('.').pop() || '').toLowerCase();

    // 1. Validación de tamaño (Máximo 5MB, mínimo 50 bytes)
    if (file.size > 5 * 1024 * 1024) {
      return { valid: false, errorMsg: '⚠️ El archivo supera el tamaño máximo permitido de 5 MB.' };
    }
    if (file.size < 50) {
      return { valid: false, errorMsg: '⚠️ El archivo está vacío o dañado (tamaño inferior a 50 bytes).' };
    }

    // 2. Fotografía 4x4 SOLO puede ser imagen (JPG o PNG), NUNCA PDF
    if (fieldName === 'foto') {
      const allowedImageExts = ['jpg', 'jpeg', 'png', 'webp'];
      if (!allowedImageExts.includes(ext)) {
        return { valid: false, errorMsg: '⚠️ La Fotografía Personal 4x4 debe ser una imagen (JPG o PNG). No se permite formato PDF ni otros documentos.' };
      }
    } else {
      // Documentos generales: PDF o Imagen
      const allowedDocExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
      if (!allowedDocExts.includes(ext)) {
        return { valid: false, errorMsg: `⚠️ Extensión .${ext} no permitida. Solo se admiten documentos PDF e imágenes oficiales (JPG, PNG).` };
      }
    }

    // 3. Inspección binaria de Magic Bytes (Firmas de archivo reales)
    try {
      const headerBuffer = await file.slice(0, 16).arrayBuffer();
      const bytes = new Uint8Array(headerBuffer);

      if (ext === 'pdf') {
        // Cabecera PDF: %PDF- (0x25, 0x50, 0x44, 0x46, 0x2D)
        const isPdf = bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46;
        if (!isPdf) {
          return { valid: false, errorMsg: '⚠️ Archivo no auténtico: El archivo seleccionado no corresponde a un documento PDF válido (cabecera corrupta o falsa).' };
        }

        // 4. Detección de código maligno en PDFs (/JavaScript, /Launch, /EmbeddedFiles, etc.)
        const textSlice = await file.slice(0, Math.min(file.size, 1024 * 1024)).text();
        const maliciousPatterns = [
          { regex: /\/JavaScript/i, name: 'scripts ejecutables (/JavaScript)' },
          { regex: /\/JS\s*[\(\[<]/i, name: 'scripts embebidos (/JS)' },
          { regex: /\/Launch/i, name: 'comandos del sistema (/Launch)' },
          { regex: /\/EmbeddedFiles/i, name: 'archivos ejecutables incrustados (/EmbeddedFiles)' },
          { regex: /<\?php/i, name: 'código PHP incrustado' },
          { regex: /<\?=/i, name: 'código PHP abreviado' },
          { regex: /<script/i, name: 'etiquetas web de script (<script)' },
          { regex: /eval\s*\(/i, name: 'ejecución dinámica eval()' }
        ];

        for (const item of maliciousPatterns) {
          if (item.regex.test(textSlice)) {
            return {
              valid: false,
              errorMsg: `⚠️ ALERTA DE SEGURIDAD: Se ha detectado ${item.name} dentro del documento PDF. Por estrictas políticas de ciberseguridad institucional de la EIE, este archivo no puede ser subido.`
            };
          }
        }
      } else if (ext === 'jpg' || ext === 'jpeg') {
        // JPEG Magic bytes: FF D8 FF
        const isJpg = bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF;
        if (!isJpg) {
          return { valid: false, errorMsg: '⚠️ Archivo no auténtico: La cabecera binaria no coincide con una imagen JPEG/JPG real.' };
        }
      } else if (ext === 'png') {
        // PNG Magic bytes: 89 50 4E 47
        const isPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47;
        if (!isPng) {
          return { valid: false, errorMsg: '⚠️ Archivo no auténtico: La cabecera binaria no coincide con una imagen PNG real.' };
        }
      }
    } catch (e: any) {
      console.warn('Error al verificar magic bytes:', e);
    }

    return { valid: true };
  }

  // Files operations — with client-side image compression (RF16 - HU16 - T2)
  /**
   * Se activa cuando el usuario selecciona un archivo para subir.
   * Realiza validaciones de ciberseguridad, coherencia, comprime imágenes y guarda el archivo en memoria.
   * @param event El evento input con el archivo.
   * @param fieldName El nombre del campo (carnet, titulo, etc.).
   */
  async onFileSelected(event: any, fieldName: string) {
    const file = event.target.files[0];
    if (!file) return;

    const fileInput = event.target as HTMLInputElement;
    const archivos = this.inscriptionForm.get('archivos') as FormGroup;

    // Reiniciar advertencia previa
    this.fileWarnings[fieldName] = '';

    // ================= SEGURIDAD Y VERIFICACIÓN BINARIA =================
    const securityCheck = await this.validateFileSecurity(file, fieldName);
    if (!securityCheck.valid) {
      if (fileInput) fileInput.value = '';
      archivos.patchValue({ [fieldName]: null });
      this.fileNames[fieldName] = '';
      this.modalType = 'error';
      this.modalMessage = securityCheck.errorMsg || 'Archivo no permitido por razones de seguridad.';
      this.showModal = true;
      return;
    }

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

    if (fieldName === 'carnetMilitarDoc') {
      if (nameLower.includes('cossmil') || nameLower.includes('titulo') || nameLower.includes('nacimiento')) {
        this.fileWarnings[fieldName] = 'El archivo seleccionado parece ser otro documento en lugar de tu Carnet Militar.';
      }
    }

    if (file.type.startsWith('image/') || /\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
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
      // PDFs genuinos sin compresión
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
              this.isPhotoFondoRojoValid = false;
              if (this.fileWarnings['foto']) {
                this.fileWarnings['foto'] += ' Además, el color de fondo no parece ser ROJO. Recuerda que es obligatorio fondo rojo para la inscripción.';
              } else {
                this.fileWarnings['foto'] = '⚠️ El color de fondo no parece ser ROJO. Recuerda que es obligatorio subir una foto con fondo rojo.';
              }
            } else {
              this.isPhotoFondoRojoValid = true;
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
    if (fieldName === 'foto') {
      this.isPhotoFondoRojoValid = true;
    }

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

    const missingDocs = this.getMissingDocuments();
    if (missingDocs.length > 0 || !this.validateStep(3)) {
      this.modalType = 'error';
      if (this.userType === 'normal') {
        this.modalMessage = 'No cumple con los requisitos obligatorios para la inscripción:\n\n' + missingDocs.join('\n') + '\n\nPara completar su registro como Usuario Normal debe presentar obligatoriamente: Carnet de Identidad, Título de Bachiller, Certificado de Nacimiento y Fotografía Personal 4x4 con fondo rojo.';
      } else {
        this.modalMessage = 'No se puede enviar la inscripción porque faltan documentos obligatorios o no cumplen con los requisitos:\n\n' + missingDocs.join('\n');
      }
      this.showModal = true;
      return;
    }

    if (!this.validateStep(1) || !this.validateStep(2) || this.inscriptionForm.invalid) {
      const allErrors: string[] = [];
      const s1 = this.getStepErrors(1);
      const s2 = this.getStepErrors(2);
      if (s1.length > 0) allErrors.push('--- PASO 1 (Datos Personales) ---', ...s1);
      if (s2.length > 0) allErrors.push('--- PASO 2 (Contacto y Curso) ---', ...s2);

      this.modalType = 'error';
      this.modalMessage = 'Hay campos obligatorios incompletos o con formato incorrecto:\n\n' + allErrors.join('\n');
      this.showModal = true;
      return;
    }

    this.isLoading = true;

    const formData = new FormData();
    const formVal = this.inscriptionForm.value;

    // Solo anexar campos de texto que apliquen y que no estén vacíos
    Object.keys(formVal).forEach(key => {
      if (key !== 'archivos') {
        const val = formVal[key];
        // Si es usuario normal, excluir campos militares
        if (this.userType === 'normal' && (key === 'carnetCossmil' || key === 'carnetMilitar' || key === 'carnetMilitarSerie' || key === 'gradoAcademico' || key === 'armaEspecialidad')) {
          return;
        }
        if (val !== null && val !== undefined && val !== '') {
          formData.append(key, val);
        }
      }
    });

    const archivos = formVal.archivos || {};
    Object.keys(archivos).forEach(key => {
      const file = archivos[key];
      if (file instanceof File || file instanceof Blob) {
        // Excluir archivos que no correspondan al tipo de usuario
        if (this.userType === 'normal' && (key === 'carnetCossmil' || key === 'carnetMilitarDoc' || key === 'credencialEmi' || key === 'deposito')) {
          return;
        }
        if (this.userType !== 'emi' && (key === 'credencialEmi' || key === 'deposito')) {
          return;
        }
        if (this.userType !== 'militar' && this.userType !== 'hijo_militar' && key === 'carnetCossmil') {
          return;
        }
        if (this.userType !== 'militar' && key === 'carnetMilitarDoc') {
          return;
        }
        formData.append(key, file);
      }
    });

    this.inscriptionService.enviarInscripcion(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.modalType = 'success';
        this.modalMessage = '¡Inscripción enviada con éxito! Tu solicitud ha sido registrada correctamente y pasará a revisión por el área académica/administrativa.';
        this.showModal = true;

        // Reiniciar formulario
        this.inscriptionForm.reset({
          userType: 'normal',
          tipoCurso: 'presencial',
          idioma: 'Inglés',
          celularPrefix: '+591',
          archivos: { carnet: null, titulo: null, nacimiento: null, deposito: null, foto: null, credencialEmi: null, carnetCossmil: null, carnetMilitarDoc: null }
        });

        this.isPhotoFondoRojoValid = true;

        // Reiniciar nombres de archivos y valores reales de entrada
        Object.keys(this.fileNames).forEach(key => {
          this.fileNames[key] = '';
          this.fileWarnings[key] = '';
          const fileInput = document.getElementById('file_' + key) as HTMLInputElement;
          if (fileInput) {
            fileInput.value = '';
          }
        });

        this.isSubmitted = false;
        this.currentStep = 1;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      },
      error: (err: any) => {
        this.isLoading = false;
        console.error('Submission error:', err);
        this.modalType = 'error';

        const errObj = err || {};
        const errorList: string[] = [];

        // Extraer lista de errores del backend (soporta claves en español e inglés)
        const rawErrors = errObj.errores || errObj.errors || errObj.error?.errors || errObj.error?.errores;
        if (rawErrors && typeof rawErrors === 'object') {
          Object.keys(rawErrors).forEach(field => {
            const val = rawErrors[field];
            if (Array.isArray(val)) {
              errorList.push(...val);
            } else if (typeof val === 'string') {
              errorList.push(val);
            }
          });
        }

        const generalMsg = errObj.mensaje || errObj.message || errObj.error?.message || errObj.error?.mensaje;

        if (errorList.length > 0) {
          this.modalMessage = 'El servidor detectó las siguientes observaciones en el formulario:\n\n• ' + errorList.join('\n• ');
        } else if (generalMsg && !generalMsg.includes('Error del servidor')) {
          this.modalMessage = generalMsg;
        } else {
          this.modalMessage = 'No se pudo enviar la inscripción. Por favor revisa que todos los campos y documentos cumplan con los requisitos.';
        }
        this.showModal = true;
      }
    });
  }
}
