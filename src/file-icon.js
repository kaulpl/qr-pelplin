// Shared attachment classification and SVG icon for CMS file lists.
export function fileKind(file={}){
 const mime=String(file.mime||file.mime_type||'').toLowerCase();let path='';try{path=new URL(file.url||file.source_url||'','https://example.invalid').pathname;}catch{path=file.url||'';}const ext=(path.split('.').pop()||'').toLowerCase();
 if(mime==='application/pdf'||ext==='pdf')return {kind:'pdf',label:'PDF'};
 if(mime.startsWith('audio/')||['mp3','wav','ogg','flac','m4a','aac'].includes(ext))return {kind:'audio',label:ext==='mp3'||['audio/mpeg','audio/mp3'].includes(mime)?'MP3':'AUDIO'};
 if(/msword|wordprocessingml|opendocument.text/.test(mime)||['doc','docx','odt','rtf'].includes(ext))return {kind:'word',label:ext==='doc'?'DOC':ext==='odt'?'ODT':'DOCX'};
 if(/ms-excel|spreadsheetml|opendocument.spreadsheet/.test(mime)||['xls','xlsx','ods','csv'].includes(ext))return {kind:'sheet',label:['xls','ods','csv'].includes(ext)?ext.toUpperCase():'XLSX'};
 if(/powerpoint|presentationml|opendocument.presentation/.test(mime)||['ppt','pptx','xppt','pps','ppsx','odp'].includes(ext))return {kind:'slides',label:['ppt','pps','odp'].includes(ext)?ext.toUpperCase():'PPTX'};
 if(mime.startsWith('image/')||['jpg','jpeg','png','webp','gif','avif'].includes(ext))return {kind:'image',label:'FOTO'};
 if(mime.startsWith('video/')||['mp4','webm','mov'].includes(ext))return {kind:'video',label:'VIDEO'};
 if(/zip|rar|7z|compressed/.test(mime)||['zip','rar','7z'].includes(ext))return {kind:'archive',label:['zip','rar','7z'].includes(ext)?ext.toUpperCase():'ZIP'};
 if(mime.startsWith('text/')||['txt','json'].includes(ext))return {kind:'text',label:ext==='csv'?'CSV':'TXT'};
 return {kind:'file',label:'PLIK'};
}
import {computed} from 'vue/dist/vue.esm-bundler.js';
export default {props:['file'],setup(props){return {info:computed(()=>fileKind(props.file))};},template:`<span class="qrpc-file-icon" role="img" :aria-label="'Plik '+info.label" :title="info.label"><svg viewBox="0 0 44 52" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 2h18l9 9v38H9zM27 2v9h9"/><g v-if="info.kind==='audio'"><path d="M21 31V18l9-2v12M21 21l9-2"/><ellipse cx="18" cy="31" rx="3" ry="2"/><ellipse cx="27" cy="28" rx="3" ry="2"/></g><g v-else-if="info.kind==='sheet'"><rect x="14" y="17" width="17" height="15"/><path d="M14 22h17M14 27h17M20 17v15M25 17v15"/></g><g v-else-if="info.kind==='slides'"><rect x="14" y="17" width="17" height="13" rx="1"/><path d="M22 30v4M18 34h8m-8-8 4-5 4 5"/></g><g v-else-if="info.kind==='image'"><rect x="14" y="17" width="17" height="15" rx="1"/><circle cx="19" cy="21" r="1"/><path d="m14 29 6-6 4 4 3-3 4 5"/></g><g v-else-if="info.kind==='video'"><rect x="14" y="17" width="17" height="15" rx="1"/><path d="m20 21 7 4-7 4z"/></g><g v-else-if="info.kind==='archive'"><path d="M22 16v3m0 3v3m0 3v3"/><rect x="19" y="31" width="6" height="4"/></g><g v-else><path d="M15 18h14M15 23h14M15 28h10"/></g><text x="22" y="44" stroke="none" fill="currentColor" font-family="Arial,sans-serif" font-size="7.5" font-weight="700" text-anchor="middle">{{info.label}}</text></svg></span>`};
