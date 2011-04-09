<?php
//***********************************************************************
// Назначение: Передача товаров из 1С в virtuemart
// Автор оригинала: Перушев Владислав (e-mail:	homutke@mail.ru)
// Автор пересборки: Дуденков М.В. (email: mihail@termservis.ru)
// Авторские права: использовать, а также распространять данный скрипт
//                  разрешается только с разрешением автора скрипта
//***********************************************************************

define ( 'VM_VERSION', '1.2' ); 	// Версия скрипта. Будет обновляться!
define ( 'VM_CODING', 'UTF-8' ); 	// Кодировка выгрузки заказов (пока не применяется)
define ( 'VM_ZIP', 'yes' ); 		// Использование zip архивов
define ( 'VM_ZIPSIZE', 2048000 ); 	// максимальный размер архива в байтах
define ( 'VM_MAXRAND', 9000000 ); 	// максимальный значение рандомного имени картинки
define ( 'VM_NDS', 'no' ); 		// Учитывать в цене из 1С налог НДС? 
define ( 'VM_CATALOG', 'yes' ); 	// Копировать структуру каталогов? 

ini_set ( 'display_errors', '1' );
error_reporting ( E_ALL );
define ( '_JEXEC', 1 );
define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'JPATH_BASE', dirname ( __FILE__ ) . '' );
define ( 'JPATH_BASE_PICTURE', JPATH_BASE . '/components/com_virtuemart/shop_image/product' );
define ( 'JPATH_BASE_PICTURE_SMALL', JPATH_BASE_PICTURE . '/resized' );

require_once (JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once (JPATH_BASE . DS . 'includes' . DS . 'framework.php');
require ( 'libraries' .DS. 'joomla' .DS. 'factory.php');
$mainframe = & JFactory::getApplication ( 'site' );
$mainframe->initialise ();
$db = & JFactory::getDBO ();
jimport ( 'joomla.error.log' );
jimport ( 'joomla.user.helper' );
$log = &JLog::getInstance ( 'vmshop_1c.log.php' );

//*******************Массивы*******************
$category = array ();
$tax_rate = array ();
$products = array ();
$price = array ();
$price_tovar = array ();
$char_type_name = array ();
$manufacturer_1C_ID = '';
$manufacturer = array ();
$vendor_1C_ID = 0;
$vendor = array();
$mf_category_id = 0;
$shoppper	=	array();
$size_img = array();
$new_width = array();
$new_height = array();
$old_width = array();
$old_height = array();

//*******************Этапы подключения 1с и virtuemart*******************

//*******************Авторизация*******************
if (isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'checkauth') 
{
	print CheckAuthUser();
}

//*******************Поключение 1с к virtuemart*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'catalog' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'export') 
{
	print 'success\n';
}
//*******************Выбор архивировать или нет*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'catalog' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'init') 
{
	print "zip=" . VM_ZIP . "\n" . VM_ZIPSIZE;
}
//*******************Загрузка архива*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'catalog' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'file' && isset ( $_REQUEST ['filename'] )) 
{
	print loadfile () . "\n" . $_REQUEST ['filename'];
}
//*******************Операция с файлами*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'catalog' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'import') {
	$cnt = 0;

	switch ($_REQUEST ['filename']) {
		case "import.xml" :
			$file_txt = scandir ( JPATH_BASE_PICTURE ); 
			foreach ( $file_txt as $filename_to_save )
			{
				$log->addEntry ( array ('comment' => 'unzip файл ' . $filename_to_save ) );
				if (substr ( $filename_to_save, - 3 ) == 'zip') 
				{
					unzip ( $filename_to_save, JPATH_BASE_PICTURE . DS );
					unlink ( JPATH_BASE_PICTURE . DS . $filename_to_save );
				}
			}
			print "success\n";
			break;

		case "offers.xml" :
			if (file_exists ( JPATH_BASE_PICTURE . "/" . 'offers.xml' )) 
			{
				$log->addEntry ( array ('comment' => 'Анализ xml ' . JPATH_BASE_PICTURE . '/import.xml' ) );
				$log->addEntry ( array ('comment' => 'Анализ xml ' . JPATH_BASE_PICTURE . '/offers.xml' ) );
				$xml1 = simplexml_load_file ( JPATH_BASE_PICTURE . DS . 'import.xml' );
				$xml2 = simplexml_load_file ( JPATH_BASE_PICTURE . DS . 'offers.xml' );
				creatunit ( $xml1, $xml2 );

				if (file_exists ( JPATH_BASE_PICTURE . DS . 'import.xml' )) 
				{
					unlink ( JPATH_BASE_PICTURE . DS. "import.xml" );
				}
				if (file_exists ( JPATH_BASE_PICTURE . DS . 'offers.xml' )) 
				{
					unlink ( JPATH_BASE_PICTURE . DS . "offers.xml" );
				}
				
				print "success\noffer.xml";
				break;
			}
	}
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//*******************Поключение virtuemart к 1с*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'sale' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'query') 
{
	createzakaz ();
}
//*******************Выбор архивировать или нет*******************
if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'sale' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'init') 
{
	print "zip=" . "no" . "\n" . FIX_ZIPSIZE;
}

if (isset ( $_REQUEST ['type'] ) && $_REQUEST ['type'] == 'sale' && isset ( $_REQUEST ['mode'] ) && $_REQUEST ['mode'] == 'success') {
	print 'success\n';

}

//*******************Работа движка*******************
function CheckAuthUser()
{
	global $db;

	if (isset($_SERVER['PHP_AUTH_USER'])) 
	{
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
#*******************Загрузка архива*******************
function loadfile() {

	global $log;

	$filename_to_save = JPATH_BASE_PICTURE . DS . $_REQUEST ['filename'];

	$log->addEntry ( array ('comment' => 'loadfile & create dir name ' . $filename_to_save ) );

	$image_data = file_get_contents ( "php://input" );

	if (isset ( $image_data )) {

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
# *******************Распаковка архивов*******************
function unzip($file, $folder = '') {

	global $log;
	$log->addEntry ( array ('comment' => 'unzip file 3 ' . $folder . $file ) );
	$zip = zip_open ( $folder . $file );
	$files = 0;

	if ($zip) 
	{
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
	} 
	else 
	{
		$log->addEntry ( array ('comment' => 'error unzip file ' . $name ) );
	}
}
// *******************Добавление товара в магазин*******************
function creatunit($xml1, $xml2) 
{
	global $db;
	global $log;
	global $size_img;
	global $category;
	
	$vendor_1C_ID = 1;
	
	if($xml1 == '' or $xml2 == '')
	{
		print 'error load xml';
	}
	
	//*******************Создаем производителей
	/*$property = '';
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
	return $property;*/
	
	//Берем информацию о существующих производителях
	/*$manufacturer_id = array ();
	$manufacturer_name = array ();
	$sql = "SELECT manufacturer_id as id, mf_name as name FROM #__vm_manufacturer";
	$db->setQuery ( $sql );
	$rows = $db->loadObjectList ();
	foreach ( $rows as $row ) {
		$manufacturer_id ["$row->id"] = ( int ) $row->id;
		$manufacturer_name ["$row->id"] = ( string ) $row->name;
	}*/
	
	//*******************Загрузка ценовых групп из магазина
	$Shopper_id = array ();
	$Shopper_name = array ();
	$Shopper_def = array ();
	$sql = "SELECT shopper_group_id as id, mf_name as name, default FROM #__vm_shopper_group";
	$db->setQuery ( $sql );
	$rows = $db->loadObjectList ();
	if (! empty ( $rows )) {
		foreach ( $rows as $row ) {
			$Shopper_id ["$row->id"] = ( int ) $row->id;
			$Shopper_name ["$row->id"] = ( string ) $row->name;
			$Shopper_def ["$row->id"] = ( int ) $row->default;
		}
	}
	
	//$count2 = count($Shopper_name);
	//echo $count2;
	
	//*******************Проверка и добавление новых групп
	$groups_cash = $xml2->ПакетПредложений->ТипыЦен;
	if (isset($groups_cash))
	{
		foreach ($groups_cash->ТипЦены as $cash_data)
		{
			$db->setQuery ( "SELECT shopper_group_name FROM #__vm_shopper_group WHERE `shopper_group_name` = '".$cash_data->Наименование."'" );
			$rows = $db->loadResult ();
			$id_cash = (string) $cash_data->Ид;
			if (!isset ( $rows )) 
			{
				$ins = new stdClass ();
				$ins->shopper_group_id 			= NULL;
				$ins->shopper_group_name 		= (string) $cash_data->Наименование;
				$ins->vendor_id 				= $vendor_1C_ID;
				$ins->shopper_group_desc 		= (string) $cash_data->Наименование;
				$ins->show_price_including_tax 	= 1;
				$ins->default 					= 0;
				$ins->category_list 			= null;
			
				if (! $db->insertObject ( '#__vm_shopper_group', $ins, 'shopper_group_id' )) 
				{
					print 'error mysql 1';
				}
				$group_name ["$id_cash"] = $ins->shopper_group_name;
			}
			else
			{
				$group_name ["$id_cash"] = ( string ) $rows;
			}
		}
	}
	
	//*******************Создание группы
	if (VM_CATALOG == 'yes')
	{
		//Создаем дерево каталогов из 1С
		$cat_name = array();
		$cat_owner = array();
		$sub_cat = array();
		$category = array();
		
		$cat = $xml1->Классификатор->Группы;
		foreach ($cat->Группа as $cat_data)
		{
			$category ['owner'] = 0;
			
			$cat_owner['$cat_data->Ид'] = ( int ) $category ['owner'];
			
			$cat_name['$cat_data->Ид'] = ( string ) $cat_data->Наименование;
			
			$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $cat_name['$cat_data->Ид'] . "'" );
			$rows_sub_Count = $db->loadResult ();
			
			if (isset ( $rows_sub_Count ))
			{
				$category ['category_id'] ['$cat_data->Ид'] = ( int ) $rows_sub_Count;
			}
			else
			{
				$ins = new stdClass ();
				$ins->category_id = NULL;
				$ins->category_name = $cat_name['$cat_data->Ид'];
				$ins->category_description = $cat_name['$cat_data->Ид'];
				$ins->vendor_id = $vendor_1C_ID;
				$ins->category_publish = 'Y';
				$ins->category_browsepage = 'browse_3';
				$ins->cdate = time ();
				$ins->mdate = time ();
				$ins->category_flypage = 'flypage.tpl';
				$ins->category_thumb_image = '';
				$ins->category_full_image = '';
				$ins->list_order = 1;
                                $ins->products_per_row = 3;
			
				if (! $db->insertObject ( '#__vm_category', $ins, 'category_id' )) 
				{
					print 'error mysql 2';
				}
				
				$category ['category_id'] ['$cat_data->Ид'] = ( int ) $ins->category_id;
				
				$ins = new stdClass ();
				$ins->category_parent_id = ( int ) $cat_owner['$cat_data->Ид'];
				$ins->category_child_id = ( int ) $category ['category_id'] ['$cat_data->Ид'];
				$ins->category_list = null;
		
				if (! $db->insertObject ( '#__vm_category_xref', $ins )) 
				{
					print 'error mysql 3';
				}
			}	

			if (isset($cat_data->Группы->Группа))
			{
				catalog_add($cat_data->Группы, $category ['category_id'] ['$cat_data->Ид'], $category);	
			}
		}
	}
	else
	{
		//Создаем один каталог и в него запихиваем все товары
		$name 	= "Неразобранное";
			
		$category ['owner'] = 0;
		
		$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $name . "'" );
		$rows_sub_Count = $db->loadResult ();
		
		if (isset ( $rows_sub_Count ))
		{
			$category ['category_id'] = ( int ) $rows_sub_Count;
		}
		else // Если группа по имени не найдена базе то мы ее создаем
		{
			$ins = new stdClass ();
			$ins->category_id = NULL;
			$ins->category_name = 'Неразобранное';
			$ins->category_description = 'Категория не видна в каталоге, требуется разобрать по группам';
			$ins->vendor_id = $vendor_1C_ID;
			$ins->category_publish = 'N';
			$ins->category_browsepage = 'browse_3';
			$ins->cdate = time ();
			$ins->mdate = time ();
			$ins->category_flypage = 'flypage.tpl';
			$ins->category_thumb_image = '';
			$ins->category_full_image = '';
			$ins->list_order = 3;
		
			if (! $db->insertObject ( '#__vm_category', $ins, 'category_id' )) 
			{
				print 'error mysql 2';
			}
			
			$category ['category_id'] = ( int ) $ins->category_id;
			
			$ins = new stdClass ();
			$ins->category_parent_id = ( int ) $category ['owner'];
			$ins->category_child_id = ( int ) $category ['category_id'];
			$ins->category_list = null;
	
			if (! $db->insertObject ( '#__vm_category_xref', $ins )) 
			{
				print 'error mysql 3';
			}
		}
	}
	
	//*******************Добавление продуктов
	$tovar = $xml1->Каталог->Товары;
	if (isset($tovar))
	{
		//Добавляем ид ценовых групп
		$i = 0;
		foreach ($xml2->xpath('//ТипЦены') as $xml2_cash_gr2) 
		{
			$id_cash_group[$i] = $xml2_cash_gr2->Ид;
			$name_cash_group[$i] = $xml2_cash_gr2->Наименование;
			
			$db->setQuery ( "SELECT shopper_group_id FROM #__vm_shopper_group where shopper_group_name = '" . $name_cash_group[$i] . "'" );
			$rows_sub_Count = $db->loadResult ();
			//Изменяем цену товара
			if (isset ( $rows_sub_Count ))
			{
				$new_cash_gr[$i] = ( int ) $rows_sub_Count;	
			}
			$i = $i + 1;
		}
		
		$id_tov = 0;
		foreach ($tovar->Товар as $product_data)
		{
			$modif = False;
			$status = (string)$product_data->Статус;
			$product_sku = (string)$product_data->Артикул;
			$product_name = (string)$product_data->Наименование;
			$product_ed = (string)$product_data->БазоваяЕдиница;
			$product = (string)$product_data->Ид;
			foreach ($product_data->Группы->Ид as $groups_data)
			{
				$product_cat = (string)$groups_data;
				foreach ($xml1->xpath('//Группа') as $xml_gr) 
				{
					if ($xml_gr->Ид == $product_cat)
					{
						$product_cat_name = (string)$xml_gr->Наименование;
						$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $product_cat_name . "'" );
						$rows_sub_Count_2 = $db->loadResult ();
						
						if (isset ( $rows_sub_Count_2 ))
						{
							$category ['category_id'] ['$id_tov'] = ( int ) $rows_sub_Count_2;
						}
					}
				}
			}
			$product_image = '';
			$product_full_image = '';
			$product_thumb_image = '';
			$product_img_2 = 0;
			$stavka = '';
			$stavkands = '';
			$nalog_group = '';
			$nalogi = '';
			$sh_group = 0;
			
			// Добавляем в базу Наш налог
			$nalogi = $product_data->СтавкиНалогов;
			if (isset($nalogi->СтавкаНалога))
			{
				foreach ($nalogi->СтавкаНалога as $nalog)
				{
					if ($nalog->Наименование == 'НДС')
					{
						$stavka = (string)$nalog->Ставка;
						$stavkands = $stavka/100;
						$db->setQuery ( "SELECT tax_rate_id FROM #__vm_tax_rate WHERE `tax_rate` = '".$stavkands."'" );
						$nalog_group = $db->loadResult ();
						if (!isset ( $nalog_group )) //Изменяем существующий продукт в случае полной выгрузки
						{
							$ins = new stdClass ();
							$ins->tax_rate_id = NULL;
							$ins->vendor_id = $vendor_1C_ID;
							$ins->tax_state = '-';
							$ins->tax_country = 'RUS';
							$ins->mdate = time ();
							$ins->tax_rate = $stavkands;
							
							if (! $db->insertObject ( '#__vm_tax_rate', $ins, 'tax_rate_id' )) 
							{
								print 'error mysql 6';
							}
							$product_tax_id = $ins->tax_rate;
						}
						else
						{
							$product_tax_id = ( int ) $nalog_group;						
						}
					}
				}
			}
			else
			{
				$product_tax_id = '0';
			}
			
			if (!isset ( $product_sku ) or $product_sku == '') //Если нет артикуля
			{
				$product_sku = substr((string)$product_data->Ид,0,8);
			}
			if ($status != 'Удален') //Если не помечен Удален
			{
				$sql = "SELECT product_sku, product_in_stock, product_id  FROM #__vm_product WHERE `product_sku` = '".$product_sku."' or `product_sku` = '".$product_sku."_".substr((string)$product_data->Ид,0,8)."'";
				$db->setQuery ( $sql );
				$rows = $db->loadObjectList ();				
				
				if (!empty($rows)) //Изменяем существующий продукт в случае полной выгрузки
				{
					$sql = "SELECT product_sku, product_in_stock, product_id  FROM #__vm_product WHERE `product_sku` = '".$product_sku."' or `product_sku` = '".$product_sku."_".substr((string)$product_data->Ид,0,8)."' AND `product_name` LIKE '".$product_name."'";
					$db->query ( $sql );
					$rows_2 = $db->loadObject ();
					if ($rows_2)
					{	
						$product_stock_base = $rows_2->product_in_stock;
						$product_id_base = $rows_2->product_id;
						
						$product_data_id = (string)$product_data->Ид;
						
						$sql = "SELECT product_price, product_price_id FROM #__vm_product_price where product_id = '" . $product_id_base . "' ORDER BY product_price_id ASC";
						$db->setQuery ( $sql );
						$rows_3 = $db->loadObjectList ();	
						$k = 0;
						foreach ($rows_3 as $prod_price)
						{
							$prod_price_id[$k] = $prod_price->product_price_id;
							$prod_price_item[$k] = $prod_price->product_price;
							$k = $k + 1;	
						}
					
						$modif = True;
					}
					else
					{
						if ($rows_2->product_sku != $product_sku."_".substr((string)$product_data->Ид,0,8))
						$product_sku = $product_sku."_".substr((string)$product_data->Ид,0,8);
						$modif = False;
					}
				}
				
								
				if ($modif == False) //Добавляем продукт
				{				
					$product_image = $product_data->Картинка;
					if (isset ( $product_image ) and $product_image <> '')
					{
						$img_id = 0;
						foreach ($product_data->Картинка as $product_img)
						{
							$product_img_small = substr ( $product_img, 16 );
							$new_img = rand(0,VM_MAXRAND);
							while(file_exists ( JPATH_BASE_PICTURE . DS . $new_img . '.jpg' ))
							{
								$new_img = rand(0,VM_MAXRAND);	
							}

							//Копируем картинки
							if (is_file ( JPATH_BASE_PICTURE . DS . $product_img ) )
							{
								copy(JPATH_BASE_PICTURE . DS . $product_img, JPATH_BASE_PICTURE . DS . $new_img . '.jpg');
								unlink(JPATH_BASE_PICTURE . DS . $product_img);
							}
							$width = '90';
							$height = '90';
							$src_img = JPATH_BASE_PICTURE . DS . $new_img . '.jpg';
							$out_img = JPATH_BASE_PICTURE . DS . 'resized' . DS . $new_img . '_'.$width.'x'.$height.'.jpg';
							$out_img200 = JPATH_BASE_PICTURE . DS . 'resized' . DS . $new_img . '_200x200.jpg';	
							$out_img500 = JPATH_BASE_PICTURE . DS . 'resized' . DS . $new_img . '_500x500.jpg';		
							//Изменяем размер картинки
							//$size_img200=img_resize($src_img, $out_img200, 200, 200, 0xFFFFFF, 100);
							$size_img500=img_resize($src_img, $src_img, 500, 500, 0xFFFFFF, 100);
							$size_img = img_resize($src_img, $out_img, $width, $height, 0xFFFFFF, 100);
							$new_width["$img_id"] = $size_img["new_width"];
							$new_height["$img_id"] = $size_img["new_height"];
							$old_width["$img_id"] = $size_img["old_width"];
							$old_height["$img_id"] = $size_img["old_height"];
							
							//Заносим данные о картинке в базу						
							if ($img_id == 0)
							{
								$product_full_image = $new_img . '.jpg';
								$product_thumb_image = 'resized/' . $new_img . '_'.$width.'x'.$height.'.jpg';
								$product_img_2 = 0;
							}
							else
							{
								$product_img_2 = 1;
								$product_img_2_name["$img_id"] = $new_img . '.jpg';
							}
							$img_id++;
						}
					}
					//Отбираем колл-во
					foreach ($xml2->xpath('//Предложение') as $xml2_gr) 
					{
						if ($xml2_gr->Ид == $product)
						{
							$product_val = (string)$xml2_gr->Количество;
							if (!isset ( $product_val ))
							{
								$product_val = "0";
							}
						}
					}
					
					$ins = new stdClass ();
					$ins->product_id = NULL;
					$ins->vendor_id 	=	$vendor_1C_ID;
					$ins->product_parent_id	=	'0';
					$ins->product_sku = $product_sku;
					$ins->product_name = $product_name;
					$ins->product_desc = (string)$product_data->ПолноеНаименование;
					$ins->product_publish = 'Y';
					$ins->product_available_date = time ();
					$ins->product_in_stock		 = $product_val;
					$ins->cdate = time ();
					$ins->mdate = time ();
					$ins->product_tax_id = $product_tax_id;
					
					$ins->product_full_image = $product_full_image;
					$ins->product_thumb_image = $product_thumb_image;
				
					if (! isset ( $product_ed )) {
						$ins->product_unit = 'piece';
					} else {
						$ins->product_unit = $product_ed;
					}
					$ins->child_options = 'N,N,N,N,N,N,20%,10%,';
					$ins->quantity_options = 'none,0,0,1';
				
					if (! $db->insertObject ( '#__vm_product', $ins, 'product_id' )) 
					{
						print 'error mysql 4';
					}
					
					$produkt_id = ( int ) $ins->product_id;
					
					if ($product_img_2 == 1)
					{
						foreach ($product_img_2_name as $key => $img_2_name)
						{
							$ins = new stdClass ();
							$ins->file_id = NULL;
							$ins->file_product_id = $produkt_id;
							$ins->file_name = '/components/com_virtuemart/shop_image/product/' . $img_2_name;
							$ins->file_title = (string)$product_data->Наименование;
							$ins->file_description = '';
							$ins->file_extension = 'jpeg';
							$ins->file_mimetype = 'image/pjpeg';
							$ins->file_url = 'components/com_virtuemart/shop_image/product/' . $img_2_name;
							$ins->file_published = '1';
							$ins->file_is_image = '1';
							$ins->file_image_height = $old_height["$key"];
							$ins->file_image_width = $old_width["$key"];
							$ins->file_image_thumb_height = $new_height["$key"];
							$ins->file_image_thumb_width = $new_width["$key"];
														
							if (! $db->insertObject ( '#__vm_product_files', $ins, 'file_id' )) 
							{
								print 'error mysql 5';
							} 
						}
					}
					
					//Добавляем производителя к продукту
					$ins = new stdClass ();
					$ins->product_id = $produkt_id;
					$ins->manufacturer_id = '0';
					
					if (! $db->insertObject ( '#__vm_product_mf_xref', $ins )) 
					{
						print 'error mysql 6';
					}
					
					//Добавляем категорию к продукту
					$ins = new stdClass ();
					if (VM_CATALOG == 'yes')
					{
						foreach ($product_data->Группы->Ид as $groups_data_id)
						{
							$product_cat_id = (string)$groups_data_id;
						}
						if (count($product_cat_id) > 1)
						{
							$cat_id = (int)$category ['category_id'] ['$id_tov'];
							$ins->category_id = $cat_id;
						}
						else
						{
							$cat_id = (int)$category ['category_id'] ['$id_tov'];
							$ins->category_id = $cat_id;
						}
					}
					else
					{					
						$ins->category_id = $category ['category_id'];
					}
					$ins->product_id = $produkt_id;
					$ins->product_list = '1';
					
					if (! $db->insertObject ( '#__vm_product_category_xref', $ins )) 
					{
						print 'error mysql 7';
					}
					
					//Добавляем цену к продукту
					$price_cash = $xml2->ПакетПредложений->Предложения;
					if (isset($price_cash))
					{
						//Разбиваем цену по ценовым группам
						$id = 0;
						foreach ($price_cash->Предложение as $price_data)
						{
							if ($price_data->Ид == $product)
							{
								foreach ($price_data->Цены->Цена as $price_tovar_data)
								{
									$id_tovar = '';
									$valyuta = '';
									$sh_group = 0;
									$valyuta_new = '';
									
									$valyuta = (string)$price_tovar_data->Валюта;
									if($valyuta == 'руб' or $valyuta == 'Руб' or $valyuta == 'рубль')
									{
										$valyuta_new = 'RUB';
									}
									elseif($valyuta == 'евр' or $valyuta == 'Евр' or $valyuta == 'евро')
									{
										$valyuta_new = 'EUR';
									}
									$id_tovar = (string) $price_tovar_data->ИдТипаЦены;
									
									// Проверяем наличие НДС
									$nds_nalog_tovar = '';
									$nds_nalog = array();
									$nds_nalog_id = $xml2->ПакетПредложений->ТипыЦен;
									foreach ($nds_nalog_id->ТипЦены as $nds_nalog_tovar)
									{
										if ($nds_nalog_tovar->Ид == $id_tovar)
										{
											$nds_nalog["$id_tovar"] = $nds_nalog_id->Налог;
										}
									}
									if (isset ( $nds_nalog["$id_tovar"] ) & VM_NDS == 'yes')
									{
										$price_new = (string)$price_tovar_data->ЦенаЗаЕдиницу;
										$price_new = str_replace(",", ".", $price_new);
										$price_new = $price_new*100/118; //Получаем цену без НДС
										$price_new = round($price_new,2);
									}
									else
									{
										$price_new = (string)$price_tovar_data->ЦенаЗаЕдиницу;
										$price_new = str_replace(",", ".", $price_new);
									}
									$db->setQuery ( "SELECT shopper_group_id FROM #__vm_shopper_group WHERE `shopper_group_name` = '".$group_name ["$id_tovar"]."'" );
									$sh_group = $db->loadResult ();
																		
									$ins = new stdClass ();
									$ins->product_price_id = NULL;
									$ins->product_id = $produkt_id;
									$ins->product_price = $price_new;
									$ins->product_currency = $valyuta_new;
									$ins->product_price_vdate = '0';
									$ins->product_price_edate = '0';
									$ins->cdate = time ();
									$ins->mdate = time ();
									$ins->shopper_group_id =  ( int ) $sh_group;
									$ins->price_quantity_start = '0';
									$ins->price_quantity_end = '0'; 
									
									if (! $db->insertObject ( '#__vm_product_price', $ins, 'product_price_id' )) 
									{
										print 'error mysql 8';
									}
								}
							}
						}
					}
				}
				
				//Применяем изменения
				if ($modif == True)
				{
					foreach ($xml2->xpath('//Предложение') as $keyxml => $xml2_gr2) 
					{
						//проверяем кол-во
						if ($xml2_gr2->Ид == $product)
						{
							$product_val = (string)$xml2_gr2->Количество;
							if (!isset ( $product_val ))
							{
								$product_val = "0";
							}
							//Изменяем кол-во товара
							if ($product_stock_base != $product_val)
							{
								$query = "UPDATE #__vm_product SET product_in_stock='".$product_val."' where product_id='".$product_id_base."'";
								$db->setQuery ( $query );
								$db->query ();
							}
						}
						//проверяем цену
						/*foreach ($xml2->ПакетПредложений->Предложения->Предложение[$keyxml]->Цены->Цена as $key => $xml2_cash_gr) 
						{
							if ($xml2_cash_gr->ИдТипаЦены == $id_cash_group[$key])
							{
								$new_cash = (string)$xml2_cash_gr->ЦенаЗаЕдиницу;
								
								//$db->setQuery ( "SELECT product_price, product_price_id FROM #__vm_product_price where product_id = '" . $product_id_base . "' AND shopper_group_id = '" . $new_cash_gr[$key] . "'" );
								//$rows2 = $db->loadObject ();
								
								//if ($rows2->product_price != $new_cash)
								//{
									$query = "UPDATE #__vm_product_price SET product_price='".$new_cash."' where product_id='".$product_id_base."' AND shopper_group_id = '" . $new_cash_gr[$key] . "'";
									$db->setQuery ( $query );
									$db->query ();
								//}	
							}
						}*/
					}
					//проверяем цену
					$var = count($xml2->ПакетПредложений->Предложения->Предложение)-1;
					for ($i = 0; $i <= $var; $i++)
					{
						$predlozhenie = (string)$xml2->ПакетПредложений->Предложения->Предложение[$i]->Ид;
						$predlozhenie_cash = (string)$xml2->ПакетПредложений->Предложения->Предложение[$i]->Цены;
						if ($predlozhenie == $product_data_id)
						{
							for ($s = 0; $s <= count($prod_price_item)-1; $s++)
							{
								$predlozhenie_cash_new = (string)$xml2->ПакетПредложений->Предложения->Предложение[$i]->Цены->Цена[$s]->ЦенаЗаЕдиницу;
								$predlozhenie_cash_new = str_replace(",", ".", $predlozhenie_cash_new);
								
								// Проверяем наличие НДС
								$id_tovar = (string) (string)$xml2->ПакетПредложений->Предложения->Предложение[$i]->Цены->Цена[$s]->ИдТипаЦены;
								$nds_nalog_tovar = '';
								$nds_nalog = array();
								$nds_nalog_id = $xml2->ПакетПредложений->ТипыЦен;
								foreach ($nds_nalog_id->ТипЦены as $nds_nalog_tovar)
								{
									if ($nds_nalog_tovar->Ид == $id_tovar)
									{
										$nds_nalog["$id_tovar"] = $nds_nalog_id->Налог;
									}
								}
								if (isset ( $nds_nalog["$id_tovar"] ) & VM_NDS == 'yes')
								{
									$predlozhenie_cash_new = $predlozhenie_cash_new*100/118; //Получаем цену без НДС
									$predlozhenie_cash_new = round($predlozhenie_cash_new,2);
								}
								if ($prod_price_item[$s] != $predlozhenie_cash_new && isset($predlozhenie_cash_new) && $predlozhenie_cash_new <> '')
								{
									$query = "UPDATE #__vm_product_price SET mdate='".time ()."' , product_price='".$predlozhenie_cash_new."' where product_price_id='".$prod_price_id[$s]."'";
									$db->setQuery ( $query );
									$db->query ();
								}
							}
						}
					}
									
					// Пока не делал
					//print "Erorr in new product";
				}				
				
			}
			
			$id_tov = $id_tov + 1;
			
		}
	}
	else
	{
		//print 'error 8';
	}
	
}

function img_resize($src, $out, $width, $height, $color = 0xFFFFFF, $quality = 100) 
{
    // Если файл не существует
    if (!file_exists($src)) {
        print 'error resize, file not load';  
    }

    // Получаем массив с информацией о размере и формате картинки (mime)
    $size = getimagesize($src);

    // Исходя из формата (mime) картинки, узнаем с каким форматом имеем дело
    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    //и какую функцию использовать для ее создания
    $picfunc = 'imagecreatefrom'.$format;

    // Вычилсить горизонтальное соотношение
    $gor = $width  / $size[0];
    // Вертикальное соотношение
    $ver = $height / $size[1];  

    // Если не задана высота, вычислить изходя из ширины, пропорционально
    if ($height == 0) {
        $ver = $gor;
        $height  = $ver * $size[1];
    }
	// Так же если не задана ширина
	elseif ($width == 0) {
        $gor = $ver;
        $width   = $gor * $size[0];
    }

    // Формируем размер изображения
    $ratio   = min($gor, $ver);
    // Нужно ли пропорциональное преобразование
    if ($gor == $ratio)
        $use_gor = true;
    else
        $use_gor = false;

    $new_width   = $use_gor  ? $width  : floor($size[0] * $ratio);
    $new_height  = !$use_gor ? $height : floor($size[1] * $ratio);
    $new_left    = $use_gor  ? 0 : floor(($width - $new_width)   / 2);
    $new_top     = !$use_gor ? 0 : floor(($height - $new_height) / 2);

    $picsrc  = $picfunc($src);
    // Создание изображения в памяти
    $picout = imagecreatetruecolor($width, $height);

    // Заполнение цветом
    imagefill($picout, 0, 0, $color);
    // Нанесение старого на новое
    imagecopyresampled($picout, $picsrc, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

    // Создание файла изображения
    imagejpeg($picout, $out, $quality);

    // Очистка памяти
    imagedestroy($picsrc);
    imagedestroy($picout);
	
	$size_img["new_width"] = $new_width;
	$size_img["new_height"] = $new_height;
	$size_img["old_width"] = $size[0];
	$size_img["old_height"] = $size[1];

    return $size_img;
}

function catalog_add($xml, $owner, $category) 
{
	global $db;
	global $log;

	$vendor_1C_ID = "1";
	$sub_cat_owner = array();
	$sub_cat_name = array();
	foreach($xml->Группа as $xml_data)
	{
		$sub_cat_owner['$xml_data->Ид'] = ( int ) $owner;
			
		$sub_cat_name['$xml_data->Ид'] = ( string ) $xml_data->Наименование;
		
		$db->setQuery ( "SELECT category_id FROM #__vm_category where category_name = '" . $sub_cat_name['$xml_data->Ид'] . "'" );
		$rows_sub_Count = $db->loadResult ();
			
		if (isset ( $rows_sub_Count ))
		{
			$category ['category_id'] ['$xml_data->Ид'] = ( int ) $rows_sub_Count;
		}
		else
		{	
			$ins = new stdClass ();
			$ins->category_id = NULL;
			$ins->category_name = $sub_cat_name['$xml_data->Ид'];
			$ins->category_description = $sub_cat_name['$xml_data->Ид'];
			$ins->vendor_id = $vendor_1C_ID;
			$ins->category_publish = 'Y';
			$ins->category_browsepage = 'browse_3';
			$ins->cdate = time ();
			$ins->mdate = time ();
			$ins->category_flypage = 'flypage.tpl';
			$ins->category_thumb_image = '';
			$ins->category_full_image = '';
			$ins->list_order = 1;
			
			if (! $db->insertObject ( '#__vm_category', $ins, 'category_id' )) 
			{
				print 'error mysql 9';
			}
			
			$category ['category_id'] ['$xml_data->Ид'] = ( int ) $ins->category_id;
			
			$ins = new stdClass ();
			$ins->category_parent_id = ( int ) $sub_cat_owner['$xml_data->Ид'];
			$ins->category_child_id = ( int ) $category ['category_id'] ['$xml_data->Ид'];
			$ins->category_list = null;
			if (! $db->insertObject ( '#__vm_category_xref', $ins )) 
			{
				print 'error mysql 10';
			}
			
			$log->addEntry ( array ('comment' => 'groups_create ' . $category ['category_id'] ['$xml_data->Ид'] . ";" . $sub_cat_name['$xml_data->Ид'] ) );
		}
		$count = count($xml_data->Группы->Группа);
			
		if ($count > 0)
		{
			catalog_add($xml_data->Группы, $category ['category_id'] ['$xml_data->Ид'], $category);	
		}
	}
	
	return $category;
}
?>