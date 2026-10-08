<?php defined('ABSPATH') || exit; $s=qrp_settings(); ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('qrp-portal qrp-font-'.$s['font'].(is_singular('qrp_item')?' qrp-entry-page':'')); ?>><?php wp_body_open(); ?><a class="qrp-skip" href="#main">Przejdź do treści</a>
<?php echo qrp_template('header',['s'=>$s]); ?>
<main id="main">
<?php if(isset($_GET['qrp_credits']))echo qrp_template('credits');
elseif(is_singular('qrp_item')) { while(have_posts()){the_post();echo qrp_template('entry',['s'=>$s]);} }
elseif(is_tax('qrp_category')) { echo qrp_template('category',['s'=>$s]); }
else echo qrp_template('landing',['s'=>$s]); ?>
</main><?php echo qrp_template('footer',['s'=>$s]); wp_footer(); ?></body></html>
