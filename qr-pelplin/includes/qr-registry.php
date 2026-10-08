<?php
defined('ABSPATH') || exit;
function qrp_qr_target($source){
    $target=(int)get_post_meta($source->ID,'qrp_qr_target',true);$post=$target?get_post($target):$source;
    return $post && $post->post_type==='qrp_item' && $post->post_status==='publish' && !$post->post_password ? $post : false;
}
function qrp_qr_row($post){
    $target=qrp_qr_target($post);$target_id=(int)get_post_meta($post->ID,'qrp_qr_target',true)?:$post->ID;
    return ['id'=>$post->ID,'title'=>$post->post_title,'url'=>qrp_scan_url($post->ID),'target_id'=>$target_id,'target_title'=>get_the_title($target_id)?:'Usunięta treść','target_url'=>$target?qrp_target_url($target):'','available'=>(bool)$target,'png'=>wp_get_attachment_url(get_post_meta($post->ID,'qrp_qr_png',true))?:'','svg'=>wp_get_attachment_url(get_post_meta($post->ID,'qrp_qr_svg',true))?:''];
}
function qrp_qr_reassign($request){
    $source=get_post(absint($request['id']));$data=(array)$request->get_json_params();$target=get_post(absint($data['target_id']??0));
    if(!$source || $source->post_type!=='qrp_item' || (!get_post_meta($source->ID,'qrp_qr_png',true)&&!get_post_meta($source->ID,'qrp_qr_svg',true)))return new WP_Error('qrp_code_missing','Nie znaleziono wygenerowanego kodu.',['status'=>404]);
    if(!$target || $target->post_type!=='qrp_item' || $target->post_status!=='publish' || $target->post_password)return new WP_Error('qrp_target_invalid','Wybierz opublikowaną treść QR bez hasła.',['status'=>400]);
    update_post_meta($source->ID,'qrp_qr_target',$target->ID);return qrp_qr_row($source);
}
add_action('rest_api_init',function(){
    register_rest_route('qr-pelplin/v1','/codes',['methods'=>'GET','permission_callback'=>'qrp_admin_permission','callback'=>function($r){
        $q=new WP_Query(['post_type'=>'qrp_item','post_status'=>['publish','draft','pending','private','trash'],'posts_per_page'=>20,'paged'=>max(1,absint($r['page']??1)),'s'=>sanitize_text_field($r['search']??''),'orderby'=>'ID','order'=>'DESC','meta_query'=>['relation'=>'OR',['key'=>'qrp_qr_png','compare'=>'EXISTS'],['key'=>'qrp_qr_svg','compare'=>'EXISTS']]]);
        return ['items'=>array_map('qrp_qr_row',$q->posts),'pages'=>$q->max_num_pages,'total'=>$q->found_posts];
    }]);
    register_rest_route('qr-pelplin/v1','/codes/(?P<id>\d+)',['methods'=>'POST','permission_callback'=>'qrp_admin_permission','callback'=>'qrp_qr_reassign']);
    register_rest_route('qr-pelplin/v1','/code-targets',['methods'=>'GET','permission_callback'=>'qrp_admin_permission','callback'=>function($r){
        $q=new WP_Query(['post_type'=>'qrp_item','post_status'=>'publish','has_password'=>false,'posts_per_page'=>50,'paged'=>max(1,absint($r['page']??1)),'s'=>sanitize_text_field($r['search']??''),'orderby'=>'title','order'=>'ASC']);
        return ['items'=>array_map(function($p){return ['id'=>$p->ID,'title'=>get_the_title($p),'url'=>qrp_target_url($p)];},$q->posts),'pages'=>$q->max_num_pages];
    }]);
});
