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

$o = new output('sima-img-kach');
$o->echo = false;
$pars->proxy = '67.205.68.11:8080';


// -------------- начинаем обработку-----------------------

$rows = $db->child_gr();
$o->add('Количество категорий для обработки (закачка картинок) '.sizeof($rows));
foreach ($rows as $value){
if (!$value->product_status==2){//не обрабатываем если не скачан или обработан
	$o->add('Обработка категории:'.$value->product_name.' ид:'.$value->product_id);
	$complete = TRUE; // если скачаны не все файлы категории
	$id = $value->product_id;
	$rows_pr = $db->get_product($id);
	
	//if(count($rows_pr==0)){$o->add('Группа вернула нуль элементов (товаров) '.$value->product_name);}
	while ($row = @mysql_fetch_array($rows_pr)) { //идем по товарам
		if ($row['product_status'] <> 4){
		$file = IMAGE_BASE.DS.$row['product_sku'].'.jpg';
		$url = TARGET.'/images/photo/big/'.$row['product_sku'].'.jpg';
		while (!$res or $p >_TRY){
			$res = $pars->get_img_to_file($url,$file);
			if(!res=='ok'){sleep(5);}
		}
		if($res<>'ok'){
			$o->add('Не могу закачать картинку товара '.$row['product_name'].' арт. :'.$row['product_sku']);
			$complete=false;
		
		}else {$db->update_status(4, $row['product_id']);}
		unset($res);
		exit;
		}
	} //идем по товарам
	if($complete){$db->update_status(4, $id);}
	sleep(10);
	
}

}//проход по категориям

?>