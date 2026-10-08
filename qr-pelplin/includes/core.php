<?php
defined('ABSPATH') || exit;
function qrp_register() {
 register_post_type('qrp_item', [
  'labels'=>['name'=>'Treści QR','singular_name'=>'Treść QR','add_new_item'=>'Dodaj treść QR'],
  'public'=>true,'show_in_rest'=>true,'supports'=>['title','editor','excerpt','thumbnail','revisions'],
  'rewrite'=>['slug'=>'q','with_front'=>false],'has_archive'=>false,'menu_icon'=>'dashicons-location-alt'
 ]);
 register_taxonomy('qrp_category','qrp_item',['label'=>'Kategorie QR','public'=>true,'hierarchical'=>true,'show_in_rest'=>true]);
 foreach (['Historia i dziedzictwo','Ciekawe miejsca','Zdjęcia i multimedia','Wydarzenia i lokalne inicjatywy'] as $name) {
  if (!term_exists($name,'qrp_category')) wp_insert_term($name,'qrp_category');
 }
}
add_action('init','qrp_register');
function qrp_activate() {
 qrp_register();
 if (!get_option('qrp_landing_page')) {
  $page=get_page_by_path('odkrywaj-pelplin');
  if (!$page) $id=wp_insert_post(['post_title'=>'Odkrywaj Pelplin','post_name'=>'odkrywaj-pelplin','post_type'=>'page','post_status'=>'publish','post_content'=>'[qr_pelplin_landing]']);
  else $id=$page->ID;
  if (!is_wp_error($id)) update_option('qrp_landing_page',(int)$id);
 }
 flush_rewrite_rules();
}
function qrp_deactivate(){flush_rewrite_rules();}
add_shortcode('qr_pelplin_landing',function(){
 $items=get_posts(['post_type'=>'qrp_item','post_status'=>'publish','numberposts'=>12]);
 ob_start();include QRP_DIR.'templates/landing.php';return ob_get_clean();
});
add_filter('the_content',function($content){
 if (!is_singular('qrp_item') || !in_the_loop() || !is_main_query()) return $content;
 return '<div class="qrp-entry"><p class="qrp-back"><a href="'.esc_url(home_url('/')).'">← Odkrywaj Pelplin</a></p>'.$content.'</div>';
},20);
add_action('wp_enqueue_scripts',function(){
 if (is_front_page() || is_singular('qrp_item') || is_page((int)get_option('qrp_landing_page'))) wp_enqueue_style('qrp-public',plugins_url('../assets/public.css',__FILE__),[], '0.1.0');
});
add_action('admin_menu',function(){
 add_menu_page('QR Pelplin','QR Pelplin','manage_options','qrp-dashboard','qrp_dashboard','dashicons-admin-site-alt3',25);
 add_submenu_page('qrp-dashboard','Ustawienia','Ustawienia','manage_options','qrp-settings','qrp_settings');
});
function qrp_dashboard(){
 if (!current_user_can('manage_options')) return;
 $count=wp_count_posts('qrp_item');
 echo '<div class="wrap"><h1>QR Pelplin</h1><p>Opublikowane treści: <strong>'.esc_html((string)($count->publish??0)).'</strong></p><p><a class="button button-primary" href="'.esc_url(admin_url('post-new.php?post_type=qrp_item')).'">Dodaj treść QR</a></p><p>Generator kodów QR i statystyki zostaną dodane w kolejnym etapie.</p>';
 $latest=qrp_release_info();
 echo '<h2>Aktualizacje wtyczki</h2><p>Wersja zainstalowana: 0.1.0. '.($latest?'Najnowsza wersja na GitHubie: '.esc_html($latest['version']):'Nie udało się pobrać informacji o wydaniu.').'</p>';
 echo '<p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=qrp_check_update'),'qrp_check_update')).'">Sprawdź aktualizacje (odśwież cache)</a> <a class="button button-primary" href="'.esc_url(admin_url('plugins.php')).'">Przejdź do aktualizacji</a></p></div>';
}
function qrp_settings(){
 if (!current_user_can('manage_options')) return;
 if (isset($_POST['qrp_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['qrp_nonce'])),'qrp_settings')) {
  $id=absint($_POST['qrp_page']??0);
  if ($id && get_post_type($id)==='page') update_option('qrp_landing_page',$id);
  echo '<div class="notice notice-success"><p>Zapisano.</p></div>';
 }
 echo '<div class="wrap"><h1>Ustawienia QR Pelplin</h1><form method="post">';
 wp_nonce_field('qrp_settings','qrp_nonce');
 wp_dropdown_pages(['name'=>'qrp_page','selected'=>(int)get_option('qrp_landing_page')]);
 submit_button('Zapisz');
 echo '</form><p>Aby ustawić landing jako stronę główną: Ustawienia → Czytanie → Strona statyczna.</p></div>';
}
