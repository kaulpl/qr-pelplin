<?php
/**
 * Plugin Name: QR Pelplin CMS
 * Version: 0.1.0
 * Description: Headless CMS dla qr.pelplin.pl
 */
if (!defined('ABSPATH')) exit;
add_action('init',function(){
 register_post_type('qr_entry',['labels'=>['name'=>'Treści QR','singular_name'=>'Treść QR'],'public'=>true,'show_in_rest'=>true,'rest_base'=>'qr_entry','supports'=>['title','editor','excerpt','thumbnail','revisions'],'rewrite'=>['slug'=>'qr']]);
 register_taxonomy('qr_category','qr_entry',['label'=>'Kategorie QR','public'=>true,'show_in_rest'=>true,'hierarchical'=>true]);
});
