<?php
require ('include/sund.class.php');

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');

$cat = scandir(IMAGE_BASE);
foreach ($cat as $file){
if ($file =='.' or $file =='..'){continue;}
$file_500 = IMAGE_BASE.DS.'1_500_'.$file;
$file = IMAGE_BASE.DS.$file;
$logo = 'include/img - logo.png';
$o = new output('add_logo');

ex_Mysql::add_logo($file,$logo,TRUE);
ex_Mysql::img_resize($file,$file_500,500,500,$color = 0xFFFFFF,80);
//exit();
}
?>