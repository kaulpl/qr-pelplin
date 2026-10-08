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
            'export'=>wp_nonce_url(admin_url('admin-post.php?action=qrp_export'),'qrp_export'),
        ]);
    }
    $screen=get_current_screen();
    if($screen && $screen->post_type==='qrp_item' && in_array($hook,['post.php','post-new.php'],true)){
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
    add_meta_box('qrp-location','Miejsce i multimedia','qrp_location_box','qrp_item','normal','default');
});
function qrp_location_box($post){
    wp_nonce_field('qrp_location','qrp_location_nonce');
    foreach(['qrp_address'=>'Adres miejsca','qrp_lat'=>'Szerokość geograficzna (np. 53.9285)','qrp_lng'=>'Długość geograficzna (np. 18.6977)','qrp_audio'=>'ID pliku audio z biblioteki mediów','qrp_video'=>'ID pliku wideo z biblioteki mediów'] as $k=>$label){
        echo '<p><label for="'.esc_attr($k).'">'.esc_html($label).'</label><br><input class="widefat" id="'.esc_attr($k).'" name="'.esc_attr($k).'" value="'.esc_attr(get_post_meta($post->ID,$k,true)).'"></p>';
    }
    echo '<p><label><input type="checkbox" name="qrp_featured" value="1" '.checked(get_post_meta($post->ID,'qrp_featured',true),'1',false).'> Treść wyróżniona</label></p><p>Zdjęcie karty ustaw jako obrazek wyróżniający. Galerie, audio i wideo można też dodawać blokami edytora.</p>';
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
