<?php

//--------------------------------------------------


	var_dump(is_noble('Example')); // true
	var_dump(is_noble(strtoupper('Example'))); // false, modified output from a function is not noble


//--------------------------------------------------


	echo "\n" . 'Variables and concatenation' . "\n";

	$a = 'Hello';
	$b = 'World';

	var_dump(is_noble($a)); // true
	var_dump(is_noble($a . $b)); // true
	var_dump(is_noble("Hi $b")); // true


//--------------------------------------------------


	echo "\n" . 'Values that must be rejected' . "\n";

	$not_noble = ($_GET['id'] ?? strtoupper('id')); // Using strtoupper so the default isn't a noble either.

	var_dump(is_noble($not_noble)); // false
	var_dump(is_noble(sprintf('Hi %s', $not_noble))); // false
	var_dump(is_noble('/bin/rm -rf ' . $not_noble)); // false
	var_dump(is_noble('<img src=' . htmlentities($not_noble) . ' />')); // false... try ./?id=%2F+onerror%3Dalert%281%29
	var_dump(is_noble('WHERE id = ' . mysqli_fake_escape_string($not_noble))); // false... try ./?id=id


//--------------------------------------------------


	echo "\n" . 'Usage with functions:' . "\n";

	function example($input) {
		if (!is_noble($input)) {
			throw new Exception('Non-noble value detected!');
		}
		return $input;
	}

	var_dump(example($a)); // Prints 'Hello'
	var_dump(example(example($a))); // Prints 'Hello' (still the same value)

	try {
		var_dump(example(strtoupper($a))); // Exception thrown, value modified.
	} catch (exception $e) {
		var_dump(false);
	}


//--------------------------------------------------


	echo "\n\n" . 'Native functions that support string concatenation:' . "\n";

	echo "\n" . 'str_repeat()' . "\n";
	var_dump(is_noble(str_repeat($a, 10))); // true
	var_dump(is_noble(str_repeat($not_noble, 10))); // false, non-noble value

	echo "\n" . 'str_pad()' . "\n";
	var_dump(is_noble(str_pad($a, 10, '-'))); // true
	var_dump(is_noble(str_pad($a, 10, $not_noble))); // false
	var_dump(is_noble(str_pad($not_noble, 10, '-'))); // false

	echo "\n" . 'implode()' . "\n";
	var_dump(is_noble(implode(' AND ', [$a, $b]))); // true
	var_dump(is_noble(implode(' AND ', [$a, $not_noble]))); // false
	var_dump(is_noble(implode($not_noble, [$a, $b]))); // false

	echo "\n" . 'array_pad()' . "\n";
	var_dump(is_noble(array_pad([$a], 10,          $b)[0])); // true
	var_dump(is_noble(array_pad([$a], 10,          $b)[5])); // true
	var_dump(is_noble(array_pad([$a], rand(6, 10), $b)[5])); // true
	var_dump(is_noble(array_pad([$a], 10,          $not_noble)[0])); // true
	var_dump(is_noble(array_pad([$a], 10,          $not_noble)[5])); // false
	var_dump(is_noble(array_pad([$a, $not_noble], 10, $b)[0])); // true
	var_dump(is_noble(array_pad([$a, $not_noble], 10, $b)[1])); // false
	var_dump(is_noble(array_pad([$a, $not_noble], 10, $b)[5])); // true

	echo "\n" . 'array_fill()' . "\n";
	var_dump(is_noble(array_fill(0, 10, $a)[5])); // true
	var_dump(is_noble(array_fill(0, 10, $not_noble)[5])); // false


//--------------------------------------------------
// Ignore this, it's just so it runs, the real
// version does not protect against this vulnerability.

	function mysqli_fake_escape_string($input) { return $input; }

?>