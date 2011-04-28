<?php
//require ('include/connectVM.php');

//$db_my  = new ex_Mysql();

//сменим image full path al
//$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $name . "'" );

$manufacturer_ID = vendor_create($manufacturer); //создаем или берем cуществующий ид производителя

vm_unpublish_product_mnf(); // снимаем с публикации все данного mnf

//vm_product_notpublish_if_not_updated(); //опасная функция !!! not publish если товара нет такого в обновке с симы

//************** Занесем головные группы************************
$rows = $db_my->parent_gr();
while ($row =  mysql_fetch_array($rows)){
	if ($row['product_status']<> 3 ){
		$pcat_id =vm_get_category_id($row['product_name'], 0);
		if (!$pcat_id){ //создадим новую
			$pcat_id=newCategory($row['product_name']);
			newGroups_xref(0,$pcat_id);
		}//создадим новую
		
		$db_my->update_status('5', $pcat_id);
	}
}
unset($rows);
unset($row);
unset($pcat_id);
//************** Занесем дочерние группы************************

$rows = $db_my->child_gr2();
while ($row =  mysql_fetch_array($rows)){
	if ($row['product_status']<> 3 ){
		$parent_name = $db_my->get_from_id($row['product_parent_id']);
		$parent_id = vm_get_category_id($parent_name['product_name'], 0);
		$pcat_id =vm_get_category_id_name($row['product_name'], $parent_name['product_name']);
		if (!$pcat_id){//создадим новую
			$pcat_id=newCategory($row['product_name'],'');
			newGroups_xref($parent_id,$pcat_id);
		}//создадим новую
		$db_my->update_status('5', $pcat_id);
	}
}


//******************* обработка товаров***************************
$rows = $db_my->get_from_status('4');  // берем со статусом 4

while ($row =  mysql_fetch_array($rows)){
	if  (!$row['product_isgroup']){ //это группа
		$product_id =  vm_get_id($row['product_name'],$row['product_sku']); // есть ли такой товар по имени и артикулу
		
		$product_full_image =$manufacturer_ID.'_'.'500_'.$row['product_sku'].'.jpg';
		$product_thumb_image =mysql_escape_string('resized/'.$manufacturer_ID.'_'.'90_'.$row['product_sku'].'.jpg');
		
		if ($product_id){//есть такой товар
			vm_set_publish($product_id); //установим флаг publish
			$product_price = round ($row['product_price']*$row['product_margin']);
			vm_update_image($product_id, $product_full_image, $product_thumb_image); // обновим картинку
			vm_newProduct_price($product_id,$product_price); //установим цену
			
		}//есть такой товар
		else {//нет такого товара 

			$product_id=newProducts(0,$row['product_sku'], $row['product_name'], $row['product_desc'], $product_full_image, $product_thumb_image, $row['product_ed']);
			$product_price = round ($row['product_price']*$row['product_margin']);
			vm_newProduct_price($product_id,$product_price); //установим цену
			
		}  //нет такого товара 
		
		
		
		$parent_name = $db_my->get_from_id($row['product_parent_id']);
		$ppname = $db_my->get_from_id($parent_name['product_parent_id']);
		if(!$ppname){
			$category_id = vm_get_category_id($parent_name['product_name'], 0);
		}else {
			$category_id = vm_get_category_id_name($parent_name['product_name'], $ppname['product_name']);
		}
		newProducts_xref($category_id,$product_id);
		vm_productmf_xref($product_id);
		$db_my->update_status('5', $row['product_id']);
		
		}//это группа
}




// добавим картинку к группам из товара 








?>