<?php

//--------------------------------------------------
// Function

	/**
	 * @param literal-string $cmd
	 * @param array<int, string> $args
	 */
	function parameterised_exec(string $cmd, array $args = []): string|false {

		if (function_exists('is_literal') && !is_literal($cmd)) {
			throw new Exception('The first argument must be a literal');
		}

		$offset = 0;
		$k = 0;
		while (($pos = strpos($cmd, '?', $offset)) !== false) {
			if (!isset($args[$k])) {
				throw new Exception('Missing parameter "' . ($k + 1) . '"');
			}
			$arg = escapeshellarg($args[$k]);
			$cmd = substr($cmd, 0, $pos) . $arg . substr($cmd, ($pos + 1));
			$offset = ($pos + strlen($arg));
			$k++;
		}
		if (isset($args[$k])) {
			throw new Exception('Unused parameter "' . ($k + 1) . '"');
		}

		return exec($cmd);

	}

//--------------------------------------------------
// Example

	$search = sprintf((string) ($_GET['q'] ?? 'example'));

	if ($search) {

		echo parameterised_exec('grep ? /path/to/file | wc -l', [
				$search,
			]);

		echo parameterised_exec('grep ' . $search . ' /path/to/file | wc -l');

	}

?>