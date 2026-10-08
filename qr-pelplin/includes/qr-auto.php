<?php
defined('ABSPATH') || exit;
function qrp_matrix_png($matrix,$width){
    $modules=count($matrix)+8;$scale=max((int)ceil(256/$modules),(int)floor($width/$modules));$size=$modules*$scale;$raw='';
    for($y=0;$y<$size;$y++){$row="\0";$my=(int)floor($y/$scale)-4;for($x=0;$x<$size;$x++){$mx=(int)floor($x/$scale)-4;$dark=$my>=0&&$mx>=0&&$my<count($matrix)&&$mx<count($matrix)&&$matrix[$my][$mx]==='1';$row.=$dark?"\0":"\xff";}$raw.=$row;}
    $chunk=function($type,$bytes){return pack('N',strlen($bytes)).$type.$bytes.pack('N',crc32($type.$bytes));};
    return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',$size,$size,8,0,0,0,0)).$chunk('IDAT',gzcompress($raw,9)).$chunk('IEND','');
}
function qrp_ensure_qr($id){
    static $running=[];$post=get_post($id);if(!$post||$post->post_type!=='qrp_item'||$post->post_status!=='publish')return false;
    if(get_post_meta($id,'qrp_qr_issued',true)||get_post_meta($id,'qrp_qr_png',true)||get_post_meta($id,'qrp_qr_svg',true))return true;
    $lock='qrp_qr_creation_'.$id;if(!add_option($lock,time(),'','no'))return false;
    if(isset($running[$id]))return false;$running[$id]=true;
    try{require_once QRP_DIR.'lib/qr-encoder.php';$url=qrp_scan_url($id);$qr=\QRP\Encoder\QRCode::getMinimumQRCode($url,QRP_QR_ERROR_CORRECT_LEVEL_M);$n=$qr->getModuleCount();$matrix=[];for($y=0;$y<$n;$y++){$row='';for($x=0;$x<$n;$x++)$row.=$qr->isDark($y,$x)?'1':'0';$matrix[]=$row;}
        $request=new WP_REST_Request('POST');$request->set_url_params(['id'=>$id]);$request->set_header('Content-Type','application/json');$request->set_body(wp_json_encode(['url'=>$url,'matrix'=>$matrix,'png'=>'data:image/png;base64,'.base64_encode(qrp_matrix_png($matrix,qrp_settings()['qr_size']))]));$result=qrp_save_qr($request);
    }catch(Throwable $error){$result=new WP_Error('qrp_auto','Nie udało się wygenerować QR: '.$error->getMessage());}
    unset($running[$id]);delete_option($lock);if(is_wp_error($result))update_post_meta($id,'qrp_qr_error',$result->get_error_message());else delete_post_meta($id,'qrp_qr_error');return $result;
}
add_action('transition_post_status',function($new,$old,$post){if($post->post_type==='qrp_item'&&$new==='publish'&&$old!=='publish'&&!wp_is_post_revision($post->ID))qrp_ensure_qr($post->ID);},30,3);

// Preserve the one-time issuance rule for QR files created by earlier plugin versions.
add_action('init',function(){
    if(get_option('qrp_qr_immutable_migrated'))return;
    $page=1;do{$query=new WP_Query(['post_type'=>'qrp_item','post_status'=>['publish','draft','pending','private','trash'],'posts_per_page'=>100,'paged'=>$page,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','meta_query'=>['relation'=>'OR',['key'=>'qrp_qr_png','compare'=>'EXISTS'],['key'=>'qrp_qr_svg','compare'=>'EXISTS']]]);foreach($query->posts as $id)if(!get_post_meta($id,'qrp_qr_issued',true))update_post_meta($id,'qrp_qr_issued',time());$page++;}while($page<=$query->max_num_pages);
    update_option('qrp_qr_immutable_migrated',1);
},25);
