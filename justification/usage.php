<?php

//--------------------------------------------------


	var_dump(is_literal('Example')); // true
	var_dump(is_literal(strtoupper('Example'))); // false, modified output from a function is not literal


//--------------------------------------------------


	echo "\n" . 'Variables and concatenation' . "\n";

	$a = 'Hello';
	$b = 'World';

	var_dump(is_literal($a)); // true
	var_dump(is_literal($a . $b)); // true
	var_dump(is_literal("Hi $b")); // true


//--------------------------------------------------


	echo "\n" . 'Values that must be rejected' . "\n";

	$not_literal = ($_GET['id'] ?? strtoupper('id')); // Using strtoupper so the default isn't a literal either.

	var_dump(is_literal($not_literal)); // false
	var_dump(is_literal(sprintf('Hi %s', $not_literal))); // false
	var_dump(is_literal('/bin/rm -rf ' . $not_literal)); // false
	var_dump(is_literal('<img src=' . htmlentities($not_literal) . ' />')); // false... try ./?id=%2F+onerror%3Dalert%281%29
	var_dump(is_literal('WHERE id = ' . mysqli_fake_escape_string($not_literal))); // false... try ./?id=id


//--------------------------------------------------


	echo "\n" . 'Usage with functions:' . "\n";

	function example($input) {
		if (!is_literal($input)) {
			throw new Exception('Non-literal value detected!');
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
	var_dump(is_literal(str_repeat($a, 10))); // true
	var_dump(is_literal(str_repeat($not_literal, 10))); // false, non-literal value

	echo "\n" . 'str_pad()' . "\n";
	var_dump(is_literal(str_pad($a, 10, '-'))); // true
	var_dump(is_literal(str_pad($a, 10, $not_literal))); // false
	var_dump(is_literal(str_pad($not_literal, 10, '-'))); // false

	echo "\n" . 'implode()' . "\n";
	var_dump(is_literal(implode(' AND ', [$a, $b]))); // true
	var_dump(is_literal(implode(' AND ', [$a, $not_literal]))); // false
	var_dump(is_literal(implode($not_literal, [$a, $b]))); // false

	echo "\n" . 'array_pad()' . "\n";
	var_dump(is_literal(array_pad([$a], 10,          $b)[0])); // true
	var_dump(is_literal(array_pad([$a], 10,          $b)[5])); // true
	var_dump(is_literal(array_pad([$a], rand(6, 10), $b)[5])); // true
	var_dump(is_literal(array_pad([$a], 10,          $not_literal)[0])); // true
	var_dump(is_literal(array_pad([$a], 10,          $not_literal)[5])); // false
	var_dump(is_literal(array_pad([$a, $not_literal], 10, $b)[0])); // true
	var_dump(is_literal(array_pad([$a, $not_literal], 10, $b)[1])); // false
	var_dump(is_literal(array_pad([$a, $not_literal], 10, $b)[5])); // true

	echo "\n" . 'array_fill()' . "\n";
	var_dump(is_literal(array_fill(0, 10, $a)[5])); // true
	var_dump(is_literal(array_fill(0, 10, $not_literal)[5])); // false


//--------------------------------------------------
// Ignore this, it's just so it runs, the real
// version does not protect against this vulnerability.

	function mysqli_fake_escape_string($input) { return $input; }

?>