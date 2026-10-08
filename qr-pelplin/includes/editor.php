<?php
defined('ABSPATH') || exit;
function qrp_editor_permission(){return current_user_can('edit_posts');}
function qrp_editor_entry($post){
    $id=$post->ID;$sections=get_post_meta($id,'qrp_sections',true);
    if(!is_array($sections)||!$sections)$sections=[['uid'=>'legacy-'.$id,'html'=>$post->post_content]];
    $asset=function($aid){$file=qrp_media_file($aid);if(!$file)return null;$file['thumbnail']=wp_get_attachment_image_url($aid,'medium')?:'';return $file;};
    $terms=wp_get_post_terms($id,'qrp_category',['fields'=>'ids']);
    return ['id'=>$id,'title'=>$post->post_title,'excerpt'=>$post->post_excerpt,'slug'=>$post->post_name,'status'=>$post->post_status,'modified'=>$post->post_modified,'sections'=>$sections,'categories'=>is_wp_error($terms)?[]:$terms,
        'thumbnail'=>get_post_thumbnail_id($id)?$asset(get_post_thumbnail_id($id)):null,
        'gallery'=>array_values(array_filter(array_map($asset,qrp_clean_gallery(get_post_meta($id,'qrp_gallery',true))))),
        'attachments'=>array_values(array_filter(array_map($asset,array_values(array_unique(array_merge(qrp_entry_files($id),array_filter([absint(get_post_meta($id,'qrp_audio',true)),absint(get_post_meta($id,'qrp_video',true))]))))))),
        'primary'=>qrp_primary_file($id),'delivery'=>get_post_meta($id,'qrp_delivery',true)?:'auto',
        'lat'=>get_post_meta($id,'qrp_lat',true),'lng'=>get_post_meta($id,'qrp_lng',true),'address'=>get_post_meta($id,'qrp_address',true),'url'=>$post->post_status==='publish'?get_permalink($id):get_preview_post_link($post),
        'qr_png'=>wp_get_attachment_url(get_post_meta($id,'qrp_qr_png',true))?:'','qr_svg'=>wp_get_attachment_url(get_post_meta($id,'qrp_qr_svg',true))?:''];
}
function qrp_editor_save($request){
    $data=(array)$request->get_json_params();$id=absint($request['id']??0);$post=$id?get_post($id):null;
    if($id&&(!$post||$post->post_type!=='qrp_item'||!current_user_can('edit_post',$id)))return new WP_Error('qrp_forbidden','Brak uprawnień do tej treści.',['status'=>403]);
    $status=sanitize_key($data['status']??($post?$post->post_status:'draft'));
    if(!in_array($status,['draft','pending','publish','private'],true))return new WP_Error('qrp_status','Nieprawidłowy status wpisu.',['status'=>400]);
    if(in_array($status,['publish','private'],true)&&!current_user_can('publish_posts'))return new WP_Error('qrp_publish','Nie masz uprawnienia do publikowania.',['status'=>403]);
    $title=sanitize_text_field($data['title']??'');if(!$title)return new WP_Error('qrp_title','Podaj tytuł wpisu.',['status'=>400]);
    $sections=[];$html='';foreach(array_slice(is_array($data['sections']??null)?$data['sections']:[],0,50) as $section){if(!is_array($section))continue;$content=wp_kses_post((string)($section['html']??''));$sections[]=['uid'=>sanitize_key($section['uid']??wp_generate_uuid4()),'html'=>$content];$html.=$content."\n";}
    $lat=$data['lat']??'';$lng=$data['lng']??'';
    if(($lat!==''||$lng!=='')&&(!is_numeric($lat)||!is_numeric($lng)||abs((float)$lat)>90||abs((float)$lng)>180))return new WP_Error('qrp_location','Wybierz prawidłowy punkt na mapie.',['status'=>400]);
    $categories=array_values(array_unique(array_map('absint',is_array($data['categories']??null)?$data['categories']:[])));
    foreach($categories as $term)if(!term_exists($term,'qrp_category'))return new WP_Error('qrp_category','Wybrana kategoria nie istnieje.',['status'=>400]);
    $args=['post_type'=>'qrp_item','post_title'=>$title,'post_excerpt'=>sanitize_textarea_field($data['excerpt']??''),'post_content'=>$html,'post_status'=>$status];if($id)$args['ID']=$id;else$args['post_author']=get_current_user_id();
    if(isset($data['slug'])&&$data['slug']!=='')$args['post_name']=sanitize_title($data['slug']);
    $saved=wp_insert_post(wp_slash($args),true);if(is_wp_error($saved))return $saved;
    update_post_meta($saved,'qrp_sections',$sections);
    foreach(['gallery'=>'qrp_gallery','attachments'=>'qrp_attachments'] as $input=>$key){$ids=array_map(function($asset){return absint(is_array($asset)?($asset['id']??0):$asset);},is_array($data[$input]??null)?$data[$input]:[]);update_post_meta($saved,$key,$input==='gallery'?qrp_clean_gallery($ids):qrp_clean_attachments($ids));}
    // Old document pages are merged into the new attachment list on load.
    delete_post_meta($saved,'qrp_documents');delete_post_meta($saved,'qrp_audio');delete_post_meta($saved,'qrp_video');
    $primary=is_array($data['primary']??null)?absint($data['primary']['id']??0):0;update_post_meta($saved,'qrp_primary_file',qrp_clean_primary_file($primary));
    foreach(['address'=>'qrp_address','lat'=>'qrp_lat','lng'=>'qrp_lng'] as $input=>$key)update_post_meta($saved,$key,sanitize_text_field((string)($data[$input]??'')));
    update_post_meta($saved,'qrp_delivery',qrp_clean_delivery($data['delivery']??'auto'));wp_set_object_terms($saved,$categories,'qrp_category');
    $thumbnail=is_array($data['thumbnail']??null)?absint($data['thumbnail']['id']??0):0;if($thumbnail&&wp_attachment_is_image($thumbnail))set_post_thumbnail($saved,$thumbnail);else delete_post_thumbnail($saved);
    return qrp_editor_entry(get_post($saved));
}
add_action('rest_api_init',function(){
    register_rest_route('qr-pelplin/v1','/entries',[
        ['methods'=>'GET','permission_callback'=>'qrp_editor_permission','callback'=>function($r){$args=['post_type'=>'qrp_item','post_status'=>['publish','draft','pending','private'],'posts_per_page'=>20,'paged'=>max(1,absint($r['page']??1)),'orderby'=>'modified','order'=>'DESC','s'=>sanitize_text_field($r['search']??'')];if(!current_user_can('edit_others_posts'))$args['author']=get_current_user_id();$q=new WP_Query($args);return ['items'=>array_map(function($p){return ['id'=>$p->ID,'title'=>$p->post_title,'status'=>$p->post_status,'modified'=>$p->post_modified,'url'=>get_permalink($p)];},$q->posts),'pages'=>$q->max_num_pages,'total'=>$q->found_posts];}],
        ['methods'=>'POST','permission_callback'=>'qrp_editor_permission','callback'=>'qrp_editor_save'],
    ]);
    register_rest_route('qr-pelplin/v1','/entries/(?P<id>\d+)',[
        ['methods'=>'GET','permission_callback'=>'qrp_edit_permission','callback'=>function($r){return qrp_editor_entry(get_post(absint($r['id'])));}],
        ['methods'=>'POST','permission_callback'=>'qrp_edit_permission','callback'=>'qrp_editor_save'],
        ['methods'=>'DELETE','permission_callback'=>function($r){return get_post_type(absint($r['id']))==='qrp_item'&&current_user_can('delete_post',absint($r['id']));},'callback'=>function($r){return wp_trash_post(absint($r['id']))?['trashed'=>true]:new WP_Error('qrp_trash','Nie udało się przenieść wpisu do kosza.',['status'=>500]);}],
    ]);
});
add_filter('get_edit_post_link',function($link,$id){return get_post_type($id)==='qrp_item'?add_query_arg(['page'=>'qrp-items','item'=>$id],admin_url('admin.php')):$link;},10,2);
add_action('admin_init',function(){
    global $pagenow;
    if($pagenow==='post-new.php'&&($_GET['post_type']??'')==='qrp_item'){wp_safe_redirect(admin_url('admin.php?page=qrp-items&new=1'));exit;}
    if($pagenow==='post.php'&&isset($_GET['post'])&&get_post_type(absint($_GET['post']))==='qrp_item'&&($_GET['action']??'edit')==='edit'){wp_safe_redirect(admin_url('admin.php?page=qrp-items&item='.absint($_GET['post'])));exit;}
});

add_action('rest_api_init',function(){register_rest_route('qr-pelplin/v1','/entry-categories',['methods'=>'POST','permission_callback'=>function(){return current_user_can('manage_categories');},'callback'=>function($r){
    $name=sanitize_text_field($r['name']??'');if(!$name)return new WP_Error('qrp_category_name','Podaj nazwę kategorii.',['status'=>400]);
    $existing=get_term_by('name',$name,'qrp_category');$created=$existing?['term_id'=>$existing->term_id]:wp_insert_term($name,'qrp_category');if(is_wp_error($created))return $created;$term=get_term($created['term_id'],'qrp_category');return ['id'=>$term->term_id,'name'=>$term->name,'slug'=>$term->slug];
}]);});
