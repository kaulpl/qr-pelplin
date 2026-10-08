<?php
require '/wordpress/wp-load.php';
wp_set_current_user(1);
function qrp_test($condition,$message){if(!$condition)throw new Exception($message);}
$uploads=wp_upload_dir();wp_mkdir_p($uploads['path']);
function qrp_fixture($name,$mime,$bytes){$u=wp_upload_dir();$path=$u['path'].'/'.$name;file_put_contents($path,$bytes);return wp_insert_attachment(['post_title'=>$name,'post_mime_type'=>$mime,'post_status'=>'inherit'],$path,0);}
$stream="BT /F1 18 Tf 30 340 Td (Material PDF - QR Pelplin) Tj ET";
$objects=['<</Type/Catalog/Pages 2 0 R>>','<</Type/Pages/Kids[3 0 R]/Count 1>>','<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 400]/Resources<</Font<</F1 5 0 R>>>>/Contents 4 0 R>>','<</Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",'<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>'];
$pdfbytes="%PDF-1.4\n";$offsets=[0];foreach($objects as $i=>$object){$offsets[]=strlen($pdfbytes);$pdfbytes.=($i+1)." 0 obj\n".$object."\nendobj\n";}
$xref=strlen($pdfbytes);$pdfbytes.="xref\n0 6\n0000000000 65535 f \n";foreach(array_slice($offsets,1) as $offset)$pdfbytes.=sprintf("%010d 00000 n \n",$offset);$pdfbytes.="trailer\n<</Size 6/Root 1 0 R>>\nstartxref\n".$xref."\n%%EOF";
$pdf=qrp_fixture('test-material.pdf','application/pdf',$pdfbytes);
$jpg=qrp_fixture('test-photo.jpg','image/jpeg',file_get_contents(QRP_DIR.'assets/hero.jpg'));
$mp3=qrp_fixture('test-audio.mp3','audio/mpeg',str_repeat("\xFF\xFB\x90\x00".str_repeat("\0",413),16));
$bad=qrp_fixture('unsafe.svg','image/svg+xml','<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
qrp_test(!qrp_media_file($bad),'Arbitrary SVG cannot be selected as content file');
qrp_test(qrp_clean_gallery([$jpg,$pdf,$bad,$jpg])===[$jpg],'Gallery validates images and deduplicates');
qrp_test(qrp_clean_documents([$jpg,$pdf,$mp3,$bad])===[$jpg,$pdf],'Documents accept only safe images/PDF');
$ids=[];
foreach(['pdf'=>$pdf,'jpg'=>$jpg,'mp3'=>$mp3] as $kind=>$asset){$id=wp_insert_post(['post_type'=>'qrp_item','post_status'=>'publish','post_title'=>'Sam plik '.$kind,'post_content'=>'']);update_post_meta($id,'qrp_primary_file',$asset);qrp_token($id);$ids[$kind]=$id;qrp_test(qrp_delivery(get_post($id))===($kind==='mp3'?'audio':'download'),'File-only auto routing: '.$kind);}
$id=wp_insert_post(['post_type'=>'qrp_item','post_status'=>'publish','post_title'=>'Historia z galerią i dokumentem','post_content'=>'<!-- wp:paragraph --><p>Treść wpisu z opisem historii.</p><!-- /wp:paragraph -->']);
wp_set_object_terms($id,['Zabytki'],'qrp_category');update_post_meta($id,'qrp_primary_file',$pdf);update_post_meta($id,'qrp_gallery',[$jpg]);update_post_meta($id,'qrp_documents',[$pdf,$jpg]);qrp_token($id);$ids['content']=$id;
qrp_test(qrp_delivery(get_post($id))==='content','Text and file renders content');
$empty=wp_insert_post(['post_type'=>'qrp_item','post_status'=>'publish','post_title'=>'Puste bloki','post_content'=>'<!-- wp:paragraph --><p>&nbsp;</p><!-- /wp:paragraph -->']);update_post_meta($empty,'qrp_primary_file',$pdf);qrp_test(qrp_delivery(get_post($empty))==='download','Empty paragraph is not content');
update_post_meta($empty,'qrp_gallery',[$jpg]);qrp_test(qrp_delivery(get_post($empty))==='content','Gallery alone renders content');
update_post_meta($empty,'qrp_gallery',[]);update_post_meta($empty,'qrp_delivery','preview');qrp_test(qrp_delivery(get_post($empty))==='preview','Explicit file preview');
update_post_meta($empty,'qrp_delivery','download');qrp_test(str_contains(qrp_target_url(get_post($empty)),'qrp_download=1'),'Download target keeps stable post redirect');
$fake=['version'=>'1.2.0','url'=>'https://github.com/kaulpl/qr-pelplin/releases/download/v1.2.0/qr-pelplin.zip','published'=>'2026-10-08','release_url'=>'https://github.com/kaulpl/qr-pelplin/releases/tag/v1.2.0'];set_transient('qrp_release',$fake,3600);$status=qrp_check_update();qrp_test($status['available'] && str_contains($status['update_url'],'_wpnonce=') && !str_contains($status['update_url'],'&amp;'),'Update URL is raw and nonce-protected');
wp_set_current_user(0);$status=qrp_check_update();qrp_test(!$status['can_update'] && !$status['update_url'],'Anonymous cannot install updates');wp_set_current_user(1);
delete_transient('qrp_release');update_option('qrp_test_media',$ids);
flush_rewrite_rules();echo 'MEDIA AND UPDATE TESTS PASSED';
