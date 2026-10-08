import fs from 'node:fs';
let text='Third-party software included in compiled production bundles\n\n';
for(const name of ['vue','@vue/shared','@vue/reactivity','@vue/runtime-core','@vue/runtime-dom','@vue/compiler-core','@vue/compiler-dom','qrcode','dijkstrajs','entities']) {
let dir=`node_modules/${name}`;
if(!fs.existsSync(dir)){const pnpm=fs.readdirSync('node_modules/.pnpm').find(p=>p.startsWith(name.replace('/','+')+'@'));if(pnpm)dir=`node_modules/.pnpm/${pnpm}/node_modules/${name}`;}
const file=fs.existsSync(dir)?fs.readdirSync(dir).find(f=>/^licen[cs]e(?:\..+)?$/i.test(f)):null;
if(!file)throw Error('License missing: '+name);
text+='\n--- '+name+' ---\n'+fs.readFileSync(dir+'/'+file,'utf8')+'\n';
}
fs.writeFileSync('qr-pelplin/assets/THIRD-PARTY-LICENSES.txt',text);
