import {getDocument,GlobalWorkerOptions} from 'pdfjs-dist/legacy/build/pdf.mjs';
const assets=window.qrpPublic.pdfAssets;GlobalWorkerOptions.workerSrc=assets+'pdf.worker.min.js';
for(const root of document.querySelectorAll('[data-pdf-viewer]')){
  const status=root.querySelector('[data-pdf-status]'),canvas=root.querySelector('canvas'),prev=root.querySelector('[data-pdf-prev]'),next=root.querySelector('[data-pdf-next]'),counter=root.querySelector('[data-pdf-counter]'),pageInput=root.querySelector('[data-pdf-page]'),text=root.querySelector('[data-pdf-text]');
  let pdf,pageNumber=1,task,loading=false,rendering=false,pending=false,documentTask;
  async function render(){
    if(!pdf)return;if(rendering){pending=true;task?.cancel();return;}rendering=true;prev.disabled=true;next.disabled=true;
    try{
      const page=await pdf.getPage(pageNumber),base=page.getViewport({scale:1}),width=Math.max(220,root.querySelector('[data-pdf-sheet]').clientWidth-2),scale=width/base.width,viewport=page.getViewport({scale}),ratio=Math.min(window.devicePixelRatio||1,2);
      canvas.width=Math.floor(viewport.width*ratio);canvas.height=Math.floor(viewport.height*ratio);canvas.style.width='100%';canvas.style.height='auto';canvas.setAttribute('aria-label','Strona '+pageNumber+' z '+pdf.numPages);
      task=page.render({canvas,canvasContext:canvas.getContext('2d'),viewport,transform:ratio!==1?[ratio,0,0,ratio,0,0]:undefined});await task.promise;
      counter.textContent='/ '+pdf.numPages;pageInput.value=pageNumber;pageInput.max=pdf.numPages;text.textContent=(await page.getTextContent()).items.map(i=>i.str||'').join(' ');status.textContent='';root.dataset.rendered='true';
    }catch(e){if(e.name!=='RenderingCancelledException')status.textContent='Nie udało się wyświetlić tej strony PDF. Użyj linku „Otwórz PDF”.';}
    finally{rendering=false;task=null;prev.disabled=pageNumber<=1;next.disabled=pageNumber>=pdf.numPages;if(pending){pending=false;render();}}
  }
  async function load(){if(loading)return;loading=true;status.textContent='Otwieranie PDF…';
    try{documentTask=getDocument({isEvalSupported:false,url:root.dataset.pdfUrl,cMapUrl:assets+'cmaps/',cMapPacked:true,standardFontDataUrl:assets+'standard_fonts/',wasmUrl:assets+'wasm/',iccUrl:assets+'iccs/'});
      documentTask.onPassword=(setPassword,reason)=>{status.textContent='Dokument jest zabezpieczony hasłem.';const password=window.prompt(reason===2?'Nieprawidłowe hasło. Wpisz hasło do PDF:':'Wpisz hasło do PDF:');if(password===null){documentTask.destroy();return;}setPassword(password);};
      pdf=await documentTask.promise;await render();
    }catch(e){status.textContent='Nie udało się otworzyć PDF. Spróbuj linku „Otwórz PDF” poniżej.';root.dataset.failed='true';}
  }
  prev.addEventListener('click',()=>{pageNumber=Math.max(1,pageNumber-1);render();});next.addEventListener('click',()=>{pageNumber=Math.min(pdf.numPages,pageNumber+1);render();});pageInput.addEventListener('change',()=>{if(pdf){pageNumber=Math.max(1,Math.min(pdf.numPages,Number(pageInput.value)||1));render();}});
  let resize;new ResizeObserver(()=>{clearTimeout(resize);resize=setTimeout(()=>{if(pdf)render();},180);}).observe(root.querySelector('[data-pdf-sheet]'));
  const observer=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting)){load();observer.disconnect();}},{rootMargin:'300px'});observer.observe(root);
}
