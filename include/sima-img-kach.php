<?php

// -------------- начинаем обработку-----------------------

$rows = $db_my->child_gr();
$o->add('Количество категорий для обработки (закачка картинок) '.sizeof($rows));
$o->add('-------------------------------------------------------');
foreach ($rows as $value){
if ($value->product_status==2){//не обрабатываем если не скачан или обработан
	$complete = TRUE; // если скачаны не все файлы категории
	$id = $value->product_id; //id категории которую качаем
	$c = 0; //счетчик скачанных файлов в котегории
	$rows_pr = $db_my->get_product_from_parent($id);
	$kol_el = mysql_num_rows($rows_pr);
	$o->add('Обработка категории:'.$value->product_name.' ид:'.$value->product_id.'. Количество элементов: '.$kol_el);
	if(!$kol_el){
		$o->add('Группа вернула нуль элементов (товаров) '.$value->product_name);
		//$db_my->del($id);
		$complete = false;
		
		continue;}
	while ($row = mysql_fetch_array($rows_pr)) { //идем по товарам
		if ($row['product_status'] <> 4 and $row['product_status']<>3){
		$file = IMAGE_BASE.DS.$row['product_sku'].'.jpg';
		$url = TARGET.'/images/photo/big/'.$row['product_sku'].'.jpg';
		if (file_exists($file) and filesize($file)) {
			$o->add('Файл существует '.$row['product_sku']);
			$db_my->update_status(4, $row['product_id']);
			continue;
		} //файл существует
		//проверим может уже в vm лежит такой 
		$file_500 = VM_IMAGE.DS.VENDOR.'_500_'.$row['product_sku'].'.jpg';
		if (file_exists($file_500) and filesize($file_500)) {
			$o->add('Файл существует в VM '.$row['product_sku']);
			$db_my->update_status(4, $row['product_id']);
			continue;
		}
		if ($wget){
		file_put_contents(WGET_BASE.DS.WGET_FILE, $url."\r\n", FILE_TEXT|FILE_APPEND);
//		$complete=false;
		continue;
		}
		$res = $pars->get_url_to_file($url, $file, _TRY);
		
		if($res<>'ok'){
			$o->add('Не могу закачать картинку товара '.$row['product_name'].' арт. :'.$row['product_sku']);
			$complete=false;
		
		}else { //файл скачался
			$db_my->update_status(4, $row['product_id']);
			$c = $c+1;
		}
		unset($res);
		//exit;
		}
	} //идем по товарам
	$o->add('Скачано файлов :'.$c);
	$o->add('----------------------------------------------------');
	if($complete){$db_my->update_status(4, $id);}
	//sleep(10);
	
}

}//проход по категориям

?>