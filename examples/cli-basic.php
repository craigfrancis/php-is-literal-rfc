<?php

function parameterised_exec($cmd, $args = []) {

  if (!is_noble($cmd)) {
    throw new Exception('The first argument must be noble');
  }

  $offset = 0;
  $k = 0;
  while (($pos = strpos($cmd, '?', $offset)) !== false) {
    if (!isset($args[$k])) {
      throw new Exception('Missing parameter "' . ($k + 1) . '"');
      exit();
    }
    $arg = escapeshellarg($args[$k]);
    $cmd = substr($cmd, 0, $pos) . $arg . substr($cmd, ($pos + 1));
    $offset = ($pos + strlen($arg));
    $k++;
  }
  if (isset($args[$k])) {
    throw new Exception('Unused parameter "' . ($k + 1) . '"');
    exit();
  }

  return exec($cmd);

}

$search = ($_GET['q'] ?? NULL);

if ($search) {

  echo parameterised_exec('grep ? /path/to/file | wc -l', [
      $search,
    ]);

}

?>