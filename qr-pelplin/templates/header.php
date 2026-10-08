<?php defined('ABSPATH') || exit; ?>
<header class="qrp-header"><a class="qrp-brand" href="<?php echo esc_url(qrp_landing_url()); ?>">
<?php if($s['logo']): ?><img src="<?php echo esc_url(qrp_image($s['logo'])); ?>" alt="<?php echo esc_attr($s['brand']); ?>"><?php else: ?><img src="<?php echo esc_url(QRP_URL.'assets/brand.svg'); ?>" alt=""><span><?php echo esc_html($s['brand']); ?></span><?php endif; ?></a>
<button class="qrp-menu-toggle" aria-expanded="false" aria-controls="qrp-navigation">Menu <span>☰</span></button>
<nav id="qrp-navigation" aria-label="Menu główne"><?php foreach($s['menu'] as $link): ?><a href="<?php echo esc_url(str_starts_with($link['url'],'#')?qrp_landing_url().$link['url']:$link['url']); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?><span class="qrp-language" aria-label="Język polski">PL</span></nav></header>
