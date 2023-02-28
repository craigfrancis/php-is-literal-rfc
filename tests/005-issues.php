<?php

	$sql = '';
	for ($k = 0; $k < 2; $k++) {
		if ($sql !== '') {
			$sql .= '-';
		}
		$values_sql  = 'A';
		$values_sql .= 'B';
		$sql .= $values_sql;
	}

	var_dump($sql, is_literal($sql));


echo "\n--------------------------------------------------\n\n";


	$b = 'BBB';

	$array = [
			// 'XXX',
			'AAA ' . $b,
			'CCC',
		];

	$sql = '';
	foreach ($array as $part) {
		if ($sql !== '') {
			$sql .= ' / '; // <-- Adding this literal is a problem?
			var_dump([$sql, is_literal($sql)]);
		}
		$sql .= $part;
		var_dump([$sql, is_literal($sql)]);
	}


echo "\n--------------------------------------------------\n\n";


	class example {

		private $config = [
				'where_sql' => 'a = b',
			];

		function test() {

			$sql = $this->config['where_sql']; // <-- Starting with a property value

			// $sql = 'a = b';

			var_dump([$sql, is_literal($sql)]);

			$sql .= ' AND c = ?'; // <-- Causes this concatenating assignment to fail.

			// $sql = $sql . ' AND b = ?';

			var_dump([$sql, is_literal($sql)]);

		}

	}

	$example = new example();
	$example->test();


echo "\n--------------------------------------------------\n\n";


	var_dump(is_literal(implode(' ', ['']))); // Fine

	var_dump(is_literal(implode(' ', []))); // Not?


?>