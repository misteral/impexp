<?php

ini_set ( 'max_execution_time', 0);// убираем ограничение по времени;
ini_set ( 'max_input_time', 0); //
set_time_limit (0);

require ('include/sund.class.php');

$db  = new ex_Mysql();
$pars = new parse();


define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'IMAGE_BASE', dirname ( __FILE__ ) . DS.'images' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ( 'VENDOR','1' ); //вендор сима
define( '_TRY', 3); //количество попыток закачки
define( 'WGET', 'wget.sima-images' );
$wget = TRUE;
if (file_exists(WGET)){unlink(WGET);}

$o = new output('sima-img-kach');
$o->echo = false;
//$pars->proxy = '67.205.68.11:8080';
$pars->proxy = '10.44.33.88:8118';


// -------------- начинаем обработку-----------------------

$rows = $db->child_gr();
$o->add('Количество категорий для обработки (закачка картинок) '.sizeof($rows));
$o->add('-------------------------------------------------------');
foreach ($rows as $value){
if ($value->product_status==2){//не обрабатываем если не скачан или обработан
	$complete = TRUE; // если скачаны не все файлы категории
	$id = $value->product_id; //id категории которую качаем
	$c = 0; //счетчик скачанных файлов в котегории
	$rows_pr = $db->get_product($id);
	$kol_el = @mysql_num_rows($rows_pr);
	$o->add('Обработка категории:'.$value->product_name.' ид:'.$value->product_id.'. Количество элементов: '.$kol_el);
	if(!$kol_el){
		$o->add('Группа вернула нуль элементов (товаров) '.$value->product_name);
		$complete = false;
		continue;}
	while ($row = @mysql_fetch_array($rows_pr)) { //идем по товарам
		if ($row['product_status'] <> 4 and $row['product_status']<>3){
		$file = IMAGE_BASE.DS.$row['product_sku'].'.jpg';
		$url = TARGET.'/images/photo/big/'.$row['product_sku'].'.jpg';
		if (file_exists($file) and filesize($file)) {
			$o->add('Файл существует '.$row['product_sku']);
			$db->update_status(4, $row['product_id']);
			continue;
		} //файл существует
		if ($wget){
		file_put_contents(WGET, $url."\r\n", FILE_TEXT|FILE_APPEND);
//		$complete=false;
		continue;
		}
		$res = $pars->get_url_to_file($url, $file, _TRY);
		
		if($res<>'ok'){
			$o->add('Не могу закачать картинку товара '.$row['product_name'].' арт. :'.$row['product_sku']);
			$complete=false;
		
		}else { //файл скачался
			$db->update_status(4, $row['product_id']);
			$c = $c+1;
		}
		unset($res);
		//exit;
		}
	} //идем по товарам
	$o->add('Скачано файлов :'.$c);
	$o->add('----------------------------------------------------');
	if($complete){$db->update_status(4, $id);}
	//sleep(10);
	
}

}//проход по категориям

?>