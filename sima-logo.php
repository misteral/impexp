<?php
require ('include/sund.class.php');

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ('VM_IMAGE',dirname(dirname ( __FILE__ )).DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');

$cat = scandir(IMAGE_BASE);
foreach ($cat as $file){
if ($file =='.' or $file =='..'){continue;}
$file_500 = VM_IMAGE.DS.'1_500_'.$file;
$file_90 = VM_IMAGE.DS.'resized'.DS.'1_90_'.$file;
$file = IMAGE_BASE.DS.$file;
$logo = 'include/img - logo.png';
$o = new output('add_logo');

//ex_Mysql::add_logo($file,$logo,false);
if (!file_exists($file_500)){ex_Mysql::img_resize($file,$file_500,500,500,$color = 0xFFFFFF,80);}
if (!file_exists($file_90)){ex_Mysql::img_resize($file,$file_90,90,90,$color = 0xFFFFFF,100);}


//exit;
}
?>