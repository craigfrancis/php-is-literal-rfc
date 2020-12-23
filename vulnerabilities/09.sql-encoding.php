<?php

	$mysqli->query('SET NAMES latin1');

	$name = '家';

	$sql = 'INSERT INTO user (name) VALUES ("' . $mysqli->escape_string($name) . '")';

	$result = $mysqli->query($sql);

	echo $sql;

?>