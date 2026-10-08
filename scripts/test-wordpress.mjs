import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {runCLI} from '@wp-playground/cli';
const blueprint=JSON.parse(fs.readFileSync('tests/blueprint.json','utf8'));
const app=await runCLI({command:'server',port:9402,php:process.env.QRP_TEST_PHP||'8.0',wp:'6.9',mount:[{hostPath:path.resolve('qr-pelplin'),vfsPath:'/wordpress/wp-content/plugins/qr-pelplin'}],blueprint});
try{const result=await app.playground.run({code:fs.readFileSync('tests/wordpress-media.php','utf8')});const output=new TextDecoder().decode(result.bytes);assert.equal(result.exitCode,0,result.errors+'\n'+output);assert.match(output,/MEDIA AND UPDATE TESTS PASSED/);console.log(output);}finally{await app[Symbol.asyncDispose]();}
