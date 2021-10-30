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

//--------------------------------------------------

	$b = 'BBB';

	$array = [
			// 'XXX',
			'AAA ' . $b,
			'CCC',
		];

	$sql = '';
	foreach ($array as $part) {
		if ($sql !== '') {
			$sql .= ' / ';
			var_dump([$sql, is_literal($sql)]);
		}
		$sql .= $part;
		var_dump([$sql, is_literal($sql)]);
	}

?>