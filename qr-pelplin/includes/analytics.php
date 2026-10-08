<?php
defined('ABSPATH') || exit;
function qrp_install_analytics() {
    global $wpdb;
    require_once ABSPATH.'wp-admin/includes/upgrade.php'; $collate=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$wpdb->prefix}qrp_stats (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint unsigned NOT NULL,
        day date NOT NULL,
        kind varchar(8) NOT NULL,
        hits bigint unsigned NOT NULL DEFAULT 0,
        visitors bigint unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY post_day_kind (post_id,day,kind),
        KEY day (day)
    ) $collate;");
    dbDelta("CREATE TABLE {$wpdb->prefix}qrp_visitors (
        fingerprint char(64) NOT NULL,
        post_id bigint unsigned NOT NULL,
        day date NOT NULL,
        kind varchar(8) NOT NULL,
        last_hit bigint unsigned NOT NULL,
        PRIMARY KEY  (fingerprint,post_id,day,kind),
        KEY day (day)
    ) $collate;");
    update_option('qrp_db_version',QRP_VERSION);
}
function qrp_record($id,$kind) {
    global $wpdb;
    if (!qrp_settings()['analytics_enabled'] || current_user_can('edit_post',$id)) return;
    $ua=substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??'')),0,512);
    if (!$ua || preg_match('/bot|crawler|spider|preview|headless|uptime|monitor/i',$ua)) return;
    $day=current_time('Y-m-d'); $now=time();
    $ip=$_SERVER['REMOTE_ADDR']??''; // Never trust proxy headers or store the source IP.
    $fingerprint=hash_hmac('sha256',$day.'|'.$ip.'|'.$ua,wp_salt('nonce'));
    $new=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}qrp_visitors (fingerprint,post_id,day,kind,last_hit) VALUES (%s,%d,%s,%s,%d)",$fingerprint,$id,$day,$kind,$now));
    if ($new===false) return;
    if (!$new) {
        $accepted=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}qrp_visitors SET last_hit=%d WHERE fingerprint=%s AND post_id=%d AND day=%s AND kind=%s AND last_hit < %d",$now,$fingerprint,$id,$day,$kind,$now-5));
        if (!$accepted) return;
    }
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}qrp_stats (post_id,day,kind,hits,visitors) VALUES (%d,%s,%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1,visitors=visitors+VALUES(visitors)",$id,$day,$kind,$new?1:0));
}
add_action('template_redirect',function(){
    if (!isset($_GET['qrp_code'])) return;
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow'); header('Referrer-Policy: same-origin');
    if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') {status_header(405); exit;}
    $token=sanitize_text_field(wp_unslash($_GET['qrp_code']));
    if (!preg_match('/^[a-f0-9]{24}$/',$token)) wp_die('Nieprawidłowy kod QR.','Kod QR',['response'=>404]);
    $posts=get_posts(['post_type'=>'qrp_item','post_status'=>'publish','numberposts'=>1,'meta_key'=>'qrp_token','meta_value'=>$token,'has_password'=>false]);
    if (!$posts) wp_die('Ta treść jest obecnie niedostępna. Skontaktuj się z administratorem portalu.','Treść QR niedostępna',['response'=>410]);
    qrp_record($posts[0]->ID,'scan');
    wp_safe_redirect(qrp_target_url($posts[0]),302); exit;
},0);
add_action('qrp_cleanup',function(){
    global $wpdb; $s=qrp_settings();
    $cutoff=wp_date('Y-m-d',time()-$s['retention_days']*DAY_IN_SECONDS);
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}qrp_stats WHERE day < %s",$cutoff));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}qrp_visitors WHERE day < %s",wp_date('Y-m-d',time()-2*DAY_IN_SECONDS)));
});
function qrp_stats($days=30) {
    global $wpdb; $days=max(1,min(730,absint($days))); $from=wp_date('Y-m-d',time()-($days-1)*DAY_IN_SECONDS);
    $daily=$wpdb->get_results($wpdb->prepare("SELECT day,kind,SUM(hits) hits,SUM(visitors) visitors FROM {$wpdb->prefix}qrp_stats WHERE day >= %s GROUP BY day,kind ORDER BY day",$from),ARRAY_A);
    $top=$wpdb->get_results($wpdb->prepare("SELECT post_id,kind,SUM(hits) hits,SUM(visitors) visitors FROM {$wpdb->prefix}qrp_stats WHERE day >= %s GROUP BY post_id,kind ORDER BY hits DESC LIMIT 100",$from),ARRAY_A);
    foreach($top as &$row) {$row['title']=get_the_title($row['post_id'])?:'Usunięta treść #'.$row['post_id'];$row['edit_url']=get_edit_post_link($row['post_id'],'raw');} unset($row);
    return ['daily'=>$daily,'top'=>$top,'from'=>$from,'to'=>current_time('Y-m-d')];
}
