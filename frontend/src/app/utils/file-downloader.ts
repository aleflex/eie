/**
 * Utilidad universal para descargar, visualizar e imprimir archivos (PDF, Excel, imágenes)
 * tanto en Navegadores Web (Chrome, PC) como en Celulares Reales y APK (Capacitor / Android WebView).
 * 100% Funcional Offline y Online.
 */
export function downloadFile(blob: Blob, fileName: string) {
  const blobUrl = window.URL.createObjectURL(blob);

  // 1. Ejecutar siempre la descarga directa en el navegador o dispositivo
  const triggerDirectDownload = (hrefUrl: string) => {
    try {
      const a = document.createElement('a');
      a.href = hrefUrl;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } catch (e) {
      console.warn('Error en triggerDirectDownload:', e);
    }
  };

  const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (window as any).Capacitor !== undefined;

  if (isMobile) {
    const reader = new FileReader();
    reader.onloadend = () => {
      const base64data = reader.result as string;

      if (fileName.toLowerCase().endsWith('.pdf')) {
        // En móviles/tablets abrimos el visor enriquecido con controles de Guardar, Imprimir y Compartir
        const pdfWin = window.open('', '_blank');
        if (pdfWin) {
          pdfWin.document.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>${fileName}</title>
              <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { 
                  background-color: #0f172a; 
                  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                  display: flex; 
                  flex-direction: column; 
                  height: 100vh; 
                  width: 100vw; 
                  overflow: hidden; 
                }
                .top-bar { 
                  background-color: #0f172a; 
                  color: white; 
                  padding: 10px 14px; 
                  display: flex; 
                  justify-content: space-between; 
                  align-items: center; 
                  z-index: 100; 
                  box-shadow: 0 2px 10px rgba(0,0,0,0.5); 
                  gap: 10px;
                  flex-wrap: wrap;
                }
                .file-title { 
                  font-size: 13px; 
                  font-weight: 700; 
                  max-width: 40%; 
                  overflow: hidden; 
                  text-overflow: ellipsis; 
                  white-space: nowrap; 
                  color: #f8fafc; 
                }
                .btn-group {
                  display: flex;
                  gap: 8px;
                  align-items: center;
                  flex-wrap: wrap;
                }
                .btn-action { 
                  color: white; 
                  border: none; 
                  padding: 8px 13px; 
                  border-radius: 6px; 
                  font-weight: 700; 
                  font-size: 12px; 
                  cursor: pointer; 
                  display: inline-flex; 
                  align-items: center; 
                  gap: 5px; 
                  box-shadow: 0 2px 6px rgba(0,0,0,0.25); 
                  transition: background-color 0.2s, transform 0.1s;
                }
                .btn-save { background-color: #16a34a; }
                .btn-save:active { background-color: #15803d; transform: scale(0.97); }
                .btn-print { background-color: #0284c7; }
                .btn-print:active { background-color: #0369a1; transform: scale(0.97); }
                .btn-share { background-color: #7c3aed; }
                .btn-share:active { background-color: #6d28d9; transform: scale(0.97); }
                .btn-close { background-color: #475569; }
                .btn-close:active { background-color: #334155; transform: scale(0.97); }
                .pdf-body { 
                  flex: 1; 
                  width: 100%; 
                  height: calc(100vh - 54px); 
                  display: flex; 
                  flex-direction: column; 
                  background: #1e293b; 
                }
                iframe, object, embed { 
                  width: 100%; 
                  height: 100%; 
                  border: none; 
                  flex: 1; 
                }
                @media print {
                  .top-bar { display: none !important; }
                  body, .pdf-body { height: auto !important; background: white !important; }
                  iframe, object, embed { height: 100vh !important; }
                }
              </style>
            </head>
            <body>
              <div class="top-bar">
                <span class="file-title">📄 ${fileName}</span>
                <div class="btn-group">
                  <button id="btn-save" onclick="guardarArchivo()" class="btn-action btn-save">
                    💾 GUARDAR / DESCARGAR
                  </button>
                  <button id="btn-print" onclick="imprimirArchivo()" class="btn-action btn-print">
                    🖨️ IMPRIMIR
                  </button>
                  <button id="btn-share" onclick="compartirArchivo()" class="btn-action btn-share">
                    📲 COMPARTIR
                  </button>
                  <button onclick="window.close()" class="btn-action btn-close">
                    ✖ CERRAR
                  </button>
                </div>
              </div>
              <div class="pdf-body">
                <iframe id="pdf-frame" src="${base64data}" width="100%" height="100%">
                  <object data="${base64data}" type="application/pdf" width="100%" height="100%">
                    <embed src="${base64data}" type="application/pdf" width="100%" height="100%" />
                  </object>
                </iframe>
              </div>

              <script>
                function guardarArchivo() {
                  try {
                    const a = document.createElement('a');
                    a.href = "${base64data}";
                    a.download = "${fileName}";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                    const btn = document.getElementById('btn-save');
                    if (btn) {
                      const orig = btn.innerHTML;
                      btn.innerHTML = '✅ ¡DESCARGADO!';
                      setTimeout(() => { btn.innerHTML = orig; }, 2500);
                    }
                  } catch (err) {
                    alert('Error al guardar: ' + err.message);
                  }
                }

                function imprimirArchivo() {
                  try {
                    const frame = document.getElementById('pdf-frame');
                    if (frame && frame.contentWindow) {
                      frame.contentWindow.focus();
                      frame.contentWindow.print();
                      return;
                    }
                  } catch (e) {
                    console.warn('Error imprimiendo iframe:', e);
                  }
                  window.print();
                }

                async function compartirArchivo() {
                  try {
                    const res = await fetch("${base64data}");
                    const blob = await res.blob();
                    const file = new File([blob], "${fileName}", { type: "application/pdf" });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                      await navigator.share({
                        files: [file],
                        title: "${fileName}",
                        text: "Documento oficial Escuela de Idiomas del Ejército"
                      });
                    } else {
                      guardarArchivo();
                    }
                  } catch (err) {
                    guardarArchivo();
                  }
                }

                // Disparar guardado automático inicial en el dispositivo
                setTimeout(() => {
                  guardarArchivo();
                }, 400);
              </script>
            </body>
            </html>
          `);
        } else {
          // Si el bloqueador de popups no permitió abrir ventana, descargar directamente
          triggerDirectDownload(base64data);
        }
      } else {
        // Para archivos Excel (.xlsx) en móviles/APK
        triggerDirectDownload(base64data);
      }
    };
    reader.readAsDataURL(blob);
  } else {
    // Para Navegadores Web en PC/Escritorio
    triggerDirectDownload(blobUrl);
    setTimeout(() => window.URL.revokeObjectURL(blobUrl), 20000);
  }
}
