<?php defined('ABSPATH') || exit; $term=get_queried_object(); $query=qrp_content_query(['category'=>$term->term_id,'page'=>max(1,(int)get_query_var('paged'))]); ?>
<?php
$category_s=$s;$category_s['hero_title']=$term->name;$category_s['hero_text']=wp_strip_all_tags($term->description)?:'Odkryj miejsca, historie i materiały w tej kategorii. Wybierz kafelek, aby poznać opowieść.';
$category_s['hero_eyebrow']='ODKRYWAJ PELPLIN · KATEGORIE';$category_s['hero_image']=$s['category_images'][$term->term_id]??0;$category_s['hero_background']=qrp_category_image($term,$s);$category_s['hero_button']='Przeglądaj wpisy';$category_s['hero_button_url']='#tresci-kategorii';$category_s['show_qr_badge']=false;
echo qrp_template('hero',['s'=>$category_s,'category_hero'=>true]); ?>
<section id="tresci-kategorii" class="qrp-section qrp-archive" data-content data-count="<?php echo absint($s['content_count']); ?>" data-category="<?php echo absint($term->term_id); ?>" data-page="<?php echo max(1,absint(get_query_var('paged'))); ?>">
<a class="qrp-text-link" href="<?php echo esc_url(qrp_landing_url().'#kategorie'); ?>">← Wszystkie kategorie</a>

<nav class="qrp-category-tabs" aria-label="Kategorie treści"><?php $terms=get_terms(['taxonomy'=>'qrp_category','hide_empty'=>false]);if(!is_wp_error($terms))foreach($terms as $category): ?><a href="<?php echo esc_url(get_term_link($category)); ?>" <?php if($category->term_id===$term->term_id)echo 'aria-current="page"'; ?>><?php echo esc_html($category->name); ?></a><?php endforeach; ?></nav>
<span class="qrp-total screen-reader-text"><?php echo absint($query->found_posts); ?> treści</span>
<?php if($s['show_search']): ?><form class="qrp-search" role="search"><label class="screen-reader-text" for="qrp-search">Szukaj w tej kategorii</label><input id="qrp-search" name="search" type="search" placeholder="Szukaj w tej kategorii…"><input type="hidden" name="category" value="<?php echo absint($term->term_id); ?>"><button class="qrp-button" type="submit">Szukaj →</button></form><?php endif; ?>
<div class="qrp-content-grid" data-grid><?php qrp_cards($query->posts); ?></div><p data-content-status role="status"><?php if(!$query->post_count)echo 'W tej kategorii nie ma jeszcze opublikowanych treści.'; ?></p><button class="qrp-outline" data-more <?php if($query->max_num_pages<=max(1,(int)get_query_var('paged')))echo 'hidden'; ?>>Pokaż kolejne treści ↓</button>
<noscript><?php echo paginate_links(['total'=>$query->max_num_pages,'current'=>max(1,(int)get_query_var('paged'))]); ?></noscript>
</section>
