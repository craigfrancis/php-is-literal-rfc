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

	class db {

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
		// Example

			/**
			 * @param literal-string $sql
			 * @param array<int, int|string> $parameters
			 * @param array<string, string> $aliases
			 */
			function query(string $sql, array $parameters = [], array $aliases = []): void {

				$this->literal_check($sql);

				foreach ($aliases as $name => $value) {
					// if (!preg_match('/^[a-z0-9_]+$/', $name))  throw new Exception('Invalid alias name "' . $name . '"');
					// if (!preg_match('/^[a-z0-9_]+$/', $value)) throw new Exception('Invalid alias value "' . $value . '"');
					$sql = str_replace('{' . $name . '}', '`' . str_replace('`', '``', $value) . '`', $sql);
				}

				print_r($sql);
				echo "\n\n";

			}

			/**
			 * https://www.drupal.org/docs/7/security/writing-secure-code/database-access#s-multiple-arguments
			 * https://github.com/drupal/drupal/blob/8eb8dcc8425295d1a4278613031812bff7d98c15/includes/database/database.inc#L2036
			 * https://stackoverflow.com/questions/907806/passing-an-array-to-a-query-using-a-where-clause/23641033#23641033
			 * https://www.php.net/manual/en/pdostatement.execute.php#example-1047
			 * @return literal-string
			 */
			function placeholders(int $count): string {

				$sql = '?';
				for ($k = 1; $k < $count; $k++) {
					$sql .= ',?';
				}
				return $sql;

				// return implode(',', array_fill(0, $count, '?'));

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
// Example 1


	$db = new db();
	// $db->unsafe_disable_injection_protection();

	$id = sprintf((string) ($_GET['id'] ?? '1')); // Use sprintf() to mark as a non-literal string

	$db->query('SELECT name FROM user WHERE id = ?', [$id]);

	$db->query('SELECT name FROM user WHERE id = ' . $id); // INSECURE

	echo '--------------------------------------------------' . "\n";


//--------------------------------------------------
// Example 2


	$parameters = [];

	$where_sql = 'u.deleted IS NULL';



	$name = sprintf((string) ($_GET['name'] ?? 'MyName')); // Use sprintf() to mark as a non-literal string
	if ($name) {

		$where_sql .= ' AND
			u.name LIKE ?';

		$parameters[] = '%' . $name . '%';

	}



	$ids = [1, 2, 3];
	// if (count($ids) > 0) {

		$where_sql .= ' AND
			u.id IN (' . $db->placeholders(count($ids)) . ')';

		$parameters = array_merge($parameters, $ids);

	// }



	$sql = '
		SELECT
			u.name,
			u.email
		FROM
			user AS u
		WHERE
			' . $where_sql;



	$order_by = sprintf((string) ($_GET['sort'] ?? 'email')); // Use sprintf() to mark as a non-literal string
	$order_fields = ['name', 'email'];
	$order_id = array_search($order_by, $order_fields);
	$sql .= '
		ORDER BY
			' . $order_fields[$order_id]; // Limited to known-safe fields.



	$sql .= '
		LIMIT
			?, ?';
	$parameters[] = 0;
	$parameters[] = 3;



	$db->query($sql, $parameters);


	echo '--------------------------------------------------' . "\n\n";


//--------------------------------------------------
// Example 3, field aliases (try to avoid)


	$order_by = sprintf((string) ($_GET['sort'] ?? 'email')); // Use sprintf() to mark as a non-literal string


	$sql = '
		SELECT
			u.name
		FROM
			user AS u
		ORDER BY
			' . $order_by;

	$db->query($sql);


	$sql = '
		SELECT
			u.name
		FROM
			user AS u
		ORDER BY
			{sort}';

	$db->query($sql, [], [
			'sort' => $order_by,
		]);


	echo '--------------------------------------------------' . "\n\n";


//--------------------------------------------------
// Example 4, bit more complex


	$parameters = [];

	$aliases = [
			'with_1'  => sprintf('w1'), // Using sprintf to mark as a non-literal string
			'table_1' => sprintf('user'),
			'field_1' => sprintf('email'),
			'field_2' => sprintf('dob'), // ... All of these are user defined fields.
		];

	$with_sql = '{with_1} AS (SELECT id, name, type, {field_1} as f1, deleted FROM {table_1})';

	$sql = "
		WITH
			$with_sql
		SELECT
			t.name,
			t.f1
		FROM
			{with_1} AS t
		WHERE
			t.type = ? AND
			t.deleted IS NULL";

	$parameters[] = sprintf((string) ($_GET['type'] ?? 'admin')); // Using sprintf to mark as a non-literal string

	$db->query($sql, $parameters, $aliases);


?>