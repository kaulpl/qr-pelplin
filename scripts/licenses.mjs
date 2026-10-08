import fs from 'node:fs';
let text='Third-party software included in compiled production bundles\n\n';
for(const name of ['vue','@vue/shared','@vue/reactivity','@vue/runtime-core','@vue/runtime-dom','@vue/compiler-core','@vue/compiler-dom','qrcode','dijkstrajs','entities']) {
const paths=[`node_modules/${name}/LICENSE`,`node_modules/${name}/LICENSE.txt`,`node_modules/${name}/LICENSE.md`];
let source=paths.find(p=>fs.existsSync(p));if(!source){const pnpm=fs.readdirSync('node_modules/.pnpm').find(p=>p.startsWith(name.replace('/','+')+'@'));if(pnpm)source=['LICENSE','LICENSE.txt','LICENSE.md'].map(f=>`node_modules/.pnpm/${pnpm}/node_modules/${name}/${f}`).find(p=>fs.existsSync(p));}
if(source)text+='\n--- '+name+' ---\n'+fs.readFileSync(source,'utf8')+'\n';else throw Error('License missing: '+name);
}
fs.writeFileSync('qr-pelplin/assets/THIRD-PARTY-LICENSES.txt',text);
