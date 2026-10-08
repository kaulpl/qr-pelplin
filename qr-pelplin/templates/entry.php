<?php defined('ABSPATH') || exit; $id=get_the_ID(); ?>
<article class="qrp-entry qrp-section" data-entry-id="<?php echo absint($id); ?>"><a class="qrp-text-link" href="<?php echo esc_url(qrp_landing_url()); ?>">← Wróć do odkrywania</a><header><p class="qrp-eyebrow"><?php $terms=wp_get_post_terms($id,'qrp_category');if(!is_wp_error($terms))echo esc_html(implode(' · ',wp_list_pluck($terms,'name'))); ?></p><h1><?php the_title(); ?></h1><?php if(has_excerpt()): ?><p class="qrp-entry-lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?></header>
<?php if(post_password_required()): echo get_the_password_form(); else: ?>
<?php if(has_post_thumbnail()) the_post_thumbnail('full',['class'=>'qrp-entry-cover']); ?>
<div class="qrp-prose"><?php the_content(); ?></div>
<?php foreach(['audio','video'] as $type){$asset=(int)get_post_meta($id,'qrp_'.$type,true);$url=$asset?wp_get_attachment_url($asset):'';if($url && str_starts_with(get_post_mime_type($asset)?:'',$type.'/'))echo '<'.$type.' controls preload="metadata" src="'.esc_url($url).'"></'.$type.'>';} ?>
<?php $lat=get_post_meta($id,'qrp_lat',true);$lng=get_post_meta($id,'qrp_lng',true);if(is_numeric($lat)&&is_numeric($lng)): ?><aside class="qrp-entry-location"><p><?php echo qrp_icon('pin').' '.esc_html(get_post_meta($id,'qrp_address',true)); ?></p><a class="qrp-button" href="<?php echo esc_url('https://www.openstreetmap.org/?mlat='.(float)$lat.'&mlon='.(float)$lng.'#map=17/'.(float)$lat.'/'.(float)$lng); ?>" target="_blank" rel="noopener">Zobacz miejsce na mapie ↗</a></aside><?php endif; ?>
<?php endif; ?></article>
