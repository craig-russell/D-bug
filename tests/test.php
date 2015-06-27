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

chdir(__DIR__);

include '../d-bug.php';

class A {}

class B extends stdClass {
	public static function testA() {
		return false;
	}
	
	private function testC() {
		return true;
	}
	
	public function testD() {
		return true;
	}
	
	public function testB() {
		return 1;
	}
}

class C extends B {
	public $a = 'test';
	public $b = [[1, 2, 3, 4], 2, 3, 4];
	
	const C = 42;
	
	public static function testA($a, $b = true) {
		return true;
	}
	
	public function testB() {
		return 2;
	}
}

class E implements Iterator {
	public function current() {}
	public function key() {}
	public function next() {}
	public function rewind() {}
	public function valid() {}
}

class F extends mysqli {
	public function use_result() {
		echo "test\n";
	}
}

$test = [];
$test[] = fopen(__FILE__, 'r');
$test[] = 'test';
$test[] = 1;
$test[] = 2.3;
$test[] = true;
$test[] = null;
$test[] = [[1, 2, 3, 4], 2, 3, 4, new C()];
$test[] = new A();
$test[] = new C();
$test[] = new E();
$test[] = new F();

foreach($test as $k => $t) {
	echo "Test #", $k, "\n\n";
	D::bug($t, false);
	echo "----------------------------------------\n\n";
}

fclose($test[0]);