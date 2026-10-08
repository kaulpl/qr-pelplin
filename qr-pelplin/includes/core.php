<?php
defined('ABSPATH') || exit;
function qrp_register() {
    register_post_type('qrp_item', [
        'labels'=>['name'=>'Treści QR','singular_name'=>'Treść QR','add_new_item'=>'Dodaj treść QR','edit_item'=>'Edytuj treść QR'],
        'public'=>true,'show_in_rest'=>true,'show_in_menu'=>current_user_can('manage_options')?'qrp-dashboard':true,'menu_icon'=>'dashicons-location-alt',
        'supports'=>['title','editor','excerpt','thumbnail','revisions','page-attributes'], 'rewrite'=>['slug'=>'q','with_front'=>false], 'has_archive'=>false,
    ]);
    register_taxonomy('qrp_category',['qrp_item'],['label'=>'Kategorie QR','public'=>true,'hierarchical'=>true,'show_in_rest'=>true,'rewrite'=>['slug'=>'kategoria-qr']]);
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
function qrp_scan_url($id) { return add_query_arg('qrp_code',qrp_token($id),home_url('/')); }
function qrp_template($file,$vars=[]) { extract($vars,EXTR_SKIP); ob_start(); include QRP_DIR.'templates/'.$file.'.php'; return ob_get_clean(); }
add_shortcode('qr_pelplin_landing',function(){return qrp_template('landing',['s'=>qrp_settings()]);});
add_filter('template_include',function($template){
    $s=qrp_settings();
    if (($s['landing_page'] && is_page($s['landing_page'])) || is_singular('qrp_item') || is_tax('qrp_category')) return QRP_DIR.'templates/shell.php';
    return $template;
});
function qrp_is_portal() { $s=qrp_settings(); return is_singular('qrp_item') || is_tax('qrp_category') || ($s['landing_page'] && is_page($s['landing_page'])) || (is_singular() && has_shortcode(get_post()->post_content??'','qr_pelplin_landing')); }
add_action('wp_enqueue_scripts',function(){
    if (!qrp_is_portal()) return;
    wp_enqueue_style('qrp-public',QRP_URL.'assets/public.css',[],QRP_VERSION);
    wp_enqueue_script('qrp-public',QRP_URL.'assets/dist/public.js',[],QRP_VERSION,true);
    wp_localize_script('qrp-public','qrpPublic',['api'=>rest_url('qr-pelplin/v1/'),'landing'=>qrp_landing_url()]);
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
