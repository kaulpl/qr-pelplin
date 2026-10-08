export type Entry={id:number;slug:string;title:{rendered:string};excerpt:{rendered:string};content:{rendered:string}};
const url=process.env.WORDPRESS_API_URL?.replace(/\/$/,'');
export async function entries():Promise<Entry[]>{if(!url)return [];try{const r=await fetch(`${url}/wp-json/wp/v2/qr_entry?per_page=100`,{next:{revalidate:120}});return r.ok?await r.json():[]}catch{return []}}
export async function entry(slug:string):Promise<Entry|null>{if(!url)return null;try{const r=await fetch(`${url}/wp-json/wp/v2/qr_entry?slug=${encodeURIComponent(slug)}`,{next:{revalidate:120}});return r.ok?(await r.json())[0]??null:null}catch{return null}}
