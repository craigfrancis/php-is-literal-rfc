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

			protected $protection_level = 1;
				// 0 = No checks, could be useful on the production server.
				// 1 = Just warnings, the default.
				// 2 = Exceptions, for anyone who wants to be absolutely sure.

			function literal_check($var) {
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
			function enforce_injection_protection() {
				$this->protection_level = 2;
			}
			function unsafe_disable_injection_protection() {
				$this->protection_level = 0; // Not recommended, try `new unsafe_value('XXX')` for special cases.
			}

		//--------------------------------------------------
		// Example

			function query($sql, $parameters = [], $aliases = []) {

				$this->literal_check($sql);

				foreach ($aliases as $name => $value) {
					if (!preg_match('/^[a-z0-9_]+$/', $name)) {
						throw new Exception('Invalid alias name "' . $name . '"');
					} else if (!preg_match('/^[a-z0-9_]+$/', $value)) {
						throw new Exception('Invalid alias value "' . $value . '"');
					} else {
						$sql = str_replace('{' . $name . '}', '`' . $value . '`', $sql);
					}
				}

				var_dump($sql);
				echo "\n\n";

			}

			// https://github.com/drupal/drupal/blob/8eb8dcc8425295d1a4278613031812bff7d98c15/includes/database/database.inc#L2036
			function placeholders($arguments) {
				return implode(',', array_fill(0, count($arguments), '?'));
			}

	}

	class unsafe_value {
		private $value = '';
		function __construct($unsafe_value) {
			$this->value = $unsafe_value;
		}
		function __toString() {
			return $this->value;
		}
	}

//--------------------------------------------------
// Example 1


	$db = new db();

	$id = sprintf('123'); // Using sprintf to mark as a non-literal string

	$db->query('SELECT name FROM user WHERE id = ?', [$id]);

	$db->query('SELECT name FROM user WHERE id = ' . $id); // INSECURE


//--------------------------------------------------
// Example 2


	$parameters = [];

	$where_sql = 'u.deleted IS NULL';



	$name = ($_GET['name'] ?? 'MyName');
	if ($name) {
		$where_sql .= ' AND u.name LIKE ?';
		$parameters[] = '%' . $name . '%';
	}



	$ids = [1, 2, 3];
	if (count($ids) > 0) {

		$where_sql .= ' AND u.id IN (' . $db->placeholders($ids) . ')';
		$parameters = array_merge($parameters, $ids);

	}



	$sql = '
		SELECT
			u.name,
			u.email
		FROM
			user AS u
		WHERE
			' . $where_sql;



	$order_by = ($_GET['sort'] ?? 'email');
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


//--------------------------------------------------
// Example 3, with aliases.


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

	$parameters[] = ($_GET['type'] ?? 'admin');

	$db->query($sql, $parameters, $aliases);


?>