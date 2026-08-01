/**
 * Utilidad universal para descargar, visualizar e imprimir archivos (PDF, Excel, imágenes)
 * tanto en Navegadores Web (Chrome, PC) como en Celulares Reales y APK (Capacitor / Android WebView)
 * 100% Funcional Offline y Online sin dependencias de librerías CDN externas.
 */
export function downloadFile(blob: Blob, fileName: string) {
  const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (window as any).Capacitor !== undefined;

  if (isMobile) {
    const reader = new FileReader();
    reader.onloadend = () => {
      const base64data = reader.result as string;

      if (fileName.toLowerCase().endsWith('.pdf')) {
        // En Android APK y WebView, abrimos un visor embebido seguro 100% offline con el PDF en base64
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
                body { background-color: #1e293b; font-family: system-ui, -apple-system, sans-serif; display: flex; flex-direction: column; height: 100vh; width: 100vw; overflow: hidden; }
                .top-bar { background-color: #0f172a; color: white; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.5); }
                .file-title { font-size: 13px; font-weight: 700; max-width: 55%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #f8fafc; }
                .btn-print { background-color: #0284c7; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.25); }
                .btn-print:active { background-color: #0369a1; transform: scale(0.97); }
                .pdf-body { flex: 1; width: 100%; height: calc(100vh - 54px); display: flex; flex-direction: column; background: #323639; }
                object, embed, iframe { width: 100%; height: 100%; border: none; flex: 1; }
                @media print {
                  .top-bar { display: none !important; }
                  body, .pdf-body { height: auto !important; background: white !important; }
                  object, embed, iframe { height: 100vh !important; }
                }
              </style>
            </head>
            <body>
              <div class="top-bar">
                <span class="file-title">📄 ${fileName}</span>
                <button onclick="window.print()" class="btn-print">🖨️ GUARDAR / IMPRIMIR PDF</button>
              </div>
              <div class="pdf-body">
                <object data="${base64data}" type="application/pdf" width="100%" height="100%">
                  <embed src="${base64data}" type="application/pdf" width="100%" height="100%" />
                  <iframe src="${base64data}" width="100%" height="100%"></iframe>
                </object>
              </div>
            </body>
            </html>
          `);
        } else {
          // Fallback directo si no abre ventana secundaria
          const a = document.createElement('a');
          a.href = base64data;
          a.download = fileName;
          a.target = '_blank';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
        }
      } else {
        // Para archivos Excel (.xlsx) en móviles/APK
        const a = document.createElement('a');
        a.href = base64data;
        a.download = fileName;
        a.target = '_blank';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }
    };
    reader.readAsDataURL(blob);
  } else {
    // Para Navegadores Web en PC/Escritorio
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => window.URL.revokeObjectURL(url), 1000);
  }
}
