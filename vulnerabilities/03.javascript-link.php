<?php

	$url = 'javascript:alert(1)';

	echo '<a href="' . htmlentities($url) . '">Link</a>';

?>