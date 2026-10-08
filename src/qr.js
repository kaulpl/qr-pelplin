import QRCode from 'qrcode';
export function qrMatrix(url) {const code=QRCode.create(url,{errorCorrectionLevel:'M'});return Array.from({length:code.modules.size},(_,y)=>Array.from({length:code.modules.size},(_,x)=>code.modules.get(y,x)?'1':'0').join(''));}
const root=document.querySelector('#qrp-generator');
if(root){const button=root.querySelector('[data-generate]'),status=root.querySelector('[data-status]');
  button.addEventListener('click',async()=>{
    button.disabled=true;status.textContent='Generowanie kodu i zapisywanie plików…';
    const call=async(path,body)=>{const r=await fetch(window.qrpQR.api+path,{method:body?'POST':'GET',headers:{'X-WP-Nonce':window.qrpQR.nonce,'Content-Type':'application/json'},body:body?JSON.stringify(body):undefined});const data=await r.json();if(!r.ok)throw Error(data.message||'Błąd zapisu');return data;};
    try{const path='qr/'+root.dataset.id;const data=await call(path),matrix=qrMatrix(data.url),png=await QRCode.toDataURL(data.url,{errorCorrectionLevel:'M',width:data.size,margin:4});const files=await call(path,{url:data.url,matrix,png});
      const preview=root.querySelector('[data-preview]');preview.replaceChildren();const img=document.createElement('img');img.src=files.png.url;img.alt='Kod QR';img.style.maxWidth='180px';img.style.width='100%';preview.append(img);
      const links=root.querySelector('[data-downloads]');links.replaceChildren();for(const format of ['svg','png']){const a=document.createElement('a');a.className='button';a.href=files[format].url;a.download='qr-pelplin-'+root.dataset.id+'.'+format;a.textContent='Pobierz '+format.toUpperCase();links.append(a,document.createTextNode(' '));}
      if(root.querySelector('[data-insert]').checked){
        const editor=window.wp?.data?.dispatch('core/block-editor');
        if(editor?.insertBlocks)editor.insertBlocks(window.wp.blocks.createBlock('core/image',{id:files.png.id,url:files.png.url,alt:'Kod QR do tej treści'}));
        else if(window.tinymce?.activeEditor)window.tinymce.activeEditor.insertContent('<img src="'+files.png.url+'" alt="Kod QR">');
        else{const textarea=document.querySelector('#content');if(textarea)textarea.value+='\n<img src="'+files.png.url+'" alt="Kod QR">';}
      }
      status.textContent='Gotowe. SVG i PNG dołączono do wpisu w bibliotece mediów. Jeśli wstawiono obraz do treści, zapisz wpis.';
    }catch(e){status.textContent=e.message;}finally{button.disabled=false;}
  });
}
