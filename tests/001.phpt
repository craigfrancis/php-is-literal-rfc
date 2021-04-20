<?php

//--------------------------------------------------
//
// https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/run.md
//
// TEST=1
//   A basic test on the string concat performance.
//   It's intended to focus on the worse possible
//   case, and is currently showing:
//   (1.341s to 1.358s = +0.017s, about +1.3%)
//
// TEST=2
//   In an earlier version of the patch, the `$x = $_GET`
//   test from micro_bench.php was quite a bit slower.
//
// TEST=3
//   Extending the previous test, instead simply accessing
//   a single value from the $_GET array, there does not
//   appear to be any difference (because it's not really
//   doing anything.
//
//--------------------------------------------------

const N = 5000000;

function gethrtime()
{
  $hrtime = hrtime();
  return (($hrtime[0]*1000000000 + $hrtime[1]) / 1000000000);
}

function start_test()
{
  ob_start();
  return gethrtime();
}

function end_test($start)
{
  $end = gethrtime();
  ob_end_clean();
  echo ' ' . number_format($end-$start,3) . "\n";
}

$test = intval($_ENV['TEST'] ?? 1);

//--------------------------------------------------

if ($test === 1) {

	$t = start_test();
	$a = 'a';
	$b = 'b';
	$c = 'c';
	for ($i = 0; $i < N; ++$i) {
		$x = $a . 'b' . $c . 1 . " [$a $b]";
	}
	end_test($t);

} else if ($test === 2) {

	$_GET['a'] = 1;
	$t = start_test();
	for ($i = 0; $i < N; ++$i) {
		$x = $_GET['a'];
	}
	end_test($t);

} else if ($test === 3) {

	$t = start_test();
	for ($i = 0; $i < N; ++$i) {
		$x = $_GET;
	}
	end_test($t);

} else {

	echo 'Invalid test (' . $test . ')' . "\n";

}

?>