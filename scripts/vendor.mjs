import fs from 'node:fs';
const dest='qr-pelplin/assets/vendor';fs.mkdirSync(dest,{recursive:true});
fs.copyFileSync('node_modules/pdfjs-dist/legacy/build/pdf.worker.min.mjs',dest+'/pdf.worker.min.js');
for(const dir of ['cmaps','standard_fonts','wasm','iccs'])fs.cpSync('node_modules/pdfjs-dist/'+dir,dest+'/'+dir,{recursive:true});
fs.copyFileSync('node_modules/pdfjs-dist/LICENSE',dest+'/PDFJS-LICENSE.txt');
