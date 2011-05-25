<?php
$o->add('-------------------------------------------------------------');
$o->add('Добавляем наши логотипы ресайзим и перносим изображения в VM');
$c = 0;
$cat = scandir(IMAGE_BASE);
foreach ($cat as $file){
if ($file =='.' or $file =='..' or !filesize(IMAGE_BASE.DS.$file)){continue;}
$file_500 = VM_IMAGE.DS.VENDOR.'_500_'.$file;
$file_90 = VM_IMAGE.DS.'resized'.DS.VENDOR.'_90_'.$file;
$logo_file = IMAGE_BASE.DS.'logo_'.$file;
$file = IMAGE_BASE.DS.$file;
$logo = 'include/img - logo.png';
//$o = new output('add_logo');
if (!file_exists($file_500) or !file_exists($file_90)) {
	ex_Mysql::add_logo($file,$logo,100,false);
	if (!file_exists($file_500)){ex_Mysql::img_resize($logo_file,$file_500,500,500,$color = 0xFFFFFF,80);}
	if (!file_exists($file_90)){ex_Mysql::img_resize($logo_file,$file_90,90,90,$color = 0xFFFFFF,80);}
	unlink($logo_file);
	++$c;
}
unlink($file);
//exit;
}

$o->add('Добавлено логотипов '.$c);
$o->add('-------------------------------------------------------------');
?>