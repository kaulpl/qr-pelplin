<?php
defined('ABSPATH') || exit;
function qrp_inline_allowed_ids($id){return array_values(array_unique(array_merge(qrp_clean_gallery(get_post_meta($id,'qrp_gallery',true)),qrp_entry_files($id),array_filter([absint(get_post_meta($id,'qrp_primary_file',true)),absint(get_post_meta($id,'qrp_audio',true)),absint(get_post_meta($id,'qrp_video',true))]))));}
function qrp_inline_ids($post){
    $found=[];$allowed=qrp_inline_allowed_ids($post->ID);preg_match_all('/'.get_shortcode_regex(['qrp_image','qrp_file']).'/s',$post->post_content,$matches,PREG_SET_ORDER);
    foreach($matches as $match){if($match[1]==='['&&$match[6]===']')continue;$atts=shortcode_parse_atts($match[3]);$id=absint(is_array($atts)?($atts['id']??0):0);$file=qrp_media_file($id,$post->ID);if(in_array($id,$allowed,true)&&$file&&($match[2]!=='qrp_image'||$file['type']==='image'))$found[]=$id;}
    return array_values(array_unique($found));
}
function qrp_inline_shortcode($atts,$content=null,$tag='qrp_file'){
    $post=get_post();if(!$post||$post->post_type!=='qrp_item'||post_password_required($post))return '';$atts=shortcode_atts(['id'=>0],$atts,$tag);$id=absint($atts['id']);$file=qrp_media_file($id,$post->ID);
    if(!$file||!in_array($id,qrp_inline_allowed_ids($post->ID),true)||($tag==='qrp_image'&&$file['type']!=='image'))return '<span class="qrp-material-unavailable">Materiał niedostępny.</span>';
    if($tag==='qrp_image')return '<figure class="qrp-inline-image"><div class="qrp-photo-frame">'.wp_get_attachment_image($id,'large',false,['loading'=>'lazy']).qrp_photo_title($id).'</div>'.qrp_photo_credit($id).'</figure>';
    ob_start();qrp_render_file($file);return '<div class="qrp-inline-file" data-inline-file="'.$id.'">'.ob_get_clean().'</div>';
}
add_shortcode('qrp_image','qrp_inline_shortcode');add_shortcode('qrp_file','qrp_inline_shortcode');
