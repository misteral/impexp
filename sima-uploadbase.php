<?php
require ('include/sund.class.php');
require ('include/connectVM.php');

$db_my  = new ex_Mysql();

//Проверим промзводителя и если нет создадим
$manufacturer = 'sima-land';
//сменим image full path al
//$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $name . "'" );

$manufacturer_ID = vendor_create($manufacturer); //создаем или берем cуществующий ид производителя

vm_product_notpublish_if_not_updated(); //опасная функция !!! not publish если товара нет такого в обновке с симы

$rows = $db_my->get_from_status('4');

while ($row =  mysql_fetch_array($rows)){
	if  (!$row['product_isgroup']){ //это группа
		$res =  vm_get_id($row['product_name'],$row['product_sku']); // есть ли такой товар по имени и артикулу
		if ($res){//есть такой товар
			vm_set_publish($res); //установим флаг publish
			$product_price = round ($row['product_price']*$row['product_margin']);
			vm_newProduct_price($res,$product_price); //установим цену
			
		}//есть такой товар
		else {//нет такого товара 
			$product_full_image =$manufacturer_ID.'_'.'500_'.$row['product_sku'].'.jpg';
			$product_thumb_image = 	'resized'.DS.$manufacturer_ID.'_'.'90_'.$row['product_sku'].'.jpg';
			newProducts(0,$row['product_sku'], $row['product_name'], $row['product_desc'], $product_full_image, $product_thumb_image, $row['product_ed']);
			
			vm_newProduct_price(vm_get_id($row['product_name'],$row['product_sku']),$product_price); //установим цену	
		}  //нет такого товара 
		$db_my->update_status('5', $row['product_id']);
		
	} //это группа
	else 
	{
	if (!$row['product_parent_id']){ //возмем все головные группы
		$cat_id = vm_get_category_id($row['product_name'],$row['product_parent_id']);
		if ($cat_id){ // есть такая категория
			//установить флаг паблиш в алгоритме а надо ли ???? 
		}else {
			//выберем все дочерние 
			$res = $db_my->get_product_from_parent($row['product_id']);
			$kol = mysql_num_rows($res);
			
			while ($cat_row = mysql_fetch_array($res)){
				
			}
		}
		
	}//возмем все головные группы
	}
}
?>