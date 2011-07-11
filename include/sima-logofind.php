<?php
$o->add('-------------------------------------------------------------');
$o->add('Ищем не скачанные картинки и косяки с трумбами');
$o->add('-------------------------------------------------------------');
$o->add('Обработаем продукты');

$sql = "SELECT DISTINCT a.product_full_image FROM #__vm_product a, #__vm_product_mf_xref b
where a.product_publish = 'Y' and a.product_id = b.product_id and b.manufacturer_id = 1";
$db->setQuery ( $sql );
$rows = $db-> loadAssocList();
$size = sizeof($rows);
for ($i=0; $i<$size; $i++){
	$fullimage = VM_IMAGE.DS.$rows[$i]["product_full_image"];
	$f_sima_name = substr_replace(basename($fullimage), "", 0,6);
	//$thumbimage = VM_IMAGE.DS.'resized'.DS.$rows[$i]["product_thumb_image"];
	if (!file_exists($fullimage) and (!file_exists(IMAGE_BASE.DS.$f_sima_name))){
		$url = TARGET."/images/photo/big/".$f_sima_name;
		$urls[] = $url.";".IMAGE_BASE.DS.$f_sima_name;
	}
//	if (!file_exists($thumbimage))$a_t_image[] = basename($fullimage);
}
$o->add('Картинок на закачку проход 1 - '.sizeof($urls));
if ($urls)$urls = $pars->multiget($urls);
$o->add('Картинок на закачку проход 2 - '.sizeof($urls));
if ($urls)$urls = $pars->multiget($urls); //еще разок 
$o->add('Так и не выкачал '.sizeof($urls));
//добавляем логотип переносим в нужный каталог
include('include/sima-logo.php');

$o->add('-------------------------------------------------------------');
$o->add('Обработаем категории');

$sql = 'SELECT category_full_image,category_thumb_image FROM #__vm_category WHERE category_publish = "Y"';
$db->setQuery ( $sql );
$rows = $db-> loadAssocList();
$size = sizeof($rows);
for ($i=0; $i<$size; $i++){
	$thumbimage = VM_IMAGE.DS.$rows[$i]["category_thumb_image"];
	$fullimage = VM_IMAGE.DS.$rows[$i]["category_full_image"];
	if (!file_exists($thumbimage) and (file_exists($fullimage))){ //маленькой нет а большая есть
		ex_Mysql::img_resize($fullimage,$thumbimage,90,90,$color = 0xFFFFFF,80);		
	}
}

?>