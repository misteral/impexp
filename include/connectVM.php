<?
//define ( 'DS', DIRECTORY_SEPARATOR );
# директория в которой расположен движок /joomla/ ,если в корне сайта то пусто
//define ( 'JPATH_BASE', dirname(dirname(dirname ( __FILE__ ))) . '' );
# директория в которую записываются картинки и файл обмена
//define ( 'JPATH_BASE_PICTURE', JPATH_BASE .DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');
# директория в которую записываются маленькие картинки
//define ( 'JPATH_BASE_PICTURE_SMALL', JPATH_BASE_PICTURE .DS.'resized' );

require_once (JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once (JPATH_BASE . DS . 'includes' . DS . 'framework.php');
require (JPATH_BASE .DS. 'libraries' .DS. 'joomla' .DS. 'factory.php');
// initialize the application
$mainframe = & JFactory::getApplication ( 'site' );
$mainframe->initialise ();
$db = & JFactory::getDBO ();
//$session = $session = & JFactory::getSession ();
jimport ( 'joomla.error.log' );
jimport ( 'joomla.user.helper' );
$log = &JLog::getInstance ( 'connectVM.log' );
//$session->set('fl_commerceml', $array);
//$session->get('fl_commerceml');
//  категории товара

//$category = array ();

//$tax_rate = array ();
# товар
//$products = array ();
# типы цен
//$price = array ();
# цены на товар
//$price_tovar = array ();

# характеристики на товар
//$char_type_name = array ();
# производитель
$manufacturer_ID = '';
//$manufacturer = array ();
#ID продавца, имя продавца, мое ID
$vendor_ID = 1;
#ID группы производителей ищется по имени продавца берем из CML
$mf_category_id = 0;

# Загрузка файла из 1С методом POST
# Все файлы попадают в директорию JPATH_BASE_PICTURE
# Загрузка файла POST'ом
function loadfile() {

	global $log;
	#global $session;

	#$image_data = "";
	//Считываем файл в строку


	$filename_to_save = JPATH_BASE_PICTURE . DS . $_REQUEST ['filename'];

	$log->addEntry ( array ('comment' => 'loadfile & create dir name ' . $filename_to_save ) );

	$image_data = file_get_contents ( "php://input" );

	if (isset ( $image_data )) {
		//if (file_exists($filename_to_save)) {unlink($filename_to_save);}


		$png_file = fopen ( $filename_to_save, "ab" ) or die ( "File not opened" );
		if ($png_file) {
			set_file_buffer ( $png_file, 20 );
			fwrite ( $png_file, $image_data );
			fclose ( $png_file );

			$log->addEntry ( array ('loadfile' => 'Получен файл  ' . $filename_to_save ) );

			return "success";
		}
	}
	$log->addEntry ( array ('loadfile' => 'Ошибка получения файла ' ) );
	return "error POST";
}
# Распаковка архивов
function unzip($file, $folder = '') {

	global $log;
	$log->addEntry ( array ('comment' => 'unzip file 3 ' . $folder . $file ) );
	$zip = zip_open ( $folder . $file );
	$files = 0;
	#$folders = 0;

	if ($zip) {
		while ( $zip_entry = zip_read ( $zip ) ) {

			$name = $folder . zip_entry_name ( $zip_entry );

			$path_parts = pathinfo ( $name );
			# Создем отсутствующие директории
			$log->addEntry ( array ('comment' => 'loadfile create dir name ' . $path_parts ['dirname'] ) );
			if (! is_dir ( $path_parts ['dirname'] )) {
				mkdir ( $path_parts ['dirname'], 0755, true );
			}

			if (zip_entry_open ( $zip, $zip_entry, "r" )) {
				$buf = zip_entry_read ( $zip_entry, zip_entry_filesize ( $zip_entry ) );

				$file = fopen ( $name, "wb" );
				if ($file) {
					fwrite ( $file, $buf );
					fclose ( $file );
					$files ++;
				} else {
					$log->addEntry ( array ('comment' => 'error unzipopen file ' . $name ) );
				}
				zip_entry_close ( $zip_entry );
			}
		}
		zip_close ( $zip );
	} else {
		$log->addEntry ( array ('comment' => 'error unzip file ' . $name ) );
	}

}
# Загрузим производителей товара в массив
function LoadmanufacturerName() {
	global $db;
	$manufacturer = array ();
	$sql = "SELECT manufacturer_id as id, mf_name as name FROM #__vm_manufacturer";
	$db->setQuery ( $sql );
	$rows = $db->loadObjectList ();
	foreach ( $rows as $row ) {
		$manufacturer ["$row->name"] = ( int ) $row->id;
	}
	return $manufacturer;
}

# Загрузим производителей товара в массив
function LoadShopperName($vendor_id) {
	/*	global $db;
	$Shopper = array ();
	$sql = "SELECT shopper_group_id as id, mf_name as name FROM #__vm_shopper_group";
	$db->setQuery ( $sql );
	$rows = $db->loadObjectList ();
	foreach ( $rows as $row ) {
	$manufacturer ["$row->name"] = ( int ) $row->id;
	}
	return $manufacturer;
	*/
}

# Загрузим продавцов товара в массив
function LoadVendorName($vendor_id) {
	/*	global $db;
	$vendor = array ();
	$sql = "vendor_id as id, vendor_name as name FROM #__vm_vendor";
	$db->setQuery ( $sql );
	$rows = $db->loadObjectList ();
	foreach ( $rows as $row ) {
	$vendor["$row->name"] = ( int ) $row->id;
	}
	return $vendor;
	*/
}


# Очистка таблиц в базе
function ClearBase($change) {
	global $db;

	if ($change == 1) {
		# Чистим таблицу категорий
		$query = 'DELETE FROM #__vm_category';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу дерева категорий
		$query = 'DELETE FROM #__vm_category_xref';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу товаров
		$query = 'DELETE FROM #__vm_product';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу произодителей
		$query = 'DELETE FROM #__vm_manufacturer';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу групп произодителей
		$query = 'DELETE FROM #__vm_manufacturer_category';
		$db->setQuery ( $query );
		$db->query ();





		# Чистим таблицу привязки товаров к категориям
		$query = 'DELETE FROM #__vm_product_category_xref';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу привязки товаров к производителям
		$query = 'DELETE FROM #__vm_product_mf_xref';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу продавцов
		#$query = 'DELETE FROM #__vm_vendor';
		#$db->setQuery ( $query );
		#$db->query ();

		# Чистим таблицу привязки продавцов к категориям
		#$query = 'DELETE FROM #__vm_vendor_category';
		#$db->setQuery ( $query );
		#$db->query ();

		# Чистим таблицу атрибутов (характеристики товара)
		$query = 'DELETE FROM #__vm_product_attribute';
		$db->setQuery ( $query );
		$db->query ();

		# Чистим таблицу атрибутов (характеристики товара)
		$query = 'DELETE FROM #__vm_product_attribute_sku';
		$db->setQuery ( $query );
		$db->query ();
	}
}
# Очистка таблиц в базе
function ClearBase2($change) {
	global $db;
	if ($change == 1) {
		$query = 'DELETE FROM #__vm_product_price';
		$db->setQuery ( $query );
		$db->query ();
		$query = 'DELETE FROM #__vm_product_product_type_xref';
		$db->setQuery ( $query );
		$db->query ();
		$query = 'DELETE FROM #__vm_tax_rate';
		$db->setQuery ( $query );
	}
}
# Пишем свойста характеристик на товар
function Write_product_attribute_sku()
{
	/*global $char_type_name;

	global $db;
	global $log;
	$log->addEntry ( array ('comment' => 'Write_product_attribute_sku product price add char '.$product_id.':'.$char_type));
	foreach ($char_type_name as $product_id => $char_type) {
	$i = 1;

	foreach ($char_type as $index => $char_type_attr)
	{
	$ins = new stdClass ();
	$ins->product_id 		= $char_type->product_id;
	$ins->attribute_name 	= $attr->attribute_name;
	$ins->attribute_list 	= $i;
	$i++;
	if (! $db->insertObject ( '#__vm_product_attribute_sku', $ins)) {
	return false;
	}
	}
	}*/
}



 
/**
 *Ищем продавца если не находим то записываем нового
 *Ищем группу производителей привязанных к продавцу если не находим то записываем новую группу
 *$vendor txt
 */
function vendor_create($vendor)
{
	global $db;
	global $log;

	global $mf_category_id;
	$vendor_name	=	$vendor;
	$vendor_store_name	=	$vendor;

	$db->setQuery ( "SELECT mf_category_id FROM #__vm_manufacturer_category where mf_category_name = '" . $vendor_name . "'" );
	$rows_sub_Count = $db->loadResult ();
	if (isset ( $rows_sub_Count )) {
		return $mf_category_id	= (int)$rows_sub_Count;
	} else // Если группа поизводителей по имени не найдена в базе то мы ее создаем
	{
		return $mf_category_id	=	manufacturer_category_create($vendor_name,$vendor_store_name);
	}

/*	################################################################################
	$db->setQuery ( "SELECT vendor_id FROM #__vm_vendor where vendor_name = '" . $vendor_name . "'" );
	$rows_sub_Count = $db->loadResult ();

	// Если группа покупателей по имени есть в базе то мы ее не меняем , а берем ее id
	if (isset ( $rows_sub_Count )) {
		return (int)$rows_sub_Count;
	} else // Если группа покупателей по имени не найдена базе то мы ее создаем
	{
		$ins = new stdClass ();
		$ins->vendor_id 		= NULL;
		$ins->vendor_name 		= $vendor_name;
		$ins->vendor_store_name = $vendor_store_name;
		#$ins->vendor_currency = 'RUB';
		$ins->vendor_country	= "RU";
		if (! $db->insertObject ( '#__vm_vendor', $ins, 'vendor_id' )) {
			return false;
		}
		return $ins->vendor_id;
	}*/
}

/**
 * Создание новой категории
 * @param $category_name
 * @param ;category_description
 * @return boolean
 */
function newCategory($category_name, $category_description = '') {

	global $vendor_ID;

	global $db;


	$ins = new stdClass ();
	$ins->category_id = NULL;
	$ins->category_name = $category_name;
	$ins->category_description = $category_description;
	$ins->vendor_id = $vendor_ID;
	$ins->category_publish = 'Y';
	$ins->category_browsepage = 'browse_3';
	$ins->cdate = time ();
	$ins->mdate = time ();
	$ins->category_flypage = 'flypage.tpl';
	$ins->category_thumb_image = '';
	$ins->category_full_image = '';
	$ins->list_order = 1;

	if (! $db->insertObject ( '#__vm_category', $ins, 'category_id' )) {
		return false;
	}

	return $ins->category_id;
}



# Создание новой ссылки товара на группу
function newProducts_xref($category_id, $product_id) {

	global $db;

	$ins = new stdClass ();
	$ins->category_id = $category_id;
	$ins->product_id = $product_id;
	$ins->product_list = 1;

	if (! $db->insertObject ( '#__vm_product_category_xref', $ins )) {
		return false;
	}

}



/**
 * Добавляет соответствие продукт производитель
 * @param  $product_id
 * @param  $manufacturer_id global
 * @return boolean
 */
function vm_productmf_xref( $product_id) {
	global $db;
	global $manufacturer_ID;
		$ins = new stdClass ();
		$ins->product_id = $product_id;
		$ins->manufacturer_id = $manufacturer_ID;
	
		if (! $db->insertObject ( '#__vm_product_mf_xref', $ins )) {
			return false;
		}	
} //vm_productmf_xref


/**
 * Добавляет соответствте категория категория
 * @param  $parent_id
 * @param  $category_id
 * @return boolean
 */
function newGroups_xref( $parent_id, $category_id ) {
	global $db;

		$ins = new stdClass ();
		$ins->category_parent_id = $parent_id;
		$ins->category_child_id = $category_id;
		$ins->category_list = null;

		if (! $db->insertObject ( '#__vm_category_xref', $ins )) {
			return false;
		}	
} //newGroups_xref

/**
 *Создание нового товара
 *  $product_parent_id,$product_SKU, $product_name, $product_desc, $product_full_image, $product_thumb_image, $product_ed
 */
function newProducts($product_parent_id,$product_SKU, $product_name, $product_desc, $product_full_image, $product_thumb_image, $product_ed) {
	global $db;

	//global $vendor_1C_ID;

	$ins = new stdClass ();
	$ins->product_id = NULL;
	$ins->vendor_id 	=	1;
	$ins->product_parent_id	=	$product_parent_id;
	$ins->product_SKU = $product_SKU;
	$ins->product_name = $product_name;
	$ins->product_desc = $product_desc;
	$ins->product_s_desc = $product_desc;
	$ins->product_publish = 'Y';
	$ins->product_available_date = time ();
	$ins->product_in_stock		 = 99;
	$ins->cdate = time ();
	$ins->mdate = time ();

	$ins->product_full_image = $product_full_image;
	$ins->product_thumb_image = $product_thumb_image;

	if (! isset ( $product_ed )) {
		$ins->product_unit = 'piece';
	} else {
		$ins->product_unit = $product_ed;
	}
	$ins->child_options = 'N,N,N,N,N,N,20%,10%,';
	$ins->quantity_options = 'none,0,0,1';

	if (! $db->insertObject ( '#__vm_product', $ins, 'product_id' )) {
		return false;
	}

	return $ins->product_id;
}
# Парсинг типов цен

# Парсинг типов цен на характеристики


# Создание новой группы для производителя
function manufacturer_category_create($name,$desc='') {
	global $db;

	$ins = new stdClass ();
	$ins->mf_category_id 			= NULL;
	$ins->mf_category_name 			= $name;
	$ins->mf_category_desc 			= $desc;

	if (! $db->insertObject ( '#__vm_manufacturer_category', $ins, 'mf_category_id' )) {
		return false;
	}
	return $ins->mf_category_id;
}


# Создание нового производителя с привязкой к группе продавца
function manufacturer_create($name) {

	global $db;

	global $mf_category_id;

	$ins = new stdClass ();
	$ins->manufacturer_id 	= NULL;
	$ins->mf_name 			= $name;
	$ins->mf_category_id 	= $mf_category_id;
	$ins->mf_desc 			= '';

	if (! $db->insertObject ( '#__vm_manufacturer', $ins, 'manufacturer_id' )) {
		return false;
	}
	return $ins->manufacturer_id;
}
# Обработка характеристик товара возвращает строковый индекс характеристик
#



/**
 *Берет ид из vm_product по наименованиею и артикулу
 *$product_name
 *$product_sku
 */
function vm_get_id($product_name,$product_sku) {
	global $db;	
	$q = "SELECT product_id FROM #__vm_product where product_name = '" . $product_name . "' and product_sku = '". $product_sku."'";
	$db->setQuery ($q);
	$rows_sub_Count = $db->loadResult ();
	if (isset ( $rows_sub_Count )) {
	return $rows_sub_Count;
	}
	return 0;
}

/**
 * Обновляем картинку у продукта и desc
 * @param unknown_type $product_id
 * @param unknown_type $product_full_image
 * @param unknown_type $product_thumb_image
 */
function vm_update_image($product_id, $product_full_image, $product_thumb_image,$desc) {
	global $db;	
	$item = new stdClass();
	$item->product_id = $product_id;
	$item->product_full_image = $product_full_image;
	$item->product_thumb_image = $product_thumb_image;
	$item->product_desc = $desc;
	$item->product_s_desc = $desc;
	$db->updateObject( '#__vm_product', $item, '$product_id' );
}





/**
 *Берет ид из vm_category по наименованиею и парент ID
 *$product_name
 *$product_parent_id
 */
function vm_get_category_id($category_name,$parent_id) {
	global $db;	
	$q ="SELECT a.category_id FROM #__vm_category a,jos_vm_category_xref b  
	where 
	b.category_parent_id = ".$parent_id."
	and b.category_child_id = a.category_id
	and a.category_name = '" . $category_name . "'";

	$db->setQuery ($q);
	$rows_sub_Count = $db->loadResult ();
	if (isset ( $rows_sub_Count )) {
	return $rows_sub_Count;
	}
	return 0;
}

/**
 * Берет из vm_category по наименованиею и наименованию парент
 * @param  $category_name
 * @param  $parent_name
 * @return null|number
 */
function vm_get_category_id_name($category_name,$parent_name) {
	global $db;	
	$q ="	SELECT a.category_id FROM #__vm_category a,#__vm_category_xref b, #__vm_category c  
	where 
	b.category_parent_id = c.category_id
	and b.category_child_id = a.category_id
	and a.category_name = '".$category_name."'
	and c.category_name = '".$parent_name."'";

	$db->setQuery ($q);
	$rows_sub_Count = $db->loadResult ();
	if (isset ( $rows_sub_Count )) {
	return $rows_sub_Count;
	}
	return 0;
}


/**
 * Снимает с публикации категорию
 * @todo сделать функцию 
 */
function vm_unpublish_category_mnf() {
	global $db;
	global $manufacturer_ID;
}
/**
 * снимает с публикации всю продукция в контексте данного mnf
 */
function vm_unpublish_product_mnf() {
	global $db;
	global $manufacturer_ID;
	$q = "update #__vm_product a, #__vm_product_mf_xref b
	set a.product_publish = 'N' 
	where b.manufacturer_id = ".$manufacturer_ID." and b.product_id = a.product_id";
	$db->setQuery ($q);
	$db->query ();
}

/**
 * Устанавливает publish на продукт $product_id
 */
function vm_set_publish($product_id) {
	global $db;
	$q = "update #__vm_product set product_publish = 'Y' where product_id = '".$product_id."'";
	$db->setQuery ($q);
	$db->query ();
}

/**
 * Снимаем с публикации
 * @param  $product_id
 */
function vm_set_unpublish ($product_id){
	global $db;
	$q = "update #__vm_product set product_publish = 'N' where product_id = '".$product_id."'";
	$db->setQuery ($q);
	$db->query ();
}

/**
 *Ставит нот паблиш если нет в обновках 
 */
function vm_product_notpublish_if_not_updated(){
	global $db;
	global $manufacturer_ID;
	
	$q = "update #__vm_product,#__vm_product_mf_xref
	set #__vm_product.product_publish = 'N'
	where
	#__vm_product_mf_xref.manufacturer_id = ".$manufacturer_ID."
	and #__vm_product_mf_xref.manufacturer_id = #__vm_product.product_id
	and #__vm_product.product_sku not in (select product_sku from #__al_import where product_vendor = ".$manufacturer_ID.")";
	$db->setQuery ($q);
	$db->query ();
	
	
	
}

/**
 * Ставит картинку на группу из подчиненного товара
 */
function vm_set_group_img() {
	global $db;
	$q = "select distinct a.category_id, a.category_name from jos_vm_category a
			left join jos_vm_product_category_xref b on a.category_id = b.category_id
			where a.category_full_image = ''
			or a.category_thumb_image = ''
			or a.category_thumb_image is null
			or a.category_full_image is null
			and a.category_publish = 'Y'";
	$db->setQuery ( $q );
	$rows = $db->loadObjectList ();
	foreach ( $rows as $row ) {
			$q = "select a.* from #__vm_product a
					left join #__vm_product_category_xref b on a.product_id = b.product_id 
					where b.category_id =".$row->category_id."
					and a.product_full_image <> ''
					and a.product_publish = 'Y'
					order by a.product_id desc
					limit 1 ";
			$db->setQuery ( $q );
			$row_product = $db->loadObject ();
			if ($row_product){ //у категории есть продукт с картинкой 
				$item = new stdClass();
				$item->category_id = $row->category_id;
				$item->category_full_image = $row_product->product_full_image;
				$item->category_thumb_image = $row_product->product_thumb_image;
				$db->updateObject( '#__vm_category', $item, 'category_id' );
				
			}else {//у категории нет продукта, берем из подчиненной категории
				
			$sql = "SELECT a.* FROM jos_vm_category a\n"
		    . "left join jos_vm_category_xref b ON a.category_id=b.category_child_id\n"
		    . "where \n"
		    . "a.category_publish = 'Y'\n"
		    . "and a.category_thumb_image <>''\n"
		    . "and a.category_full_image <>''\n"
		    . "and a.category_thumb_image is not null\n"
		    . "and a.category_full_image is not null\n"
		    . "and b.category_parent_id = ".$row->category_id."  \n"
		    . "limit 1\n"
		    . "";
		    $db->setQuery ( $sql);
		    $row_cat = $db->loadObject ();
		    if ($row_cat){
		    	$item = new stdClass();
				$item->category_id = $row->category_id;
				$item->category_full_image = $row_cat->category_full_image;
				$item->category_thumb_image = $row_cat->category_thumb_image;
				$db->updateObject( '#__vm_category', $item, 'category_id' );
		    }
			}
		}
		

	  
}


# Создание дерева групп
/**
 * Устанавливает цену на продукт
 * @param  $product_id
 * @param  $price_tovar
 * @return boolean
 */
function vm_newProduct_price($product_id,$price_tovar) {

	global $db;
	global $log;

	$val = 'RUB';
	$shopper_group_id = '8';
	$q = 'delete from #__vm_product_price where product_id = '.$product_id;
	$db->setQuery ($q );
	$db->query ();
	$db->setQuery ( "REPLACE INTO  #__vm_product_price
				(product_id, product_price, product_currency, 
				product_price_vdate, product_price_edate,cdate,
				mdate,shopper_group_id,price_quantity_start,
				price_quantity_end ) 
				VALUES (" . $product_id . 
				',' . $price_tovar . ',' .
				"'" . $val . "'" . ',' . '0,0,' . time () .
				',' . time () . ',' .
				$shopper_group_id . ',' . '0,0)' );

				if (! $result = $db->query ()) {
					echo $db->stderr ();
					return false;
				}

				# Изменим запасы товара на складе
/*				$log->addEntry ( array ('comment' => 'change qnty ' .  $price_tovar_data ['product_id'] . " =".$price_tovar_data ['Количество']) );
				$query = "UPDATE #__vm_product SET product_in_stock=" . $price_tovar_data ['Количество'] .
				" where product_id=" . $price_tovar_data ['product_id'];
				$db->setQuery ( $query );
				$db->query ();*/

	
}
# Обход свойств для поиска id производителя, по id производителя в свойствах товара находим значение свойства производитель

# Обход дерева групп полученных из 1С

# добавляем группу покупателей
function newShopperGroupCreate($name) {

	global $vendor_1C_ID;

	global $db;

	$ins = new stdClass ();
	$ins->shopper_group_id 			= NULL;
	$ins->shopper_group_name 		= $name;
	$ins->vendor_id 				= $vendor_1C_ID;
	$ins->shopper_group_desc 		= '';
	$ins->show_price_including_tax 	= 1;
	$ins->default 					= 1;
	$ins->category_list 			= null;

	if (! $db->insertObject ( '#__vm_shopper_group', $ins, 'shopper_group_id' )) {
		return false;
	}
	return $ins->shopper_group_id;
}


/**
 *заполнение цены товара
 */



# выгрузка заказов из VirtueMart
function createzakaz() {

	global $db;

	$db->setQuery ( "SELECT * FROM #__vm_product  WHERE order_status LIKE 'P'" );
	$list = $db->loadObjectList ();

	if (! empty ( $list )) {

		$timechange = time ();

		$no_spaces = '<?xml version="1.0" encoding="UTF-8"?>
							<КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date ( 'Y-m-d', $timechange ) . 'T' . date ( 'H:m:s', $timechange ) . '"></КоммерческаяИнформация>';
		$xml = new SimpleXMLElement ( $no_spaces );

		foreach ( $list as $zakazy ) {
			$doc = $xml->addChild ( "Документ" );

			# Валюта документа
			$val = ( string ) $zakazy->order_currency;
			switch ($val) {
				case 'руб' :
					$val = 'RUB';
					break;
				case 'RUB' :
					$val = 'руб';
					break;
			}

			$doc->addChild ( "Ид", $zakazy->order_id );
			$doc->addChild ( "Номер", $zakazy->order_id );
			$doc->addChild ( "Дата", date ( 'Y-m-d', $zakazy->mdate ) );
			$doc->addChild ( "ХозОперация", "Заказ товара" );
			$doc->addChild ( "Роль", "Продавец" );
			$doc->addChild ( "Валюта", $val );
			$doc->addChild ( "Курс", $zakazy->order_tax );
			$doc->addChild ( "Сумма", $zakazy->order_subtotal );
			$doc->addChild ( "Время", date ( 'H:m:s', $zakazy->mdate ) );

			// Контрагенты
			$db->setQuery ( "SELECT TOP 1 * FROM #__vm_order_user_info WHERE order_id =" . $zakazy->order_id . " AND user_id=" . $zakazy->user_id );
			$client = $db->loadObjectList ();

			if (! empty ( $client ) & (FIX_CLIENT == 1)) {
				$FIO = $client->last_name . " " . $client->first_name . " " . $client->middle_name;

				$k1 = $doc->addChild ( 'Контрагенты' );
				$k1_1 = $k1->addChild ( 'Контрагент' );
				$k1_2 = $k1_1->addChild ( "Наименование", $FIO );
				$k1_2 = $k1_1->addChild ( "Роль", "Покупатель" );
				$k1_2 = $k1_1->addChild ( "ПолноеНаименование", $client->title . " " . $FIO );
				$k1_2 = $k1_1->addChild ( "Имя", $client->first_name );
				$k1_2 = $k1_1->addChild ( "Фамилия", $client->last_name );

			} else {
				$k1 = $doc->addChild ( 'Контрагенты' );
				$k1_1 = $k1->addChild ( 'Контрагент' );
				$k1_2 = $k1_1->addChild ( "Наименование", "Физ лицо" );
				$k1_2 = $k1_1->addChild ( "Роль", "Покупатель" );
				$k1_2 = $k1_1->addChild ( "ПолноеНаименование", "Физ лицо" );
				$k1_2 = $k1_1->addChild ( "Имя", "лицо" );
				$k1_2 = $k1_1->addChild ( "Фамилия", "Физ" );
			}
			/*$k1_3 = $k1_1->addChild("АдресРегистрации");
			$k1_4 = $k1_3->addChild("Представление");
			$k1_4 = $k1_3->addChild("АдресноеПоле");
			<Представление>87698</Представление>
			- <АдресноеПоле>
			<Тип>Почтовый индекс</Тип>
			<Значение>6546</Значение>
			</АдресноеПоле>
			- <АдресноеПоле>
			<Тип>Улица</Тип>
			<Значение>87698</Значение>
			</АдресноеПоле>
			</АдресРегистрации>
			*/

			//$db->setQuery("SELECT jos_vm_product.*,it.product_id,it.product_item_price,it.product_quantity,it.product_final_price FROM #__vm_order_item as it LEFT OUTER JOIN #__vm_product ON #__vm_order_item.product_id = #__vm_product.product_id WHERE #__vm_order_item.order_id ="  . $zakazy->order_id);


			$db->setQuery ( "SELECT it.product_id as product_id, it.product_item_price as product_item_price,
				it.product_quantity as product_quantity, it.product_final_price as product_final_price,pd.product_name as product_name
				FROM #__vm_order_item AS it LEFT OUTER JOIN #__vm_product AS pd ON it.product_id = pd.product_id
				WHERE it.order_id =" . $zakazy->order_id );

			$list_z = $db->loadObjectList ();

			foreach ( $list_z as $razbor_zakaza_t ) {

				$t1 = $doc->addChild ( 'Товары' );
				$t1_1 = $t1->addChild ( 'Товар' );
				$t1_2 = $t1_1->addChild ( "Ид", $razbor_zakaza_t->product_id );
				$t1_2 = $t1_1->addChild ( "Наименование", $razbor_zakaza_t->product_name );
				$t1_2 = $t1_1->addChild ( "ЦенаЗаЕдиницу", $razbor_zakaza_t->product_item_price );
				$t1_2 = $t1_1->addChild ( "Количество", $razbor_zakaza_t->product_quantity );
				$t1_2 = $t1_1->addChild ( "Сумма", $razbor_zakaza_t->product_final_price );
				$t1_2 = $t1_1->addChild ( "ЗначенияРеквизитов" );
				$t1_3 = $t1_2->addChild ( "ЗначениеРеквизита" );
				$t1_4 = $t1_3->addChild ( "Наименование", "ВидНоменклатуры" );
				$t1_4 = $t1_3->addChild ( "Значение", "Товар" );

				$t1_2 = $t1_1->addChild ( "ЗначенияРеквизитов" );
				$t1_3 = $t1_2->addChild ( "ЗначениеРеквизита" );
				$t1_4 = $t1_3->addChild ( "Наименование", "ТипНоменклатуры" );
				$t1_4 = $t1_3->addChild ( "Значение", "Товар" );

			}

		}

		// print the SimpleXMLElement as a XML well-formed string
		if (FIX_CODING == 'UTF-8') {
			header ( "Content-type: text/xml; charset=utf-8" );
			print iconv ( "utf-8", "windows-1251", $xml->asXML () );
		} else {
			print $xml->asXML ();
		}

	} else {
		print "error";
	}

}


function CheckAuthUser()
{
	global $db;

	if (isset($_SERVER['PHP_AUTH_USER'])) {
		$username	=	trim($_SERVER['PHP_AUTH_USER']);
		$password	=	trim($_SERVER['PHP_AUTH_PW']);

		$query = "SELECT `id`, `password` FROM #__users where username='" . $username . "'";
		//"' AND usertype='Super Administrator'";
		$db->setQuery( $query );
		$result = $db->loadObject();

		if($result)
		{
			$parts   = explode( ':', $result->password );
			$crypt   = $parts[0];
			$salt   = @$parts[1];
			$testcrypt = JUserHelper::getCryptedPassword($password, $salt);

			if ($crypt == $testcrypt) {
				return 'success';
			}
		}
	}
	return 'false user name or password';
}






?>
