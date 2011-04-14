<?php
require ('include/sund.class.php');

$db_my  = new ex_Mysql();


ini_set ( 'display_errors', '1' );
error_reporting ( E_ALL );
define ( '_JEXEC', 1 );
define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'JPATH_BASE', dirname(dirname ( __FILE__ )) . '' );
define ( 'JPATH_BASE_PICTURE', JPATH_BASE .DS.'components'.DS.'com_virtuemart'.DS.'shop_image'.DS.'product');
define ( 'JPATH_BASE_PICTURE_SMALL', JPATH_BASE_PICTURE .DS.'resized' );

require_once (JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once (JPATH_BASE . DS . 'includes' . DS . 'framework.php');
require (JPATH_BASE.DS. 'libraries' .DS. 'joomla' .DS. 'factory.php');

$mainframe = & JFactory::getApplication ( 'site' );
$mainframe->initialise ();
$db = & JFactory::getDBO ();
jimport ( 'joomla.error.log' );
jimport ( 'joomla.user.helper' );
$log = &JLog::getInstance ( 'sima-uploader.log' );

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



$sql = "SELECT *  FROM #__vm_product ";
$db->
$rows_2 = $db->loadObject ();

?>