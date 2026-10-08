<?php
defined('ABSPATH') || exit;
add_action('admin_menu',function(){
    add_menu_page('QR Pelplin','QR Pelplin','manage_options','qrp-dashboard','qrp_admin_page','dashicons-location-alt',25);
    add_submenu_page('qrp-dashboard','Wygląd i CMS','Wygląd i CMS','manage_options','qrp-settings','qrp_admin_page');
    add_submenu_page('qrp-dashboard','Statystyki','Statystyki','manage_options','qrp-stats','qrp_admin_page');
    add_submenu_page('qrp-dashboard','Kategorie','Kategorie','manage_categories','edit-tags.php?taxonomy=qrp_category&post_type=qrp_item');
});
function qrp_admin_page(){ if(current_user_can('manage_options')) echo '<div class="wrap"><div id="qrp-admin"><p>Ładowanie panelu QR Pelplin…</p></div></div>'; }
add_action('admin_enqueue_scripts',function($hook){
    if (str_contains($hook,'qrp-')) {
        wp_enqueue_media();wp_enqueue_style('qrp-admin',QRP_URL.'assets/admin.css',[],QRP_VERSION);
        wp_enqueue_script('qrp-admin',QRP_URL.'assets/dist/admin.js',[],QRP_VERSION,true);
        $categories=get_terms(['taxonomy'=>'qrp_category','hide_empty'=>false]); $s=qrp_settings();
        wp_localize_script('qrp-admin','qrpAdmin',[
            'api'=>rest_url('qr-pelplin/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'settings'=>$s,
            'page'=>sanitize_key($_GET['page']??'qrp-dashboard'),'version'=>QRP_VERSION,
            'pages'=>array_map(function($p){return ['id'=>$p->ID,'title'=>$p->post_title];},get_pages()),
            'categories'=>is_wp_error($categories)?[]:array_map(function($t){return ['id'=>$t->term_id,'name'=>$t->name];},$categories),
            'images'=>array_map(function($id){return ['id'=>$id,'url'=>qrp_image($id)];},array_values(array_unique(array_filter(array_merge([$s['logo'],$s['footer_logo'],$s['hero_image'],$s['map_image'],$s['about_image']],array_values($s['category_images'])))))),
            'preview'=>qrp_landing_url(),'add'=>admin_url('post-new.php?post_type=qrp_item'),'posts'=>admin_url('edit.php?post_type=qrp_item'),
            'export'=>html_entity_decode(wp_nonce_url(admin_url('admin-post.php?action=qrp_export'),'qrp_export'),ENT_QUOTES,'UTF-8'),
        ]);
    }
    $screen=get_current_screen();
    if($screen && $screen->post_type==='qrp_item' && in_array($hook,['post.php','post-new.php'],true)){
        wp_enqueue_media();wp_enqueue_style('qrp-editor',QRP_URL.'assets/editor.css',[],QRP_VERSION);wp_enqueue_style('qrp-location',QRP_URL.'assets/dist/location.css',[],QRP_VERSION);wp_enqueue_script('qrp-location',QRP_URL.'assets/dist/location.js',[],QRP_VERSION,true);wp_enqueue_script('qrp-media',QRP_URL.'assets/dist/media.js',[],QRP_VERSION,true);
        wp_enqueue_script('qrp-qr',QRP_URL.'assets/dist/qr.js',['wp-data','wp-blocks'],QRP_VERSION,true);
        wp_localize_script('qrp-qr','qrpQR',['api'=>rest_url('qr-pelplin/v1/'),'nonce'=>wp_create_nonce('wp_rest')]);
    }
});
add_action('add_meta_boxes',function(){
    add_meta_box('qrp-qr-box','Kod QR — stały adres',function($post){
        $png=wp_get_attachment_url(get_post_meta($post->ID,'qrp_qr_png',true));
        echo '<div id="qrp-generator" data-id="'.absint($post->ID).'"><p>Kod prowadzi do tej treści i zachowuje adres po zmianie jej tytułu. Przed drukiem opublikuj wpis.</p><button type="button" class="button button-primary" data-generate>Wygeneruj i dołącz QR</button><p><label><input type="checkbox" data-insert> Wstaw także obraz QR do treści</label></p><p data-status role="status"></p><div data-preview>';
        if($png) echo '<img src="'.esc_url($png).'" alt="Kod QR" style="max-width:180px;width:100%">';
        echo '</div><p data-downloads>';
        foreach(['svg','png'] as $f){$url=wp_get_attachment_url(get_post_meta($post->ID,'qrp_qr_'.$f,true));if($url) echo '<a class="button" href="'.esc_url($url).'" download>Pobierz '.esc_html(strtoupper($f)).'</a> ';}
        echo '</p></div>';
    },'qrp_item','side','high');
    add_meta_box('qrp-media','Pliki i prezentacja','qrp_media_box','qrp_item','side','high');
    add_meta_box('qrp-location','Miejsce na mapie','qrp_location_box','qrp_item','side','default');
});
function qrp_location_box($post){
    wp_nonce_field('qrp_location','qrp_location_nonce');$settings=qrp_settings();
    echo '<p><label for="qrp_address">Adres / nazwa miejsca</label><input class="widefat" id="qrp_address" name="qrp_address" value="'.esc_attr(get_post_meta($post->ID,'qrp_address',true)).'"></p>';
    echo '<div data-location-picker data-lat="'.esc_attr($settings['map_lat']).'" data-lng="'.esc_attr($settings['map_lng']).'"><button class="button button-primary" type="button" data-location-open>Wybierz miejsce na mapie</button><p data-location-status role="status">'.(get_post_meta($post->ID,'qrp_lat',true)!==''?'Lokalizacja jest wybrana.':'Nie wybrano jeszcze lokalizacji.').'</p><button class="button" type="button" data-location-clear>Usuń lokalizację</button><dialog class="qrp-location-dialog"><div class="qrp-location-dialog-head"><h2>Wskaż miejsce</h2><button class="button" type="button" data-location-close>Gotowe — wróć do wpisu</button></div><p>Kliknij mapę lub przeciągnij znacznik. Możesz również przesunąć mapę i wybrać jej środek. Po zamknięciu zapisz wpis.</p><div data-location-map tabindex="0" aria-label="Mapa wyboru miejsca. Strzałkami przesuń mapę, Enter wybiera środek."></div><p data-tile-status role="status"></p><button class="button" type="button" data-location-center>Wybierz środek mapy</button></dialog></div><details><summary>Dane lokalizacji</summary>';
    foreach(['qrp_lat'=>'Szerokość','qrp_lng'=>'Długość'] as $key=>$label)echo '<p><label>'.esc_html($label).'<input class="widefat" readonly id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr(get_post_meta($post->ID,$key,true)).'"></label></p>';
    echo '</details>';
    foreach(['qrp_audio','qrp_video'] as $key)echo '<input type="hidden" name="'.esc_attr($key).'" value="'.absint(get_post_meta($post->ID,$key,true)).'">';
    echo '<p><label><input type="checkbox" name="qrp_featured" value="1" '.checked(get_post_meta($post->ID,'qrp_featured',true),'1',false).'> Treść wyróżniona</label></p>';
}
add_action('save_post_qrp_item',function($id){
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post',$id) || !isset($_POST['qrp_location_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['qrp_location_nonce'])),'qrp_location')) return;
    update_post_meta($id,'qrp_address',sanitize_text_field(wp_unslash($_POST['qrp_address']??'')));
    foreach(['qrp_lat'=>90,'qrp_lng'=>180] as $k=>$limit){$v=wp_unslash($_POST[$k]??'');if(is_numeric($v)&&abs((float)$v)<=$limit)update_post_meta($id,$k,(string)(float)$v);else delete_post_meta($id,$k);}
    foreach(['qrp_audio'=>'audio/','qrp_video'=>'video/'] as $k=>$mime){$v=absint($_POST[$k]??0);update_post_meta($id,$k,$v&&str_starts_with(get_post_mime_type($v)?:'',$mime)?$v:0);}
    update_post_meta($id,'qrp_featured',isset($_POST['qrp_featured'])?'1':'0');
});
add_filter('manage_qrp_item_posts_columns',function($c){$c['qrp_code']='Kod QR';return $c;});
add_action('manage_qrp_item_posts_custom_column',function($column,$id){if($column==='qrp_code')echo get_post_meta($id,'qrp_qr_png',true)?'✓ SVG + PNG':'Jeszcze nie wygenerowano';},10,2);

function qrp_media_box($post){
    wp_nonce_field('qrp_media','qrp_media_nonce');
    echo '<div data-qrp-media-box><p><label for="qrp_delivery"><strong>Co pokazać po zeskanowaniu QR?</strong></label><br><select id="qrp_delivery" name="qrp_delivery">';
    foreach(['auto'=>'Automatycznie: treść lub sam plik','content'=>'Strona z treścią i materiałami','download'=>'Pobierz plik główny (PDF / JPG / MP3)','preview'=>'Strona z podglądem pliku','audio'=>'Odtwarzacz MP3'] as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected(get_post_meta($post->ID,'qrp_delivery',true)?:'auto',$value,false).'>'.esc_html($label).'</option>';
    echo '</select></p><p>Automatycznie: jeśli wpis zawiera treść, galerię lub strony dokumentów, pokaże stronę. Sam PDF otworzy podgląd bez pobierania, JPG będzie pobrany, a MP3 otworzy odtwarzacz. Przeglądarka może wymagać naciśnięcia przycisku odtwarzania.</p>';
    foreach(['qrp_attachments'=>'Załączniki wpisu — dowolna liczba plików','qrp_primary_file'=>'Plik główny (opcjonalnie)','qrp_gallery'=>'Galeria zdjęć','qrp_documents'=>'Strony materiału (PDF / JPG)'] as $key=>$label){
        $multiple=$key!=='qrp_primary_file';$ids=$multiple?(array)get_post_meta($post->ID,$key,true):[absint(get_post_meta($post->ID,$key,true))];$ids=array_filter($ids);
        echo '<p><strong>'.esc_html($label).'</strong></p><input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($multiple?implode(',',$ids):($ids[0]??0)).'"><div data-media-preview="'.esc_attr($key).'">';
        foreach($ids as $id)echo '<span data-media-id="'.absint($id).'" data-media-title="'.esc_attr(get_the_title($id)).'" data-media-mime="'.esc_attr(get_post_mime_type($id)).'">'.esc_html(get_the_title($id)).'</span>';
        echo '</div><p><button type="button" class="button" data-media-select="'.esc_attr($key).'" data-multiple="'.($multiple?'true':'false').'" data-title="'.esc_attr($label).'">Dodaj / wybierz pliki</button> <button type="button" class="button" data-media-clear="'.esc_attr($key).'">Wyczyść</button></p>';
    }
    echo '<p>Zdjęcia i dokumenty będą prezentowane w kolejności wyboru. Możesz także używać bloków Galeria, Obraz, Plik i Audio w edytorze WordPressa. Po zmianie materiałów zapisz wpis. Dotychczasowy kod QR pozostaje ważny.</p></div>';
}
add_action('save_post_qrp_item',function($id){
    if(wp_is_post_revision($id)||wp_is_post_autosave($id)||!current_user_can('edit_post',$id)||!isset($_POST['qrp_media_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['qrp_media_nonce'])),'qrp_media'))return;
    update_post_meta($id,'qrp_delivery',qrp_clean_delivery(sanitize_key($_POST['qrp_delivery']??'auto')));
    update_post_meta($id,'qrp_primary_file',qrp_clean_primary_file($_POST['qrp_primary_file']??0));
    foreach(['qrp_gallery'=>'qrp_clean_gallery','qrp_documents'=>'qrp_clean_documents','qrp_attachments'=>'qrp_clean_attachments'] as $key=>$sanitize){$ids=array_map('absint',explode(',',sanitize_text_field(wp_unslash($_POST[$key]??''))));update_post_meta($id,$key,$sanitize($ids));}
});
