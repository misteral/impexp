<?php
require ('include/sund.class.php');

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');

$file = IMAGE_BASE.DS.'424639.jpg';
$logo = IMAGE_BASE.DS.'img - logo.png';
$o = new output('add_logo');
ex_Mysql::add_logo($file,$logo);

?>