<?php
namespace gaucho;

use Medoo\Medoo;

class Kit{
	var $db;
	function asset($urls,$print=true,$autoIndent=true){
		if(is_string($urls)){
			$arr[]=$urls;
			$urls=$arr;
		}
		$out=null;
		foreach($urls as $key=>$url){
			if(isset($_ENV['THEME'])){
				$url='src/view/'.$_ENV['THEME'].'/'.$url;
			}
			$filename=$this->root().'/'.$url;
			$path_parts=pathinfo($url);
			$ext=$path_parts['extension'];
			if(file_exists($filename)){
				$md5=md5_file($filename);
				if(isset($_ENV['SITE_URL'])){
					$url=$_ENV['SITE_URL'].'/'.$url."?$md5";
				}else{
					$url=$url."?$md5";
				}
				if($autoIndent and $key<>0){
					$out.=chr(9).chr(9);
				}
				if($ext=='css'){
					$out.='<link rel="stylesheet" href="'.$url.'" />';
				}
				if($ext=='js'){
					$out.='<script type="text/javascript" src="';
					$out.=$url.'"></script>';
				}
				$out.=PHP_EOL;
			}
		}
		$out=trim($out);
		if($print){
			print $out;
		}else{
			return $out;
		}
	}
	function batch($arr,$length){
		$offset=0;
		$pages=[];
		$count=count($arr);
		if($count<=$length){
			$limit=1;
		}else{
			$times=bcdiv($count,$length);
			$mod=bcmod($count,$length);
			if($mod>=1){
				$plus=1;
			}else{
				$plus=0;
			}
			$limit=$times+$plus;
		}
		$batch=[];
		$i=1;
		$offset=0;
		while($i<=$limit){
			$batch[$i]=array_slice($arr,$offset,$length,true);		
			$offset=$offset+$length;		
			$i++;
		}
		return $batch;
	}
	function code($httpCode){
		http_response_code($httpCode);
	}
	function controller($name){
		$root=$this->root();
		$className=$name.'Controller';
		$filename=$root.'/src/controller/'.$className.'.php';
		if(file_exists($filename)){
			require $filename;
			$ns='src\controller\\'.$className;
			$obj=new $ns();
			return $obj;
		}else{
			die('controller <b>'.$filename.'</b> not found');
		}
	}
	function db(){
		if(!is_object($this->db)){
			$this->db=new Medoo([
				'type' => 'mysql',
				'host' => $_ENV['DB_HOST'],
				'database' => $_ENV['DB_NAME'],
				'username' => $_ENV['DB_USER'],
				'password' => $_ENV['DB_PASSWORD'],
				'charset' => 'utf8mb4',
				'collation' => 'utf8mb4_general_ci',
				'port' => 3306
			]);
		}
		return $this->db;		
	}
	function dom($html){
		require_once __DIR__.'/simple_html_dom.php';
		return str_get_html($html);
	}
	function endTime($start_str){
		$end_str=microtime(1);
		if(!function_exists('bcdiv')){
			die("composer require phpseclib/bcmath_compat");
		}
	    return bcsub($end_str,$start_str,3);//tempo em segundos
	}
	function isCli(){
		if (php_sapi_name() == "cli") {
			return true;
		} else {
			return false;
		}
	}	
	function markdown($str,$html=false){
		$Parsedown = new \Parsedown();
		$Parsedown->setMarkupEscaped($html);
		return $Parsedown->text($str);
	}	
	function method($raw=false){
		$method=$_SERVER['REQUEST_METHOD'];
		if($raw){
			return $method;
		}else{
			if($method=='POST'){
				return 'POST';
			}else{
				return 'GET';
			}
		}
	}
	function model($name){
		$root=$this->root();
		$className=$name.'Model';
		$filename=$root.'/src/model/'.$className.'.php';
		if(file_exists($filename)){
			require $filename;
			$ns='src\model\\'.$className;
			$obj=new $ns();
			return $obj;
		}else{
			die('model <b>'.$filename.'</b> not found');
		}
	}	
	function json($data,$print=true){
		$str=json_encode($data,JSON_PRETTY_PRINT);
		if($print){
			header('Content-Type: application/json');
			die($str);
		}else{
			return $str;
		}
	}
	function redirect($url){
		header('Location: '.$url);
		die();
	}	
	function root(){
		return realpath(__DIR__.'/../../../../');
	}
	function segment($segmentId=null){
		$str=$_SERVER["REQUEST_URI"];
		$str=@explode('?', $str)[0];
		$arr=explode('/', $str);
		$arr=array_filter($arr);
		$arr=array_values($arr);
		if(count($arr)<1){
			$segment[1]='/';
		}else{
			$i=1;
			foreach ($arr as $key => $value) {
				$segment[$i++]=$value;
			}
		}
		if(is_null($segmentId)){
			return $segment;
		}else{
			if(isset($segment[$segmentId])){
				return $segment[$segmentId];
			}else{
				return false;
			}
		}
	}
	function showErrors($display_errors=true){
		if(isset($_ENV['DISPLAY_ERRORS'])){
			$display_errors=$_ENV['DISPLAY_ERRORS'];
		}
		if($display_errors){
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(E_ALL);
		}else{
			ini_set('display_errors', 0);
			ini_set('display_startup_errors', 0);
			error_reporting(0);
		}
	}
	function startTime(){
		return microtime(1);
	}
	function updateBatch($table,$data){
		$sql='';
		foreach ($data as $value) {
			$where=[
				'id'=>$value['id']
			];
			ob_start();
			$this->db()->debug()->update($table,$value,$where);
			$str=ob_get_clean();
			$sql.=$str.';'.PHP_EOL;
		}
		return $this->db()->query($sql)->fetchAll();
	}
	function random($tamanho=11){
		$str='0123456789';
		$str .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
		$randomStr = '';
		$len=mb_strlen($str);
		for ($i = 0; $i < $tamanho; $i++) {
			$randomStr .= $str[rand(0,$len-1)];
		}
		return $randomStr;
	}
	function view($name,$data=[],$print=true){
		$filename=$this->root().'/src/view/'.$_ENV['THEME'].'/';
		$filename.=$name.'.php';
		if(file_exists($filename)){
			$data['data']=$data;
			extract($data);
			if($print){
				require $filename;
			}else{
				ob_start();
				require $filename;
				$output=ob_get_contents();
				ob_end_clean();
				return $output;
			}
		}else{
			$str='<b>'.$filename.'</b> not found';
			die($str);
		}
	}
}
