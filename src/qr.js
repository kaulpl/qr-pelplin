import QRCode from 'qrcode';
export function qrMatrix(url) {const code=QRCode.create(url,{errorCorrectionLevel:'M'});return Array.from({length:code.modules.size},(_,y)=>Array.from({length:code.modules.size},(_,x)=>code.modules.get(y,x)?'1':'0').join(''));}
