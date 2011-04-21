<?php

$count = 0;
$rows = $db_my->child_gr();
$o->add('Количество категорий для обработки '.sizeof($rows));
foreach ($rows as $value){
if ($value->product_status==1){//не обрабатываем если не скачан или обработан
	$o->timer_start(); // стартанем таймер
	$dop = 1;
	$id = $value->product_id;
	$sku = $value->product_sku;
	$o->add('Начинаем обработку категории '.$value->product_name.'. Файл='.$id.'_'.$dop.'.html');
	$file = CPATH_BASE.DS.$sku.'_'.$dop.'.html';
	if (!file_exists($file) or !filesize($file)){$o->add('Отсутствует файл категории');continue;}
	$document = file_get_html($file);
	unset($file);
	$e=$document->find('table[class=item-list] tr[id=item-list-tr]'); //нашли нужную таблицу
	if ($document->innertext!=='' and !sizeof($e)) {$o->add('!!!!!!!!!!!!НЕТ ЭЛЕМЕНТОВ ДЛЯ ОБРАБОТКИ!!!!!!!!!!!!!!!!Подозрение на изменение шаблона!');continue;}
		else {$o->add('Количество элементов для обработки='.sizeof($e));}
		foreach ($e as $el1) { 
			$item = new item_VM();
			//$lid=$db_my->last_id()+1;
			
			$look = $o->chk($el1->getElementByTagName('td.item-list-name-photo a'), '!!!!!! Пуст родительский элемент td.item-list-name-photo');
			if (!$skip){$item->product_name=mysql_escape_string($o->ch($look->text(),'Имя продукта пустое!!!!!!!!!!'));}		
			if (!$skip){$look=$o->chk($el1->getElementByTagName('td.item-list-sklad'),'!!!!!!Пустой родителький элемент td.item-list-sklad');}
			if (!$skip){$item->product_ost= $o->ch($look->text(),'Остаток пуст у'.$item->product_name);}
			
			
			if (!$skip){$look = $o->chk($el1->getElementByTagName('td.item-list-name-photo img'),'!!!!! Пустой родителький элемент td.item-list-name-photo img');}
			if (!$skip){$item->product_sku=$o->ch($look->id,'SKU пуст у '.$item->product_name);}
			if (!$skip){$look= $o->chk($el1->getElementByTagName('td.item-list-name-photo a'),'!!!!!!Пустой родителький элемент td.item-list-name-photo a');}
			if (!$skip){$item->product_url = $o->ch($look->href,'Ссылка пустая у '.$item->product_name);}
			
			if (!$skip){$look= $o->chk($el1->getElementByTagName('div.itemlistunit'),'нет единицы измерения вставим свою');
				//на некоторых единицы не запонены заполним шт и продолжим
				if (!$skip){$item->product_ed = $o->ch($look->text(),'Ед пустая у '.$item->product_name);}
				if ($skip){$skip = false; $item->product_ed = 'шт';}
			}
			
			if (!$skip){$look=$o->chk($el1->getElementByTagName('div.item-list-price-div'),'!!!!!!Пустой родителький элемент div.item-list-price-div');}
			if (!$skip){$item->product_price = $o->ch($look->text(),'Цена пустая у '.$item->product_name);}
			if (!$skip){$look=$o->chk($el1->getElementByTagName('td.item-list-minimal'),'!!!!!!Пустой родителький элемент td.item-list-minimal');}
			if (!$skip){$item->product_min = $o->ch($look->text(),'Минимум пуст у '.$item->product_name);}
			if (!$skip){$look=$o->chk($el1->getElementByTagName('div.item-list-size'),'Пустой родителький элемент div.item-list-size');}
			if (!$skip){$desk1=$o->ch($look->text(),'');}
			if (!$skip){$look=$o->chk($el1->getElementByTagName('div.item-list-stuff'),'Пустой родителький элемент div.item-list-stuff');}
			if (!$skip){$desk2=$o->ch($look->text(),'');}
		
			if (!$skip){//не берем в расчет товар которого мало 		
			    $item->product_ost=trim (str_replace('новинка!', '', $item->product_ost));
			    
			    if ($item->product_price<1000 and $item->product_ost =='от 0 до 10' 
				     or $item->product_ost =='от 10 до 50' 
				     or $item->product_ost =='от 50 до 100'
			     )
			     {$skip=true;}
			}
			
			if(!$skip){
				// обработаем наименование уберем артикул 
				$item->product_name = trim(str_replace($item->product_sku, '', $item->product_name));
				//запятую если есть desk2 и desc1
				if ($desk2 and $desk1){$desk2 = $desk2.',';}
	
				$item->product_desc =trim($desk2).' '.trim($desk1);
				$item->product_ed = trim($item->product_ed);
				$item->product_min =trim($item->product_min);
				$item->product_ost = trim($item->product_ost);
				$item->product_isgroup = 0;
				$item->product_parent_id = $id;
				$item->product_vendor = VENDOR;
				$item->product_status = 2; //закачан
				if ($th_id = $db_my->get_id($item->product_name, $item->product_sku, $item->product_parent_id)){//найден такой же
					$db_my->update($item,$th_id);
				}else {//новый элемент
					$db_my->add($item);
					$count = $count+1; // общий подсчет
				}
			}else {
				$db_my->count_inc('skip');
				$skip = false;
				}
			
	
			//$db_my->update_status (2, $db_my->last_id());
		//exit();
	}//цикл по строкам таблицы
	$db_my->update_status(2, $id);
// освободим переменные	
		 
	
	if ($document instanceof simple_html_dom){$document->clear();}; 
	unset($document);
	//if ($look instanceof simple_html_dom_node){$look>clear();};
	unset($look);
	//if ($el1 instanceof simple_html_dom_node){$el1>clear();}; 
	unset($el1);
	unset($e);
	
	$o->add('Категория ' .$value->product_name.' id =  '.$value->product_id.' add:'.$db_my->count_get('add').', skip:'.$db_my->count_get('skip').', update:'.$db_my->count_get('update').' Выполенено за:'.$o->timer_get() );
	$o->add('------------------------------------------------------------------------------------------------------------------------');
	$db_my->count_reset('add');$db_my->count_reset('skip'); $db_my->count_reset('update'); //обнулим счетчики на категории
	
}	//не обрабатываем если не скачан
} //идем по целевым группам

$o->add('------------------------------------------------------------------------------------------------------------------------');
$o->add('------------------------------------------------------------------------------------------------------------------------');
$o->add('Количество добавленных элементов '.$count);


// ********************Обработка наценки на товар ****************************



?>