<?php

class D {
	const STYLE = 'text-align: left; color: black; background-color: white; font-size: medium; padding: 10px; font-family: monospace; text-transform: none;';
	
	/**
	 * Check if it's safe to show debug output
	 * 
	 * @return bool
	 */
	public static function bugMode() {
		if(!self::bugWeb())
			return true;
		
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
		
		return $ip == '127.0.0.1' || preg_match('/^(192\.168|172\.16|10)\./S', $ip);
	}
	
	/**
	 * Check if the script is running on the web
	 * 
	 * @return bool
	 */
	public static function bugWeb() {
		return php_sapi_name() != 'cli';
	}
	
	/**
	 * Dump a variable recursively
	 * 
	 * @param mixed $var
	 * @param bool $dump
	 * @param bool $exit
	 */
	public static function bugR($var, $dump = false, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo '<pre style="', self::STYLE, '">';
		
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
	
	/**
	 * Generate a backtrace
	 * 
	 * @param bool $exit
	 */
	public static function bugBacktrace($exit = true) {
		ob_start();
		debug_print_backtrace();
		self::bugR(ob_get_clean(), false, $exit);
	}
	
	/**
	 * Dump the php ini settings
	 * 
	 * @param bool $exit
	 */
	public static function bugIni($exit = true) {
		self::bugR(ini_get_all(), false, $exit);
	}
	
	/**
	 * List all currently included files
	 * 
	 * @param bool $exit
	 */
	public static function bugIncludes($exit = true) {
		self::bugR(implode("\n", get_included_files()), false, $exit);
	}
	
	/**
	 * Turn D-bug on itself
	 * 
	 * @param bool $exit
	 */
	public static function bugMe($exit = true) {
		self::bug(new self(), $exit);
	}
	
	/**
	 * Dump a variable, and provide type info
	 * Recurses one level into arrays and objects
	 * Provides extended info about methods and properties
	 * 
	 * @param mixed $var
	 * @param bool $exit
	 */
	public static function bug($var, $exit = true) {
		if(!self::bugMode())
			return;
		
		if(self::bugWeb())
			echo '<pre style="', self::STYLE, '">', "\n";
		
		$type = gettype($var);
		if(in_array($type, array('unknown', 'NULL'))) {
			echo '(' . $type . ")\n";
		}
		else if(!in_array($type, array('object', 'array'))) {
			echo self::_bugShort($var), "\n";
		}
		else if($type == 'array') {
			echo '(' . $type . ' ' . sizeof($var) . ")\n\n";
			
			foreach($var as $k => $v) {
				$type = gettype($v);
				echo "\t", self::_emphasize($k), ' => ', self::_bugShort($v, 1), "\n";
			}
		}
		else if($type == 'object') {
			$class = get_class($var);
			$reflectionClass = new ReflectionClass($class);
			echo self::_bugShort($var), "\n\n";
			
			if(get_parent_class($class)) {
				echo "Extends:\n";
				$ancestorClass = $class;
				while(true) {
					$ancestorClass = get_parent_class($ancestorClass);
					if($ancestorClass)
						echo "\t", self::_emphasize($ancestorClass), "\n\t\t", self::_bugDeclaration(new ReflectionClass($ancestorClass)), "\n";
					else
						break;
				}
			}
			
			$implements = class_implements($class);
			if($implements) {
				echo "\nImplements:\n";
				foreach($implements as $implementedClass) {
					echo "\t", self::_emphasize($implementedClass), "\n\t\t", self::_bugDeclaration(new ReflectionClass($implementedClass)), "\n";
				}
			}
			
			//output a list of constants
			$constants = $reflectionClass->getConstants();
			if($constants) {
			echo "\nConstants:\n";
				foreach($constants as $k => $v) {
					echo "\t", self::_emphasize($k), ' = ', self::_bugShort($v, 1), "\n";
				}
			}
			
			//output a list of properties
			$properties = $reflectionClass->getProperties();
			if($properties) {
				echo "\nProperties:\n";
				foreach($properties as $property) {
					$property->setAccessible(true);
					$k = $property->getName();
					$v = $property->getValue($var);
					
					echo "\t", self::_getVisibility($property), ' ';
					if($property->isStatic())
						echo 'static ';
					echo self::_emphasize('$' . $k), ' = ', self::_bugShort($v, 1), "\n";
				}
			}
			
			//output a list of methods
			$methods = $reflectionClass->getMethods();
			if($methods) {
				echo "\nMethods:\n";
				foreach($methods as $method) {
					//get a pretty list of parameters for this method
					$method->setAccessible(true);
					$params = $method->getParameters();
					foreach($params as $k => $v)
						$params[$k] = preg_replace('/(^Parameter #\d+ \[ | \]$|\v)/S', '', $v);
					
					echo "\t", self::_getVisibility($method), ' ';
					if($method->isStatic())
						echo 'static ';
					echo 'function ', self::_emphasize($method->getName()), '(', implode(', ', $params), ")\n\t\t", self::_bugDeclaration($method), "\n";
				}
			}
		}
		
		if(self::bugWeb())
			echo "\n</pre>";
		echo "\n";
		
		if($exit)
			exit;
	}
	
	/**
	 * Get formatted info about a variable
	 * 
	 * @param mixed $v
	 * @param int $indentationLevel
	 * 
	 * @return string
	 */
	protected static function _bugShort($v, $indentationLevel = 0) {
		$indentation = str_repeat("\t", $indentationLevel);
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
			$out .= ' ' . self::_emphasize($class) . ")\n" . $indentation . "\t" . self::_bugDeclaration(new ReflectionClass($class));
		}
		elseif($type == 'boolean')
			$out .= ') ' . ($v ? 'true' : 'false');
		elseif($type == 'string')
			$out .= ' ' . strlen($v) . ') ' . $v;
		else
			$out .= ') ' . $v;
		
		return $out;
	}
	
	/**
	 * Gets declaration info from a reflection object
	 * 
	 * @param object $reflection
	 * 
	 * @return string
	 */
	protected static function _bugDeclaration($reflection) {
		$file = $reflection->getFileName();
		$line = $reflection->getStartLine();
		if($line !== false)
			return $file . ' : ' . $line;
		else
			return 'Predefined';
	}
	
	/**
	 * Check a reflection object's visibility
	 * 
	 * @param object $reflection
	 * 
	 * @return string
	 */
	protected static function _getVisibility($reflection) {
		$reflection->setAccessible(true);
		if($reflection->isPublic())
			return 'public';
		elseif($reflection->isProtected())
			return 'protected';
		else
			return 'private';
	}
	
	/**
	 * Add appropriate emphasis to a string
	 * 
	 * @param string $in
	 * 
	 * @return string
	 */
	protected static function _emphasize($in) {
		$out = '';
		$web = self::bugWeb();
		if($web)
			return '<strong>' . $in . '</strong>';
		else
			return $in;
	}
}