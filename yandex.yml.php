<?php
$cfg_name    = "Сувенирная лавка Сундучок"; // Название Вашего магазина
$cfg_company = "ИП Бобров А.В.";         // Название компании - владельца
$cfg_url     = "http://www.e-sunduchok.ru";                         // URL корня сайта
$cfg_delivery= "true";                                              // Возможность доставки
$cfg_delivery_cost = "100";                                      // Стоимость доставки
$cfg_currency= "RUR";                                             // Валюта в которой указаны Ваши цены
$cfg_sales_notes= "Доставка до транспортной компании";        //  Общие замечания о доставке


$file  = 'e-sunduchok_ru.yml';
if (file_exists($file)){unlink($file);}

//===================================================================


/***********************************************************
* Скрипт market.php коннектора к Yandex.Market от 18.04.2009 для магазина на движке Joomla Virtuemart
* авторы доработок:
*	Саид Дашук (dashuk@ngs.ru)
*	Вячеслав, он же DyaDya (www.free-lance.ru/users/DyaDya, www.vashmaster.ru) от 13 октября 2009
*
* Доработки под старую версию джумлы:
* 1. Подключение к настройкам Joomla (теперь не надо в скрипте прописывать реквизиты доступа к БД,
*         а достаточно указать конфигурационный файл 'configuration.php')
* 2. В скрипт добавлены комментарии для новичков
* 3. ВЫБОРКА ВСЕХ ТОВАРОВ ПРОИСХОДИТ ОДНИМ ЗАПРОСОМ!
* 4. Добавлен шаблон для описания товара (тег <description>)
* 5. Курсы валют, могут быть приравнены к курсу ЦБРФ, по умолчанию, считается, что цены в рублях!
* 6. Добавлены теги "доставка" и "производитель" (использовать их или нет, решайте сами)
* 7. Добавлена возможность фильтрации товаров по коду. Для этого нужно создать два файла с кодами товаров, примеры прилагаются.
* 8. Убраны дубли товаров, которые отправлялись в маркет, если товары были привязаны к нескольким категориям.
* 9. Решил отсылать заголовок MIME, чтобы правильно отображался XML-документ в ФФ (а не ТУПО как текст).
* 10. Добавил htmlspecialchars() для значений описания товара. А то часто пихают непонятные теги и &.
*
* Проверялось на Joomla! 1.5.9 Production/Stable / VirtueMart 1.1.2 stable

//  Внимание! Вы можете бесплатно использовать скрипт на свой страх и риск. За любые ошибки разработчики отвественности не несут.
**************************************************/

#http://www.flasher.ru/forum/archive/index.php/t-99223.html
header('Content-type: application/xml');
header("Content-Type: text/xml; charset=windows-1251");

include '../configuration.php';

#подключаем файл с массивом кодов товаров, которые должны идти в маркет
$ids_on=array();//список кодов, которые вывести в маркет
include('./ids_on.php');//закоментируйте инклуд, если нужно чтобы ВСЕ товары шли в маркет!

#подключаем файл с массивом кодов товаров, которые НЕ должны идти в маркет
$ids_off=array();//список кодов, которые не пускать в маркет
include('./ids_off.php');

#подключаем файл с массивом кодов категорий, которые НЕ должны идти в маркет
$cat_ids_off=array();//список кодов категорий, которые не пускать в маркет
include('./cat_ids_off.php');


$cfg_name = $mosConfig_fromname;
//Полное наименование компании, владеющей магазином. Не публикуется, используется для внутренней идентификации.
$cfg_company = $mosConfig_fromname;
//URL-адрес главной страницы магазина
$cfg_url = 'www.vashmaster.ru';//вписать адрес сайта магазина

// Шаблон для описания товара
// Вместо {product_name} - будет вставлено наименование товара
// Вместо {product_desc} - будет вставлено краткое описание
// ПРИМЕР:
$description_template = '{product_name} от производителя';
$description_template = '{product_desc}';

// Ставка за клик (в центах)
$bid = '10';

$hostname 				= $mosConfig_host;
$username 				= $mosConfig_user;
$password 				= $mosConfig_password;
$dbName 				= $mosConfig_db;
$category 				= $mosConfig_dbprefix."vm_category";
$category_xref 			= $mosConfig_dbprefix."vm_category_xref";
$userstable 			= $mosConfig_dbprefix."vm_product";
$pricetable 			= $mosConfig_dbprefix."vm_product_price";
$product_category_xref 	= $mosConfig_dbprefix."vm_product_category_xref";

mysql_connect($hostname,$username,$password) OR DIE("Не могу создать соединение ");
mysql_select_db($dbName) or die(mysql_error());

// Исправтье на нужное значение, если у вас другая кодировка в БД
mysql_query('set names cp1251');

echo"<?xml version='1.0' encoding='windows-1251'?>\n";
echo"<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n";
echo"<yml_catalog date=\"";
echo date('Y-m-d H:i');
echo"\">\n";
echo"<shop>\n";
echo"<name>$cfg_name</name>\n";
echo"<company>$cfg_company</company>\n";
echo"<url>http://$cfg_url/</url>\n";

// курсы валют, приравнены к курсу ЦБРФ
echo "<currencies>\n";
echo "<currency  id=\"RUR\" rate=\"1\"/>\n";
//echo"<currency  id=\"USD\" rate=\"CBRF\"/>\n";
//echo"<currency  id=\"EUR\" rate=\"CBRF\"/>\n";
//echo"<currency  id=\"UAH\" rate=\"1\"/>\n";
echo"</currencies>\n";

// Секция категорий
echo"<categories>\n";
$query_cat = "SELECT * FROM $category_xref";
$res_cat = mysql_query($query_cat) or die(mysql_error());
$rw=1;
while ($row_cat=mysql_fetch_array($res_cat)) {
	$cat_parent_id=$row_cat['category_parent_id'];
	$cat_child_id=$row_cat['category_child_id'];

		if ($cat_ids_off && in_array($cat_child_id, $cat_ids_off)){
		#если массив $cat_ids_off не пустой и в нём есть кода категории, то в маркет НЕЛЬЗЯ!
			continue;
		}

	$query2 = "SELECT category_name FROM $category WHERE category_id=".$row_cat['category_child_id'];
	$res_cat1 = mysql_query($query2) or die(mysql_error());
	$name_cat=mysql_fetch_array($res_cat1);
	$cat_name=$name_cat['category_name'];
	if ($cat_parent_id==0) {
		echo"<category id=\"".$cat_child_id."\">".htmlspecialchars($cat_name)."</category>\n";
	}
	else {
		echo"<category id=\"".$cat_child_id."\" parentId=\"".$cat_parent_id."\">".htmlspecialchars($cat_name)."</category>\n";
	}
	$rw++;
}
echo"</categories>\n";

// Секция описания товаров
echo"<offers>\n";

$tb_product 				= $mosConfig_dbprefix."vm_product";
$tb_manufacturer			= $mosConfig_dbprefix."vm_manufacturer";
$tb_product_mf_xref 		= $mosConfig_dbprefix."vm_product_mf_xref";
$tb_category				= $mosConfig_dbprefix."vm_category";
$tb_product_category_xref	= $mosConfig_dbprefix."vm_product_category_xref";
$tb_price					= $mosConfig_dbprefix."vm_product_price";

$query = "
SELECT
	$tb_product.product_id,
	$tb_product.product_name,
	$tb_manufacturer.mf_name,
	$tb_manufacturer.manufacturer_id,
	$tb_category.category_name,
	$tb_category.category_id,
	$tb_product_category_xref.category_id,
	$tb_price.product_price,
	$tb_product.product_sku,
	$tb_product.product_in_stock,
	$tb_product.product_unit,
	$tb_product.product_full_image,
	$tb_product.product_s_desc,
	$tb_product.product_weight
FROM
	($tb_product_category_xref
RIGHT JOIN ($tb_price
RIGHT JOIN (($tb_product_mf_xref
RIGHT JOIN $tb_product
ON $tb_product_mf_xref.product_id = $tb_product.product_id)
LEFT JOIN $tb_manufacturer
ON $tb_product_mf_xref.manufacturer_id = $tb_manufacturer.manufacturer_id)
ON $tb_price.product_id = $tb_product.product_id)
ON $tb_product_category_xref.product_id = $tb_product.product_id)
LEFT JOIN $tb_category
ON $tb_product_category_xref.category_id = $tb_category.category_id
WHERE $tb_product.product_publish='Y'
";

$row = d2a($query);
$product_log = Array();

$tmp=array();//чтобы не было повторных товаров, если они висят в нескольких рубриках!

for($i=0;$i<count($row);$i++) {
	if (!in_array($row[$i]['product_id'], $product_log) AND ($row[$i]['product_price'])) {
		$product_log[] = $row[$i]['product_id'];

#$url="http://$cfg_url/index.php?page=shop.product_details&amp;flypage=flypage-ask.tpl&amp;product_id=".$row[$i]['product_id']."&amp;category_id=".$row[$i]['category_id']."&amp;manufacturer_id=".$row[$i]['manufacturer_id'];
		$url="http://$cfg_url/index.php?page=shop.product_details&amp;flypage=flypage.tpl&amp;product_id=".$row[$i]['product_id']."&amp;category_id=".$row[$i]['category_id']."&amp;option=com_virtuemart&amp;from=market";

		
		$product_full_image = "http://$cfg_url/components/com_virtuemart/shop_image/product/".$row[$i]['product_full_image'];
		$tags = Array ('{product_name}', '{product_desc}');
		$repl = Array ($row[$i]['product_name'], $row[$i]['product_s_desc']);
		$product_price = substr($row[$i]['product_price'], 0, -3);
		$product_cat_id=$row[$i]['category_id'];

		if ($cat_ids_off && in_array($product_cat_id, $cat_ids_off)){
		#если массив $cat_ids_off не пустой и в нём есть кода категории, то в маркет НЕЛЬЗЯ!
			continue;
		}

		if ($ids_on && !in_array($row[$i]['product_id'], $ids_on)){
		#если массив $ids_on не пустой, но в нём нет кода товара, то в маркет НЕЛЬЗЯ!
			continue;
		}

		if ($ids_off && in_array($row[$i]['product_id'], $ids_off)){
		#если массив $ids_off не пустой и в нём есть кода товара, то в маркет НЕЛЬЗЯ!
			continue;
		}
		
		if (in_array($row[$i]['product_id'], $tmp)){
		#Дубль. Товар уже ушёл в маркет. 
			continue;
		}
		$tmp[]=$row[$i]['product_id'];

# пропустить категорию
#		if ($product_cat_id==61){
#			continue;
#		}

		echo"\n<offer id=\"".$row[$i]['product_id']."\" available=\"true\" bid=\"$bid\">\n";
		echo"<url>".$url."</url>\n";
		echo"<price>$product_price</price>\n";
		// Валюта в которой указаны Ваши цены
		echo"<currencyId>RUR</currencyId>\n";
		echo"<categoryId>".$product_cat_id."</categoryId>\n";
		echo"<picture>".$product_full_image ."</picture>\n";
		// Возможность доставки
		echo"<delivery>true</delivery> \n";
		echo"<name>".htmlspecialchars(strip_tags($row[$i]['product_name']))."</name>\n";
		echo"<description>".htmlspecialchars(strip_tags(str_replace($tags, $repl, $description_template)))."</description>\n";
#		echo"<sales_notes><sales_notes>\n";
		echo"</offer>\n";
	}
}

echo"</offers>\n";
echo"</shop>\n";
echo"</yml_catalog>\n";

function d2a($query){
    $result = mysql_query($query) or die("Query failed : " . mysql_error());
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {$res[] = $line;}
    mysql_free_result($result);
    return $res;
}
function add($txt){
	global $file;	
	//$line = $txt."\r\n";
 	file_put_contents($file, $txt, FILE_APPEND );
}


//===================================================================
include '../configuration.php';
$cfg = new JConfig();
$hostname = $cfg->host; 
$username = $cfg->user; 
$password = $cfg->password; 
$dbName   = $cfg->db; 
$category = "jos_vm_category";
$category_xref = "jos_vm_category_xref";
$userstable = "jos_vm_product"; 
$pricetable = "jos_vm_product_price";
$product_category_xref =  "jos_vm_product_category_xref";



mysql_connect($hostname,$username,$password) OR DIE("Не могу создать соединение "); 
mysql_select_db($dbName) or die(mysql_error());
mysql_query('set names utf8'); 

add ("<?xml version='1.0' encoding='UTF-8'?>\n");
add ( "<!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>\n");
add ( "<yml_catalog date=\"".date('Y-m-d H:i')."\">\n"); 
add ( "<shop>\n");
add ( "<name>".$cfg_name."</name>\n");
add ( "<company>".$cfg_company."</company>\n");
add ( "<url>".$cfg_url."</url>\n");

add ( "<currencies>\n");
add ( "<currency  id=\"RUR\" rate=\"1\"/>\n");
add ( "<currency  id=\"USD\" rate=\"CBRF\"/>\n");
add ( "<currency  id=\"EUR\" rate=\"CBRF\"/>\n");
add ( "</currencies>\n");

add ( "<categories>\n");
$query_cat = "SELECT * FROM $category_xref"; 
$res_cat = mysql_query($query_cat) or die(mysql_error()); 
$rw=1; 
while ($row_cat=mysql_fetch_array($res_cat)) { 
$cat_parent_id=$row_cat['category_parent_id'];
$cat_child_id=$row_cat['category_child_id'];
$query2 = "SELECT category_name FROM $category WHERE category_publish='Y' and category_id=".$row_cat['category_child_id'];
$res_cat1 = mysql_query($query2) or die(mysql_error()); 
$name_cat=mysql_fetch_array($res_cat1);
$cat_name=$name_cat['category_name'];

if ($cat_name) {
if ($cat_parent_id==0) {
add ( "<category id=\"".$cat_child_id."\">".$cat_name."</category>\n");
}
else {            
add ( "<category id=\"".$cat_child_id."\" parentId=\"".$cat_parent_id."\">".$cat_name."</category>\n");
}
}
$rw++;
}

add ("</categories>\n");
add ("<offers>\n");

$tb_product             = $cfg->dbprefix."vm_product";
$tb_manufacturer        = $cfg->dbprefix."vm_manufacturer";
$tb_product_mf_xref         = $cfg->dbprefix."vm_product_mf_xref";
$tb_category            = $cfg->dbprefix."vm_category";
$tb_product_category_xref    = $cfg->dbprefix."vm_product_category_xref";
$tb_price            = $cfg->dbprefix."vm_product_price";

$query = " SELECT
$tb_product.product_id,
$tb_product.product_name,
$tb_manufacturer.mf_name,
$tb_manufacturer.manufacturer_id,
$tb_category.category_name,
$tb_category.category_id,
$tb_product_category_xref.category_id,
$tb_price.product_price,
$tb_product.product_sku,
$tb_product.product_in_stock,
$tb_product.product_unit,
$tb_product.product_thumb_image,
$tb_product.product_s_desc,
$tb_product.product_weight
FROM
($tb_product_category_xref
RIGHT JOIN ($tb_price
RIGHT JOIN (($tb_product_mf_xref
RIGHT JOIN $tb_product
ON $tb_product_mf_xref.product_id = $tb_product.product_id)
LEFT JOIN $tb_manufacturer
ON $tb_product_mf_xref.manufacturer_id = $tb_manufacturer.manufacturer_id)
ON $tb_price.product_id = $tb_product.product_id)
ON $tb_product_category_xref.product_id = $tb_product.product_id)
LEFT JOIN $tb_category
ON $tb_product_category_xref.category_id = $tb_category.category_id
WHERE $tb_product.product_publish='Y' and $tb_product.product_in_stock>0
";

$row = d2a($query);
$product_log = Array();
for($i=0;$i<count($row);$i++) {
if (!in_array($row[$i]['product_id'],$product_log) AND ($row[$i]['product_price'])) {
$product_log[] = $row[$i]['product_id'];
$url=$cfg_url."/index.php?page=shop.product_details&amp;product_id=".$row[$i]['product_id']."&amp;option=com_virtuemart";
$product_thumb_image = $cfg_url."/components/com_virtuemart/shop_image/product/".$row[$i]['product_thumb_image'];
$tags = Array ('{product_name}','{product_desc}');
$repl = Array ($row[$i]['product_name'],$row[$i]['product_s_desc']);
$desc = $row[$i]['product_s_desc'];
$product_price = substr($row[$i]['product_price'], 0, -3);
$product_cat_id=$row[$i]['category_id'];
$product_sku=$row[$i]['product_sku'];
$category_name=$row[$i]['category_name'];
$mf_name=$row[$i]['mf_name'];

add ("\n<offer id=\"".$row[$i]['product_id']."\" available=\"true\" >\n");
add ("<url>".$url."</url>\n");
add ("<price>".$product_price."</price>\n");
add ("<currencyId>".$cfg_currency."</currencyId>\n");       
add ("<categoryId>".$product_cat_id."</categoryId>\n");
add ("<picture>".$product_thumb_image."</picture>\n");
add ("<delivery>".$cfg_delivery."</delivery> \n"); 
add ("<local_delivery_cost>".$cfg_delivery_cost."</local_delivery_cost> \n");
add ( "<name>");
add ( HtmlSpecialChars(strip_tags($row[$i]['product_name'])));
add ( "</name>\n");
add ("<vendor>".$mf_name."</vendor>\n");
add ("<vendorCode>".HtmlSpecialChars($product_sku)."</vendorCode>\n");
add ("<description>".HtmlSpecialChars($desc)."</description>\n");
add ( "<sales_notes>".$cfg_sales_notes."</sales_notes> \n");
add ("</offer>\n");
}
}
add ("</offers>\n");
add ("</shop>\n");
add ("</yml_catalog>\n");
function d2a($query){

$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {$res[] = $line;}
mysql_free_result($result);

return $res;

}



?>