<?php

	$id = 'id';

	$sql = 'SELECT 1 FROM user WHERE id=' . $mysqli->escape_string($id);

	$result = $mysqli->query($sql);

	echo $sql;
	echo "<br />\n";
	echo $result->num_rows;

?>