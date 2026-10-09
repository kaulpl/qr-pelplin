<?php
defined('ABSPATH') || exit;
function qrp_register() {
    register_post_type('qrp_item', [
        'labels'=>['name'=>'Treści QR','singular_name'=>'Treść QR','add_new_item'=>'Dodaj treść QR','edit_item'=>'Edytuj treść QR'],
        'public'=>true,'show_in_rest'=>true,'show_in_menu'=>false,'menu_icon'=>'dashicons-location-alt',
        'supports'=>['title','editor','excerpt','thumbnail','revisions','page-attributes'], 'rewrite'=>['slug'=>'w','with_front'=>false], 'has_archive'=>false,
    ]);
    register_taxonomy('qrp_category',['qrp_item'],['label'=>'Kategorie QR','public'=>true,'hierarchical'=>true,'show_in_rest'=>true,'rewrite'=>['slug'=>'k','with_front'=>false]]);
    foreach (['qrp_lat','qrp_lng','qrp_address','qrp_featured','qrp_audio','qrp_video','qrp_qr_png','qrp_qr_svg'] as $key) register_post_meta('qrp_item',$key,[
        'single'=>true, 'type'=>in_array($key,['qrp_audio','qrp_video','qrp_qr_png','qrp_qr_svg'],true)?'integer':'string',
        'show_in_rest'=>true, 'sanitize_callback'=>in_array($key,['qrp_audio','qrp_video','qrp_qr_png','qrp_qr_svg'],true)?'absint':'sanitize_text_field',
        'auth_callback'=>function($allowed,$key,$id){return current_user_can('edit_post',$id);},
    ]);
}
add_action('init','qrp_register');
add_action('init',function(){if(!wp_next_scheduled('qrp_cleanup'))wp_schedule_event(time()+3600,'daily','qrp_cleanup');});
function qrp_activate() {
    qrp_register(); qrp_install_analytics();
    $settings=qrp_settings();
    if (!$settings['landing_page']) {
        $id=(int)get_option('qrp_landing_page',0);
        if (!$id || get_post_type($id)!=='page') $id=wp_insert_post(['post_title'=>'Odkrywaj Pelplin','post_name'=>'odkrywaj-pelplin','post_type'=>'page','post_status'=>'publish','post_content'=>'[qr_pelplin_landing]']);
        if (!is_wp_error($id)) {$settings['landing_page']=(int)$id; update_option('qrp_settings',$settings);}
    }
    foreach (['Zabytki','Historia','Turystyka','Kultura'] as $name) if (!term_exists($name,'qrp_category')) wp_insert_term($name,'qrp_category');
    if (!wp_next_scheduled('qrp_cleanup')) wp_schedule_event(time()+3600,'daily','qrp_cleanup');
    flush_rewrite_rules();
}
function qrp_deactivate() { wp_clear_scheduled_hook('qrp_cleanup'); flush_rewrite_rules(); }
add_action('plugins_loaded',function(){if(get_option('qrp_db_version')!==QRP_VERSION) qrp_install_analytics();});
function qrp_landing_url() { $s=qrp_settings(); return $s['landing_page'] ? get_permalink($s['landing_page']) : home_url('/'); }
function qrp_token($id) {
    $token=get_post_meta($id,'qrp_token',true);
    if (!$token) {
        $token=bin2hex(random_bytes(12));
        if (!add_post_meta($id,'qrp_token',$token,true)) $token=get_post_meta($id,'qrp_token',true);
    }
    return $token;
}
function qrp_scan_url($id) { return get_option('permalink_structure') ? home_url('/q/'.qrp_token($id).'/') : add_query_arg('qrp_code',qrp_token($id),home_url('/')); }
function qrp_template($file,$vars=[]) { extract($vars,EXTR_SKIP); ob_start(); include QRP_DIR.'templates/'.$file.'.php'; return ob_get_clean(); }
add_shortcode('qr_pelplin_landing',function(){return qrp_template('landing',['s'=>qrp_settings()]);});
add_filter('template_include',function($template){
    $s=qrp_settings();
    if (isset($_GET['qrp_credits']) || ($s['landing_page'] && is_page($s['landing_page'])) || is_singular('qrp_item') || is_tax('qrp_category')) return QRP_DIR.'templates/shell.php';
    return $template;
});
function qrp_is_portal() { $s=qrp_settings(); return isset($_GET['qrp_credits']) || is_singular('qrp_item') || is_tax('qrp_category') || ($s['landing_page'] && is_page($s['landing_page'])) || (is_singular() && has_shortcode(get_post()->post_content??'','qr_pelplin_landing')); }
add_action('wp_enqueue_scripts',function(){
    if (!qrp_is_portal()) return;
    wp_enqueue_style('qrp-map-style',QRP_URL.'assets/dist/public.css',[],QRP_VERSION);
    wp_enqueue_style('qrp-public',QRP_URL.'assets/public.css',[],QRP_VERSION);
    wp_enqueue_script('qrp-public',QRP_URL.'assets/dist/public.js',[],QRP_VERSION,true);
    wp_localize_script('qrp-public','qrpPublic',['api'=>rest_url('qr-pelplin/v1/'),'landing'=>qrp_landing_url(),'pdfAssets'=>QRP_URL.'assets/vendor/']);
    if(is_singular('qrp_item')){
        $files=qrp_entry_files(get_queried_object_id());$primary=qrp_primary_file(get_queried_object_id());if($primary)$files[]=$primary['id'];
        foreach($files as $id)if(get_post_mime_type($id)==='application/pdf'){wp_enqueue_script('qrp-pdf',QRP_URL.'assets/dist/pdf.js',['qrp-public'],QRP_VERSION,true);break;}
    }
    $s=qrp_settings();
    wp_add_inline_style('qrp-public',':root{--qrp-accent:'.sanitize_hex_color($s['accent']).';--qrp-bg:'.sanitize_hex_color($s['background']).';--qrp-text:'.sanitize_hex_color($s['text_color']).';}');
});
function qrp_item_data($post) {
    $terms=wp_get_post_terms($post->ID,'qrp_category');
    $image=get_the_post_thumbnail_url($post,'large');
    return ['id'=>$post->ID,'title'=>get_the_title($post),'url'=>qrp_target_url($post),'excerpt'=>wp_strip_all_tags(get_the_excerpt($post)), 'image'=>$image?:QRP_URL.'assets/place.svg',
        'categories'=>is_wp_error($terms)?[]:array_map(function($term){return ['id'=>$term->term_id,'name'=>$term->name];},$terms),
        'lat'=>get_post_meta($post->ID,'qrp_lat',true),'lng'=>get_post_meta($post->ID,'qrp_lng',true),'address'=>get_post_meta($post->ID,'qrp_address',true)];
}
function qrp_content_query($params=[]) {
    $s=qrp_settings(); $page=max(1,absint($params['page']??1));
    $args=['post_type'=>'qrp_item','post_status'=>'publish','has_password'=>false,'posts_per_page'=>max(1,min(48,absint($params['per_page']??$s['content_count']))),'paged'=>$page,
        'orderby'=>$s['content_order'],'order'=>$s['content_order']==='date'?'DESC':'ASC'];
    if (!empty($params['search'])) $args['s']=sanitize_text_field($params['search']);
    if (!empty($params['category'])) $args['tax_query']=[['taxonomy'=>'qrp_category','field'=>'term_id','terms'=>absint($params['category'])]];
    return new WP_Query($args);
}
function qrp_cards($items) {
    foreach ($items as $post) { $item=qrp_item_data($post); echo qrp_template('card',['item'=>$item]); }
}

// Plugin upgrades do not run activation hooks. Refresh custom routes once per version.
add_action('init',function(){
    if(get_option('qrp_rewrite_version')!==QRP_VERSION){flush_rewrite_rules(false);update_option('qrp_rewrite_version',QRP_VERSION);}
},99);
function qrp_feature_term($feature){
    $id=absint($feature['category_id']??0);if($id){$term=get_term($id,'qrp_category');if($term&&!is_wp_error($term))return $term;}
    $title=$feature['title']??'';$term=get_term_by('name',$title,'qrp_category')?:get_term_by('slug',sanitize_title($title),'qrp_category');if($term)return $term;
    $candidates=['crown'=>['historia-i-dziedzictwo','historia'],'map'=>['ciekawe-miejsca','turystyka'],'image'=>['zdjecia-i-multimedia','kultura'],'people'=>['wydarzenia-i-lokalne-inicjatywy','wydarzenia']];
    foreach($candidates[$feature['icon']??'']??[] as $slug){$term=get_term_by('slug',$slug,'qrp_category');if($term)return $term;}
    return false;
}

function qrp_category_image($term,$settings=null){
    $s=$settings?:qrp_settings();$custom=qrp_image($s['category_images'][$term->term_id]??0);if($custom)return $custom;
    $slug=$term->slug;$key='places';
    if(str_contains($slug,'histor')||str_contains($slug,'dziedzict'))$key='history';
    elseif(str_contains($slug,'multimed')||str_contains($slug,'zdjec')||str_contains($slug,'kultura'))$key='multimedia';
    elseif(str_contains($slug,'wydarz')||str_contains($slug,'inicjaty'))$key='events';
    $file='assets/categories/'.$key.'.webp';return file_exists(QRP_DIR.$file)?QRP_URL.$file:QRP_URL.'assets/hero.jpg';
}
function qrp_default_category_images(){
    $out=[];$terms=get_terms(['taxonomy'=>'qrp_category','hide_empty'=>false]);if(!is_wp_error($terms))foreach($terms as $term)$out[$term->term_id]=qrp_category_image($term,qrp_defaults());return $out;
}

add_filter('query_vars',function($vars){$vars[]='qrp_code';$vars[]='qrp_legacy';return $vars;});
add_action('init',function(){
    add_rewrite_rule('^q/([a-f0-9]{24})/?$','index.php?qrp_code=$matches[1]','top');
    add_rewrite_rule('^q/([^/]+)/?$','index.php?post_type=qrp_item&name=$matches[1]&qrp_legacy=1','bottom');
    add_rewrite_rule('^kategoria-qr/(.+?)/page/([0-9]+)/?$','index.php?qrp_category=$matches[1]&paged=$matches[2]&qrp_legacy=1','top');
    add_rewrite_rule('^kategoria-qr/(.+?)/?$','index.php?qrp_category=$matches[1]&qrp_legacy=1','top');
},20);
add_action('template_redirect',function(){
    if(!get_query_var('qrp_legacy') || is_404())return;
    $url=is_singular('qrp_item')?get_permalink():get_term_link(get_queried_object());
    if(is_wp_error($url))return;
    if(is_tax('qrp_category')&&get_query_var('paged')>1)$url=trailingslashit($url).'page/'.absint(get_query_var('paged')).'/';
    wp_safe_redirect($url,301);exit;
},1);

add_action('template_redirect',function(){if(qrp_is_portal()&&qrp_settings()['favicon'])remove_action('wp_head','wp_site_icon',99);},21);
add_action('wp_head',function(){if(!qrp_is_portal())return;$icon=qrp_image(qrp_settings()['favicon'],'full');if($icon)echo '<link rel="icon" href="'.esc_url($icon).'">' . "\n" . '<link rel="apple-touch-icon" href="'.esc_url($icon).'">' . "\n";},4);
