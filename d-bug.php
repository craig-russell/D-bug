<?php

class D {
	public static function debugMode() {
		return true;
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER;
		$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $headers['REMOTE_ADDR'];
		
		return $ip == '127.0.0.1' || preg_match('/^192\.168\./S', $ip);
	}
	
	public static function bug($var, $dump = false, $exit = true) {
		if(!self::debugMode())
			return;
		
		echo '<pre>';
		if($dump)
			var_dump($var);
		else
			print_r($var);
		echo "</pre>\n";
		
		if($exit)
			exit;
	}
	
	public static function backtrace($exit = true) {
		if(!self::debugMode())
			return;
		
		echo '<pre>';
		debug_print_backtrace();
		echo "</pre>\n";
		
		if($exit)
			exit;
	}
	
	public static function bugType($var, $exit = true) {
		if(!self::debugMode())
			return;
		
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
				echo 'Class: ' . get_class($var), "\n\n";
				
				echo "Ancestors:\n";
				$class = get_class($var);
				while(true) {
					$class = get_parent_class($class);
					if($class)
						echo "\t", $class, "\n";
					else
						break;
				}
				
				echo "\nMethods:\n";
				$methods = get_class_methods($var);
				if($methods) {
					foreach($methods as $method)
						echo "\t", $method, "\n";
				}
				else
					echo "\tNo methods\n";
				echo "\nVars:\n";
			}
			
			$var = (array)$var;
			$pretty = array();
			foreach($var as $k => $v) {
				$type = gettype($v);
				if(in_array($type, array('object', 'array', 'resource', 'unknown', 'NULL')))
					echo "\t", $k, ' => (', $type, ")\n";
				elseif($type == 'bool')
					echo "\t", $k, ' => (' . $type . ') ' . ($v ? 'true' : 'false'), "\n";
				else
					echo "\t", $k, ' => (' . $type . ') ' . $v, "\n";
			}
		}
		echo "\n</pre>\n";
		
		if($exit)
			exit;
	}
	
	public static function ini($exit = true) {
		self::bug(ini_get_all(), false, $exit);
	}
}