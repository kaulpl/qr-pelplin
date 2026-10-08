import L from 'leaflet';import 'leaflet/dist/leaflet.css';
for(const root of document.querySelectorAll('[data-location-picker]')){
  const dialog=root.querySelector('dialog'),container=root.querySelector('[data-location-map]'),lat=document.querySelector('#qrp_lat'),lng=document.querySelector('#qrp_lng'),status=root.querySelector('[data-location-status]');let map,marker;
  const icon=L.divIcon({className:'qrp-location-marker',html:'<span>●</span>',iconSize:[30,30],iconAnchor:[15,15]});
  function setPoint(point){const latitude=Math.max(-90,Math.min(90,point.lat)),longitude=((point.lng+180)%360+360)%360-180;lat.value=latitude.toFixed(6);lng.value=longitude.toFixed(6);lat.dispatchEvent(new Event('change',{bubbles:true}));lng.dispatchEvent(new Event('change',{bubbles:true}));status.textContent='Miejsce wybrane. Zapisz wpis, aby zachować lokalizację.';if(marker)marker.setLatLng([latitude,longitude]);else{marker=L.marker([latitude,longitude],{icon,draggable:true}).addTo(map);marker.on('dragend',()=>setPoint(marker.getLatLng()));}}
  root.querySelector('[data-location-open]').addEventListener('click',()=>{dialog.showModal();
    if(!map){const selected=lat.value!==''&&lng.value!==''&&Number.isFinite(Number(lat.value))&&Number.isFinite(Number(lng.value));const center=selected?[Number(lat.value),Number(lng.value)]:[Number(root.dataset.lat),Number(root.dataset.lng)];
      map=L.map(container,{center,zoom:selected?17:14});const tiles=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'}).addTo(map);tiles.on('tileerror',()=>{root.querySelector('[data-tile-status]').textContent='Nie udało się pobrać części mapy. Sprawdź połączenie i spróbuj ponownie.';});
      map.on('click',e=>setPoint(e.latlng));if(selected)setPoint({lat:center[0],lng:center[1]});
      container.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();setPoint(map.getCenter());}});
    }setTimeout(()=>map.invalidateSize(),0);
  });
  root.querySelector('[data-location-close]').addEventListener('click',()=>dialog.close());
  root.querySelector('[data-location-center]').addEventListener('click',()=>setPoint(map.getCenter()));
  root.querySelector('[data-location-clear]').addEventListener('click',()=>{lat.value='';lng.value='';lat.dispatchEvent(new Event('change',{bubbles:true}));lng.dispatchEvent(new Event('change',{bubbles:true}));if(marker){map.removeLayer(marker);marker=null;}status.textContent='Lokalizacja została usunięta. Zapisz wpis.';});
}
