import { Capacitor } from '@capacitor/core';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import * as XLSX from 'xlsx';

/**
 * Utilidad universal para descargar, visualizar y compartir archivos (PDF, Excel, imágenes)
 * tanto en Navegadores Web (PC / Móvil) como dentro del APK Nativo Android (Capacitor).
 */
export async function downloadFile(blob: Blob, fileName: string) {
  let finalFileName = fileName;
  let finalBlob = blob;

  // Si es un archivo de Excel (.xlsx o .xls)
  if (fileName.toLowerCase().endsWith('.xlsx') || fileName.toLowerCase().endsWith('.xls')) {
    try {
      const header = await blob.slice(0, 10).text();
      // Si no inicia con el encabezado OpenXML ZIP 'PK', es una tabla HTML/XML del backend
      if (!header.startsWith('PK')) {
        const textContent = await blob.text();
        const workbook = XLSX.read(textContent, { type: 'string' });
        const xlsxBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
        finalBlob = new Blob([xlsxBuffer], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        finalFileName = fileName.replace(/\.xls$/i, '.xlsx');
        if (!finalFileName.toLowerCase().endsWith('.xlsx')) {
          finalFileName += '.xlsx';
        }
      }
    } catch (err) {
      console.warn('Fallo convirtiendo a XLSX nativo con SheetJS, descargando original:', err);
    }
  }

  // 1. Detectar si estamos dentro del APK Nativo de Android (Capacitor)
  const isNative = Capacitor.isNativePlatform();

  if (isNative) {
    try {
      // Convertir Blob a Base64
      const reader = new FileReader();
      const base64Promise = new Promise<string>((resolve, reject) => {
        reader.onloadend = () => {
          const result = reader.result as string;
          const base64 = result.includes(',') ? result.split(',')[1] : result;
          resolve(base64);
        };
        reader.onerror = reject;
      });
      reader.readAsDataURL(finalBlob);
      const base64Data = await base64Promise;

      // Escribir archivo directamente en el almacenamiento de la app (Directory.Cache)
      const saved = await Filesystem.writeFile({
        path: finalFileName,
        data: base64Data,
        directory: Directory.Cache
      });

      // Abrir diálogo nativo del sistema Android para abrir con lector de PDF/Excel o Guardar en Descargas
      await Share.share({
        title: finalFileName,
        text: `Documento: ${finalFileName}`,
        url: saved.uri,
        dialogTitle: `Abrir o Descargar ${finalFileName}`
      });

      return;
    } catch (err: any) {
      console.warn('Fallo en descarga nativa por Filesystem/Share, usando fallback:', err);
    }
  }

  // 2. Si es Navegador Web en PC o navegador móvil estándar
  const blobUrl = window.URL.createObjectURL(finalBlob);
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = finalFileName;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  setTimeout(() => window.URL.revokeObjectURL(blobUrl), 20000);
}

