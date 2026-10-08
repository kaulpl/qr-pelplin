<?php defined('ABSPATH') || exit;
if(post_password_required())return;
$files=array_unique(array_merge(qrp_entry_files($id),array_filter([$file['id']??0,(int)get_post_meta($id,'qrp_audio',true)])));$pdfs=0;$mp3s=0;
foreach($files as $asset){$mime=get_post_mime_type($asset);if($mime==='application/pdf')$pdfs++;if(in_array($mime,['audio/mpeg','audio/mp3'],true))$mp3s++;}
$photos=count(qrp_clean_gallery(get_post_meta($id,'qrp_gallery',true)));
$text=wp_strip_all_tags(strip_shortcodes(get_post_field('post_content',$id)));preg_match_all('/[\p{L}\p{N}]+/u',$text,$words);$minutes=max(1,(int)ceil(count($words[0])/200));
$icons=['clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>','file'=>'<path d="M14 3H6v18h12V7zm0 0v4h4M9 12h6M9 16h6"/>','audio'=>'<path d="M9 18V5l11-2v13M9 8l11-2"/><ellipse cx="6" cy="18" rx="3" ry="2"/><ellipse cx="17" cy="16" rx="3" ry="2"/>','image'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="1"/><path d="m3 17 6-6 4 4 3-3 5 5"/>'];
$items=[['clock',$minutes.' min czytania']];if($pdfs)$items[]=['file','PDF: '.$pdfs];if($mp3s)$items[]=['audio','MP3: '.$mp3s];if($photos)$items[]=['image','Galeria: '.$photos.' zdjęć'];
?><div class="qrp-entry-info" aria-label="Informacje o wpisie"><?php foreach($items as [$icon,$label]): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $icons[$icon]; ?></svg><?php echo esc_html($label); ?></span><?php endforeach; ?></div>
