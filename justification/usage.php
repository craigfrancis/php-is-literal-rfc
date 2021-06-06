<?php


	var_dump(is_literal('Example')); // true
	var_dump(is_literal(sprintf('Example'))); // false, modified output from a function is not a literal.


	echo "\n" . 'Variables and concatenation' . "\n";


	$a = 'Hello';
	$b = 'World';

	var_dump(is_literal($a)); // true
	var_dump(is_literal($a . $b)); // true
	var_dump(is_literal("Hi $b")); // true


	echo "\n" . 'Values that must be rejected' . "\n";


	var_dump(is_literal($_GET['id'] ?? NULL)); // false
	var_dump(is_literal('WHERE id = ' . intval($_GET['id'] ?? NULL))); // false
	var_dump(is_literal('<input name="q" value="' . ($_GET['q'] ?? NULL) . '" />')); // false
	var_dump(is_literal('/bin/rm -rf ' . ($_GET['path'] ?? NULL))); // false
	var_dump(is_literal(rand(0, 10))); // false
	var_dump(is_literal(sprintf('Example %d', true))); // false


	echo "\n" . 'Usage with functions:' . "\n";


	function example($input) {
		if (!is_literal($input)) {
			throw new Exception('Non-literal detected!');
		}
		return $input;
	}

	var_dump(example($a)); // Prints 'Hello'
	var_dump(example(example($a))); // Prints 'Hello' (still the same literal)

	try {
		var_dump(example(strtoupper($a))); // Exception thrown, value modified.
	} catch (exception $e) {
		var_dump(false);
	}


	echo "\n\n" . 'Native functions that support string concatenation:' . "\n";


	echo "\n" . 'str_repeat()' . "\n";
	var_dump(is_literal(str_repeat($a, 10))); // true
	var_dump(is_literal(str_repeat($a, rand(1, 10)))); // true
	var_dump(is_literal(str_repeat(rand(1, 10), 10))); // false, non-literal input

	echo "\n" . 'str_pad()' . "\n";
	var_dump(is_literal(str_pad($a, 10, '-'))); // true
	var_dump(is_literal(str_pad($a, 10, rand(1, 10)))); // false

	echo "\n" . 'implode()' . "\n";
	var_dump(is_literal(implode(' AND ', [$a, $b]))); // true
	var_dump(is_literal(implode(' AND ', [$a, rand(1, 10)]))); // false

	echo "\n" . 'array_pad()' . "\n";
	var_dump(is_literal(array_pad([$a], 10,          $b)[0])); // true
	var_dump(is_literal(array_pad([$a], 10,          $b)[5])); // true
	var_dump(is_literal(array_pad([$a], rand(6, 10), $b)[5])); // true
	var_dump(is_literal(array_pad([$a], 10,          rand(1, 10))[0])); // true
	var_dump(is_literal(array_pad([$a], 10,          rand(1, 10))[5])); // false
	var_dump(is_literal(array_pad([$a, rand(1, 10)], 10, $b)[0])); // true
	var_dump(is_literal(array_pad([$a, rand(1, 10)], 10, $b)[1])); // false
	var_dump(is_literal(array_pad([$a, rand(1, 10)], 10, $b)[5])); // true

	echo "\n" . 'array_fill()' . "\n";
	var_dump(is_literal(array_fill(0, 10, $a)[5])); // true
	var_dump(is_literal(array_fill(0, 10, rand(1, 10))[5])); // false


?>