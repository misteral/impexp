<?php 

/**
 * Класс для работы с mysql
 * @author BobrovAV
 * @version = 0.0.2
 **/

require 'phpgdwatermarker.php';

class ex_Mysql {
	private $count = array(
		'add'=>0,
		'update'=>0,
		'skip'=>0
	); //названия счетчиков
	
    private $db;
 	  
    public function __construct(){
        $this->connect();
    }
    function __destruct(){
    	@mysql_close($this->db);
    }
    /**
     *Значение счетчика итераций к базе
     * @param unknown_type $name
     */
    function count_get($name){
    	return $this->count[$name];
    }
    function count_inc($name) {
    	 $this->count[$name]=$this->count[$name]+1;
    }
	function count_reset($name) {
		$this->count[$name]=0;
	}
    private function connect() {
		$user = 'root';
    	$host = 'localhost';
		$basename = 'sundmart';
		$pass = '';
		$this->db = @mysql_connect($host,$user,$pathword);
		if (!$this->db) 
		{
		echo " ( Not connected  MY SQL  ) ";
		$output=sprintf("Not connected  MY SQL",$output);
		};
		if (!@mysql_select_db($basename,$this->db))
		{
		echo " ( Not connected  MY SQL DB ) ";
		$output=sprintf("Not connected  MY SQL DB",$output);
		};
		$res = @mysql_query("SET NAMES utf8",$this->db);
	}
	private function query($q){
		$res = @mysql_query($q,$this->db);
		if(!$res){
			echo "Ошибка MYSQL говорит:", mysql_error();
			exit;
		}
		else{ return $res;}
	}
	private function query_arr($q){
		$res = @mysql_query($q,$this->db);
		if(!$res){
			echo "Ошибка MYSQL говорит:", mysql_error();
			exit;
		}
		else{ return mysql_fetch_array($res);mysql_free_result($res);}
	}
	
	
	
	/**
	 Добавляем в базу
	 * @param unknown_type $item_VM
	 */
	function add ($it){
	$query = "
	insert into jos_al_import(
--    product_id                  INT NOT NULL,
    product_parent_id,
    product_sku,
    product_desc,
    product_full_image,
    product_url,
    product_name,
    product_vendor,
    product_isgroup,
    product_price,
    product_status,
    product_date_add,
    product_ost,
    product_ed,
    product_min   
    )
    values (
    ".$it->product_parent_id.",
    '".$it->product_sku."',
    '".$it->product_desc."',
    '".$it->product_full_image."',
    '".$it->product_url."',
    '".$it->product_name."',
    ".$it->product_vendor.", 
    ".$it->product_isgroup.",  
   ".$it->product_price.",  
   ".$it->product_status.",  
   CURRENT_TIMESTAMP,  
   '".$it->product_ost."',  
      '".$it->product_ed."',         
    '".$it->product_min."');";
	$res = $this->query($query);
	$this->count_inc('add');
	}

	
	/**
	 * Обновляет путем итерации свойств объекта
	 * @param item_VM $itemVM
	 */
	function update (item_VM $itemVM, $id){
		//$id = $itemVM->product_id;
		foreach ($itemVM as $key => $value){
			if (!$value){continue;} //пропускаем если нет элемента
			$q = "update jos_al_import 
			set ".$key." = '".$value."'
			where product_id = ".$id."
			;"
			;
			$res = $this->query($q);
		}
		$this->count_inc('update'); 
	}
	
	/**
	 * Возвращает последний ID
	 * @return Ambigous <number, unknown>
	 */
	function last_id(){
	    $q = 'SELECT MAX(product_id) as id FROM jos_al_import;';
		$res = $this->query($q);
        $last = @mysql_fetch_array($res);
        $last = $last['id'];
        if (!$last){$last = 0;}
        return $last;	
	}
	
	
	/**
	 * Удаляет елемент по ID ...
	 * @param  $id
	 */
	function del($id){
		$q = 'delete from jos_al_import where product_id = '.$id;
		$res = $this->query($q);
	}

	
	/**
	 Новая или нет определяет по $product_name, $product_sku, $product_parent_id
	 * @param  $product_name
	 * @param  $product_sku
	 * @param  $product_parent_id
	 * @return true or false
	 */
	function isnew($product_name, $product_sku, $product_parent_id) {
		$q = "select COUNT(*) as cn from jos_al_import where
			product_name ='".$product_name."' 
			and product_sku = '".$product_sku."' 
			and product_parent_id = '".$product_parent_id."' 
			";
		$res = $this->query($q);
		$last = @mysql_fetch_array($res);
        $last = $last['cn'];
        if (!$last){$last = true;
        }else {$last = false;}
        return $last;	
	}
	
	
	/**
	 * Чистит базу
	 * @param unknown_type $product_vendor
	 */
	function clear($product_vendor = 0){
		if($product_vendor_id){
			$q= 'delete from jos_al_import where product_vendor ='.$product_vendor.';';
		}
		else{$q = 'truncate jos_al_import;';}
		$res = $this->query($q);
		
	}

	/**
	 * Не работает.
	 */
	function create(){
		$q="
		DROP TABLE IF EXISTS jos_al_import;
CREATE TABLE IF NOT EXISTS jos_al_import (
  product_id int(11) NOT NULL auto_increment,
  product_parent_id int(11) NOT NULL default '0',
  product_sku varchar(64) default NULL,
  product_desc text,
  product_thumb_image varchar(255) default NULL,
  product_full_image varchar(255) default NULL,
  product_url varchar(255) default NULL,
  product_name varchar(64) default NULL,
  product_vendor varchar(100) default NULL,
  product_isgroup tinyint(1) NOT NULL default '0',
  product_price               INT,
  product_status              SMALLINT,
  product_date_add            DATETIME,
  PRIMARY KEY  (product_id),
  KEY idx_product_product_id (product_id),
  KEY idx_product_sku (product_sku),
  KEY idx_product_name (product_name)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT=' Al import products ' ;
		";
	$res = $this->query($q);
	} 

/**Возвращает все подчиненные группы (весь второй уровень) + головные без подкатегорий */
	function child_gr(){
	$q = 'select * from jos_al_import where product_parent_id<>0 and product_isgroup = true
			union
			select * from jos_al_import where product_id not in (select distinct product_parent_id from jos_al_import)
 			and product_parent_id = 0 and product_isgroup = true';
//$q='select * from jos_al_import where product_id = 124';
	$res = $this->query($q);
	$e_arr = array();
	while  ($row = mysql_fetch_array($res)) {
        $c = new item_VM(); 
	    $c->product_id= $row['product_id'];                
	    $c->product_parent_id= $row['product_parent_id'];         
	    $c->product_sku= $row['product_sku'];                 
	    $c->product_desc = $row['product_desc'];               
	    $c->product_thumb_image = $row['product_thumb_image'];        
	    $c->product_full_image= $row['product_full_image'];          
	    $c->product_url = $row['product_url'];                
	    $c->product_name = $row['product_name'];              
	    $c->product_vendor = $row['product_vendor'];             
	    $c->product_isgroup = $row['product_isgroup'];            
	    $c->product_price = $row['product_price'];  
	    $c->product_status = $row['product_status'];           
        $e_arr[] = $c;
    }
    mysql_free_result($res);
    return $e_arr; 	
	}


/**Возвращает головные групп */
	function parent_gr() {
		$q = "select * from jos_al_import where product_parent_id = 0 and product_isgroup = 1";
		$res = $this->query($q);
		return $res;
	}
	
	/**
	 * Возвращает весь второй уровень ...
	 * @return null
	 */
	function child_gr2() {
		$q = "select * from jos_al_import where product_parent_id <> 0 and product_isgroup = 1";
		$res = $this->query($q);
		return $res;
	}


	
	/**
	 * Обновляет статус продукта и ставит дату в product_date_add 
	 * 1. Скачан
	 * @param unknown_type $status
	 * @param unknown_type $id
	 */
	function update_status($status, $id) {
		$q = "update jos_al_import
		set product_status = ".$status.",
		product_date_add = CURRENT_TIMESTAMP
		where product_id =".$id.";";
		$res = $this->query($q);
	}

	/**
	 * Возвращает статтус продукта и дату обновления
	 * @param unknown_type $id
	 * @return array
	 */
	function product_status($id) {
		$q = 'select product_status,product_date_add from jos_al_import where product_id ='.$id.';' ;
		$res = @mysql_fetch_array($this->query($q));
		return $res;
	}

	
	/**
	 * !!!!!!  не работает Удаляет категорию полностью включая подчиненные группы и товары
	 * не проверена
	 * @param unknown_type $cat_name,$vendor
	 */
	function delcat($cat_name,$vendor) {
		$q="select product_id from jos_al_import where product_name like '".$cat_name."' and product_parent_id = 0 and product_vendor =".$vendor;
		$res = $this->query($q);
		while  ($row = mysql_fetch_array($res)) { // идем по главным группам
			$q = "select product_id from jos_al_import where product_parent_id =".$row ;
			$res2 = $this->query($q);
			while  ($row2 = mysql_fetch_array($res2)) {// идем по подчиненным группа
				$q = "select product_id from jos_al_import where product_parent_id =".$row2 ;
				$res3 = $this->query($q);
				while  ($row3 = mysql_fetch_array($res3)) { // идем по товрарм
						$q = "delete from jos_al_import where product_parent_id =".$row3 ;
						$res4 = $this->query($q);
				}	
			}
		}
	}

	
	/**
	 * Смена статуса у группы с глубиной 3 сверху вниз
	 * @param unknown_type $cat_name
	 * @param unknown_type $status
	 * @param unknown_type $vendor
	 */
	function change_status($cat_name,$status,$vendor) {
		$q="select product_id from jos_al_import where product_name like '".$cat_name."' and product_parent_id = 0 and product_vendor =".$vendor;
		$res = $this->query($q);
		while  ($row = mysql_fetch_array($res)) { // идем по главным группам
			$q = "select product_id from jos_al_import where product_parent_id =".$row[product_id] ;
			$res2 = $this->query($q);
			while  ($row2 = mysql_fetch_array($res2)) {// идем по подчиненным группа
				$q = "select product_id from jos_al_import where product_parent_id =".$row2[product_id] ;
				$res3 = $this->query($q);
				while  ($row3 = mysql_fetch_array($res3)) { // идем по товрарм
						$this->update_status($status, $row3[product_id]);
				}
				mysql_free_result($res3);
				$this->update_status($status, $row2[product_id]);
				}
			$this->update_status($status, $row[product_id]);
			mysql_free_result($res2);
			}
		mysql_free_result($res);
	}

	/**
	 * Не дописана
	 * @param unknown_type $cat_name
	 * @param unknown_type $product_margin
	 * @param unknown_type $vendor
	 */
	function change_margin ($cat_name,$product_margin,$vendor){

		$q="select product_id from jos_al_import where product_name like '".$cat_name."' and product_parent_id = 0 and product_vendor =".$vendor;
		$res = $this->query($q);
		while  ($row = mysql_fetch_array($res)) { // идем по главным группам
			$q = "select product_id from jos_al_import where product_parent_id =".$row[product_id] ;
			$res2 = $this->query($q);
			while  ($row2 = mysql_fetch_array($res2)) {// идем по подчиненным группа
				$q = "select product_id from jos_al_import where product_parent_id =".$row2[product_id] ;
				$res3 = $this->query($q);
				while  ($row3 = mysql_fetch_array($res3)) { // идем по товрарм
						$this->update_status($status, $row3[product_id]);
				}
				mysql_free_result($res3);
				$this->update_status($status, $row2[product_id]);
				}
			$this->update_status($status, $row[product_id]);
			mysql_free_result($res2);
			}
		mysql_free_result($res);
		
	}
	
	function update_margin($product_id){
		$q = "";
	}
	
	/**
	 * Берет продукты из категории
	 * 
	 * @param  $id  парент
	 * @return array
	 */
	function get_product_from_parent($id) {
		$q = 'select * from jos_al_import where product_parent_id ='.$id;
		$res = $this->query($q);
		return $res;
	} //_get_product($id)

	function del_null_cat() {
		//идем сверху вниз на 3 уровня
		$q0 = 'select * from jos_al_import where product_parent_id = 0 and product_isgroup = true';
		$res0 = $this->query($q0);
		while ($row0 = mysql_fetch_array($res0)) {
			$q1 = 'select * from jos_al_import where product_parent_id = '.$row0['product_id'];
			$res1 = $this->query($q1);
			if (!mysql_num_rows($res1)){ //удаляем головную группу если нет ничего в ней 
				$this->del($row0['product_id']);
				continue;
				}
			while ($row1 = mysql_fetch_array($res1)) {
					$q2 = 'select * from jos_al_import where product_parent_id = '.$row1['product_id'];
					$res2 = $this->query($q2);
					if (!mysql_num_rows($res2)){ //удаляем головную группу если нет ничего в ней 
						$this->del($row1['product_id']);
						continue;
					}	
				}
		}
		
	} //del_null_cat($id)
	
	
	/**
	 * Берем все по ID продукта ...
	 * @param unknown_type $id
	 */
	function get_from_id($id) {
		$q = 'select * from jos_al_import where product_id ='.$id;
		$res = $this->query($q);
		return @mysql_fetch_array($res);
	} //_get_product($id)

	
	
	/**
	 * Берем все  со статусом 
	 * @param unknown_type $product_status
	 * return @mysql_fetch_array($res);
	 */
	function get_from_status ($product_status){
		$q = 'select * from jos_al_import where product_status ='.$product_status;
		$res = $this->query($q);
		if ($res){return $res;}
		return 0;
		
	}

	
	
	/**
	 * Берет Id по совпадению $product_name, $product_sku, $product_parent_id
	 * @param unknown_type $product_name
	 * @param unknown_type $product_sku
	 * @param unknown_type $product_parent_id
	 * @return id or false если нет такого 
	 */
	function get_id($product_name, $product_sku, $product_parent_id){
		$q = "select product_id as id from jos_al_import where
		product_name ='".$product_name."' 
		and product_sku = '".$product_sku."' 
		and product_parent_id = '".$product_parent_id."' 
		";
		$res = $this->query($q);
		$last = @mysql_fetch_array($res);
		if (@mysql_num_rows($last)>1){$this->errorer('get_id вернуло больше одного, полный дубляж категории');exit;}
		$last = $last['id'];
		if (!$last){return false;}// не нашел такого элемента
        return $last;	
	}


/** Trims an image then optionally adds padding around it.
* $im  = Image link resource
* $bg  = The background color to trim from the image
* $pad = Amount of padding to add to the trimmed image
*      (acts simlar to the "padding" CSS property: "top [right [bottom [left]]]")
*/
	function imagetrim(&$im, $bg, $pad=null){

    // Calculate padding for each side.
    if (isset($pad)){
        $pp = explode(' ', $pad);
        if (isset($pp[3])){
            $p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
        }else if (isset($pp[2])){
            $p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
        }else if (isset($pp[1])){
            $p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
        }else{
            $p = array_fill(0, 4, (int) $pp[0]);
        }
    }else{
        $p = array_fill(0, 4, 0);
    }

    // Get the image width and height.
    $imw = imagesx($im);
    $imh = imagesy($im);

    // Set the X variables.
    $xmin = $imw;
    $xmax = 0;

    // Start scanning for the edges.
    for ($iy=0; $iy<$imh; $iy++){
        $first = true;
        for ($ix=0; $ix<$imw; $ix++){
            $ndx = imagecolorat($im, $ix, $iy);
            if ($ndx != $bg){
                if ($xmin > $ix){ $xmin = $ix; }
                if ($xmax < $ix){ $xmax = $ix; }
                if (!isset($ymin)){ $ymin = $iy; }
                $ymax = $iy;
                if ($first){ $ix = $xmax; $first = false; }
            }
        }
    }

    // The new width and height of the image. (not including padding)
    $imw = 1+$xmax-$xmin; // Image width in pixels
    $imh = 1+$ymax-$ymin; // Image height in pixels

    // Make another image to place the trimmed version in.
    $im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);

    // Make the background of the new image the same as the background of the old one.
    $bg2 = imagecolorallocate($im2, ($bg >> 16) & 0xFF, ($bg >> 8) & 0xFF, $bg & 0xFF);
    imagefill($im2, 0, 0, $bg2);

    // Copy it over to the new image.
    imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);

    // To finish up, we replace the old image which is referenced.
    $im = $im2;
}

   /**
    * Ресайз изображения
    * @param unknown_type $src
    * @param unknown_type $out
    * @param unknown_type $width
    * @param unknown_type $height
    * @param unknown_type $color
    * @param unknown_type $quality
    * @return null
    */
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

	function add_logo($target_file,$logo,$quality=80,$overwrite = false) {
		$watermarker = new PhpGdWatermarker($logo, PhpGdWatermarker::VALIGN_TOP, PhpGdWatermarker::HALIGN_LEFT);
		$watermarker->setImageOverwrite($overwrite); // [OPTIONAL] Default is TRUE
		$watermarker->setEdgePadding(3); // [OPTIONAL] Default is 5
		$watermarker->setWatermarkedImageNamePostfix('logo_'); // [OPTIONAL] used IFF ImageOverwrite is FALSE, default is '_watermarked'
		
		if($watermarker->applyWaterMark($target_file,$quality)){
		    return 0;
		} else {
		    echo $watermarker->getLastErrorMessage();
			//$this->errorer($watermarker->getLastErrorMessage());
		};
	}
	
	/**
	 * Пишет в class log
	 * @param unknown_type $txt
	 */
	function errorer($txt){
		$er = new output('class');
		$er->add($txt);
	}
	
} // class my_sql


/**
 * Класс элемента товара в виртуе март
 * @author BobrovAV
 *
 */
class item_VM {
public  $product_id;
public  $product_parent_id;
public  $product_sku;
public  $product_desc;
public  $product_full_image;
public  $product_url;
public  $product_name;
public  $product_vendor;
public  $product_isgroup;
public  $product_price;
public  $product_status;
public  $product_date_add;
public  $product_ed;
public  $product_min; 
public  $product_ost;    
/*	public function __construct(){
        $q = 'SELECT MAX(product_id) FROM jos_al_import;';
		$res = $this->query($q);
        $this->product_id = $this->product_id+1;
		
	}*/
}  


/**
 * Класс закачки через курл и соранения файлов  
 * @author BobrovAV
 *
 */
class parse {
	public $sleep=5;
	public $proxy;
	public $try = 3;
	public $count = 0;
	
	
	/**
	 * Скачивает и схраняет файл и перекодирует из 1251 в utf ...
	 * @param unknown_type $target_url
	 * @param unknown_type $target_file
	 * @return Ambigous <number, number>
	 */
	function get_1251_to_UTF($target_url,$target_file){
	
	$p=0;
	while (!$res and $p < $this->try){
		$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
		// make the cURL request to $target_url
	    if (!$ch = curl_init()){return 'error :Не инициализирован CURL';}
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_URL, $target_url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_REFERER, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	    if($this->proxy) {curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy));} 
            //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,TRUE);
            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
		$res = curl_exec($ch);
		if (!$res){sleep($this->sleep);++$p;} //не скачался пауза в слееп
		else {++$this->count;}
	} //while 
	if (!$res){return 'error '.curl_error($ch);}
	!$res= mb_convert_encoding($res,'UTF8', "CP1251");
	$res = $this->save($target_file, $res);
	curl_close($ch);
	return 'ok';
	
	}//get_1251_to_UTF
	
	/**
	 * Не использовть
	 * @param unknown_type $target_url
	 * @param unknown_type $target_file
	 * @param unknown_type $day
	 * @return string
	 */
	function get_1251_to_UTF_chk_day($target_url,$target_file,$day){
	
	$p=0;
	while (!$res and $p < $this->try){
		$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
		// make the cURL request to $target_url
	    if (!$ch = curl_init()){return 'error :Не инициализирован CURL';}
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_URL, $target_url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_REFERER, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	    if($this->proxy) {curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy));} 
            //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,TRUE);
            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
		$res = curl_exec($ch);
		if (!$res){sleep($this->sleep);++$p;} //не скачался пауза в слееп
		else {++$this->count;}
	} //while 
	if (!$res){return 'error '.curl_error($ch);}
	!$res= mb_convert_encoding($res,'UTF8', "CP1251");
	$res = $this->save($target_file, $res);
	curl_close($ch);
	return 'ok';
	
	}//get_1251_to_UTF
	
	
	/**
	 * Сохраняем файл
	 * @param имя файла $target_file
	 * @param текст $target_text
	 * @return number
	 */
	function save($target_file, $target_text){
		if($this->sleep) {
            $z = mt_rand($this->sleep - 3,$this->sleep +4);
			sleep($z);
			$log = "генерация задержки в ".$z."\r\n";
        }
		$res = file_put_contents($target_file, $target_text);
		if (!$res){$log .= "Ошибка сохранения файла или закачки"."\r\n";}
		else {//echo ('Скачано байт = '.$res);
	return $res;
	}
	}//save
	/**
	 * Скачивает картинку в локальный каталог
	 * @param unknown_type $target_url
	 * @param unknown_type $target_file_name
	 */
	function get_img_to_file($target_url,$target_file_name){
	$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
	$referer = 'z-ecx.image-amazon.com';
	//$o = new output('Ошибки curl');
	$fp = fopen($target_file_name, 'wb');
	if (!$ch = curl_init()){
		//$o->add('Не инициализирован CURL');
		return 'error';}
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL, $target_url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
    if($this->proxy) {
            //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,TRUE);
            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy)); 
        } 	
    curl_setopt($ch, CURLOPT_FILE, $fp);
	if (!curl_exec($ch)){
		return 'error';
		//$o->add('Немогу скачать '.$target_url);	
	}
	curl_close($ch);
	fclose ($fp);	
	return 'ok';	
	}
	
	/**
	 * Качает в файл из $url количество $try попыток
	 * this->sleep  - между попытками закачать
	 * @param string $target_url
	 * @param string $target_file_name
	 * @param $try - поптыок скачать
	 * @return ok or errpr+
	 **/
	function get_url_to_file($target_url,$target_file_name,$try){
		$this->try = $try;
		$p=0;
		while (!$res and $p < $this->try){
//			if (file_exists($target_file_name) and filesize($target_file_name)){
//				$res = 'created';
//			}else{
				$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
				$referer = 'z-ecx.image-amazon.com';
				//$o = new output('Ошибки curl');
				$fp = fopen($target_file_name, 'wb');
				if (!$ch = curl_init()){return 'error :Не инициализирован CURL';}
				curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
				curl_setopt($ch, CURLOPT_URL, $target_url);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			    if($this->proxy) {  curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy)); }
			    //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,TRUE);
			    //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			    curl_setopt($ch, CURLOPT_FILE, $fp);
				$res =curl_exec($ch);
				if (!$res){sleep($this->sleep);++$p;} //не скачался пауза в слееп
				else {++$this->count;}
	//		} //else есть файл 
		} //while 
		if (!$res){
			return 'error '.curl_error($ch);
			//$o->add('Немогу скачать '.$target_url);	
		}
	curl_close($ch);
	fclose ($fp);
	return 'ok';

	
	}
} //parse


/**
  * Класс на обработку данных на выход
 * @author BobrovAV
 * 
 * vendor для заголовка файла
 *
 */
class output  {
	//private $arr = array();
	private $st_time;
	public $echo;
	public  $vendor;
	private $path_log;
	private $file;
	private $timer_time;
	
//Имя лог-файла, куда ведем запись
//  private $logfile;
  //Указатель на этот лог-файл
  //private $logd;
  //Данные из лог-файла
 // public $data;
  //Данные этой сессии
//  public $current_data=array();
	
	public function __construct($vendor){
		$this->path_log = dirname(dirname( __FILE__ )).DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
		
		$this->st_time = $this->TimeMeasure();
	   	$this->vendor=$vendor;
	   	$this->file = $this->path_log.$this->vendor .'_al.log';
	   	if (file_exists($this->file)){unlink($this->file);}
		
			
	}
	function __destruct() {
		$now_time = $this->exec_time(); //посчитаем время
		$min = round ($now_time/60);
		$sec = $now_time - 60*$min ;
		$this->add('Время выполнения скрипта '.$min.' мин., '.round($sec,3).' сек.') ;
	}
	
	/**
	 * Добавляет текст к логу
	 * @param unknown_type $txt
	 */
	function add($txt) {
		//$this->arr[]=($txt."\r\n"); 
		 $line  = '['.date('H:i:s').'] ';
        $line .= $txt."\r\n";;
        
		if ($this->echo){echo ($txt);	}
         file_put_contents($this->file, $line, FILE_APPEND );
		
	}
/*	/**
	 * Возвращает и записывает лог 
	 * @return string
	 
	function txt() {
		
		foreach ($this->arr as $value){
			$text .= $value; 
		}
		$tex= "\r\n".'Время выполнения скрипта '.($this->exec_time()) ;
		$this->arr[] = $tex;
		$text .=$tex;
		$res = file_put_contents('al.log', $text);
		if (!$res){Echo ("Ошибка сохранения файла лога");}
		return $text;
		
	}*/
	
	
	private function TimeMeasure() {
	$q =  explode(chr(32), microtime());
	list($msec, $sec)= $q;  
    return ($sec+$msec);
	}
	
	
	/**
	 * Возвращает время выполнения скрипта
	 * @return number
	 */
	private function exec_time(){
		return round($this->TimeMeasure()-$this->st_time, 6);
	}
	/**
	 * Проверяет ни пустой ли параметр если пустой пишет в логи txt
	 * @param unknown_type $par
	 * @param unknown_type $id
	 * @return number|null
	 */
	function ch($par,$txt) {
		 global  $skip;
		if (!$par){
			$this->add($txt);
			$skip = true;
			return 0;
		}
		return trim($par);
	}
	/**
	 * Проверка принадлежности к simple_html_dom
	 * @param unknown_type $par 
	 * @param unknown_type $txt вывод в лог 
	 */
	function chk($par,$txt) {
		 global  $skip;
		if ($par and $par instanceof simple_html_dom_node){
			return $par;
			}
		else {
			$this->add($txt);
			$skip = true;
		}
	}//f chk
	
	/**
	 * Засечь таймер(включить)
	 */
	function timer_start() {
		$this->timer_time = $this->TimeMeasure();;
	}
	
	/**
	 * Время засекания
	 * @return number
	 */
	function timer_get() {
		return round($this->TimeMeasure()-$this->timer_time, 6);
	}

} 
//output

?>