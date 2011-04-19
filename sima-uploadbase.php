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

//************** Занесем головные группы************************
$rows = $db_my->parent_gr();
while ($row =  mysql_fetch_array($rows)){
	if ($row['product_status']<> 3 ){
		$pcat_id =vm_get_category_id($row['product_name'], 0);
		if ($pcat_id){
			$pcat_id=newCategory($row['product_name']);
			newGroups_xref(0,$pcat_id);
		}
	}
}

//******************* обработка товаров***************************
$rows = $db_my->get_from_status('4');

while ($row =  mysql_fetch_array($rows)){
	if  (!$row['product_isgroup']){ //это группа
		$product_id =  vm_get_id($row['product_name'],$row['product_sku']); // есть ли такой товар по имени и артикулу
		if ($product_id){//есть такой товар
			vm_set_publish($product_id); //установим флаг publish
			$product_price = round ($row['product_price']*$row['product_margin']);
			vm_newProduct_price($product_id,$product_price); //установим цену
			
		}//есть такой товар
		else {//нет такого товара 
			$product_full_image =$manufacturer_ID.'_'.'500_'.$row['product_sku'].'.jpg';
			$product_thumb_image = 	'resized'.DS.$manufacturer_ID.'_'.'90_'.$row['product_sku'].'.jpg';
			$product_id=newProducts(0,$row['product_sku'], $row['product_name'], $row['product_desc'], $product_full_image, $product_thumb_image, $row['product_ed']);
			
			vm_newProduct_price($product_id,$product_price); //установим цену	
		}  //нет такого товара 
		$db_my->update_status('5', $row['product_id']);
		
		// **** обработка его групп****
		//$pp_id  = $row['product_parent_id'];
		$pare = $db_my->get_from_id($row['product_parent_id']);
		$product_flag = true;
		$ppare_id = $pare['product_parent_id'];
		$ppare_name = $db_my->get_from_id($ppare_id);
		$ppare_name = $ppare_name['product_name'];
		while ($ppare_id) {//обработка групп пока не найдем головную
			$cat_id = vm_get_category_id_name($pare['product_name'], $ppare_name);
			if (!$cat_id){//нет такой категории cоздадим
				$cat_id = newCategory($pare['product_name']);
			}
			if ($product_flag){ //продукт
				newProducts_xref($cat_id,$product_id);
				$product_flag = false;
			}else{
				
				
				newGroups_xref($row['product_parent_id']);
			}//else //продукт
		}//обработка групп пока не найдем головную
		
		
		
		
	} //это группа

}
?>