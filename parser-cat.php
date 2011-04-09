<?php
// описание 

ini_set ( 'max_execution_time', 0);// убираем ограничение по времени;
ini_set ( 'max_input_time', 0); //
set_time_limit (0);

require ('include/sund.class.php');
include('include/simple_html_dom.php');

$db  = new ex_Mysql();
//$db->clear(1); //почистим базу
$pars = new parse();
$out = new output('sima-parser-cat');

//$out->echo = true;

define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'CPATH_BASE', dirname ( __FILE__ ) . DS.'dw-sima' );
define ( 'TARGET', 'http://sima-land.ru' );
define ('CATALOG','/catalog.html');
define ( 'VENDOR','1' ); //вендор сима


$count = 0;

$rows = $db->child_gr();
$out->add('Количество категорий для обработки '.sizeof($rows));
foreach ($rows as $value){
if (!$value->product_status<>1){//не обрабатываем если не скачан или обработан
	$dop = 1;
	$id = $value->product_id;
	$document = file_get_html(CPATH_BASE.DS.$id.'_'.$dop.'.html');
	$e=$document->find('table[class=item-list] tr[id=item-list-tr]'); //нашли нужную таблицу
	$out->add('Начинаем обработку категории '.$value->product_name.'. Файл='.$id.'_'.$dop.'.html');
	if ($document->innertext!=='' and sizeof($e)) {$out->add('Количество элементов для обработки='.sizeof($e));}
		else {$out->add('!!!!!!!!!!!!!!!!!!!!!!!!!!!!НЕТ ЭЛЕМЕНТОВ ДЛЯ ОБРАБОТКИ!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');}
		foreach ($e as $el1) { 
		$item = new item_VM();
		$lid=$db->last_id()+1;

/*	try {
		throw new TestException();
    
	} catch (my_error $ee) {
	echo ('Ошибка обработки'.$ee->getMessage());
	}*/
	//Основной поиск элементов

		
		$look = $out->chk($el1->getElementByTagName('td.item-list-name-photo a'), '!!!!!! Пуст родительский элемент td.item-list-name-photo');
		if (!$skip){$item->product_name=@mysql_escape_string($out->ch($look->text(),'Имя продукта пустое!!!!!!!!!!'));}		
		if (!$skip){$look=$out->chk($el1->getElementByTagName('td.item-list-sklad'),'!!!!!!Пустой родителький элемент td.item-list-sklad');}
		if (!$skip){$item->product_ost= $out->ch($look->text(),'Остаток пуст у'.$item->product_name);}
		
		if (!$skip){//не берем в расчет товар которого мало 		
	    $item->product_ost=trim (str_replace('новинка!', '', $item->product_ost));
	     if ($item->product_ost =='от 0 до 10' 
	     or $item->product_ost =='от 10 до 50' 
	     or $item->product_ost =='от 50 до 100'
	     )
	     {$skip=true;}
		}
		
		if (!$skip){$look = $out->chk($el1->getElementByTagName('td.item-list-name-photo img'),'!!!!! Пустой родителький элемент td.item-list-name-photo img');}
		if (!$skip){$item->product_sku=$out->ch($look->id,'SKU пуст у '.$item->product_name);}
		if (!$skip){$look= $out->chk($el1->getElementByTagName('td.item-list-name-photo a'),'!!!!!!Пустой родителький элемент td.item-list-name-photo a');}
		if (!$skip){$item->product_url = $out->ch($look->href,'Ссылка пустая у '.$item->product_name);}
		
		if (!$skip){$look= $out->chk($el1->getElementByTagName('div.itemlistunit'),'');
		//на некоторых единицы не запонены заполним шт и продолжим
		if (!$skip){$item->product_ed = $out->ch($look->text(),'Ед пустая у '.$item->product_name);}
		if ($skip){$skip = false; $item->product_ed = 'шт';}
		}
		
		if (!$skip){$look=$out->chk($el1->getElementByTagName('div.item-list-price-div'),'!!!!!!Пустой родителький элемент div.item-list-price-div');}
		if (!$skip){$item->product_price = $out->ch($look->text(),'Цена пустая у '.$item->product_name);}
		if (!$skip){$look=$out->chk($el1->getElementByTagName('td.item-list-minimal'),'!!!!!!Пустой родителький элемент td.item-list-minimal');}
		if (!$skip){$item->product_min = $out->ch($look->text(),'Минимум пуст у '.$item->product_name);}
		if (!$skip){$look=$out->chk($el1->getElementByTagName('div.item-list-size'),'Пустой родителький элемент div.item-list-size');}
		if (!$skip){$desk1=$out->ch($look->text(),'');}
		if (!$skip){$look=$out->chk($el1->getElementByTagName('div.item-list-stuff'),'Пустой родителький элемент div.item-list-stuff');}
		if (!$skip){$desk2=$out->ch($look->text(),'');}
	
		
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
		$db->add($item);
		$count = $count+1;
		}else {
			$db->count_inc('skip');
			$skip = false;
			}
		

		//$db->update_status (2, $db->last_id());
	//exit();
	}//цикл по строкам таблицы
	$db->update_status(2, $id);
	$out->add('Категория ' .$value->product_name.' id =  '.$value->product_id.' закончена, обработано '.$db->count_get('add').', пропущено '.$db->count_get('skip'));
	$out->add('------------------------------------------------------------------------------------------------------------------------');
	$db->count_reset('add');$db->count_reset('skip'); //обнулим счетчики на категории
	$document->clear(); 
	unset($document);
}	//не обрабатываем если не скачан
} //идем по целевым группам
$out->add('Количество добавленных элементов '.$count);


?>