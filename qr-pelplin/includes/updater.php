<?php
defined('ABSPATH') || exit;
function qrp_release_info(){
    $cached=get_transient('qrp_release'); if(is_array($cached))return $cached;if($cached==='unavailable')return false;
    $r=wp_remote_get('https://api.github.com/repos/kaulpl/qr-pelplin/releases/latest',['timeout'=>8,'headers'=>['Accept'=>'application/vnd.github+json','User-Agent'=>'QR-Pelplin/'.QRP_VERSION]]);
    if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){set_transient('qrp_release','unavailable',HOUR_IN_SECONDS);return false;}
    $data=json_decode(wp_remote_retrieve_body($r),true);$version=ltrim($data['tag_name']??'','v');$url='';
    foreach($data['assets']??[] as $asset)if(($asset['name']??'')==='qr-pelplin.zip')$url=$asset['browser_download_url']??'';
    if(!preg_match('/^\d+\.\d+\.\d+$/',$version)||!str_starts_with($url,'https://github.com/kaulpl/qr-pelplin/releases/download/'))return false;
    $result=['version'=>$version,'url'=>esc_url_raw($url),'published'=>$data['published_at']??''];set_transient('qrp_release',$result,6*HOUR_IN_SECONDS);return $result;
}
add_filter('pre_set_site_transient_update_plugins',function($transient){
    $plugin=plugin_basename(QRP_DIR.'qr-pelplin.php'); if(empty($transient->checked[$plugin]))return $transient;
    $release=qrp_release_info(); if(!$release)return $transient;
    $info=(object)['slug'=>'qr-pelplin','plugin'=>$plugin,'new_version'=>$release['version'],'url'=>'https://github.com/kaulpl/qr-pelplin','package'=>$release['url'],'requires'=>'6.6','requires_php'=>'8.0','tested'=>''];
    if(version_compare($release['version'],QRP_VERSION,'>'))$transient->response[$plugin]=$info;else $transient->no_update[$plugin]=$info;return $transient;
});
add_filter('plugins_api',function($result,$action,$args){
    if($action!=='plugin_information'||($args->slug??'')!=='qr-pelplin')return $result;$release=qrp_release_info();if(!$release)return $result;
    return (object)['name'=>'QR Pelplin','slug'=>'qr-pelplin','version'=>$release['version'],'author'=>'QR Pelplin','homepage'=>'https://github.com/kaulpl/qr-pelplin','requires'=>'6.6','requires_php'=>'8.0','download_link'=>$release['url'],'sections'=>['description'=>'Portal miejski QR, landing page, CMS, kody SVG/PNG i statystyki.']];
},10,3);
