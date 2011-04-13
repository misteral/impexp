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
$o = new output('sima-kach');
$out->echo = false;

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'CPATH_BASE', dirname ( __FILE__ ) . DS.'dw-sima' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ( 'VENDOR','1' ); //вендор сима
define( '_TRY', 3); //количество попыток закачки

//$pars->proxy = '67.205.68.11:8080';
$pars->proxy = '10.44.33.88:8118';
$pars->sleep = '5';
//$pars->try = 3;

$url = TARGET.CATALOG;
$file = CPATH_BASE.DS."catalog.html";
if (file_exists($file) and filesize($file)){$creation_date = date ("d.m.y", filemtime($file));}//else{$creation_date = 0;}
$today = date("d.m.y"); 
if ($creation_date<>$today){
	$res = $pars->get_1251_to_UTF($url, $file, _TRY);
	if ($res <> 'ok'){
		$o->add('Немогу скачать каталог');
		exit();
	}else{
	$o->add('Каталог закачан');
	}//else файла нет
}else{
	$o->add('Файл существует и он не старее одного дня');
} 


//------------- разбираем каталог------

//$html = file_get_contents(CPATH_BASE.DS."catalog.html");
//$html= mb_convert_encoding($html,'UTF8', "CP1251");
$html = file_get_html(CPATH_BASE.DS."catalog.html");

$e = $html->find('table[class=catalog-all-children-category] td');
if ($html->innertext!=='' and !sizeof($e)) {$o->add('!!!!!!!!!!!!НЕТ ЭЛЕМЕНТОВ ДЛЯ ОБРАБОТКИ!!!!!!подозрение изменения шаблона каталога class=catalog-all-children-category] td');exit();}
else {$o->add('Количество элементов для обработки='.sizeof($e));}
$o->add('--------------------------------------------------------------');
//$html->clear();
unset($file);
//exit();
foreach ($e as $el2) { //нашли нужную таблицу
	$cat_first = true;
	$p = 0; //Считаем категории
	$parent=0;
	$parent_name = '';
	$e2 = $el2->find('a');//,'Не найден тег а !!!!! подозрение изменения шаблона каталога'); // вся нуная нам инфа в теге а
	if ($el2->innertext!=='' and !sizeof($e2)) {$o->add('Не найден тег а !!!!! подозрение изменения шаблона каталога');}
	foreach ($e2 as $el3){  // цикл по тегу а 
		//$lenEl3 = pq($el3)->count();
		//echo (pq($el3)->dump());
		$item = new item_VM();
		$title = $el3->text();
		$title_link = $el3->href;
		$arr = explode('/', $title_link);
		$item->product_sku = $arr[2];
		$item->product_name = $title;
		$item->product_url = $title_link;
		$item->product_parent_id = $parent;
		$item->product_isgroup = 1;
		$item->product_vendor = VENDOR;
		$item->product_price = 0;
		$item->product_status = 0;
		if ($cat_first){//первая
			if ($db->isnew($item->product_name, $item->product_sku, $item->product_parent_id)){ // новая ? 
				$parent = 0;
				$item->product_parent_id = $parent;
				//++$p;
				$db->add($item);
				$parent = $db->last_id();
				$o->add('!!!Новая головная категория '.$item->product_name);
				
			}else {// первая не новая
				$parent = $db->get_id($item->product_name, $item->product_sku, $item->product_parent_id);  // установим парент на текущую
				//$o->add('Категория '.$item->product_name);
				
				}
			$cat_first = FALSE;
			$parent_name = $item->product_name;	
		} else {//не первая 
			if ($db->isnew($item->product_name, $item->product_sku, $item->product_parent_id)){ // не первая, новая ? 
				++$p;
				$db->add($item);
			}
		}//не первая 
	}// цикл по тегу а 
		if ($p){
			$o->add('Головная категория '.$parent_name.' Добавлено новых подкатегорий: '.$p);
			$o->add('--------------------------------------------------------------');
		}
}//нашли нужную таблицу

// изменим статус на другой чтоб не качал эти категории 
$db->change_status('игрушка', 3, VENDOR);

$html->clear();
$el2->clear();
$el3->clear();
exit();
//------------------ качаем файлы по категориям------------------------------------

$rows = $db->child_gr();
foreach ($rows as $value){
	if ($value->product_status<>3){//не качаем если статус 3
		$dop = 1; // добавочный к файлу количество страниц 
		$id = $value->product_id;
		$url = $value->product_url;
		//разберем url и заменим количество товара на одну станицу
		$arr = explode('/', $url);
		$arr[4] = 500;
		$url = implode('/', $arr);
		$o->add('Качаем категорию '.$value->product_name.' с адреса '.TARGET.$url);
		$p=1;
		unset($res);
		$url = TARGET.$url;
		$file = CPATH_BASE.DS.$id.'_'.$dop.'.html';
		if (file_exists($file) and filesize($file)){$creation_date = date ("d.m.y", filemtime($file));}//else{$creation_date = 0;}
			$today = date("d.m.y"); 
			if ($creation_date<>$today){
				$res = $pars->get_1251_to_UTF($url, $file, _TRY);
				if ($res <> 'ok'){
					$o->add('!!!!!!!!!Немогу скачать категорию '. $value->product_name);
				}else{
					$o->add('Категория скачана');
					$db->update_status(1, $value->product_id);
						}//else файла нет
			}else{
				$o->add('Файл  существует и он не старее одного дня');
				$db->update_status(1, $value->product_id);	
			} 
	}else{ //не качаем если статус 3
		$o->add('Пропускаем группу '.$value->product_name);	
	}
exit;	
}		
	
	//echo ($out->txt());
	
/*	$document = phpQuery::newDocumentHTML($html);
    $el1 = $document->find('table class="paginator"'); //нашли нужную таблицу*/

//lush();
	
//	} //не качаем если статус 1
//}

?>