<?php
defined('ABSPATH') || exit;
function qrp_media_file($id, $entry_id=0){
    $id=absint($id);$post=get_post($id);$mime=get_post_mime_type($id)?:'';
    if(!$post || $post->post_type!=='attachment' || $post->post_status==='trash' || !in_array($mime,['application/pdf','image/jpeg','image/png','image/webp','image/gif','audio/mpeg','audio/mp3','audio/ogg','audio/wav','audio/x-wav','audio/flac','video/mp4','video/webm','text/plain','text/csv','application/zip','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],true))return false;
    $url=wp_get_attachment_url($id);if(!$url || !in_array(wp_parse_url($url,PHP_URL_SCHEME),['http','https'],true))return false;
    return ['id'=>$id,'mime'=>$mime,'url'=>$url,'title'=>($entry_id ? (get_post_meta($entry_id,'qrp_file_labels',true)[$id]??get_the_title($id)) : get_the_title($id)),'type'=>str_starts_with($mime,'audio/')?'audio':(str_starts_with($mime,'video/')?'video':($mime==='application/pdf'?'pdf':(str_starts_with($mime,'image/')?'image':'file')))];
}
function qrp_clean_primary_file($id){return qrp_media_file($id)?absint($id):0;}
function qrp_clean_media_ids($ids,$types){
    if(!is_array($ids))return [];$clean=[];
    foreach(array_slice($ids,0,100) as $id){$f=qrp_media_file($id);if($f && in_array($f['type'],$types,true))$clean[]=$f['id'];}
    return array_values(array_unique($clean));
}
function qrp_clean_gallery($ids){return qrp_clean_media_ids($ids,['image']);}
function qrp_clean_documents($ids){return qrp_clean_media_ids($ids,['image','pdf']);}
function qrp_clean_attachments($ids){return qrp_clean_media_ids($ids,['image','pdf','audio','video','file']);}
function qrp_entry_files($id){return array_values(array_unique(array_merge(qrp_clean_attachments(get_post_meta($id,'qrp_attachments',true)),qrp_clean_documents(get_post_meta($id,'qrp_documents',true)))));}
function qrp_clean_delivery($mode){return in_array($mode,['auto','content','download','preview','audio'],true)?$mode:'auto';}
add_action('init',function(){
    $auth=function($allowed,$key,$id){return current_user_can('edit_post',$id);};
    register_post_meta('qrp_item','qrp_primary_file',['single'=>true,'type'=>'integer','default'=>0,'show_in_rest'=>true,'sanitize_callback'=>'qrp_clean_primary_file','auth_callback'=>$auth]);
    register_post_meta('qrp_item','qrp_delivery',['single'=>true,'type'=>'string','default'=>'auto','show_in_rest'=>['schema'=>['type'=>'string','enum'=>['auto','content','download','preview','audio']]],'sanitize_callback'=>'qrp_clean_delivery','auth_callback'=>$auth]);
    foreach(['qrp_gallery'=>'qrp_clean_gallery','qrp_documents'=>'qrp_clean_documents','qrp_attachments'=>'qrp_clean_attachments'] as $key=>$sanitize)register_post_meta('qrp_item',$key,['single'=>true,'type'=>'array','default'=>[],'show_in_rest'=>['schema'=>['type'=>'array','items'=>['type'=>'integer']]],'sanitize_callback'=>$sanitize,'auth_callback'=>$auth]);
},11);
function qrp_has_content($post){
    $text=html_entity_decode(wp_strip_all_tags(strip_shortcodes($post->post_content)),ENT_QUOTES,'UTF-8');
    if(preg_replace('/[\s\x{00A0}]+/u','',$text)!=='')return true;
    if(preg_match('/<(img|audio|video|iframe|object|embed)\b|<!--\s*wp:(gallery|image|audio|video|file|embed)\b|\[[a-z][a-z0-9_-]*(?:\s|\])/i',$post->post_content))return true;
    return (bool)(qrp_clean_gallery(get_post_meta($post->ID,'qrp_gallery',true)) || qrp_entry_files($post->ID));
}
function qrp_primary_file($id){
    $file=qrp_media_file(get_post_meta($id,'qrp_primary_file',true));
    return $file?:qrp_media_file(get_post_meta($id,'qrp_audio',true));
}
function qrp_delivery($post){
    if(qrp_inline_ids($post))return 'content';
    $mode=qrp_clean_delivery(get_post_meta($post->ID,'qrp_delivery',true));$file=qrp_primary_file($post->ID);
    if(!$file)return 'content';
    if($mode==='auto')return qrp_has_content($post)?'content':($file['type']==='audio'?'audio':($file['type']==='pdf'?'preview':'download'));
    if($mode==='audio' && $file['type']!=='audio')return 'preview';
    return $mode;
}
function qrp_target_url($post){
    $url=get_permalink($post);
    return qrp_delivery($post)==='download'?add_query_arg('qrp_download','1',$url):$url;
}
add_action('template_redirect',function(){
    if(!isset($_GET['qrp_download']) || !is_singular('qrp_item'))return;
    $post=get_queried_object();
    if($post->post_status!=='publish' || post_password_required($post))wp_die('Ten plik jest niedostępny.','Plik niedostępny',['response'=>403]);
    $file=qrp_primary_file($post->ID);if(!$file)wp_die('Plik został usunięty lub nie jest dostępny.','Plik niedostępny',['response'=>410]);
    $path=get_attached_file($file['id']);$real=$path?realpath($path):false;$uploads=wp_get_upload_dir();$base=realpath($uploads['basedir']);
    if(!$real || !$base || !str_starts_with($real,$base.DIRECTORY_SEPARATOR) || !is_readable($real)){
        // Offloaded media is served by its own storage; Content-Disposition is configured there.
        if(($_SERVER['REQUEST_METHOD']??'GET')==='GET')qrp_record($post->ID,'view');
        nocache_headers();wp_redirect($file['url'],302,'QR Pelplin');exit;
    }
    nocache_headers();header('Content-Type: '.$file['mime']);header('X-Content-Type-Options: nosniff');
    $name=sanitize_file_name(wp_basename($real));$ascii=preg_replace('/[^a-zA-Z0-9._-]/','_',remove_accents($name));
    header('Content-Disposition: attachment; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($name));
    header('Content-Length: '.filesize($real));
    if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){status_header(405);exit;}
    qrp_record($post->ID,'view');
    while(ob_get_level())ob_end_clean();$handle=fopen($real,'rb');if($handle){fpassthru($handle);fclose($handle);}exit;
},2);
function qrp_info_icon(){return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>';}
function qrp_render_file($file,$autoplay=false){
    if(!$file)return;
    if($file['type']==='audio'){
        echo '<section class="qrp-audio-player"><h2>'.esc_html((in_array($file['mime'],['audio/mpeg','audio/mp3'],true)?'MP3: ':'Audio: ').($file['title']?:'Posłuchaj opowieści')).'</h2><audio controls preload="metadata" '.($autoplay?'autoplay data-qrp-autoplay':'').' src="'.esc_url($file['url']).'"></audio><p class="qrp-material-info">'.qrp_info_icon().'<span data-audio-hint>Naciśnij odtwarzanie, aby posłuchać nagrania.</span></p></section>';
    }elseif($file['type']==='video'){
        echo '<section class="qrp-video-player"><h2>'.esc_html($file['title']).'</h2><video controls preload="metadata" src="'.esc_url($file['url']).'"></video></section>';
    }elseif($file['type']==='image'){
        echo '<figure class="qrp-document-page"><img src="'.esc_url($file['url']).'" alt="'.esc_attr($file['title']).'" loading="lazy"><figcaption>'.esc_html($file['title']).'</figcaption></figure>';
    }elseif($file['type']==='pdf'){
        echo '<section class="qrp-document-page"><h2>'.esc_html('PDF: '.$file['title']).'</h2><div class="qrp-pdf-viewer" data-pdf-viewer data-pdf-url="'.esc_url($file['url']).'"><div class="qrp-pdf-toolbar"><button type="button" class="qrp-outline" data-pdf-prev disabled aria-label="Poprzednia strona">←</button><label>Strona <input type="number" min="1" value="1" data-pdf-page aria-label="Numer strony PDF"></label><span data-pdf-counter></span><button type="button" class="qrp-outline" data-pdf-next disabled aria-label="Następna strona">→</button></div><p data-pdf-status role="status">Otwieranie PDF…</p><div class="qrp-pdf-sheet" data-pdf-sheet><canvas role="img" aria-label="Podgląd PDF"></canvas></div><details class="qrp-pdf-text"><summary>Tekst strony</summary><p data-pdf-text></p></details></div><p class="qrp-material-info">'.qrp_info_icon().'<span>Możesz także <a href="'.esc_url($file['url']).'" target="_blank" rel="noopener">otworzyć PDF w osobnym oknie ↗</a>.</span></p><noscript><object data="'.esc_url($file['url']).'" type="application/pdf" class="qrp-pdf"><a href="'.esc_url($file['url']).'">Otwórz PDF</a></object></noscript></section>';
    }else{
        echo '<section class="qrp-file-download"><h2>'.esc_html($file['title']).'</h2><a class="qrp-button" href="'.esc_url($file['url']).'" download>Pobierz plik ↓</a></section>';
    }
}
