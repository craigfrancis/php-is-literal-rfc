<?php

	$mysqli->query('SET SESSION sql_mode = "NO_BACKSLASH_ESCAPES"');

	$id = '2" or "1"="1';

	$sql = 'SELECT 1 FROM user WHERE id="' . $mysqli->escape_string($id) . '"';

	$result = $mysqli->query($sql);

	echo $sql;
	echo "<br />\n";
	echo $result->num_rows;

?>