<?php

$cfg_name    = "Интернет-магазин ненужных вещей"; // Название Вашего магазина
$cfg_company = "Василий Пупкин и Партнеры";         // Название компании - владельца
$cfg_url     = "http://vasa-pupkin.ru";                         // URL корня сайта
$cfg_delivery= "true";                                              // Возможность доставки
$cfg_delivery_cost = "300";                                      // Стоимость доставки
$cfg_currency= "RUR";                                             // Валюта в которой указаны Ваши цены
$cfg_sales_notes= "Доставка в пределах МКАД";        //  Общие замечания о доставке
//==============================================
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

echo "<?xml version='1.0' encoding='UTF-8'?>\n";
echo "<!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>\n";
echo "<yml_catalog date=\"".date('Y-m-d H:i')."\">\n"; 
echo "<shop>\n";
echo "<name>".$cfg_name."</name>\n";
echo "<company>".$cfg_company."</company>\n";
echo "<url>".$cfg_url."</url>\n";

echo "<currencies>\n";
echo "<currency  id=\"RUR\" rate=\"1\"/>\n";
echo "<currency  id=\"USD\" rate=\"CBRF\"/>\n";
echo "<currency  id=\"EUR\" rate=\"CBRF\"/>\n";
echo "</currencies>\n";

echo "<categories>\n";
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
echo "<category id=\"".$cat_child_id."\">".$cat_name."</category>\n";
}
else {            
echo "<category id=\"".$cat_child_id."\" parentId=\"".$cat_parent_id."\">".$cat_name."</category>\n";
}
}
$rw++;
}

echo"</categories>\n";
echo"<offers>\n";

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

echo"\n<offer id=\"".$row[$i]['product_id']."\" available=\"true\" >\n";
echo"<url>".$url."</url>\n";
echo"<price>".$product_price."</price>\n";
echo"<currencyId>".$cfg_currency."</currencyId>\n";       
echo"<categoryId>".$product_cat_id."</categoryId>\n";
echo"<picture>".$product_thumb_image."</picture>\n";
echo"<delivery>".$cfg_delivery."</delivery> \n"; 
echo"<local_delivery_cost>".$cfg_delivery_cost."</local_delivery_cost> \n";
echo "<name>";
echo HtmlSpecialChars(strip_tags($row[$i]['product_name']));
echo "</name>\n";
echo"<vendor>".$mf_name."</vendor>\n";
echo"<vendorCode>".HtmlSpecialChars($product_sku)."</vendorCode>\n";
echo"<description>".HtmlSpecialChars($desc)."</description>\n";
echo "<sales_notes>".$cfg_sales_notes."</sales_notes> \n";
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


?>