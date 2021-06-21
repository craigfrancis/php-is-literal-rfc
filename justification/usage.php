<?php

//--------------------------------------------------


	var_dump(is_trusted('Example')); // true
	var_dump(is_trusted(strtoupper('Example'))); // false, modified output from a function is not trusted


//--------------------------------------------------


	echo "\n" . 'Variables and concatenation' . "\n";

	$a = 'Hello';
	$b = 'World';

	var_dump(is_trusted($a)); // true
	var_dump(is_trusted($a . $b)); // true
	var_dump(is_trusted("Hi $b")); // true


//--------------------------------------------------


	echo "\n" . 'Values that must be rejected' . "\n";

	$not_trusted = ($_GET['id'] ?? strtoupper('id')); // Using strtoupper so the default isn't a trusted either.

	var_dump(is_trusted($not_trusted)); // false
	var_dump(is_trusted(sprintf('Hi %s', $not_trusted))); // false
	var_dump(is_trusted('/bin/rm -rf ' . $not_trusted)); // false
	var_dump(is_trusted('<img src=' . htmlentities($not_trusted) . ' />')); // false... try ./?id=%2F+onerror%3Dalert%281%29
	var_dump(is_trusted('WHERE id = ' . mysqli_fake_escape_string($not_trusted))); // false... try ./?id=id


//--------------------------------------------------


	echo "\n" . 'Usage with functions:' . "\n";

	function example($input) {
		if (!is_trusted($input)) {
			throw new Exception('Non-trusted value detected!');
		}
		return $input;
	}

	var_dump(example($a)); // Prints 'Hello'
	var_dump(example(example($a))); // Prints 'Hello' (still the same trusted value)

	try {
		var_dump(example(strtoupper($a))); // Exception thrown, value modified.
	} catch (exception $e) {
		var_dump(false);
	}


//--------------------------------------------------


	echo "\n\n" . 'Native functions that support string concatenation:' . "\n";

	echo "\n" . 'str_repeat()' . "\n";
	var_dump(is_trusted(str_repeat($a, 10))); // true
	var_dump(is_trusted(str_repeat($not_trusted, 10))); // false, non-trusted value

	echo "\n" . 'str_pad()' . "\n";
	var_dump(is_trusted(str_pad($a, 10, '-'))); // true
	var_dump(is_trusted(str_pad($a, 10, $not_trusted))); // false
	var_dump(is_trusted(str_pad($not_trusted, 10, '-'))); // false

	echo "\n" . 'implode()' . "\n";
	var_dump(is_trusted(implode(' AND ', [$a, $b]))); // true
	var_dump(is_trusted(implode(' AND ', [$a, $not_trusted]))); // false
	var_dump(is_trusted(implode($not_trusted, [$a, $b]))); // false

	echo "\n" . 'array_pad()' . "\n";
	var_dump(is_trusted(array_pad([$a], 10,          $b)[0])); // true
	var_dump(is_trusted(array_pad([$a], 10,          $b)[5])); // true
	var_dump(is_trusted(array_pad([$a], rand(6, 10), $b)[5])); // true
	var_dump(is_trusted(array_pad([$a], 10,          $not_trusted)[0])); // true
	var_dump(is_trusted(array_pad([$a], 10,          $not_trusted)[5])); // false
	var_dump(is_trusted(array_pad([$a, $not_trusted], 10, $b)[0])); // true
	var_dump(is_trusted(array_pad([$a, $not_trusted], 10, $b)[1])); // false
	var_dump(is_trusted(array_pad([$a, $not_trusted], 10, $b)[5])); // true

	echo "\n" . 'array_fill()' . "\n";
	var_dump(is_trusted(array_fill(0, 10, $a)[5])); // true
	var_dump(is_trusted(array_fill(0, 10, $not_trusted)[5])); // false


//--------------------------------------------------
// Ignore this, it's just so it runs, the real
// version does not protect against this vulnerability.

	function mysqli_fake_escape_string($input) { return $input; }

?>