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
		ob_start();
		debug_print_backtrace();
		self::bug(ob_get_clean(), false, $exit);
	}
	
	//dump the php ini settings
	public static function bugIni($exit = true) {
		self::bug(ini_get_all(), false, $exit);
	}
	
	//list all currently included files
	public static function bugIncludes($exit = true) {
		self::bug(implode("\n", get_included_files()), false, $exit);
	}
	
	/**
	 * dump a variable, and provide type info
	 * recurses one level into arrays and objects
	 * provides extended info about methods and properties
	 */
	public static function bugType($var, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo "<pre>\n";
		
		$type = gettype($var);
		if(!array($type, array('unknown', 'NULL'))) {
			echo '(' . $type . ")\n";
		}
		else if(!in_array($type, array('object', 'array'))) {
			echo self::_bugTypeShort($var), "\n";
		}
		else if($type == 'array') {
			echo '(' . $type . ")\n\n";
			
			foreach($var as $k => $v) {
				$type = gettype($v);
				echo "\t", $k, ' => ', self::_bugTypeShort($v), "\n";
			}
		}
		else if($type == 'object') {
			$class = get_class($var);
			$reflectionClass = new ReflectionClass($class);
			echo self::_bugTypeShort($var), "\n\n";
			
			echo "Extends:\n";
			$ancestorClass = $class;
			$ancestorCount = 0;
			while(true) {
				$ancestorClass = get_parent_class($ancestorClass);
				if($ancestorClass)
					echo "\t", $ancestorClass, "\n\t\t", self::_bugDeclaration(new ReflectionClass($ancestorClass)), "\n";
				else
					break;
				++$ancestorCount;
			}
			if(!$ancestorCount)
				echo "\tNo ancestors\n";
			
			echo "\nImplements:\n";
			$implements = class_implements($class);
			$implementCount = 0;
			foreach($implements as $implementedClass) {
				echo "\t", $implementedClass, "\n\t\t", self::_bugDeclaration(new ReflectionClass($implementedClass)), "\n";
				++$implementCount;
			}
			if(!$implementCount)
				echo "\tNo interfaces\n";
			
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
					echo $method->getName(), '(', implode(', ', $params), ")\n\t\t", self::_bugDeclaration($method), "\n";
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
		if(in_array($type, array('unknown', 'NULL')))
			$out .= ')';
		elseif($type == 'resource')
			$out .= ' ' . get_resource_type($v) . ')';
		elseif($type == 'array')
			$out .= ' ' . sizeof($v) . ')';
		elseif($type == 'object') {
			$class = get_class($v);
			$out .= ' ' . $class . ")\n\t\t" . self::_bugDeclaration(new ReflectionClass($class));
		}
		elseif($type == 'boolean')
			$out .= ') ' . ($v ? 'true' : 'false');
		else
			$out .= ') ' . $v;
		
		return $out;
	}
	
	protected static function _bugDeclaration($reflection) {
		$file = $reflection->getFileName();
		$line = $reflection->getStartLine();
		if($line !== false)
			return $line . ':' . $file;
		else
			return 'Predefined';
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
}