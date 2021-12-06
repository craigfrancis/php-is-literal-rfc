<?php

/*

Test with:

	./taint-checking.php?id=id&img=%2F+onerror%3Dalert%281%29&a1=javascript%3Aalert%281%29&a2=%2F%27+onerror%3D%27alert%281%29

	composer require --dev vimeo/psalm
	./vendor/bin/psalm --init ./ 1
	./vendor/bin/psalm taint-checking.php
	./vendor/bin/psalm --taint-analysis taint-checking.php

	composer require --dev phpstan/phpstan
	./vendor/bin/phpstan analyse -l 9 taint-checking.php

Did they notice any problems?

*/

//--------------------------------------------------
// Missing quotes
//  e.g. $id = 'id'

	$mysqli = new mysqli('localhost', 'test', 'test', 'test');

	$sql = '
	   SELECT * FROM users WHERE id = ' . $mysqli->real_escape_string((string) $_GET['id']);

	// SELECT * FROM users WHERE id = id

//--------------------------------------------------
// Missing quotes
//  e.g. $img = '/ onerror=alert(1)'

	$html1 = "
	   <img src=" . htmlentities((string) $_GET['img']) . " alt='' />";

	// <img src=/ onerror=alert(1) alt='' />

//--------------------------------------------------
// Inline JavaScript
//  e.g. $a1  = 'javascript:alert(1)'

	$html2 = "
	   <a href='" . htmlentities((string) $_GET['a1']) . "'>Link 1</a>";

	// <a href='javascript:alert(1)'>Link 1</a>

//--------------------------------------------------
// Single quotes aren't always escaped
// e.g. $a2  = '/' onerror='alert(1)'
//
//  Before PHP 8.1
//    https://github.com/php/php-src/commit/50eca61f68815005f3b0f808578cc1ce3b4297f0
//  Java, Apache Commons
//   https://commons.apache.org/proper/commons-text/javadocs/api-release/org/apache/commons/text/StringEscapeUtils.html#escapeHtml4(java.lang.String)
//   "Note that the commonly used apostrophe escape character (&apos;) is not a legal entity and so is not supported"

	$html3 = "
	   <a href='" . htmlentities((string) $_GET['a2']) . "'>Line 2</a>";

	// <a href='/' onerror='alert(1)'>Line 2</a>

//--------------------------------------------------
// Keep static analysis tools happy

	/** @psalm-suppress ForbiddenCode */
	var_dump($sql, $html1, $html2, $html3);

?>