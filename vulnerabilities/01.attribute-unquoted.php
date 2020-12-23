<?php

	$url = '/ onerror=alert(1)';

	echo "<img src=" . htmlentities($url) . " alt='' />";

?>