<?


define ( 'FIX_VERSION', '1.1.3с' ); // Версия скрипта
define ( 'FIX_CODING', 'UTF-8' ); 	// Задается кодировка в которой происходит выгрузка заказов в 1С
define ( 'FIX_TYPEPRICE', '-default-' ); // Задаем имя типа цен которое выводится по умолчанию
										 // не доделано.
define ( 'FIX_CLIENT', 1 ); 		// 0 - Выгружать всех клиентов в 1С на контрагента "Физ лицо"  1- Выгружать всех клиентов в 1С как есть
define ( 'FIX_ZIP', 'yes' ); 		// true - использовать zip сжатие при получении файлов
define ( 'FIX_ZIPSIZE', 2048000 ); 	// размер арива zip в байтах


ini_set ( 'display_errors', '1' );
error_reporting ( E_ALL );
define ( '_JEXEC', 1 );
define ( 'DS', DIRECTORY_SEPARATOR );
# директория в которой расположен движок /joomla/ ,если в корне сайта то пусто
define ( 'JPATH_BASE', dirname(dirname ( __FILE__ )) . '' );
# директория в которую записываются картинки и файл обмена
define ( 'JPATH_BASE_PICTURE', JPATH_BASE .DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');
# директория в которую записываются маленькие картинки
define ( 'JPATH_BASE_PICTURE_SMALL', JPATH_BASE_PICTURE .DS.'resized' );

require_once (JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once (JPATH_BASE . DS . 'includes' . DS . 'framework.php');
require ('joomla' .DS. 'libraries' .DS. 'joomla' .DS. 'factory.php');
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
$category = array ();

$tax_rate = array ();
# товар
$products = array ();
# типы цен
$price = array ();
# цены на товар
$price_tovar = array ();

# характеристики на товар
$char_type_name = array ();
# производитель
$manufacturer_1C_ID = '';
$manufacturer = array ();
#ID продавца, имя продавца берем из CML
$vendor_1C_ID = 0;
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



# Ищем группу производителей привязанных к продавцу если не находим то записываем новую группу
# Ищем продавца если не находим то записываем нового
function vendor_create($xml)
{
	global $db;
	global $log;

	global $mf_category_id;
	$vendor_name	=	(string)$xml->Владелец->Наименование;
	$vendor_store_name	=	(string)$xml->Владелец->ОфициальноеНаименование;

	$db->setQuery ( "SELECT mf_category_id FROM #__vm_manufacturer_category where mf_category_name = '" . $vendor_name . "'" );
	$rows_sub_Count = $db->loadResult ();
	if (isset ( $rows_sub_Count )) {
		$mf_category_id	= (int)$rows_sub_Count;
	} else // Если группа поизводителей по имени не найдена в базе то мы ее создаем
	{
		$mf_category_id	=	manufacturer_category_create($vendor_name,$vendor_store_name);
	}

	################################################################################
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
	}
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
# Создание нового товара
function newProducts($product_parent_id,$product_SKU, $product_name, $product_desc, $product_full_image, $product_ed) {
	global $db;

	global $vendor_1C_ID;

	$ins = new stdClass ();
	$ins->product_id = NULL;
	$ins->vendor_id 	=	$vendor_1C_ID;
	$ins->product_parent_id	=	$product_parent_id;
	$ins->product_SKU = $product_SKU;
	$ins->product_name = $product_name;
	$ins->product_desc = $product_desc;
	$ins->product_publish = 'Y';
	$ins->product_available_date = time ();
	$ins->product_in_stock		 = 99;
	$ins->cdate = time ();
	$ins->mdate = time ();

	$ins->product_full_image = $product_full_image;
	$ins->product_thumb_image = $product_full_image;

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
function price_create($xml, $price) {

	#global $category;
	//		global $products;
	global $db;

	if (!isset($xml->ТипыЦен))

	{
		return $price;
	}
	# Прочтем все типы цен из offers.xml
	foreach ($xml->ТипыЦен->ТипЦены as $price_data)

	{
		$owner								=(string)$price_data->Ид;
		$price[$owner]['Наименование'] 		=(string)$price_data->Наименование;
		$price[$owner]['Валюта'] 			=(string)$price_data->Валюта;



		if (FIX_TYPEPRICE <> '-default-') {
			if ($price [$owner] ['Наименование'] == FIX_TYPEPRICE) {
				$price [$owner] ['Наименование'] = '-default-';
			}
		}

		$db->setQuery ( "SELECT shopper_group_id FROM #__vm_shopper_group where shopper_group_name = '" . $price [$owner] ['Наименование'] . "'" );
		$rows_sub_Count = $db->loadResult ();

		// Если группа покупателей по имени есть в базе то мы ее не меняем , а берем ее id
		if (isset ( $rows_sub_Count )) {
			$price [$owner] ['shopper_group_id'] = ( int ) $rows_sub_Count;
		} else // Если группа покупателей по имени не найдена базе то мы ее создаем
		{
			$price [$owner] ['shopper_group_id'] = newShopperGroupCreate ( $price [$owner] ['Наименование'] );
		}
	}
	return $price;
}
# Парсинг типов цен на характеристики
function price_tovar_create($xml, $price_tovar) {
	global $products;
	global $price;

	global $log;

	if (!isset($xml->Предложения))
	{
		return $price;
	}
	# Перебираем товары
	foreach ($xml->Предложения->Предложение as $price_data)
	{
		$owner = substr((string)$price_data->Ид,0,36);
		$owner2 = (string)$price_data->Ид;
		# наложить цену на характеристики
		# подбор товара по наличию характеристик
		if (isset($products[$owner2]))
		{
			$owner=$owner2;
			$log->addEntry ( array ('comment' => 'product price add char '.$owner2));
		} else
		{
			$log->addEntry ( array ('comment' => 'product price add  '.$owner));
		}

		$price_tovar [$owner] ['product_id'] = $products [$owner] ['product_id'];

		# Перебираем цены на товар
		foreach ($price_data->Цены->Цена as $price_tovar_data)

		{
			$price_tovar[$owner]['shopper_group_id'] 	= $price ["$price_tovar_data->ИдТипаЦены"] ["shopper_group_id"];
			$price_tovar[$owner]['ЦенаЗаЕдиницу']		=	(int)$price_tovar_data->ЦенаЗаЕдиницу;
			$price_tovar[$owner]['Валюта']				=	(string)$price_tovar_data->Валюта;
			$price_tovar[$owner]['Единица']				=	(string)$price_tovar_data->Единица;
			$price_tovar[$owner]['Коэффициент']			=	(string)$price_tovar_data->Коэффициент;
		}

		$price_tovar[$owner]['Количество']				=	(int)$price_data->Количество;
		#	$log->addEntry ( array ('comment' => 'product price quantity  ' . $price_tovar [$owner] ['product_id'] . " guid " . $owner . " ="	.	$price_tovar[$owner]['Количество']));
	}
	return $price_tovar;
}

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
function products_character($xml,$id,$ownerid) {
	global $db;
	global $log;

	global $products;
	global $char_type_name;



	$db->setQuery ( "DELETE  #__vm_product_attribute where product_id=". $id);
	$db->query ();


	#Перебираем характеристики товара
	#

	$i=1;
	foreach ($xml->ХарактеристикиТовара->ХарактеристикаТовара as $char_data)
	{
		#
		if (!isset($char_type_name[$ownerid]["$char_data->Наименование"]))
		{
			$ins = new stdClass ();
			$ins->product_id 		= $ownerid;
			$ins->attribute_name 	= "$char_data->Наименование";
			$ins->attribute_list 	= $i;
			$i++;
			if (! $db->insertObject ( '#__vm_product_attribute_sku', $ins)) {
				return false;
			}
			$char_type_name[$ownerid]["$char_data->Наименование"]="$char_data->Наименование";
		}



		$ins = new stdClass ();
		$ins->product_id  		= $id;
		$ins->attribute_name  	= "$char_data->Наименование";
		$ins->attribute_value 	= "$char_data->Значение";

		if (! $db->insertObject ( '#__vm_product_attribute', $ins )) {
			return false;
		}

		/*$log->addEntry ( array ('comment' => '999 ' ));
		$db->setQuery ( "REPLACE INTO  #__vm_product_attribute_sku
		(product_id, attribute_name,attribute_list)
		VALUES (" . $ownerid . "," . "$char_data->Наименование",$i );
		$db->query ();
		$i++;*/
	}

}
# Парсинг списка товаров и характеристик

function products_create($xml, $products) {

	global $category;
	global $char;
	global $manufacturer;
	global $manufacturer_1C_ID;
	global $char_type_name;

	global $db;
	global $log;

	if (!isset($xml->Товары))

	{
		return $products;
	}
	$i=0;
	foreach ($xml->Товары->Товар as $product_data)

	{

		$i++;
		$owner = substr((string)$product_data->Ид,0,36);
		$owner2 = (string)$product_data->Ид;
		# Если товар уже был загружен в массив
		if (isset ( $products [$owner] )) {

			$log->addEntry ( array ('comment' => 'product characters add '));

			# Добавим товар на характеристику
			$products [$owner2] ['product_id'] = newProducts ($products [$owner] ['product_id'],$products [$owner] ['Артикул'].':e'.$i ,$products [$owner] ['Наименование'],  $products [$owner] ['Реквизиты'] ['Полное наименование'], $products [$owner] ['picture'], $products [$owner] ['product_ed'] );

			$db->setQuery ( "REPLACE INTO  #__vm_product_mf_xref
					(product_id, manufacturer_id)
					VALUES (" . $products [$owner2] ['product_id'] . "," . $products [$owner] ['manufacturer'] . ")");
			$db->query ();


			# Связываем товар и группу
			newProducts_xref ( $products [$owner] ['Группа'], $products [$owner2] ['product_id'] );

			# Характеристики товара
			if (isset($product_data->ХарактеристикиТовара))
			{
				$log->addEntry ( array ('comment' => 'products_char_create  owner2 ' . $products [$owner2] ['product_id'] . ' owner ' . $products [$owner] ['product_id'] ));
				products_character($product_data,$products [$owner2] ['product_id'],$products [$owner] ['product_id']);
			}


		} # новый товар в массиве
		else
		{
			$products[$owner]['Наименование'] 	=(string)$product_data->Наименование;
			$products[$owner]['Артикул'] 		=(string)$product_data->Артикул;
			$products[$owner]['Группа'] 		= 0;

			foreach ($product_data->Группы as $groups_data)
			{
				$id	=	(string)$groups_data->Ид;
				$products [$owner] ['Группа'] = $category [$id] ['category_id'];
			}
			$products[$owner]['product_ed']=(string)$product_data->БазоваяЕдиница;

			# Реквизиты товара
			foreach ($product_data->ЗначенияРеквизитов->ЗначениеРеквизита as $recvizit_data)
			{
				$products [$owner] ['Реквизиты'] ["$recvizit_data->Наименование"] = "$recvizit_data->Значение";
			}


			# Свойства товара (поиск производителя)
			$products [$owner] ['manufacturer'] = 0;
			if (isset($product_data->ЗначенияСвойств))

			{
				foreach ($product_data->ЗначенияСвойств->ЗначенияСвойства as $sv_data)

				{
					# перебираем свойства ищем производителя
					if ($sv_data->Ид	=	$manufacturer_1C_ID)
					{

						# Если в производителях находим по имении производителя из 1С
						if (isset($manufacturer[(string)$sv_data->Значение]))
						{

							#$log->addEntry ( array ('comment' => 'manafactured = ' . $manufacturer[(string)$sv_data->Значение] ) );
							$products[$owner]['manufacturer']	=	$manufacturer[(string)$sv_data->Значение];
						}
						else
						{
							$products[$owner]['manufacturer']	=	manufacturer_create((string)$sv_data->Значение);
							#$log->addEntry ( array ('comment' => 'manafactured add new = ' . $products[$owner]['manufacturer'] ) );
							# Дополним таблицу производителей новым элементом
							$manufacturer[(string)$sv_data->Значение]=$products [$owner] ['manufacturer'];
						}
					}
				}
			}
			if (isset($product_data->СтавкиНалогов))
			{
				foreach ($product_data->СтавкиНалогов->СтавкаНалога as $snalog)
				{
					$products [$owner] ['СтавкаНалога'] ["$snalog->Наименование"] = "$snalog->Ставка";
				}
			}


			$products[$owner]['picture']	=	(string)$product_data->Картинка;

			$db->setQuery ( "SELECT product_id FROM #__vm_product where product_sku = '" . $products [$owner] ['Артикул'] . "'" );
			$rows_sub_Count = $db->loadResult ();

			# Если товар  по артикулу есть в базе то мы не меняем id товара , а берем его id
			if (isset ( $rows_sub_Count )) {

				$products [$owner] ['product_id'] = ( int ) $rows_sub_Count;
				# Очистим всех родственников и их цены
				$db->setQuery ("DELETE  t1 ,t2 from #__vm_product t1 inner join #__vm_product_price t2 Where t2.product_id = t1.product_parent_id AND t1.product_parent_id =" . $products [$owner] ['product_id']);
				$db->query ();

				# пытаемся сделать update всех поле полей
				$query = "UPDATE #__vm_product SET " .
				",product_s_desc='" . $products [$owner] ['Реквизиты'] ['Полное наименование'] . "'" .
				",product_name=" . $products [$owner] ['Наименование'] .
				",product_full_image='" . $products [$owner] ['picture'] .
				",product_thumb_image='" . $products [$owner] ['picture'] .
				" where product_sku = '" . $products [$owner] ['Артикул'] . "'";
				$db->setQuery ( $query );
				$db->query ();

				# Связываем товар и группу
				$query = "UPDATE #__vm_category_xref SET " . ",category_id=" . $products [$owner] ['Группа'] . " where product_id = " . $products [$owner] ['product_id'] ;
				$db->setQuery ( $query );
				$db->query ();
			} else // Если товар  по артикулу  не найден в  базе то мы ее создаем
			{
				$products [$owner] ['product_id'] = newProducts (0, $products [$owner] ['Артикул'], $products [$owner] ['Наименование'], $products [$owner] ['Реквизиты'] ['Полное наименование'], $products [$owner] ['picture'], $products [$owner] ['product_ed'] );
				# Создадим/Изменим производителя
				$log->addEntry ( array ('comment' => 'products_create ' . $products [$owner] ['product_id'] . "" . $products [$owner] ['Наименование'] . ";" . $products [$owner] ['Группа'] . ";" ) );
				# Связываем товар и группу
				newProducts_xref ( $products [$owner] ['Группа'], $products [$owner] ['product_id'] );
			}


			#$log->addEntry ( array ('comment' => 'REPLACE __vm_product_mf_xref' . $products [$owner] ['product_id'] . "," . $products [$owner] ['manufacturer']  ) );

			# Создадим/Изменим производителя
			$db->setQuery ( "REPLACE INTO  #__vm_product_mf_xref
					(product_id, manufacturer_id)
					VALUES (" . $products [$owner] ['product_id'] . "," . $products [$owner] ['manufacturer'] . ")");
			$db->query ();

			# Характеристики товара
			if (isset($product_data->ХарактеристикиТовара))
			{
				# Добавим товар на характеристику
				$products [$owner2] ['product_id'] = newProducts ($products [$owner] ['product_id'], $products [$owner] ['Артикул'].':e'.$i, $products [$owner] ['Наименование'], $products [$owner] ['Реквизиты'] ['Полное наименование'], $products [$owner] ['picture'], $products [$owner] ['product_ed'] );
				# Характеристики товара
				$log->addEntry ( array ('comment' => 'products_char_create  owner2 ' . $products [$owner2] ['product_id'] . ' owner ' . $products [$owner] ['product_id'] ));
				products_character($product_data,$products [$owner2] ['product_id'],$products [$owner] ['product_id']);
				$db->setQuery ( "REPLACE INTO  #__vm_product_mf_xref
					(product_id, manufacturer_id)
					VALUES (" . $products [$owner2] ['product_id'] . "," . $products [$owner] ['manufacturer'] . ")");
				$db->query ();
			}
		}
	}
	return $products;
}

//category_id
//manufacturer_id
//category_name
//category_description
//category_thumb_image
//category_full_image
//category_publish
//cdate
//mdate
//category_browsepage
//products_per_row
//category_flypage
//list_order


# Создание новой категории
function newCategory($category_name, $category_description = '') {

	global $vendor_1C_ID;

	global $db;


	$ins = new stdClass ();
	$ins->category_id = NULL;
	$ins->category_name = $category_name;
	$ins->category_description = $category_description;
	$ins->vendor_id = $vendor_1C_ID;
	$ins->category_publish = 'Y';
	$ins->category_browsepage = 'managed';
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

//jos_vm_category_xref
//category_parent_id
//category_child_id
//category_list


# Создание дерева групп
function groups_xref_create($category) {
	global $db;

	foreach ( $category as $category_data ) {
		$ins = new stdClass ();
		$ins->category_parent_id = ( int ) $category_data ['owner'];
		$ins->category_child_id = ( int ) $category_data ['category_id'];
		$ins->category_list = null;

		if (! $db->insertObject ( '#__vm_category_xref', $ins )) {
			return false;
		}

	}
}
# Обход свойств для поиска id производителя, по id производителя в свойствах товара находим значение свойства производитель
function property_find($xml) {
	$property = '';
	if (!isset($xml->Свойства))

	{
		return $property;
	}
	foreach ($xml->Свойства->Свойство as $property_data)
	{
		$name 	=(string)$property_data->Наименование;
		if ($name == "Производитель") {
			$property	=(string)$property_data->Ид;
		}
	}
	return $property;
}
# Обход дерева групп полученных из 1С
function groups_create($xml, $category, $owner) {

	global $db;
	global $log;

	if (!isset($xml->Группы))

	{
		return $category;
	}

	foreach ($xml->Группы->Группа as $category_data)

	{
		$name 	=(string)$category_data->Наименование;
		$cnt	=(string)$category_data->Ид;

		$category [$cnt] ['name'] = $name;
		$category [$cnt] ['owner'] = $owner;
		$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $name . "'" );
		$rows_sub_Count = $db->loadResult ();
		// Если группа по имени есть в базе то мы ее не меняем , а берем ее id
		if (isset ( $rows_sub_Count )) {
			$category [$cnt] ['category_id'] = ( int ) $rows_sub_Count;
		} else // Если группа по имени не найдена базе то мы ее создаем
		{
			$category [$cnt] ['category_id'] = newCategory ( $name );
		}

		$log->addEntry ( array ('comment' => 'groups_create ' . $category [$cnt] ['category_id'] . ";" . $name ) );

		$category = groups_create ( $category_data, $category, $category [$cnt] ['category_id'] );

	}
	return $category;
}
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

# заполнение цен товара и кол-ва
function newProduct_price($price_tovar) {

	global $db;
	global $log;

	foreach ( $price_tovar as $price_tovar_data ) {
		$val = $price_tovar_data ['Валюта'];
		switch ($price_tovar_data ['Валюта']) {
			case 'руб' :
				$val = 'RUB';
				break;
			case 'RUB' :
				$val = 'руб';
				break;
		}

		$db->setQuery ( "REPLACE INTO  #__vm_product_price
				(product_id, product_price, product_currency, 
				product_price_vdate, product_price_edate,cdate,
				mdate,shopper_group_id,price_quantity_start,
				price_quantity_end ) 
				VALUES (" . $price_tovar_data ['product_id'] . 
				',' . $price_tovar_data ['ЦенаЗаЕдиницу'] . ',' .
				"'" . $val . "'" . ',' . '0,0,' . time () .
				',' . time () . ',' .
				$price_tovar_data ['shopper_group_id'] . ',' . '0,0)' );

				if (! $result = $db->query ()) {
					echo $db->stderr ();
					return false;
				}

				# Изменим запасы товара на складе
				$log->addEntry ( array ('comment' => 'change qnty ' .  $price_tovar_data ['product_id'] . " =".$price_tovar_data ['Количество']) );
				$query = "UPDATE #__vm_product SET product_in_stock=" . $price_tovar_data ['Количество'] .
				" where product_id=" . $price_tovar_data ['product_id'];
				$db->setQuery ( $query );
				$db->query ();

	}
}
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

/
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
