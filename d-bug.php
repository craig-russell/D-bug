<?php

class D {
	//check if it's safe to show debug output
	public static function bugMode() {
		if(!self::bugWeb())
			return true;
		
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
		
		return $ip == '127.0.0.1' || preg_match('/^192\.168\./S', $ip);
	}
	
	//check if the script is running on the web
	public static function bugWeb() {
		return php_sapi_name() != 'cli';
	}
	
	//dump a variable
	public static function bug($var, $dump = false, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo '<pre>';
		
		if($dump)
			var_dump($var);
		else
			print_r($var);
		
		if(self::bugWeb())
			echo "</pre>";
		echo "\n";
		
		if($exit)
			exit;
	}
	
	//generate a backtrace
	public static function backtrace($exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo '<pre>';
		
		debug_print_backtrace();
		
		if(self::bugWeb())
			echo "</pre>\n";
		
		if($exit)
			exit;
	}
	
	//dump a variable, and provide type info
	//recurses one level into arrays and objects
	//provides extended info about methods and properties
	public static function bugType($var, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo "<pre>\n";
		
		if(!array(gettype($var), array('resource', 'unknown', 'NULL'))) {
			echo '(' . gettype($var) . ")\n";
		}
		else if(!in_array(gettype($var), array('object', 'array'))) {
			if(gettype($var) == 'boolean')
				echo '(' . gettype($var) . ') ' . ($var ? 'true' : 'false'), "\n";
			else
				echo '(' . gettype($var) . ') ' . $var, "\n";
		}
		else if(gettype($var) == 'array') {
			echo '(' . gettype($var) . ")\n\n";
			
			foreach($var as $k => $v) {
				$type = gettype($v);
				echo "\t";
				echo $k, ' => (';
				if(in_array($type, array('array', 'resource', 'unknown', 'NULL')))
					echo $type, ")\n";
				elseif($type == 'object')
					echo 'object ', get_class($v) . ")\n";
				elseif($type == 'boolean')
					echo $type . ') ' . ($v ? 'true' : 'false'), "\n";
				else
					echo $type . ') ' . $v, "\n";
			}
		}
		else if(gettype($var) == 'object') {
			echo '(' . gettype($var) . ")\n\n";
			
			$class = get_class($var);
			echo 'Class: ' . $class, "\n\n";
			
			echo "Ancestors:\n";
			$ancestorClass = $class;
			while(true) {
				$ancestorClass = get_parent_class($ancestorClass);
				if($ancestorClass)
					echo "\t", $ancestorClass, "\n";
				else
					break;
			}
			
			//output a list of methods
			echo "\nMethods:\n";
			$reflectionClass = new ReflectionClass($class);
			$methods = $reflectionClass->getMethods();
			if($methods) {
				foreach($methods as $method) {
					//get a pretty list of parameters for this method
					$method->setAccessible(true);
					$params = $method->getParameters();
					foreach($params as $k => $v)
						$params[$k] = preg_replace('/(^Parameter #\d+ \[ | \]$)/S', '', $v);
					
					echo "\t", self::_getVisibility($method), ' ';
					if($method->isStatic())
						echo 'static ';
					echo $method->getName(), '(', implode(', ', $params), ")\n";
				}
			}
			else
				echo "\tNo methods\n";
			echo "\nVars:\n";
			
			//output a list of properties
			$properties = $reflectionClass->getProperties();
			$pretty = array();
			foreach($properties as $property) {
				$property->setAccessible(true);
				$k = $property->getName();
				$v = $property->getValue($var);
				
				$type = gettype($v);
				echo "\t", self::_getVisibility($property), ' ';
				if($property->isStatic())
					echo 'static ';
				echo '$', $k, ' = (';
				if(in_array($type, array('array', 'resource', 'unknown', 'NULL')))
					echo $type, ")\n";
				elseif($type == 'object')
					echo 'object ', get_class($v) . ")\n";
				elseif($type == 'boolean')
					echo $type . ') ' . ($v ? 'true' : 'false'), "\n";
				else
					echo $type . ') ' . $v, "\n";
			}
		}
		
		if(self::bugWeb())
			echo "\n</pre>";
		echo "\n";
		
		if($exit)
			exit;
	}
	
	//check reflection object's visibility
	protected static function _getVisibility($obj) {
		$obj->setAccessible(true);
		if($obj->isPublic())
			return 'public';
		elseif($obj->isProtected())
			return 'protected';
		else
			return 'private';
	}
	
	//dump the php ini settings
	public static function ini($exit = true) {
		self::bug(ini_get_all(), false, $exit);
	}
}