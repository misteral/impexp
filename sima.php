<?php
ini_set ( 'max_execution_time', 0);// убираем ограничение по времени;
ini_set ( 'max_input_time', 0); //
set_time_limit (0);
//ini_set ( 'display_errors', '1' );
//error_reporting ( E_ALL );

define ( '_JEXEC', 1 ); 												//флаг исполнения для классов джумлы	
define ( 'DS', DIRECTORY_SEPARATOR );					
define ( 'CPATH_BASE', dirname ( __FILE__ ) . DS.'dw-sima' );         	//куда файлы складываем
define ( 'JPATH_BASE', dirname(dirname ( __FILE__ )) . '' ); 			//корень джумлы
//define ( 'IMAGE_BASE', JPATH_BASE.DS.'tmp');					    	//где картинки живут(оригиналы)
define ( 'TARGET', 'http://sima-land.ru' );								//url сайта 
define ( 'CATALOG','/catalog.html'); 									//url каталога
define ( 'VENDOR','1' ); 												//вендор сима
define ( 'DEBUG_VM', true);												//сохраняем запросы к VM в файл
define ( '_TRY', 3); 													//количество попыток закачки
define ( 'DIF_DATE', '3'); 												//количество дней на устаревание
define ( 'WGET_BASE', 'c:' . DS.'wget'.DS.'bin' );						//бинарник wget 
define ( 'WGET_FILE', 'wget.sima-images' );								//файл источник для wget
define ( 'MULTY', true);												//флаг если качаем через мульти
define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'VM_IMAGE',dirname(dirname ( __FILE__ )).DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');
define ( 'IMAGES_FOR_UPLOAD',dirname ( __FILE__ ).DS. 'upload');		//картинки для закачки на сервер

require_once ('include/sund.class.php');
require_once ('include/simple_html_dom.php');
require_once ('include/connectVM.php');

$db_my  = new ex_Mysql();


$pars = new parse();
$o = new output('sima');
$o->echo = false;

//Проверим промзводителя и если нет создадим
$manufacturer = 'sima-land';
$manufacturer_ID = 1;
#ID продавца, имя продавца, мое ID
$vendor_ID = 1;


$wget = FALSE;

if (file_exists(WGET_FILE)){unlink(WGET_FILE);}

//$pars->proxy = '67.205.68.11:8080';
//$pars->proxy = '10.44.33.88:8118';
//$pars->sleep = '5';
//$pars->try = 3;

//качаем и обрабатываем каталог
include('include/sima-kach.php');

//обрабатываем категрии с товаром
include('include/sima-parser-cat.php');

//качаем картинки
//$wget = true;
include('include/sima-img-kach.php');

//добавляем логотип переносим в нужный каталог
include('include/sima-logo.php');


//выгружаем все в virtuemart
include('include/sima-uploadbase.php');

//проверяем картинки в VM и еще раз выкачиваем
require_once ('include/sima-logofind.php');

vm_save_debug();

?>
