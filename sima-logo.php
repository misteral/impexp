<?php
require ('include/sund.class.php');

define ( 'DS', DIRECTORY_SEPARATOR );
//define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'IMAGE_BASE', 'c:' . DS.'site-images' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ('VM_IMAGE',dirname(dirname ( __FILE__ )).DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');
define ( 'VENDOR','1' ); //вендор сима

$cat = scandir(IMAGE_BASE);
foreach ($cat as $file){
if ($file =='.' or $file =='..'){continue;}
$file_500 = VM_IMAGE.DS.VENDOR.'_500_'.$file;
$file_90 = VM_IMAGE.DS.'resized'.DS.VENDOR.'_90_'.$file;
$logo_file = IMAGE_BASE.DS.'logo_'.$file;
$file = IMAGE_BASE.DS.$file;
$logo = 'include/img - logo.png';
$o = new output('add_logo');
if (!file_exists($file_500) or !file_exists($file_90)){
ex_Mysql::add_logo($file,$logo,100,false);
ex_Mysql::img_resize($logo_file,$file_500,500,500,$color = 0xFFFFFF,80);
ex_Mysql::img_resize($logo_file,$file_90,90,90,$color = 0xFFFFFF,80);
unlink($logo_file);
}

//exit;
}
?>