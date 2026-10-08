for(const box of document.querySelectorAll('[data-qrp-media-box]')){
  for(const button of box.querySelectorAll('[data-media-select]'))button.addEventListener('click',()=>{
    const key=button.dataset.mediaSelect,field=box.querySelector('[name="'+key+'"]'),multiple=button.dataset.multiple==='true';
    const frame=wp.media({title:button.dataset.title||'Wybierz materiały',button:{text:'Dołącz do wpisu'},multiple,library:{type:key==='qrp_gallery'?'image':key==='qrp_documents'?['image','application/pdf']:['image','application/pdf','audio']}});
    frame.on('select',()=>{const files=frame.state().get('selection').toJSON();field.value=multiple?files.map(f=>f.id).join(','):files[0]?.id||'0';
      const preview=box.querySelector('[data-media-preview="'+key+'"]');preview.replaceChildren();
      for(const file of files){const item=document.createElement('span');item.className='qrp-selected-media';item.textContent=file.title+' (#'+file.id+')';preview.append(item);}
      field.dispatchEvent(new Event('change',{bubbles:true}));
    });frame.open();
  });
  for(const button of box.querySelectorAll('[data-media-clear]'))button.addEventListener('click',()=>{const key=button.dataset.mediaClear;box.querySelector('[name="'+key+'"]').value=key==='qrp_primary_file'?'0':'';box.querySelector('[data-media-preview="'+key+'"]').replaceChildren();box.querySelector('[name="'+key+'"]').dispatchEvent(new Event('change',{bubbles:true}));});
}
