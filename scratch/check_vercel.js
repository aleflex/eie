const https = require('https');

const t = Date.now();
const url = `https://frontend-five-eta-86qiuy8e5p.vercel.app/login?v=${t}`;

console.log('Fetching with cache-buster:', url);

https.get(url, { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' } }, res => {
  let data = '';
  res.on('data', chunk => data += chunk);
  res.on('end', () => {
    console.log('HTML length:', data.length);
    const matches = data.match(/src="([^"]+)"/g) || [];
    console.log('Scripts found:', matches);
    const mainMatch = matches.find(m => m.includes('main'));
    if (mainMatch) {
      const src = mainMatch.replace('src="', '').replace('"', '');
      const scriptUrl = (src.startsWith('http') ? src : 'https://frontend-five-eta-86qiuy8e5p.vercel.app/' + (src.startsWith('/') ? src.slice(1) : src)) + `?v=${t}`;
      console.log('Fetching main script from:', scriptUrl);
      https.get(scriptUrl, { headers: { 'Cache-Control': 'no-cache' } }, sRes => {
        let sData = '';
        sRes.on('data', c => sData += c);
        sRes.on('end', () => {
          console.log('Main script length:', sData.length);
          console.log('Contains btn-spinner:', sData.includes('btn-spinner'));
          console.log('Contains INICIANDO SESIÓN:', sData.includes('INICIANDO SESIÓN'));
          console.log('Contains isLoggingIn:', sData.includes('isLoggingIn'));
        });
      });
    }
  });
});
