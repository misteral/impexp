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
define( '_TRY', 5); //количество попыток закачки

$o = new output('sima-img-kach');
$o->echo = false;
$pars->proxy = '67.205.68.11:8080';


// -------------- начинаем обработку-----------------------

$rows = $db->child_gr();
$o->add('Количество категорий для обработки (закачка картинок) '.sizeof($rows));
$o->add('-------------------------------------------------------');
foreach ($rows as $value){
if ($value->product_status==2){//не обрабатываем если не скачан или обработан
	$complete = TRUE; // если скачаны не все файлы категории
	$id = $value->product_id;
	$c = 0; //счетчик скачанных файлов в котегории
	$rows_pr = $db->get_product($id);
	$o->add('Обработка категории:'.$value->product_name.' ид:'.$value->product_id.'. Количество элементов: '.@mysql_num_rows($rows_pr));

	//if(count($rows_pr==0)){$o->add('Группа вернула нуль элементов (товаров) '.$value->product_name);}
	while ($row = @mysql_fetch_array($rows_pr)) { //идем по товарам
		if ($row['product_status'] <> 4 and $row['product_status']<>3){
		$file = IMAGE_BASE.DS.$row['product_sku'].'.jpg';
		$url = TARGET.'/images/photo/big/'.$row['product_sku'].'.jpg';
		while (!$res or $p >_TRY){
			if (file_exists($file) and filesize($file)){$res = 'ok'; //файл существует
			}else {$res = $pars->get_img_to_file($url,$file);}
			
			
			if(!res=='ok'){sleep(1);}
		}
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
	sleep(10);
	
}

}//проход по категориям

?>