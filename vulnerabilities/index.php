<?php

	header("Content-Security-Policy: default-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; frame-src 'self'; img-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");

	$mysqli = new mysqli('localhost', 'test', 'test', 'test');

	$examples = [];

	foreach (glob('./*.php') as $example) {
		if (preg_match('/\/([0-9]+)\.(.*)\.php/', $example, $matches)) {
			$examples[intval($matches[1])] = ['ref' => $matches[1], 'name' => $matches[2], 'path' => $example];
		}
	}

	ksort($examples, SORT_NUMERIC);

	$example_id = intval($_GET['example'] ?? 0);
	$example_view = ($examples[$example_id] ?? NULL);

?>
<!DOCTYPE html>
<html lang="en-GB" xml:lang="en-GB" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="<?= ($example_id == 5 ? 'UTF-7' : 'UTF-8') ?>" />
	<title>Vulnerabilities</title>
	<style>
		div {
			border: 2px solid #000;
			padding: 0.5em;
			margin: 1em 0;
		}
	</style>
</head>
<body>

	<ol>
		<?php foreach ($examples as $example) { ?>
			<li><a href="./?example=<?= htmlentities(urlencode($example['ref'])) ?>"><?= htmlentities($example['name']) ?></a></li>
		<?php } ?>
	</ol>

	<?php if ($example_view) { ?>

		<div><?= nl2br(str_replace("\t", '&#xA0;&#xA0;&#xA0;&#xA0;', htmlentities(file_get_contents($example_view['path'])))) ?></div>

		<div><?php require_once($example_view['path']) ?></div>

	<?php } ?>

</body>
</html>