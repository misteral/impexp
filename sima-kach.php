<?php
//exit();
ini_set ( 'max_execution_time', 0);// убираем ограничение по времени;
ini_set ( 'max_input_time', 0); //
set_time_limit (0);

require ('include/sund.class.php');
include('include/simple_html_dom.php');

$db  = new ex_Mysql();
//$db->clear(1); //почистим базу
$pars = new parse();
$out = new output('sima-kach');
$out->echo = false;

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'CPATH_BASE', dirname ( __FILE__ ) . DS.'dw-sima' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ( 'VENDOR','1' ); //вендор сима
define( '_TRY', 3); //количество попыток закачки

$pars->proxy = '67.205.68.11:8080';
$pars->sleep = '5';

//качаем каталог
//echo 'Качаем каталог'; 
//$res =  $pars->get_1251_to_UTF(TARGET.CATALOG, CPATH_BASE.DS."catalog.html");
//if (!$res) {$out->add('Не могу скачать каталог!!!');exit();}
$document = file_get_html(CPATH_BASE.DS."catalog.html");

//разбираем каталог пишем в базу 
foreach ($document->find('table[class=catalog-all-children-category] td')as $el2) { //нашли нужную таблицу
	
	$p = 0;
	$parent=0;
	foreach ($el2->find('a') as $el3){  // цикл по тегу а 
		//$lenEl3 = pq($el3)->count();
		//echo (pq($el3)->dump());
		$e = new item_VM();
		$title = $el3->text();
		$title_link = $el3->href;
		$arr = explode('/', $title_link);
		$e->product_sku = $arr[2];
		$e->product_name = $title;
		$e->product_url = $title_link;
		$e->product_parent_id = $parent;
		$e->product_isgroup = 1;
		$e->product_vendor = VENDOR;
		$e->product_price = 0;
		$e->product_status = 0;
		if ($p ==0){ //идем по головным категориям
    		$parent = $db->last_id()+1;
		}
		$p=p+1;
	$db->add($e);
	}
}
// изменим статус на другой чтоб не качал эти категории 
$db->change_status('игрушка', 3, VENDOR);
//exit;
// качаем файлы по категориям

/*$rows = $db->child_gr();
foreach ($rows as $value){
	if (!$value->product_status){//не качаем если статус не NULL или не 0 
	$dop = 1; // добавочный к файлу количество страниц 
	$id = $value->product_id;
	$url = $value->product_url;
	//разберем url и заменим количество товара на одну станицу
	$arr = explode('/', $url);
	$arr[4] = 500;
	$url = implode('/', $arr);
	$out->add('Качаем категорию '.$value->product_name.' с адреса '.TARGET.$url.'</br>');
	$p=1;
	$res=0;
	while ((!$res) or ($p> _TRY )) {
			$res = $pars->get_1251_to_UTF(TARGET.$url, CPATH_BASE.DS.$id.'_'.$dop.'.html');
			if (!$res){sleep(10);	}
			$p=$p+1;
	}
	if ($res) { //файл закачан если нет то у него статус не сменится
		$db->update_status(1, $value->product_id);
		$out->add('скачано байт '.$res.'</br>');
	}else {$out->add('Файл не скачан');}
	*/
		
	
	//echo ($out->txt());
	
/*	$document = phpQuery::newDocumentHTML($html);
    $el1 = $document->find('table class="paginator"'); //нашли нужную таблицу*/

	flush();
	
//	} //не качаем если статус 1
//}

?>