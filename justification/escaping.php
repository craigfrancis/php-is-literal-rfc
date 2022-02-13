<?php

//--------------------------------------------------
// No Escaping

	$_GET['id'] = 'id';

	$sql = 'WHERE id = ' . $_GET['id'];

//--------------------------------------------------
// Bad Escaping

	$_GET['id'] = 'id';

	$sql = 'WHERE id = ' . $mysqli->real_escape_string($_GET['id']);

//--------------------------------------------------
// Bad Escaping

	// Intended HTML usage:
	//   <input type="checkbox" name="delete[]" value="1" />
	//   <input type="checkbox" name="delete[]" value="3" />

	$_POST['delete'] = ['1) OR (1 = 1'];

	$sql = 'DELETE FROM table WHERE id IN (' . implode(',', $_POST['delete']) . ')'; // INSECURE

		// While array_map('intval') might fix this instance today, who knows who will copy/edit it in the future.
		// Instead, just use parameterised queries:
		//
		// Levi Morrison             = https://stackoverflow.com/a/23641033/538216
		// PDO Execute               = https://www.php.net/manual/en/pdostatement.execute.php#example-1012
		// Drupal Multiple Arguments = https://www.drupal.org/docs/7/security/writing-secure-code/database-access#s-multiple-arguments

//--------------------------------------------------
// Possible config issue
// If 'sql_mode' includes NO_BACKSLASH_ESCAPES

	$_GET['id'] = '2" or "1"="1';

	$sql = 'SELECT 1 FROM user WHERE id = "' . $mysqli->escape_string($_GET['id']) . '"';

//--------------------------------------------------
// Possible config issue
// If 'SET NAMES latin1' has been used, and escape_string() continues to use 'utf8'.

	$_GET['name'] = 'Zoë';

	$sql = 'INSERT INTO user (name) VALUES ("' . $mysqli->escape_string($_GET['name']) . '")';

//--------------------------------------------------
// No Escaping

	$_GET['url'] = '/ onerror=alert(1)';

	$html = '<img src=' . $_GET['url'] . ' alt="" />';

//--------------------------------------------------
// Missing Quotes

	$_GET['url'] = '/ onerror=alert(1)';

	$html = "<img src=" . htmlentities($_GET['url']) . " alt='' />";

//--------------------------------------------------
// Bad Escaping
// htmlentities() doesn't encode single quotes by default.
//  Before PHP 8.1
//    https://github.com/php/php-src/commit/50eca61f68815005f3b0f808578cc1ce3b4297f0
//  Java, Apache Commons
//   https://commons.apache.org/proper/commons-text/javadocs/api-release/org/apache/commons/text/StringEscapeUtils.html#escapeHtml4(java.lang.String)
//   "Note that the commonly used apostrophe escape character (&apos;) is not a legal entity and so is not supported"

	$_GET['url'] = "/' onerror='alert(1)";

	$html = "<img src='" . htmlentities($_GET['url']) . "' alt='' />";

//--------------------------------------------------
// Context Issue

	$_GET['url'] = 'javascript:alert(1);';

	$html = '<a href="' . htmlentities($_GET['url']) . '">Link</a>';

//--------------------------------------------------
// Context Issue
// The browsers HTML parser is not aware of escaped JavaScript strings.

	$_GET['url'] = '</script><script>alert(1)</script>';

	$html = '<script> var url = "' . addslashes($_GET['url']) . '"; </script>';

//--------------------------------------------------
// Incomplete Escaping (ref urlencode)

	$_GET['name'] = 'A&B';

	$html = '<a href="./?name=' . htmlentities($_GET['name']) . '">Link</a>';

//--------------------------------------------------
// Encoding
// PHP just assumes the string is UTF-8 (since 5.4).
// Without a charset declaration, the browser will also guess at the encoding.
// For example, this classic UTF-7 value for Internet Explorer.

	$_GET['value'] = '+ADw-script+AD4-alert(1)+ADw-+AC8-script+AD4-';

	header('Content-Type: text/html; charset=');
	header('X-Content-Type-Options: -');

	$html = '<p>' . htmlentities($_GET['value']) . '</p>';

//--------------------------------------------------
// General weirdness

	$_GET['email'] = 'b@example.com -X/www/example.php';

	$parameters = '-f' . $_GET['email'];

	// $parameters = '-f' . escapeshellarg($_GET['email']);

	mail('a@example.com', 'Subject', 'Message', NULL, $parameters);

	// It's not possible to safely escape values in $additional_parameters for mail()

?>