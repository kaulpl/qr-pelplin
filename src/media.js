for(const box of document.querySelectorAll('[data-qrp-media-box]')){
  const lists=new Map();
  for(const button of box.querySelectorAll('[data-media-select]')){
    const key=button.dataset.mediaSelect,field=box.querySelector('[name="'+key+'"]'),multiple=button.dataset.multiple==='true',preview=box.querySelector('[data-media-preview="'+key+'"]');
    let files=Array.from(preview.querySelectorAll('[data-media-id]')).map(e=>({id:Number(e.dataset.mediaId),title:e.dataset.mediaTitle,mime:e.dataset.mediaMime||''}));
    function render(){field.value=multiple?files.map(f=>f.id).join(','):files[0]?.id||'0';preview.replaceChildren();
      files.forEach((file,index)=>{const row=document.createElement('div');row.className='qrp-media-row';const badge=document.createElement('span');badge.className='qrp-media-type';badge.textContent=file.mime==='application/pdf'?'PDF':file.mime.startsWith('audio/')?'AUDIO':file.mime.startsWith('image/')?'OBRAZ':file.mime.startsWith('video/')?'WIDEO':'PLIK';const title=document.createElement('span');title.className='qrp-media-name';title.textContent=file.title||'Plik #'+file.id;row.append(badge,title);
        const control=(label,action,disabled=false)=>{const b=document.createElement('button');b.type='button';b.className='button';b.textContent=label;b.disabled=disabled;b.addEventListener('click',()=>{action();render();field.dispatchEvent(new Event('change',{bubbles:true}));});row.append(b);};
        if(multiple){control('↑',()=>files.splice(index-1,0,files.splice(index,1)[0]),index===0);control('↓',()=>files.splice(index+1,0,files.splice(index,1)[0]),index===files.length-1);}control('Usuń',()=>files.splice(index,1));preview.append(row);
      });
    }
    render();lists.set(key,()=>{files=[];render();field.dispatchEvent(new Event('change',{bubbles:true}));});
    button.addEventListener('click',()=>{const types=key==='qrp_gallery'?'image':key==='qrp_documents'?['image','application/pdf']:null;
      const frame=wp.media({title:button.dataset.title||'Dodaj pliki',button:{text:'Dołącz do wpisu'},multiple,library:types?{type:types}:{}});
      frame.on('select',()=>{const selected=frame.state().get('selection').toJSON();files=multiple?[...new Map([...files,...selected].map(f=>[f.id,f])).values()]:selected.slice(0,1);render();field.dispatchEvent(new Event('change',{bubbles:true}));});frame.open();
    });
  }
  for(const button of box.querySelectorAll('[data-media-clear]'))button.addEventListener('click',()=>lists.get(button.dataset.mediaClear)?.());
}
