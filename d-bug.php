<?php

/**
 * @package D-bug
 * 
 * @author Craig Russell
 * @link https://github.com/craig-russell/D-bug
 * @license MIT License
 * 
 * @copyright
 * Copyright (c) 2015 Craig Russell
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class D {
	const STYLE = 'text-align: left; color: black; background-color: white; font-size: medium; padding: 10px; font-family: monospace; text-transform: none; font-weight: 400;';
	
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
		self::bugClass(__CLASS__, $exit);
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
			echo self::_bugTypeString($var), "\n";
		}
		else if(!in_array($type, array('object', 'array'))) {
			echo self::_bugShort($var), "\n";
		}
		else if($type == 'array') {
			echo self::_bugTypeString($var), "\n\n";
			
			foreach($var as $k => $v) {
				$type = gettype($v);
				echo "\t", self::_emphasize($k, 'importantName'), ' => ', self::_bugShort($v, 1), "\n";
			}
		}
		else if($type == 'object') {
			$class = get_class($var);
			$reflectionClass = new ReflectionClass($class);
			
			echo self::_bugShort($var), "\n\n";
			self::_bugReflectionClass($reflectionClass, $var);
		}
		
		if(self::bugWeb())
			echo "\n</pre>";
		echo "\n";
		
		if($exit)
			exit;
	}
	
	/**
	 * Dumps information about a class without instantiating it.
	 * 
	 * @param string $class
	 * $param bool $exit
	 */
	public static function bugClass($class, $exit = true) {
		echo self::_bugShort(null, 0, 'class'), "\n\n";
		$reflectionClass = new ReflectionClass($class);
		self::_bugReflectionClass($reflectionClass);
		
		if($exit)
			exit;
	}
	
	/**
	 * Dumps a reflection class.
	 * Optionally accepts the original object.
	 * 
	 * @param object $reflectionClass
	 * @param object $var
	 */
	protected static function _bugReflectionClass($reflectionClass, $var = null) {
		$class = $reflectionClass->getName();
		$ancestors = array($reflectionClass);
		if(get_parent_class($class)) {
			echo "\n", self::_emphasize('Extends:', 'heading'), "\n";
			$ancestorClass = $class;
			while(true) {
				$ancestorClass = get_parent_class($ancestorClass);
				if($ancestorClass) {
					$ancestor = new ReflectionClass($ancestorClass);
					$ancestors[] = $ancestor;
					echo "\t", self::_emphasize($ancestorClass, 'importantName'), "\n\t\t", self::_bugDeclaration($ancestor), "\n";
				}
				else {
					break;
				}
			}
		}
		
		$implements = class_implements($class);
		if($implements) {
			echo "\n", self::_emphasize('Implements:', 'heading'), "\n";
			foreach($implements as $implementedClass) {
				echo "\t", self::_emphasize($implementedClass, 'importantName'), "\n\t\t", self::_bugDeclaration(new ReflectionClass($implementedClass)), "\n";
			}
		}
		
		//output a list of constants
		$constants = $reflectionClass->getConstants();
		if($constants) {
		echo "\n", self::_emphasize('Constants:', 'heading'), "\n";
			foreach($constants as $k => $v) {
				echo "\t", self::_emphasize($k, 'importantName'), ' = ', self::_bugShort($v, 1), "\n";
			}
		}
		
		//output a list of properties
		$properties = $reflectionClass->getProperties();
		if($properties) {
			echo "\n", self::_emphasize('Properties:', 'heading'), "\n";
			foreach($properties as $property) {
				$property->setAccessible(true);
				$k = $property->getName();
				if($var !== null)
					$v = $property->getValue($var);
				else
					$v = $property->getValue();
				
				echo "\t", self::_emphasize(self::_getVisibility($property), 'visibility'), ' ';
				if($property->isStatic())
					echo self::_emphasize('static ', 'static');
				echo self::_emphasize('$' . $k, 'importantName'), ' = ', self::_bugShort($v, 1), "\n";
			}
		}
		
		//output a list of methods
		$methods = $reflectionClass->getMethods();
		if($methods) {
			echo "\n", self::_emphasize('Methods:', 'heading'), "\n";
			foreach($methods as $method) {
				//get a pretty list of parameters for this method
				$method->setAccessible(true);
				$params = $method->getParameters();
				foreach($params as $k => $v) {
					$param = preg_replace('/(^Parameter #\d+ \[ | \]$|\v)/S', '', $v);
					$param = preg_replace('/<(required|optional)> /S', '', $param);
					if(preg_match('/^([^$]+ )?/S', $param))
						$param = preg_replace('/^([^$]+ )?(\$.+?)\b/S', self::_emphasize('$1', 'type') . self::_emphasize('$2', 'paramName'), $param);
					else
						$param = preg_replace('/^(\$.+?)\b/S', self::_emphasize('$1', 'paramName'), $param);
					$params[$k] = $param;
				}
				
				echo "\t";
				$visibility = self::_emphasize(self::_getVisibility($method), 'visibility') . ' ';
				$static = $method->isStatic() ? self::_emphasize('static ', 'static') : '';
				echo $visibility, $static, 'function ', self::_emphasize($method->getName(), 'importantName'), '(', implode(', ', $params), ")\n";
				
				$methodDeclarations = self::_bugMethodDeclaration($method, $ancestors);
				foreach($methodDeclarations as $methodDeclaration) {
					if(sizeof($methodDeclaration) == 3) {
						echo "\t\t", $methodDeclaration[0], ' : ', $methodDeclaration[1], ' (', self::_emphasize($methodDeclaration[2], 'name'), ")\n";
					}
					else {
						echo "\t\t", $methodDeclaration[0], ' (', self::_emphasize($methodDeclaration[1], 'name'), ")\n";
					}
				}
			}
		}
	}
	
	/**
	 * Get formatted info about a variable
	 * 
	 * @param mixed $v
	 * @param int $indentationLevel
	 * @param string $exoticType
	 * 
	 * @return string
	 */
	protected static function _bugShort($v, $indentationLevel = 0, $exoticType = null) {
		$indentation = str_repeat("\t", $indentationLevel);
		$type = gettype($v);
		
		if($exoticType === null)
			$out = self::_bugTypeString($v);
		else
			return self::_bugTypeString(null, $exoticType);
		
		if(in_array($type, array('unknown', 'NULL', 'resource', 'array')))
			$out .= '';
		elseif($type == 'object') {
			$class = get_class($v);
			$out .= "\n" . $indentation . "\t" . self::_bugDeclaration(new ReflectionClass($class));
		}
		elseif($type == 'boolean')
			$out .= self::_emphasize($v ? 'true' : 'false', 'value');
		elseif($type == 'string')
			$out .= self::_emphasize($v, 'value');
		else
			$out .= self::_emphasize($v, 'value');
		
		return $out;
	}
	
	/**
	 * Generates a variable's formatted type string
	 * 
	 * @param mixed $var
	 * @param string $exoticType
	 * 
	 * @return string
	 */
	protected static function _bugTypeString($var, $exoticType = null) {
		$type = $exoticType === null ? gettype($var) : $exoticType;
		$out = '(' . self::_emphasize($type, 'type');
		if($exoticType == null) {
			if($type == 'resource')
				$out .= ' ' . get_resource_type($var);
			elseif($type == 'array')
				$out .= ' ' . sizeof($var);
			elseif($type == 'object') {
				$class = get_class($var);
				$out .= ' ' . self::_emphasize($class, 'importantName');
			}
			elseif($type == 'string')
				$out .= ' ' . strlen($var);
		}
		
		$out .= ') ';
		
		return $out;
	}
	
	/**
	 * Gets declaration info from a ReflectionMethod object
	 * 
	 * @param object $reflection
	 * @param array $ancestors
	 * 
	 * @return array
	 */
	protected static function _bugMethodDeclaration($reflection, $ancestors = array()) {
		$methodName = $reflection->name;
		$declarations = array();
		foreach($ancestors as $ancestor) {
			if($ancestor->hasMethod($methodName)) {
				$method = $ancestor->getMethod($methodName);
				if(!$method->getFileName()) {
					$declarations['Predefined'] = array('Predefined', $ancestor->name);
				}
				else {
					$declaration = array($method->getFileName(), $method->getStartLine(), $ancestor->name);
					$declarations[implode(':', $declaration)] = $declaration;
				}
			}
		}
		
		return $declarations;
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
	protected static function _emphasize($in, $type = 'default') {
		$out = '';
		$web = self::bugWeb();
		if($web)
			return '<span style="' . self::_getStyle($type) . '">' . $in . '</span>';
		else
			return self::_getStyle($type) . $in . "\033[0;39m";
	}
	
	/**
	 * Attempts to get the specified style
	 * 
	 * @param string $name
	 * 
	 * return string
	 */
	protected static function _getStyle($name = 'default') {
		$styles = array(
			'default'		=> array("\033[0;39m", ''),
			'name'			=> array("\033[0;32m", 'color: green;'),
			'importantName'	=> array("\033[1;32m", 'font-weight: bold; color: green;'),
			'visibility'	=> array("\033[1;35m", 'color: magenta;'),
			'type'			=> array("\033[1;36m", 'color: darkcyan;'),
			'static'		=> array("\033[1;31m", 'color: firebrick;'),
			'paramName'		=> array("\033[0;32m", 'color: darkgreen;'),
			'value'			=> array("\033[1;37m", ''),
			'heading'		=> array("\033[1;37m", 'font-weight: bold;')
		);
		
		$style = isset($styles[$name]) ? $styles[$name] : $styles['default'];
		
		return self::bugWeb() ? $style[1] : $style[0];
	}
}