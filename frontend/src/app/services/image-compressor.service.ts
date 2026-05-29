import { Injectable } from '@angular/core';

/**
 * Servicio de Compresión de Imágenes
 * Comprime imágenes en el navegador antes de cargarlas al servidor.
 * Mantiene la relación de aspecto y reduce el tamaño de archivo
 * para optimizar tiempo de carga y ancho de banda.
 */
@Injectable({
  providedIn: 'root'
})
export class ImageCompressorService {

  constructor() { }

  /**
   * Comprime una imagen en el navegador antes de cargarla
   * @param archivo - Archivo de imagen original
   * @param anchoMaximo - Ancho máximo de la imagen comprimida (por defecto 1000px)
   * @param altoMaximo - Alto máximo de la imagen comprimida (por defecto 1000px)
   * @param calidad - Calidad de compresión de 0.0 a 1.0 (por defecto 0.75)
   * @returns Promesa que resuelve con un File comprimido
   */
  comprimirImagen(archivo: File, anchoMaximo = 1000, altoMaximo = 1000, calidad = 0.75): Promise<File> {
    // Si el archivo no es una imagen, retornar inmediatamente
    if (!archivo.type.startsWith('image/')) {
      return Promise.resolve(archivo);
    }

    return new Promise((resolve, reject) => {
      const lector = new FileReader();
      lector.readAsDataURL(archivo);
      lector.onload = (evento: any) => {
        const img = new Image();
        img.src = evento.target.result;
        img.onload = () => {
          let ancho = img.width;
          let alto = img.height;

          // Calcular nuevas dimensiones manteniendo la relación de aspecto
          if (ancho > alto) {
            if (ancho > anchoMaximo) {
              alto = Math.round((alto * anchoMaximo) / ancho);
              ancho = anchoMaximo;
            }
          } else {
            if (alto > altoMaximo) {
              ancho = Math.round((ancho * altoMaximo) / alto);
              alto = altoMaximo;
            }
          }

          // Crear canvas para dibujar la imagen redimensionada
          const canvas = document.createElement('canvas');
          canvas.width = ancho;
          canvas.height = alto;

          const ctx = canvas.getContext('2d');
          if (!ctx) {
            resolve(archivo); // Fallback al archivo original
            return;
          }

          // Dibujar la imagen en el canvas
          ctx.drawImage(img, 0, 0, ancho, alto);

          // Convertir canvas a blob y luego a File
          canvas.toBlob(
            (blob) => {
              if (blob) {
                // Procesar nombre del archivo
                const nombreOriginal = archivo.name;
                const indiceUltimoPunto = nombreOriginal.lastIndexOf('.');
                const nombreSinExtension = indiceUltimoPunto !== -1 ? nombreOriginal.substring(0, indiceUltimoPunto) : nombreOriginal;
                const nombreArchivo = `${nombreSinExtension}_comprimida.jpg`;

                // Crear archivo comprimido
                const archivoComprimido = new File([blob], nombreArchivo, {
                  type: 'image/jpeg',
                  lastModified: Date.now()
                });

                console.log(`Original: ${(archivo.size / 1024).toFixed(1)}KB, Comprimida: ${(archivoComprimido.size / 1024).toFixed(1)}KB`);
                resolve(archivoComprimido);
              } else {
                resolve(archivo); // Fallback al original
              }
            },
            'image/jpeg',
            calidad
          );
        };
        img.onerror = (err) => {
          console.error('Error al cargar imagen:', err);
          resolve(archivo); // Fallback al original
        };
      };
      lector.onerror = (err) => {
        console.error('Error de FileReader:', err);
        resolve(archivo); // Fallback al original
      };
    });
  }

  // Método heredado para compatibilidad
  compressImage(file: File, maxWidth = 1000, maxHeight = 1000, quality = 0.75): Promise<File> {
    return this.comprimirImagen(file, maxWidth, maxHeight, quality);
  }
}
