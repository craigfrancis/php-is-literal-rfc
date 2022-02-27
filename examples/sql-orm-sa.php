<?php

	// 1) We are protecting against Injection Vulnerabilities.
	//    We cannot protect against every kind of issue, e.g.
	//
	//      $sql = 'DELETE FROM my_table WHERE my_date >= ?';
	//
	//      $parameters = [$_GET['date']]; // '0000-00-00' might be an issue.
	//
	//      https://wiki.php.net/rfc/is_literal#limitations
	//
	// 2) We cannot protect against developers who are clearly
	//    trying to bypass these checks, e.g. using eval & var_export

//--------------------------------------------------
// The library

	class orm {

		//--------------------------------------------------
		// Common

			protected int $protection_level = 1;
				// 0 = No checks, could be useful on the production server.
				// 1 = Just warnings, the default.
				// 2 = Exceptions, for anyone who wants to be absolutely sure.

			function literal_check(mixed $var): void {
				if (!function_exists('is_literal') || is_literal($var)) {
					// Fine - This is a programmer defined string (bingo), or not using PHP 8.1
				} else if ($var instanceof unsafe_value) {
					// Fine - Not ideal, but at least they know this one is unsafe.
				} else if ($this->protection_level === 0) {
					// Fine - Programmer aware, and is choosing to disable this check everywhere.
				} else if ($this->protection_level === 1) {
					trigger_error('Non-literal value detected!', E_USER_WARNING);
				} else {
					throw new Exception('Non-literal value detected!');
				}
			}
			function enforce_injection_protection(): void {
				$this->protection_level = 2;
			}
			function unsafe_disable_injection_protection(): void {
				$this->protection_level = 0; // Not recommended, try `new unsafe_value('XXX')` for special cases.
			}

		//--------------------------------------------------
		// Example, basic

			/**
			 * @param literal-string $sql
			 * @param array<int, int|string> $parameters
			 */
			public function where(string $sql, array $parameters = []): void {

				$this->literal_check($sql);

				print_r($sql);
				echo "\n\n--------------------------------------------------\n\n";

			}

		//--------------------------------------------------
		// Example, CakePHP - it's WHERE clause can be a
		// fairly complicated array, and easy to get wrong.

			/**
			 * This extended type checking doesn't really work
			 * with the Static Analysis tools.
			 *
			 * @param array<int, literal-string|array<mixed>>|array<literal-string, int|string|array<mixed>> $conditions
			 */
			public function find(string $finder, array $conditions): void {

				print_r($this->_addConditions($conditions));
				echo "\n--------------------------------------------------\n\n";

			}

			/**
			 * @param array<int, literal-string|array<mixed>>|array<literal-string, int|string|array<mixed>> $conditions
			 * @param literal-string $conjunction
			 * @return array{literal-string, array<int, mixed>}
			 */
			private function _addConditions(array $conditions, string $conjunction = 'AND'): array {

				// https://github.com/cakephp/cakephp/blob/ab052da10dc5ceb2444c29aef838d10844fe5995/src/Database/Expression/QueryExpression.php#L654

				$operators = ['and', 'or', 'xor'];

				$sql = [];
				$parameters = [];

				foreach ($conditions as $k => $c) {

					if (is_numeric($k)) {

						if (is_array($c)) {
							/** @var array<int, array<mixed>> $sub_conditions */
							$sub_conditions = $c;
							list($new_sql, $new_parameters) = $this->_addConditions($sub_conditions, 'AND');
							$sql[] = $new_sql;
							$parameters = array_merge($parameters, $new_parameters);
						} else if (is_string($c)) {
							$this->literal_check($c);
							$sql[] = $c;
						}

					} else {

						$operatorId = array_search(strtolower($k), $operators);
						if ($operatorId !== false) {
							/** @var array<literal-string, int|string|array<mixed>> $sub_conditions */
							$sub_conditions = $c;
							list($new_sql, $new_parameters) = $this->_addConditions($sub_conditions, $operators[$operatorId]);
							$sql[] = $new_sql;
							$parameters = array_merge($parameters, $new_parameters);
						} else {
							$this->literal_check($k);
							$sql[] = $k . ' = ?';
							$parameters[] = $c;
						}

					}

				}

				/** @var literal-string $sql */
				$sql = '(' . implode(' ' . $conjunction . ' ', $sql) . ')';

				return [$sql, $parameters];

			}

	}

	class unsafe_value {
		private string $value = '';
		function __construct(string $unsafe_value) {
			$this->value = $unsafe_value;
		}
		function __toString(): string {
			return $this->value;
		}
	}

//--------------------------------------------------
// Setup

	class articles extends orm {
	}

	$articles = new articles();

	$id = sprintf((string) ($_GET['id'] ?? '1')); // Using sprintf to mark as a non-literal string

//--------------------------------------------------
// Basic WHERE example, as found in:
// - Doctrine (Query Builder)
// - Propel (Model Criteria)
// - RedBean (find)

	$articles->where('id = ?', [$id]);

	$articles->where('id = ' . $id); // INSECURE

//--------------------------------------------------
// CakePHP, Example 1

	$articles->find('all', [
			'category_id' => $id,
		]);

	$articles->find('all', [
			'category_id = ' . $id, // INSECURE
		]);

//--------------------------------------------------
// CakePHP, Example 2

	$articles->find('all', [
			'OR' => [
				'category_id IS NULL',
				'category_id' => $id,
			],
		]);

	$articles->find('all', [
			'OR' => [
				'category_id IS NULL',
				'category_id = ' . $id, // INSECURE
			],
		]);

//--------------------------------------------------
// CakePHP, Example 3

	$conjunction = ((string) ($_GET['conjunction'] ?? 'OR'));

	$articles->find('all', [
			$conjunction => [ // INSECURE
				'category_id IS NULL',
				'category_id' => $id,
			],
		]);

?>