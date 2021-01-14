<?php

//--------------------------------------------------
// Setup

	define('DEBUG_MODE', true);

	require_once('./html-snippets/url.php');
	require_once('./html-snippets/html-template.php');

	header("Content-Security-Policy: default-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data:;");

//--------------------------------------------------
// Basic example

	$class = ($_GET['class'] ?? 'warning');
	$name = ($_GET['name'] ?? 'Craig');

	echo ht('<strong class="?">?</strong>', [$class, $name]);

//--------------------------------------------------
// A link

	$url = url('/example/path/', ['name' => $name]);

	// $url = url('mailto:name@example.com', ['subject' => 'Hi']);

	// $url = 'javascript:alert(1)';
	// $url = url('javascript:alert(1)');

	echo ht('<a href="?">?</a>', [$url, $name]);

//--------------------------------------------------
// An image

	$img = new url_data('./html-snippets/red.gif', 'image/gif');

	$size = intval($_GET['size'] ?? 10); // Must be an int
	$alt = ($_GET['alt'] ?? 'A Red Square');

	echo ht('<img src="?" width="?" height="?" alt="?" />', [$img, $size, $size, $alt]);

//--------------------------------------------------
// Complicated example

	$html = '';
	$parameters = [];

	$html = '<p>Hi <span>?</span>,</p>';
	$parameters[] = $name;

	$last = new DateTimeImmutable('Yesterday');
	if ($last) {
		$html .= '<p>Last Login: <time datetime="?">?</time></p>';
		$parameters[] = $last->format('c');
		$parameters[] = $last->format('Y-m-d');
	}

	$messages = ['A', 'B', 'C'];
	if ($messages) {
		$html .= '<p data-details="?">You Have: <a href="?">?</a></p>';
		$parameters[] = json_encode($messages);
		$parameters[] = url(['view' => 'messages']);
		$parameters[] = count($messages) . ' Message' . (count($messages) == 1 ? '' : 's');
	}

	$html .= '<p><a href="?">Homepage</a></p>';
	$parameters[] = url('/');

	echo ht($html, $parameters);

?>