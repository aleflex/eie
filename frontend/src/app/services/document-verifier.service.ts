import { Injectable } from '@angular/core';
import { createWorker } from 'tesseract.js';

export interface DocumentValidationResult {
  valid: boolean;
  docType: string;
  confidence: number;
  detectedText?: string;
  errorMessage?: string;
  matchedKeywords?: string[];
}

@Injectable({
  providedIn: 'root'
})
export class DocumentVerifierService {
  private worker: any = null;
  private isInitializingWorker: boolean = false;

  // Palabras prohibidas que revelan fotos a pantallas de computadoras, código fuente, etc.
  private readonly screenForbiddenKeywords = [
    'discord', 'github', 'vscode', 'localhost', 'http://', 'https://',
    '.ts', '.js', '.css', '.html', 'component', 'services', 'alert.service',
    'styles.css', 'app.component', 'npm', 'node_modules', 'webpack',
    'function', 'import {', 'export class', 'console.log', 'git commit'
  ];

  // Diccionarios oficiales por tipo de documento en el Estado Plurinacional de Bolivia
  private readonly officialDictionaries: Record<string, { label: string; minRequired: number; keywords: string[] }> = {
    'carnet de identidad': {
      label: 'Cédula de Identidad (C.I.)',
      minRequired: 2,
      keywords: [
        'ESTADO PLURINACIONAL DE BOLIVIA',
        'ESTADO PLURINACIONAL',
        'PLURINACIONAL',
        'CEDULA DE IDENTIDAD',
        'CÉDULA DE IDENTIDAD',
        'CARNET DE IDENTIDAD',
        'IDENTIDAD',
        'SEGIP',
        'SERVICIO GENERAL DE IDENTIFICACION',
        'SERIE',
        'SECCION',
        'SECCIÓN',
        'NACIMIENTO',
        'EXPIRACION',
        'EXPIRACIÓN',
        'EMISION',
        'EMISIÓN',
        'DOMICILIO',
        'ESTADO CIVIL',
        'BOLIVIA',
        'PULGAR',
        'HUELLA',
        'FIRMA',
        'I<BOL',
        'BOL<'
      ]
    },
    'carnet de cossmil': {
      label: 'Carnet de Seguro COSSMIL',
      minRequired: 2,
      keywords: [
        'COSSMIL',
        'CORPORACION DEL SEGURO SOCIAL MILITAR',
        'CORPORACIÓN DEL SEGURO SOCIAL MILITAR',
        'SEGURO SOCIAL MILITAR',
        'CORPORACION',
        'MATRICULA',
        'MATRÍCULA',
        'AFILIADO',
        'BENEFICIARIO',
        'TITULAR',
        'VALIDO',
        'VENCIMIENTO',
        'SANGRE',
        'MILITAR'
      ]
    },
    'carnet militar': {
      label: 'Carnet Militar o Libreta Militar',
      minRequired: 2,
      keywords: [
        'FUERZAS ARMADAS',
        'EJERCITO',
        'EJÉRCITO',
        'MINISTERIO DE DEFENSA',
        'COMANDO EN JEFE',
        'COMANDO GENERAL',
        'CARNET MILITAR',
        'LIBRETA MILITAR',
        'LIBRETA DE SERVICIO MILITAR',
        'SERVICIO MILITAR',
        'FUERZA AEREA',
        'ARMADA BOLIVIANA',
        'GRADO',
        'ESCALAFON',
        'ESCALAFÓN',
        'MATRICULA',
        'MATRÍCULA',
        'REGIMIENTO',
        'BATALLON',
        'BATALLÓN'
      ]
    },
    'certificado de nacimiento': {
      label: 'Certificado de Nacimiento',
      minRequired: 2,
      keywords: [
        'ESTADO PLURINACIONAL DE BOLIVIA',
        'TRIBUNAL SUPREMO ELECTORAL',
        'SERECI',
        'REGISTRO CIVICO',
        'REGISTRO CÍVICO',
        'REGISTRO CIVIL',
        'CERTIFICADO DE NACIMIENTO',
        'NACIMIENTO',
        'OFICIALIA',
        'OFICIALÍA',
        'LIBRO',
        'PARTIDA',
        'FOLIO',
        'DATOS DE LA PERSONA',
        'INSCRIPCION',
        'INSCRIPCIÓN',
        'OFICIAL DE REGISTRO'
      ]
    },
    'título de bachiller': {
      label: 'Título de Bachiller',
      minRequired: 2,
      keywords: [
        'ESTADO PLURINACIONAL DE BOLIVIA',
        'MINISTERIO DE EDUCACION',
        'MINISTERIO DE EDUCACIÓN',
        'TITULO DE BACHILLER',
        'TÍTULO DE BACHILLER',
        'EN PROVISION NACIONAL',
        'EN PROVISIÓN NACIONAL',
        'BACHILLER EN HUMANIDADES',
        'BACHILLER',
        'DIRECCION DEPARTAMENTAL',
        'DIPLOMA',
        'EDUCACION',
        'EDUCACIÓN'
      ]
    }
  };

  /**
   * Inicializa el Worker de Tesseract reutilizable
   */
  private async getWorker() {
    if (this.worker) return this.worker;
    if (this.isInitializingWorker) {
      while (this.isInitializingWorker) {
        await new Promise(res => setTimeout(res, 100));
      }
      return this.worker;
    }

    this.isInitializingWorker = true;
    try {
      this.worker = await createWorker('spa', 1, {
        logger: () => {} // Silenciar logs en consola
      });
    } catch (e) {
      console.warn('Fallo inicializando Tesseract en español, intentando fallback en inglés...', e);
      try {
        this.worker = await createWorker('eng', 1, { logger: () => {} });
      } catch (err) {
        console.error('Error crítico al inicializar Tesseract OCR:', err);
      }
    } finally {
      this.isInitializingWorker = false;
    }
    return this.worker;
  }

  /**
   * Normaliza texto para búsqueda insensible a acentos y mayúsculas
   */
  private normalize(str: string): string {
    return (str || '')
      .toUpperCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^A-Z0-9\s<]/g, ' ')
      .replace(/\s+/g, ' ');
  }

  /**
   * Extrae texto de un archivo PDF si contiene capa de texto embebida
   */
  private async extractTextFromPdf(file: File): Promise<string> {
    try {
      const slice = await file.slice(0, Math.min(file.size, 512 * 1024)).text();
      return slice;
    } catch (e) {
      return '';
    }
  }

  /**
   * Valida estrictamente si una imagen o archivo corresponde al documento oficial esperado
   */
  public async verifyDocument(
    source: File | string, // File objeto o DataUrl base64 de la cámara
    expectedDocType: string
  ): Promise<DocumentValidationResult> {
    const typeKey = (expectedDocType || '').toLowerCase().trim();
    const dictionary = this.officialDictionaries[typeKey] || this.officialDictionaries['carnet de identidad'];

    let rawText = '';

    // Si es un archivo PDF nativo con texto embebido
    if (source instanceof File && (source.type === 'application/pdf' || source.name.toLowerCase().endsWith('.pdf'))) {
      const pdfText = await this.extractTextFromPdf(source);
      if (pdfText && pdfText.length > 50) {
        rawText = pdfText;
      }
    }

    // Si no obtuvimos texto suficiente del PDF, usamos OCR visual
    if (!rawText || rawText.trim().length < 20) {
      try {
        const worker = await this.getWorker();
        if (worker) {
          const res = await worker.recognize(source);
          rawText = res?.data?.text || '';
        }
      } catch (err) {
        console.error('Error en reconocimiento OCR de imagen:', err);
      }
    }

    const normText = this.normalize(rawText);
    const lowerRaw = rawText.toLowerCase();

    // 1. Detectar si es una pantalla de computadora (código de programación, Discord, browser, etc.)
    const matchedScreenWords = this.screenForbiddenKeywords.filter(k => lowerRaw.includes(k.toLowerCase()));
    if (matchedScreenWords.length >= 2) {
      return {
        valid: false,
        docType: expectedDocType,
        confidence: 0,
        detectedText: rawText,
        errorMessage: `⚠️ Captura no válida: La cámara o archivo parece estar enfocando una pantalla de computadora o código fuente (${matchedScreenWords.join(', ')}). Debes enfocar o subir el documento físico oficial o su fotocopia real.`
      };
    }

    // 2. Coincidencia de palabras clave institucionales del documento esperado
    const matchedKeywords: string[] = [];
    for (const kw of dictionary.keywords) {
      const normKw = this.normalize(kw);
      if (normText.includes(normKw)) {
        matchedKeywords.push(kw);
      }
    }

    // Verificación especial para código MRZ de C.I. boliviano (ej: I<BOL o líneas <<<)
    if (typeKey === 'carnet de identidad') {
      if (/I<BOL/i.test(rawText) || /BOL\d{6,}/i.test(rawText) || /<{4,}/.test(rawText)) {
        matchedKeywords.push('CÓDIGO MRZ / DORSO C.I.');
      }
    }

    // 3. Evaluar si cumple con los requisitos mínimos oficiales
    if (matchedKeywords.length >= dictionary.minRequired) {
      return {
        valid: true,
        docType: expectedDocType,
        confidence: Math.min(100, matchedKeywords.length * 25),
        detectedText: rawText,
        matchedKeywords
      };
    }

    // Si no se reconocieron suficientes elementos oficiales
    return {
      valid: false,
      docType: expectedDocType,
      confidence: matchedKeywords.length * 20,
      detectedText: rawText,
      matchedKeywords,
      errorMessage: `⚠️ Documento no reconocido como ${dictionary.label}.\n\nNo se detectaron los sellos, membretes ni textos oficiales requeridos (ej. Estado Plurinacional de Bolivia, SEGIP, SERECÍ o COSSMIL). Asegúrate de enfocar con buena iluminación y nitidez el documento correspondiente.`
    };
  }
}
