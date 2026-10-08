<?php
defined('ABSPATH') || exit;
function qrp_admin_permission() { return current_user_can('manage_options'); }
function qrp_edit_permission($r) { return get_post_type(absint($r['id']))==='qrp_item' && current_user_can('edit_post',absint($r['id'])); }
add_action('rest_api_init',function(){
    register_rest_route('qr-pelplin/v1','/settings',[
        ['methods'=>'GET','permission_callback'=>'qrp_admin_permission','callback'=>function(){return qrp_settings();}],
        ['methods'=>'POST','permission_callback'=>'qrp_admin_permission','callback'=>function($r){$s=qrp_sanitize_settings((array)$r->get_json_params());update_option('qrp_settings',$s);return $s;}],
    ]);
    register_rest_route('qr-pelplin/v1','/content',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){
        $q=qrp_content_query(['page'=>$r['page'],'per_page'=>$r['per_page']??qrp_settings()['content_count'],'search'=>$r['search'],'category'=>$r['category']]);
        return ['items'=>array_map('qrp_item_data',$q->posts),'pages'=>$q->max_num_pages,'total'=>$q->found_posts];
    }]);
    register_rest_route('qr-pelplin/v1','/locations',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){
        $q=new WP_Query(['post_type'=>'qrp_item','post_status'=>'publish','has_password'=>false,'posts_per_page'=>100,'paged'=>max(1,absint($r['page']??1)),'meta_query'=>[['key'=>'qrp_lat','compare'=>'EXISTS'],['key'=>'qrp_lng','compare'=>'EXISTS']]]);
        $response=new WP_REST_Response(['items'=>array_values(array_filter(array_map('qrp_item_data',$q->posts),function($i){return is_numeric($i['lat'])&&is_numeric($i['lng']);})),'pages'=>$q->max_num_pages]);$response->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');return $response;
    }]);
    register_rest_route('qr-pelplin/v1','/stats',['methods'=>'GET','permission_callback'=>'qrp_admin_permission','callback'=>function($r){return qrp_stats($r['days']??30);}]);
    register_rest_route('qr-pelplin/v1','/qr/(?P<id>\d+)',[
        ['methods'=>'GET','permission_callback'=>'qrp_edit_permission','callback'=>function($r){$id=absint($r['id']);return ['url'=>qrp_scan_url($id),'title'=>get_the_title($id),'size'=>qrp_settings()['qr_size'],'png'=>wp_get_attachment_url(get_post_meta($id,'qrp_qr_png',true)),'svg'=>wp_get_attachment_url(get_post_meta($id,'qrp_qr_svg',true))];}],
        ['methods'=>'POST','permission_callback'=>'qrp_edit_permission','callback'=>'qrp_save_qr'],
    ]);
    register_rest_route('qr-pelplin/v1','/view/(?P<id>\d+)',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>function($r){
        $post=get_post(absint($r['id']));
        if (!$post || $post->post_type!=='qrp_item' || $post->post_status!=='publish' || $post->post_password) return new WP_Error('qrp_missing','Treść niedostępna',['status'=>404]);
        qrp_record($post->ID,'view'); return ['recorded'=>true];
    }]);
});
function qrp_svg_from_matrix($matrix) {
    $n=count($matrix);
    if ($n<21 || $n>177 || ($n-21)%4!==0) return false;
    $path='';
    foreach ($matrix as $y=>$row) {
        if (!is_string($row) || strlen($row)!==$n || preg_match('/[^01]/',$row)) return false;
        for ($x=0;$x<$n;$x++) if ($row[$x]==='1') $path.='M'.($x+4).' '.($y+4).'h1v1h-1z';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.($n+8).' '.($n+8).'" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="'.$path.'" fill="#000"/></svg>';
}
function qrp_save_qr($r) {
    $id=absint($r['id']); $data=(array)$r->get_json_params();
    if (($data['url']??'')!==qrp_scan_url($id)) return new WP_Error('qrp_url','Adres QR zmienił się. Wygeneruj kod ponownie.',['status'=>409]);
    $matrix=$data['matrix']??[];
    $svg=is_array($matrix)?qrp_svg_from_matrix($matrix):false;
    $pngstring=$data['png']??'';
    if (!$svg || !is_string($pngstring) || strlen($pngstring)>4*1024*1024 || !str_starts_with($pngstring,'data:image/png;base64,')) return new WP_Error('qrp_invalid','Nieprawidłowe dane kodu.',['status'=>400]);
    $png=base64_decode(substr($pngstring,22),true); $info=$png?@getimagesizefromstring($png):false;
    if (!$info || $info[2]!==IMAGETYPE_PNG || $info[0]!==$info[1] || $info[0]<256 || $info[0]>2048) return new WP_Error('qrp_png','Nieprawidłowy PNG.',['status'=>400]);
    $result=[]; $created=[]; $old=[];
    foreach (['png'=>$png,'svg'=>$svg] as $format=>$bytes) {
        $filename='qr-pelplin-'.$id.'-'.substr(qrp_token($id),0,8).'.'.$format;
        if ($format==='svg') {
            // Only server-built QR SVG is written; arbitrary SVG uploads stay disabled.
            $dir=wp_upload_dir(); $error=$dir['error']; $file=''; $url='';
            if (!$error && wp_mkdir_p($dir['path'])) {
                $filename=wp_unique_filename($dir['path'],$filename); $file=$dir['path'].'/'.$filename; $url=$dir['url'].'/'.$filename;
                if(file_put_contents($file,$bytes)===false)$error='Nie udało się zapisać SVG.';
            } else {$error=$error?:'Nie udało się utworzyć katalogu plików.';}
            $upload=['file'=>$file,'url'=>$url,'error'=>$error];
        } else {$upload=wp_upload_bits($filename,null,$bytes);}
        if ($upload['error']) {foreach($created as $aid) wp_delete_attachment($aid,true);return new WP_Error('qrp_upload',$upload['error'],['status'=>500]);}
        $aid=wp_insert_attachment(['post_mime_type'=>$format==='png'?'image/png':'image/svg+xml','post_title'=>'QR — '.get_the_title($id).' ('.strtoupper($format).')','post_status'=>'inherit'],$upload['file'],$id,true);
        if (is_wp_error($aid)) {wp_delete_file($upload['file']);foreach($created as $a)wp_delete_attachment($a,true);return $aid;}
        $created[]=$aid;
        update_post_meta($aid,'_qrp_generated_for',$id);
        if($format==='png') {require_once ABSPATH.'wp-admin/includes/image.php';wp_update_attachment_metadata($aid,wp_generate_attachment_metadata($aid,$upload['file']));}
        $old[$format]=(int)get_post_meta($id,'qrp_qr_'.$format,true);
        $result[$format]=['id'=>$aid,'url'=>$upload['url']];
    }
    foreach($result as $format=>$asset) update_post_meta($id,'qrp_qr_'.$format,$asset['id']);
    // Preserve earlier attachments: their URLs may already be embedded in published content.
    return $result;
}
add_action('admin_post_qrp_export',function(){
    if (!current_user_can('manage_options')) wp_die('Brak uprawnień.',403);
    check_admin_referer('qrp_export'); $stats=qrp_stats(absint($_GET['days']??30));
    nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="qr-pelplin-statystyki.csv"');
    $f=fopen('php://output','w'); fwrite($f,"\xEF\xBB\xBF"); fputcsv($f,['Data','Typ','Wejścia','Odwiedzający dziennie'],';');
    foreach($stats['daily'] as $row) fputcsv($f,[$row['day'],$row['kind'],$row['hits'],$row['visitors']],';'); fclose($f);exit;
});
