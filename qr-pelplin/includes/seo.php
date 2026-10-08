<?php
defined('ABSPATH') || exit;
function qrp_seo_external(){return defined('WPSEO_VERSION')||defined('RANK_MATH_VERSION')||defined('SEOPRESS_VERSION')||defined('AIOSEO_VERSION');}
function qrp_seo_active(){return qrp_settings()['seo_enabled']&&!qrp_seo_external();}
function qrp_seo_text($text,$length=180){$text=trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags(strip_shortcodes((string)$text)),ENT_QUOTES,'UTF-8')));return function_exists('mb_substr')?mb_substr($text,0,$length):wp_html_excerpt($text,$length,'');}
function qrp_seo_data(){
    $s=qrp_settings();$name=$s['seo_site_name']?:$s['brand'];$title=$s['seo_title']?:$name;$description=$s['seo_description']?:qrp_seo_text($s['hero_text']);$url=qrp_landing_url();$image=qrp_image($s['seo_image'],'full');$noindex=!$s['seo_index']||!get_option('blog_public');$post=null;
    if(is_singular('qrp_item')){$post=get_queried_object();$title=get_post_meta($post->ID,'qrp_seo_title',true)?:($post->post_title.' — '.$name);$description=get_post_meta($post->ID,'qrp_seo_description',true)?:qrp_seo_text($post->post_excerpt?:$post->post_content);$url=get_permalink($post);$image=get_the_post_thumbnail_url($post,'full')?:$image;$noindex=$noindex||get_post_meta($post->ID,'qrp_seo_noindex',true)||$post->post_status!=='publish'||(bool)$post->post_password;if($post->post_password)$description='Treść chroniona hasłem.';}
    elseif(is_tax('qrp_category')){$term=get_queried_object();$title=$term->name.' — '.$name;$description=qrp_seo_text(term_description($term->term_id,'qrp_category'))?:('Poznaj '.$term->name.' w Pelplinie. Historie, miejsca i multimedia dostępne przez kody QR.');$link=get_term_link($term);if(!is_wp_error($link))$url=$link;$image=qrp_category_image($term,$s);if(get_query_var('paged')>1){$page=absint(get_query_var('paged'));$url=trailingslashit($url).'page/'.$page.'/';$title.=' — strona '.$page;}}
    if(isset($_GET['qrp_credits'])||isset($_GET['qrp_download'])||is_preview())$noindex=true;
    return ['title'=>qrp_seo_text($title,200),'description'=>qrp_seo_text($description,500),'url'=>$url,'image'=>$image,'noindex'=>(bool)$noindex,'name'=>$name,'post'=>$post];
}
add_action('after_setup_theme',function(){add_theme_support('title-tag');},20);
add_filter('pre_get_document_title',function($title){return qrp_seo_active()&&qrp_is_portal()?qrp_seo_data()['title']:$title;},20);
add_action('template_redirect',function(){if(qrp_seo_active()&&qrp_is_portal())remove_action('wp_head','rel_canonical');},20);
add_filter('wp_robots',function($robots){if(qrp_seo_active()&&qrp_is_portal()){$data=qrp_seo_data();if($data['noindex']){$robots['noindex']=true;unset($robots['index']);}$robots['max-image-preview']='large';}return $robots;});
add_action('wp_head',function(){
    if(!qrp_is_portal())return;$s=qrp_settings();if($s['seo_verification'])echo '<meta name="google-site-verification" content="'.esc_attr($s['seo_verification']).'">' . "\n";
    if(!qrp_seo_active())return;$data=qrp_seo_data();
    echo '<meta name="description" content="'.esc_attr($data['description']).'">' . "\n" . '<link rel="canonical" href="'.esc_url($data['url']).'">' . "\n";
    foreach(['og:title'=>$data['title'],'og:description'=>$data['description'],'og:url'=>$data['url'],'og:type'=>$data['post']?'article':'website','og:site_name'=>$data['name'],'og:locale'=>str_replace('-','_',get_bloginfo('language'))] as $key=>$value)echo '<meta property="'.esc_attr($key).'" content="'.esc_attr($value).'">' . "\n";
    if($data['image'])echo '<meta property="og:image" content="'.esc_url($data['image']).'">' . "\n";
    echo '<meta name="twitter:card" content="'.($data['image']?'summary_large_image':'summary').'">' . "\n";
    if($s['seo_schema']){$schema=['@context'=>'https://schema.org','@type'=>$data['post']?'CreativeWork':(is_tax('qrp_category')?'CollectionPage':'WebSite'),'name'=>$data['title'],'description'=>$data['description'],'url'=>$data['url'],'inLanguage'=>get_bloginfo('language')];if($data['image'])$schema['image']=$data['image'];if($data['post']){$schema['datePublished']=get_post_time(DATE_W3C,true,$data['post']);$schema['dateModified']=get_post_modified_time(DATE_W3C,true,$data['post']);}echo '<script type="application/ld+json">'.wp_json_encode($schema,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>' . "\n";}
},5);
add_filter('wp_sitemaps_posts_query_args',function($args,$type){if(!qrp_seo_active())return $args;$s=qrp_settings();if($type==='qrp_item'){$args['has_password']=false;$args['meta_query'][]=['relation'=>'OR',['key'=>'qrp_seo_noindex','compare'=>'NOT EXISTS'],['key'=>'qrp_seo_noindex','value'=>'1','compare'=>'!=']];}if($type==='page'&&!$s['seo_index']&&$s['landing_page'])$args['post__not_in'][]=absint($s['landing_page']);return $args;},10,2);
add_filter('wp_sitemaps_post_types',function($types){if(qrp_seo_active()&&!qrp_settings()['seo_index'])unset($types['qrp_item']);return $types;});
add_filter('wp_sitemaps_taxonomies',function($types){if(qrp_seo_active()&&!qrp_settings()['seo_index'])unset($types['qrp_category']);return $types;});
