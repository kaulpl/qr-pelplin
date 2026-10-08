<?php
defined('ABSPATH') || exit;
function qrp_release_info($force=false){
    if($force)delete_transient('qrp_release');
    $cached=get_transient('qrp_release');if(is_array($cached))return $cached;
    if($cached==='unavailable')return new WP_Error('qrp_github','Nie udało się połączyć z GitHubem. Spróbuj ponownie.',['status'=>502]);
    $r=wp_remote_get('https://api.github.com/repos/kaulpl/qr-pelplin/releases/latest',['timeout'=>12,'headers'=>['Accept'=>'application/vnd.github+json','User-Agent'=>'QR-Pelplin/'.QRP_VERSION]]);
    if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){set_transient('qrp_release','unavailable',5*MINUTE_IN_SECONDS);return new WP_Error('qrp_github','GitHub jest niedostępny lub odrzucił żądanie. Nie można potwierdzić dostępności aktualizacji.',['status'=>502]);}
    $data=json_decode(wp_remote_retrieve_body($r),true);$version=ltrim($data['tag_name']??'','v');$url='';
    foreach($data['assets']??[] as $asset)if(($asset['name']??'')==='qr-pelplin.zip' && ($asset['state']??'')==='uploaded')$url=$asset['browser_download_url']??'';
    if(!empty($data['draft'])||!empty($data['prerelease'])||!preg_match('/^\d+\.\d+\.\d+$/',$version)||!str_starts_with($url,'https://github.com/kaulpl/qr-pelplin/releases/download/'))return new WP_Error('qrp_package','Wydanie GitHuba nie zawiera poprawnej paczki qr-pelplin.zip.',['status'=>502]);
    $result=['version'=>$version,'url'=>esc_url_raw($url),'published'=>$data['published_at']??'','release_url'=>esc_url_raw($data['html_url']??'')];set_transient('qrp_release',$result,6*HOUR_IN_SECONDS);return $result;
}
function qrp_apply_release($transient,$release){
    if(is_wp_error($release))return $transient;
    if(!is_object($transient))$transient=new stdClass();
    $plugin=plugin_basename(QRP_DIR.'qr-pelplin.php');
    $info=(object)['slug'=>'qr-pelplin','plugin'=>$plugin,'new_version'=>$release['version'],'url'=>'https://github.com/kaulpl/qr-pelplin','package'=>$release['url'],'requires'=>'6.6','requires_php'=>'8.0'];
    if(version_compare($release['version'],QRP_VERSION,'>')){unset($transient->no_update[$plugin]);$transient->response[$plugin]=$info;}else{unset($transient->response[$plugin]);$transient->no_update[$plugin]=$info;}
    $transient->checked[$plugin]=QRP_VERSION;return $transient;
}
add_filter('pre_set_site_transient_update_plugins',function($t){
    $plugin=plugin_basename(QRP_DIR.'qr-pelplin.php');if(empty($t->checked[$plugin]))return $t;return qrp_apply_release($t,qrp_release_info());
});
function qrp_check_update($force=false){
    $release=qrp_release_info($force);if(is_wp_error($release))return $release;
    $available=version_compare($release['version'],QRP_VERSION,'>');
    $can_update=current_user_can('update_plugins') && wp_is_file_mod_allowed('plugins');
    if($force)set_site_transient('update_plugins',qrp_apply_release(get_site_transient('update_plugins'),$release));
    $plugin=plugin_basename(QRP_DIR.'qr-pelplin.php');
    return ['current'=>QRP_VERSION,'latest'=>$release['version'],'available'=>$available,'can_update'=>$can_update,'published'=>$release['published'],
        'release_url'=>$release['release_url']??'https://github.com/kaulpl/qr-pelplin/releases/latest',
        'update_url'=>$available && $can_update?html_entity_decode(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin='.urlencode($plugin)),'upgrade-plugin_'.$plugin),ENT_QUOTES,'UTF-8'):'',
        'message'=>$available?'Dostępna jest nowa wersja wtyczki.':'Wtyczka jest aktualna.',
    ];
}
add_action('rest_api_init',function(){register_rest_route('qr-pelplin/v1','/updates',[
    ['methods'=>'GET','permission_callback'=>'qrp_admin_permission','callback'=>function(){return qrp_check_update();}],
    ['methods'=>'POST','permission_callback'=>'qrp_admin_permission','callback'=>function(){return qrp_check_update(true);}],
]);});
add_filter('plugins_api',function($result,$action,$args){
    if($action!=='plugin_information'||($args->slug??'')!=='qr-pelplin')return $result;$release=qrp_release_info();if(is_wp_error($release))return $result;
    return (object)['name'=>'QR Pelplin','slug'=>'qr-pelplin','version'=>$release['version'],'author'=>'QR Pelplin','homepage'=>'https://github.com/kaulpl/qr-pelplin','requires'=>'6.6','requires_php'=>'8.0','download_link'=>$release['url'],'sections'=>['description'=>'Portal miejski QR, landing page, CMS, kody SVG/PNG i statystyki.']];
},10,3);
