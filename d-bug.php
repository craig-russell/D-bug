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
	public static function bugBacktrace($exit = true) {
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
	
	/**
	 * dump a variable, and provide type info
	 * recurses one level into arrays and objects
	 * provides extended info about methods and properties
	 * 
	 * TODO: eliminate redundant code by adding an optional recursion depth parameter
	 */
	public static function bugType($var, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo "<pre>\n";
		
		if(!array(gettype($var), array('resource', 'unknown', 'NULL'))) {
			echo '(' . gettype($var) . ")\n";
		}
		else if(!in_array(gettype($var), array('object', 'array'))) {
			echo self::_bugTypeShort($var), "\n";
		}
		else if(gettype($var) == 'array') {
			echo '(' . gettype($var) . ")\n\n";
			
			foreach($var as $k => $v) {
				$type = gettype($v);
				echo "\t", $k, ' => ', self::_bugTypeShort($v), "\n";
			}
		}
		else if(gettype($var) == 'object') {
			$class = get_class($var);
			$reflectionClass = new ReflectionClass($class);
			echo self::_bugTypeShort($var), "\n\n";
			
			echo "Extends:\n";
			$ancestorClass = $class;
			$ancestorCount = 0;
			while(true) {
				$ancestorClass = get_parent_class($ancestorClass);
				if($ancestorClass)
					echo "\t", $ancestorClass, ' ', self::_bugClassDeclaration($ancestorClass), "\n";
				else
					break;
				++$ancestorCount;
			}
			if(!$ancestorCount)
				echo "\tNo ancestors\n";
			
			//output a list of constants
			echo "\nConstants:\n";
			$constants = $reflectionClass->getConstants();
			if($constants) {
				foreach($constants as $k => $v) {
					echo "\t", $k, ' = ', self::_bugTypeShort($v), "\n";
				}
			}
			else
				echo "\tNo constants\n";
			
			//output a list of properties
			echo "\nProperties:\n";
			$properties = $reflectionClass->getProperties();
			if($properties) {
				foreach($properties as $property) {
					$property->setAccessible(true);
					$k = $property->getName();
					$v = $property->getValue($var);
					
					echo "\t", self::_getVisibility($property), ' ';
					if($property->isStatic())
						echo 'static ';
					echo '$', $k, ' = ', self::_bugTypeShort($v), "\n";
				}
			}
			else
				echo "\tNo properties\n";
			
			//output a list of methods
			echo "\nMethods:\n";
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
		}
		
		if(self::bugWeb())
			echo "\n</pre>";
		echo "\n";
		
		if($exit)
			exit;
	}
	
	protected static function _bugTypeShort($v) {
		$type = gettype($v);
		$out = '(' . $type;
		if(in_array($type, array('resource', 'unknown', 'NULL')))
			$out .= ')';
		elseif($type == 'array')
			$out .= ' ' . sizeof($v) . ')';
		elseif($type == 'object') {
			$class = get_class($v);
			$out .= ' ' . $class . ') ' . self::_bugClassDeclaration($class);
		}
		elseif($type == 'boolean')
			$out .= ') ' . ($v ? 'true' : 'false');
		else
			$out .= ') ' . $v;
		
		return $out;
	}
	
	protected static function _bugClassDeclaration($class) {
		$reflection = new ReflectionClass($class);
		$file = $reflection->getFileName();
		$line = $reflection->getStartLine();
		
		return $line . ':' . $file;
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
	public static function bugIni($exit = true) {
		self::bug(ini_get_all(), false, $exit);
	}
}