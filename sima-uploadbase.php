<?php
require ('include/sund.class.php');
$db_my  = new ex_Mysql();
include '/include/connectVM.php';

//Проверим промзводителя и если нет создадим
$manufacturer = 'sima-land';
//сменим image full path al
//$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $name . "'" );

$res = vendor_create($manufacturer); //создаем или берем cуществующий ид производителя

product_notpublish_if_not_updated(); //

$rows = mysql_fetch_array($db_my->get_from_status('4'));

foreach ($rows as $row){
	$res =  vm_get_id($row['product_name'],$row['product_sku']);
	if ($res){//есть такой товар
		vm_set_publish($row['product_id']); //установим флаг publish
		
		
	}//есть такой товар
	else {//нет такого товара 
		
	}  //нет такого товара 
	
}
?>