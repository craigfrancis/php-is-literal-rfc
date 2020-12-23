<?php

	$email = 'b@example.com -X/www/example.php';

	$parameters = "-f$email";

	// $parameters = '-f' . escapeshellarg($email);

	// mail('a@example.com', 'Subject', 'Message', NULL, $parameters);

?>