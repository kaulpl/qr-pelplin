<?php defined('ABSPATH') || exit; $s=qrp_settings(); ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('qrp-portal qrp-font-'.$s['font']); ?>><?php wp_body_open(); ?><a class="qrp-skip" href="#main">Przejdź do treści</a>
<?php echo qrp_template('header',['s'=>$s]); ?>
<main id="main">
<?php if(is_singular('qrp_item')) { while(have_posts()){the_post();echo qrp_template('entry',['s'=>$s]);} }
elseif(is_tax('qrp_category')) { echo '<section class="qrp-section qrp-archive"><p class="qrp-eyebrow">KATEGORIE TREŚCI</p><h1>'.esc_html(single_term_title('',false)).'</h1><div class="qrp-content-grid">';while(have_posts()){the_post();qrp_cards([get_post()]);}echo '</div>';the_posts_pagination();echo '</section>'; }
else echo qrp_template('landing',['s'=>$s]); ?>
</main><?php echo qrp_template('footer',['s'=>$s]); wp_footer(); ?></body></html>
