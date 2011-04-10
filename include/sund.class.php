<?php 
//require_once 'ErrorManager.php';

/**
 * Класс для работы с mysql
 * @author BobrovAV
 *
 */
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
	function update ($it){
		
	}
	function last_id(){
	    $q = 'SELECT MAX(product_id) as id FROM jos_al_import;';
		$res = $this->query($q);
        $last = @mysql_fetch_array($res);
        $last = $last['id'];
        if (!$last){$last = 0;}
        return $last;	
	}
	function del($it){}
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
/**Возвращает подчиненные группы */
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
/**Возвращает головные группы */
	function parent_gr() {
		
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
	 * Берет продукты из категории
	 * @param unknown_type $id
	 * @return array
	 */
	function get_product($id) {
		$q = 'select * from jos_al_import where product_parent_id ='.$id;
		$res = $this->query($q);
		return $res;
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
	/**
	 * Скачивает и схраняет файл и перекодирует из 1251 в utf ...
	 * @param unknown_type $target_url
	 * @param unknown_type $target_file
	 * @return Ambigous <number, number>
	 */
	function get_1251_to_UTF($target_url,$target_file){
	$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
	// make the cURL request to $target_url
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL, $target_url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_REFERER, '');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	
    if($this->proxy) {
            //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL,TRUE);
            //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy)); 
        } 
	
	
	$html= curl_exec($ch);
	if (!$html) {
		echo "<br />cURL error number:" .curl_errno($ch);
		echo "<br />cURL error:" . curl_error($ch);
		return $res = 0;
	}
	$html= mb_convert_encoding($html,'UTF8', "CP1251");
	$res = $this->save($target_file, $html);
	return $res;
	}

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
		$this->add("\r\n".'Время выполнения скрипта '.$this->exec_time().' сек.' ) ;
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
} //output






?>