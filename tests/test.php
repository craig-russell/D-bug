<?php

chdir(__DIR__);

include '../d-bug.php';

class A {
	
}

class B extends A {
	public $a = 'test';
	public $b = [[1, 2, 3, 4], 2, 3, 4];
	
	const C = 42;
	
	public static function test($a, $b = true) {
		return true;
	}
}

$test = [];
$test[] = fopen(__FILE__, 'r');
$test[] = 'test';
$test[] = 1;
$test[] = 2.3;
$test[] = true;
$test[] = null;
$test[] = [[1, 2, 3, 4], 2, 3, 4, new B()];
$test[] = new A();
$test[] = new B();

foreach($test as $k => $t) {
	echo "Test #", $k, "\n\n";
	D::bugType($t, false);
	echo "----------------------------------------\n\n";
}

fclose($test[0]);