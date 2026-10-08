const config = window.qrpPublic;
const node = (tag, text, cls) => { const e = document.createElement(tag); if (text) e.textContent = text; if (cls) e.className = cls; return e; };
function card(item) {
  const article=node('article',null,'qrp-card'), a=node('a'), picture=node('div',null,'qrp-card-image'), img=node('img');
  a.href=item.url;img.src=item.image;img.alt='';img.loading='lazy';picture.append(img,node('span','↗','qrp-card-arrow'));
  const body=node('div',null,'qrp-card-body');body.append(node('span',item.categories[0]?.name || 'PELPLIN','qrp-eyebrow'),node('h3',item.title),node('p',item.excerpt));a.append(picture,body);article.append(a);return article;
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
for (const section of document.querySelectorAll('.qrp-map-section')) {
  const panel=section.querySelector('[data-map-panel]'), frame=panel.querySelector('[data-map-frame]'), locations=panel.querySelector('[data-locations]'),status=panel.querySelector('[data-map-status]');let loaded=false;
  function map(lat,lng,zoom=14){const delta=.08/2**(zoom-12); const i=node('iframe');i.title='Mapa miejsca w OpenStreetMap';i.loading='lazy';i.src='https://www.openstreetmap.org/export/embed.html?'+new URLSearchParams({bbox:[lng-delta,lat-delta*.6,lng+delta,lat+delta*.6].join(','),layer:'mapnik',marker:lat+','+lng});frame.replaceChildren(i);}
  section.querySelector('[data-open-map]').addEventListener('click',async()=>{
    panel.hidden=false;map(Number(panel.dataset.lat),Number(panel.dataset.lng),Number(panel.dataset.zoom));if(loaded)return;
    status.textContent='Ładowanie miejsc…';try{let pages=1;for(let page=1;page<=pages;page++){const r=await fetch(config.api+'locations?page='+page);if(!r.ok)throw Error();const data=await r.json();pages=data.pages;
      for(const item of data.items){const row=node('div',null,'qrp-location-row'),button=node('button',item.title,'qrp-outline'),a=node('a','Poznaj historię →','qrp-text-link');a.href=item.url;button.addEventListener('click',()=>map(Number(item.lat),Number(item.lng),17));row.append(button,a);locations.append(row);}}
      loaded=true;status.textContent=locations.children.length?'Wybierz miejsce, aby zobaczyć je na mapie.':'Brak miejsc z uzupełnionymi współrzędnymi.';
    }catch{locations.replaceChildren();status.textContent='Nie udało się pobrać miejsc. Otwórz mapę ponownie, aby spróbować jeszcze raz.';}
  });section.querySelector('[data-close-map]').addEventListener('click',()=>{panel.hidden=true;frame.replaceChildren();});
}
const entry=document.querySelector('[data-entry-id]');
if(entry) fetch(config.api+'view/'+entry.dataset.entryId,{method:'POST',keepalive:true}).catch(()=>{});
for(const audio of document.querySelectorAll('[data-qrp-autoplay]'))audio.play().then(()=>{const hint=audio.parentElement.querySelector('[data-audio-hint]');if(hint)hint.textContent='Nagranie jest odtwarzane.';}).catch(()=>{});
