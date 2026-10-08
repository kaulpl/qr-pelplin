import fs from 'node:fs';
import path from 'node:path';
import parser from 'php-parser';
const engine=new parser.Engine({parser:{phpVersion:'8.0',suppressErrors:false},ast:{withPositions:true}});
function walk(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]);}
for(const file of walk('qr-pelplin').filter(f=>f.endsWith('.php'))){engine.parseCode(fs.readFileSync(file,'utf8'),file);console.log('PHP syntax OK:',file);}
for(const name of ['admin','qr','public','media']) if(!fs.existsSync(`qr-pelplin/assets/dist/${name}.js`))throw Error('Missing production bundle: '+name);
console.log('Production assets present.');
