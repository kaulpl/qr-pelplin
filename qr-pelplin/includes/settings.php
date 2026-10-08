<?php
defined('ABSPATH') || exit;
function qrp_defaults() {
    return [
        'landing_page' => 0, 'brand' => 'PELPLIN', 'logo' => 0, 'footer_logo' => 0,
        'hero_image' => 0, 'hero_eyebrow' => 'TREŚCI DOSTĘPNE PO ZESKANOWANIU KODÓW QR',
        'hero_title' => "Pelplin\nbliżej Ciebie", 'hero_text' => 'Zeskanuj kod QR w wybranym miejscu i odkryj historie, ciekawostki oraz multimedia związane z naszym miastem.',
        'hero_button' => 'Odkryj wszystkie treści', 'hero_button_url' => '#tresci',
        'accent' => '#d1a65f', 'background' => '#0d1417', 'text_color' => '#f4f2ed', 'font' => 'classic',
        'categories_title' => 'Przeglądaj kategorie', 'content_title' => 'Historie warte odkrycia',
        'map_title' => 'Miasto zapisane w miejscach', 'map_text' => 'Znajdź miejsca z kodami QR i odkryj ich historie.',
        'map_image' => 0, 'map_lat' => 53.9285, 'map_lng' => 18.6977, 'map_zoom' => 14,
        'about_title' => 'Jedno miasto. Wiele historii.', 'about_text' => 'Poznaj Pelplin poprzez miejsca, ludzi i opowieści. Treści są dostępne także po zeskanowaniu kodów w przestrzeni miasta.',
        'about_image' => 0, 'footer_text' => 'Historie dostępne na wyciągnięcie telefonu.', 'copyright' => 'QR Pelplin • Miasto opowiada.',
        'menu' => [['label'=>'Odkrywaj','url'=>'#tresci'],['label'=>'Mapa','url'=>'#mapa'],['label'=>'Kategorie','url'=>'#kategorie'],['label'=>'O projekcie','url'=>'#projekt']],
        'footer_menu' => [['label'=>'O projekcie','url'=>'#projekt'],['label'=>'Kontakt','url'=>'mailto:kontakt@pelplin.pl']],
        'socials' => [],
        'modules' => [['type'=>'features','enabled'=>true],['type'=>'categories','enabled'=>true],['type'=>'content','enabled'=>true],['type'=>'map','enabled'=>true],['type'=>'about','enabled'=>true]],
        'features' => [['title'=>'Historia i dziedzictwo','icon'=>'crown','url'=>'#kategorie'],['title'=>'Ciekawe miejsca','icon'=>'map','url'=>'#mapa'],['title'=>'Zdjęcia i multimedia','icon'=>'image','url'=>'#tresci'],['title'=>'Wydarzenia i inicjatywy','icon'=>'people','url'=>'#tresci']],
        'content_count' => 6, 'category_count' => 4, 'content_order' => 'date', 'show_search' => true,
        'show_qr_badge' => true, 'analytics_enabled' => true, 'retention_days' => 365,
        'category_images' => [], 'qr_size' => 1024,
    ];
}
function qrp_settings() { $saved=(array)get_option('qrp_settings',[]); if(!isset($saved['landing_page']) && get_option('qrp_landing_page')) $saved['landing_page']=absint(get_option('qrp_landing_page')); return array_replace(qrp_defaults(),$saved); }
function qrp_clean_link($url) {
    $url = trim((string)$url);
    return str_starts_with($url, '#') ? '#' . sanitize_title(substr($url,1)) : esc_url_raw($url, ['http','https','mailto','tel']);
}
function qrp_sanitize_settings($input) {
    $old = qrp_settings(); $out = $old;
    foreach (['brand','hero_eyebrow','hero_title','hero_text','hero_button','categories_title','content_title','map_title','map_text','about_title','about_text','footer_text','copyright'] as $k) if (isset($input[$k])) $out[$k] = in_array($k,['hero_text','map_text','about_text','footer_text'],true)?wp_kses_post(substr((string)$input[$k],0,5000)):sanitize_textarea_field(substr((string)$input[$k],0,5000));
    foreach (['logo','footer_logo','hero_image','map_image','about_image'] as $k) if (isset($input[$k])) {
        $id=absint($input[$k]); $out[$k] = $id && wp_attachment_is_image($id) ? $id : 0;
    }
    if (isset($input['landing_page'])) {
        $id=absint($input['landing_page']); if (!$id || get_post_type($id)==='page') $out['landing_page']=$id;
    }
    foreach (['accent','background','text_color'] as $k) if (isset($input[$k])) $out[$k] = sanitize_hex_color($input[$k]) ?: $old[$k];
    foreach (['show_search','show_qr_badge','analytics_enabled'] as $k) if (isset($input[$k])) $out[$k] = rest_sanitize_boolean($input[$k]);
    foreach (['content_count'=>[1,48], 'category_count'=>[1,24], 'map_zoom'=>[3,18], 'retention_days'=>[7,730], 'qr_size'=>[256,2048]] as $k=>$range) if (isset($input[$k])) $out[$k]=max($range[0],min($range[1],absint($input[$k])));
    foreach (['map_lat'=>[-90,90], 'map_lng'=>[-180,180]] as $k=>$range) if (isset($input[$k]) && is_numeric($input[$k])) $out[$k]=max($range[0],min($range[1],(float)$input[$k]));
    if (isset($input['hero_button_url'])) $out['hero_button_url']=qrp_clean_link($input['hero_button_url']);
    foreach (['font'=>['classic','modern'], 'content_order'=>['date','title','menu_order']] as $k=>$values) if (isset($input[$k]) && in_array($input[$k],$values,true)) $out[$k]=$input[$k];
    foreach (['menu','footer_menu','socials','features'] as $k) if (isset($input[$k]) && is_array($input[$k])) {
        $out[$k]=[];
        foreach (array_slice($input[$k],0,20) as $row) {
            if (!is_array($row)) continue;
            $out[$k][]=['category_id'=>absint($row['category_id']??0),'label'=>sanitize_text_field($row['label']??''), 'title'=>sanitize_text_field($row['title']??''), 'url'=>qrp_clean_link($row['url']??''), 'icon'=>in_array($row['icon']??'', ['crown','map','image','people','book','pin'],true)?$row['icon']:'pin'];
        }
    }
    if (isset($input['modules']) && is_array($input['modules'])) {
        $out['modules']=[]; $seen=[];
        foreach ($input['modules'] as $row) if (is_array($row) && in_array($row['type']??'', ['features','categories','content','map','about'],true) && !in_array($row['type'],$seen,true)) {
            $seen[]=$row['type']; $out['modules'][]=['type'=>$row['type'],'enabled'=>rest_sanitize_boolean($row['enabled']??false)];
        }
    }
    if (isset($input['category_images']) && is_array($input['category_images'])) {
        $out['category_images']=[];
        foreach (array_slice($input['category_images'],0,100,true) as $term=>$id) if (term_exists(absint($term),'qrp_category') && wp_attachment_is_image(absint($id))) $out['category_images'][absint($term)]=absint($id);
    }
    return $out;
}
function qrp_image($id, $size='large') { return $id ? (wp_get_attachment_image_url(absint($id),$size) ?: '') : ''; }
function qrp_icon($name) {
    $paths=['crown'=>'M3 7l4 4 5-8 5 8 4-4-3 12H6L3 7zm3 15h12','map'=>'M3 5l6-2 6 2 6-2v16l-6 2-6-2-6 2V5zm6-2v16m6-14v16','image'=>'M3 3h18v18H3V3zm0 15 6-7 4 5 3-4 5 6M8 7h.01','people'=>'M16 21v-4a4 4 0 0 0-8 0v4M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m8 18v-3a4 4 0 0 0-3-4M4 21v-3a4 4 0 0 1 3-4','book'=>'M12 5v16M3 3c4 0 6 0 9 2 3-2 5-2 9-2v16c-4 0-6 0-9 2-3-2-5-2-9-2V3z','pin'=>'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0zm-8-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="'.esc_attr($paths[$name]??$paths['pin']).'"/></svg>';
}
