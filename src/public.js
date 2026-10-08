import L from 'leaflet';import 'leaflet/dist/leaflet.css';
const config = window.qrpPublic;
const node = (tag, text, cls) => { const e = document.createElement(tag); if (text) e.textContent = text; if (cls) e.className = cls; return e; };
function card(item) {
  const article=node('article',null,'qrp-card'), a=node('a'), picture=node('div',null,'qrp-card-image'), img=node('img');
  a.href=item.url;img.src=item.image;img.alt='';img.loading='lazy';picture.append(img,node('span','↗','qrp-card-arrow'));
  const body=node('div',null,'qrp-card-body');body.append(node('span',item.categories[0]?.name || 'PELPLIN','qrp-eyebrow'),node('h3',item.title),node('p',item.excerpt),node('span','Przeczytaj więcej o tym →','qrp-button qrp-card-cta'));a.append(picture,body);article.append(a);return article;
}
const menu=document.querySelector('.qrp-menu-toggle'), nav=document.querySelector('#qrp-navigation');
menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')!=='true';menu.setAttribute('aria-expanded',String(open));nav.classList.toggle('is-open',open);});
nav?.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav.classList.remove('is-open');});
for(const section of document.querySelectorAll('[data-content]')) {
  let page=Number(section.dataset.page||1), search='',category=section.dataset.category||'',controller; const grid=section.querySelector('[data-grid]'),status=section.querySelector('[data-content-status]'),more=section.querySelector('[data-more]'),form=section.querySelector('form');
  async function load(append=false){
    controller?.abort();controller=new AbortController();const next=append?page+1:1; status.textContent='Ładowanie historii…';more.disabled=true;
    try {const params=new URLSearchParams({page:next,per_page:section.dataset.count,search,category});const r=await fetch(config.api+'content?'+params,{signal:controller.signal});if(!r.ok)throw Error();const data=await r.json();
      if(!append)grid.replaceChildren();data.items.forEach(i=>grid.append(card(i)));page=next;more.hidden=page>=data.pages;status.textContent=data.items.length?'':'Nie znaleziono treści. Zmień wyszukiwanie lub kategorię.';section.querySelector('.qrp-total').textContent=data.total+' treści';
    }catch(e){if(e.name!=='AbortError')status.textContent='Nie udało się pobrać treści. Spróbuj ponownie.';}finally{more.disabled=false;}
  }
  form?.addEventListener('submit',e=>{e.preventDefault();const data=new FormData(form);search=data.get('search');category=data.get('category');load();});more?.addEventListener('click',()=>load(true));
}

const pinIcon=L.divIcon({className:'qrp-live-pin',html:'●',iconSize:[26,26],iconAnchor:[13,13]});
function createMap(element,lat,lng,zoom=14){const map=L.map(element).setView([lat,lng],zoom);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);return map;}
for(const section of document.querySelectorAll('.qrp-map-section')){
 const panel=section.querySelector('[data-map-panel]'),frame=panel.querySelector('[data-map-frame]'),status=panel.querySelector('[data-map-status]');let map,loaded=false;
 section.querySelector('[data-open-map]').addEventListener('click',async()=>{panel.hidden=false;if(!map)map=createMap(frame,Number(panel.dataset.lat),Number(panel.dataset.lng),Number(panel.dataset.zoom));map.invalidateSize();if(loaded)return;status.textContent='Ładowanie miejsc…';const bounds=[];try{let pages=1;const group=L.layerGroup();for(let page=1;page<=pages;page++){const response=await fetch(config.api+'locations?page='+page);if(!response.ok)throw Error();const data=await response.json();pages=data.pages;for(const item of data.items){const popup=node('div',null,'qrp-map-popup'),image=node('img');image.src=item.image;image.alt='';const link=node('a','Przeczytaj więcej →','qrp-button');link.href=item.url;popup.append(image,node('h3',item.title),node('p',item.excerpt),link);const point=[Number(item.lat),Number(item.lng)];L.marker(point,{icon:pinIcon}).bindPopup(popup).addTo(group);bounds.push(point);}}group.addTo(map);if(bounds.length)map.fitBounds(bounds,{padding:[30,30],maxZoom:16});loaded=true;status.textContent=bounds.length?'Kliknij pinezkę, aby poznać historię.':'Brak miejsc na mapie.';}catch{status.textContent='Nie udało się pobrać miejsc. Otwórz mapę ponownie.';}});
 section.querySelector('[data-close-map]').addEventListener('click',()=>panel.hidden=true);
}
for(const element of document.querySelectorAll('[data-entry-map]')){const point=[Number(element.dataset.lat),Number(element.dataset.lng)];const map=createMap(element,...point,16);L.marker(point,{icon:pinIcon}).addTo(map);}
for(const gallery of document.querySelectorAll('.qrp-gallery')){
 const photos=[...gallery.querySelectorAll('a')];if(!photos.length)continue;const dialog=node('dialog',null,'qrp-lightbox'),close=node('button','✕','qrp-outline'),previous=node('button','←','qrp-outline'),next=node('button','→','qrp-outline'),image=node('img'),counter=node('p');close.setAttribute('aria-label','Zamknij galerię');previous.setAttribute('aria-label','Poprzednie zdjęcie');next.setAttribute('aria-label','Następne zdjęcie');dialog.setAttribute('aria-label','Galeria zdjęć');dialog.append(close,image,previous,counter,next);document.body.append(dialog);let index=0;
 function show(i){index=(i+photos.length)%photos.length;image.src=photos[index].href;image.alt=photos[index].querySelector('img')?.alt||'Zdjęcie z galerii';counter.textContent=(index+1)+' / '+photos.length;}
 photos.forEach((photo,i)=>photo.addEventListener('click',event=>{event.preventDefault();show(i);dialog.showModal();}));close.addEventListener('click',()=>dialog.close());previous.addEventListener('click',()=>show(index-1));next.addEventListener('click',()=>show(index+1));dialog.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'){e.preventDefault();show(index-1);}if(e.key==='ArrowRight'){e.preventDefault();show(index+1);}});dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close();});let touch;image.addEventListener('touchstart',e=>touch=e.changedTouches[0].clientX,{passive:true});image.addEventListener('touchend',e=>{const delta=e.changedTouches[0].clientX-touch;if(Math.abs(delta)>40)show(index+(delta<0?1:-1));},{passive:true});
}
const entry=document.querySelector('[data-entry-id]');
if(entry) fetch(config.api+'view/'+entry.dataset.entryId,{method:'POST',keepalive:true}).catch(()=>{});
for(const audio of document.querySelectorAll('[data-qrp-autoplay]'))audio.play().then(()=>{const hint=audio.parentElement.querySelector('[data-audio-hint]');if(hint)hint.textContent='Nagranie jest odtwarzane.';}).catch(()=>{});
