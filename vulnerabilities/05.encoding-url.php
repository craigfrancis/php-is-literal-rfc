<?php

	$name = 'A&B';

	echo '<a href="/path/?name=' . htmlentities($name) . '">Link</a>';

?>