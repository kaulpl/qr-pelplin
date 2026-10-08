<?php
defined('ABSPATH') || exit;
define('QRP_RELEASES_API','https://api.github.com/repos/kaulpl/qr-pelplin/releases/latest');
function qrp_release_info($force=false){
 $key='qrp_release_latest';
 if($force)delete_transient($key);
 $cached=get_transient($key);
 if($cached!==false)return $cached;
 $r=wp_remote_get(QRP_RELEASES_API,['timeout'=>12,'headers'=>['Accept'=>'application/vnd.github+json','User-Agent'=>'QR-Pelplin-WordPress','X-GitHub-Api-Version'=>'2022-11-28']]);
 if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return false;
 $j=json_decode(wp_remote_retrieve_body($r),true);
 if(!is_array($j)||empty($j['tag_name'])||empty($j['assets']))return false;
 $asset=null;
 foreach($j['assets'] as $a){if(($a['name']??'')==='qr-pelplin.zip'){$asset=$a;break;}}
 if(!$asset||empty($asset['browser_download_url']))return false;
 $version=ltrim((string)$j['tag_name'],'v');
 if(!preg_match('/^\d+\.\d+\.\d+$/',$version))return false;
 $url=$asset['browser_download_url'];
 if(!wp_http_validate_url($url)||parse_url($url,PHP_URL_HOST)!=='github.com')return false;
 $info=['version'=>$version,'url'=>$url,'details'=>$j['html_url']??''];
 set_transient($key,$info,6*HOUR_IN_SECONDS);
 return $info;
}
add_filter('pre_set_site_transient_update_plugins',function($transient){
 if(!is_object($transient)||empty($transient->checked))return $transient;
 $plugin='qr-pelplin/qr-pelplin.php';
 $latest=qrp_release_info();
 if(!$latest)return $transient;
 $current=$transient->checked[$plugin]??'0.1.0';
 if(version_compare($latest['version'],$current,'>')){
  $transient->response[$plugin]=(object)['slug'=>'qr-pelplin','plugin'=>$plugin,'new_version'=>$latest['version'],'url'=>$latest['details'],'package'=>$latest['url']];
 }else{unset($transient->response[$plugin]);}
 return $transient;
});
add_filter('plugins_api',function($result,$action,$args){
 if($action!=='plugin_information'||($args->slug??'')!=='qr-pelplin')return $result;
 $info=qrp_release_info();
 if(!$info)return $result;
 return (object)['name'=>'QR Pelplin','slug'=>'qr-pelplin','version'=>$info['version'],'author'=>'Gmina Pelplin','homepage'=>$info['details'],'download_link'=>$info['url'],'sections'=>['description'=>'Portal QR Pelplin zarządzany przez WordPress.']];
},10,3);
add_action('admin_post_qrp_check_update',function(){
 if(!current_user_can('update_plugins'))wp_die('Brak uprawnień');
 check_admin_referer('qrp_check_update');
 qrp_release_info(true);
 delete_site_transient('update_plugins');
 wp_clean_plugins_cache(true);
 if(function_exists('wp_update_plugins'))wp_update_plugins();
 wp_safe_redirect(admin_url('admin.php?page=qrp-dashboard&qrp_checked=1'));exit;
});
add_action('admin_notices',function(){
 if(!isset($_GET['page'])||$_GET['page']!=='qrp-dashboard')return;
 if(isset($_GET['qrp_checked']))echo '<div class="notice notice-info"><p>Sprawdzono aktualizacje QR Pelplin.</p></div>';
});
add_action('upgrader_process_complete',function($upgrader,$options){
 if(($options['action']??'')==='update'&&($options['type']??'')==='plugin'&&in_array('qr-pelplin/qr-pelplin.php',$options['plugins']??[],true)){
  delete_transient('qrp_release_latest');
  delete_site_transient('update_plugins');
  wp_clean_plugins_cache(true);
 }
},10,2);
