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
	//provides extended info about currently visible methods and properties
	public static function bugType($var, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo "<pre>\n";
		
		if(!array(gettype($var), array('resource', 'unknown', 'NULL'))) {
			echo '(' . gettype($var) . ")\n";
		}
		else if(!in_array(gettype($var), array('object', 'array', 'resource', 'unknown', 'NULL'))) {
			if(gettype($var) == 'boolean')
				echo '(' . gettype($var) . ') ' . ($var ? 'true' : 'false'), "\n";
			else
				echo '(' . gettype($var) . ') ' . $var, "\n";
		}
		else {
			echo '(' . gettype($var) . ")\n\n";
			if(gettype($var) == 'object') {
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
				
				//output a list of methods that are visible within the current scope
				echo "\nMethods:\n";
				$methods = get_class_methods($var);
				if($methods) {
					foreach($methods as $method) {
						//get a pretty list of parameters for this method
						$reflection = new ReflectionMethod($class, $method);
						$params = $reflection->getParameters();
						foreach($params as $k => $v)
							$params[$k] = preg_replace('/(^Parameter #\d+ \[ | \]$)/S', '', $v);
						
						echo "\t", self::_getVisibility($reflection), ' ';
						if($reflection->isStatic())
							echo 'static ';
						echo $method, '(', implode(', ', $params), ")\n";
					}
				}
				else
					echo "\tNo methods\n";
				echo "\nVars:\n";
			}
			
			//output a list of properties that are visible within the current scope
			$reflectionClass = new ReflectionClass($class);
			$properties = $reflectionClass->getProperties();
			$pretty = array();
			foreach($properties as $property) {
				$k = $property->getName();
				$v = $property->getValue($var);
				
				$type = gettype($v);
				echo "\t", self::_getVisibility($property), ' ';
				if($property->isStatic())
					echo 'static ';
				echo '$', $k, ' = (';
				if(in_array($type, array('object', 'array', 'resource', 'unknown', 'NULL')))
					echo $type, ")\n";
				elseif($type == 'bool')
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