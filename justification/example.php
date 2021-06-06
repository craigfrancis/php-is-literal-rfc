<?php

//--------------------------------------------------
// How an ORM or HTML Templating system can use:

	class db {

		private $pdo = NULL;
		protected $protection_level = 1;
			// 1 = Just warnings
			// 2 = Exceptions, maybe the default in a few years.

		function literal_check($var) {
			if (!function_exists('is_literal') || is_literal($var)) {
				// Fine - This is a programmer defined string (bingo), or not using PHP 8.1
			} else if ($var instanceof unsafe_sql) {
				// Fine - Not ideal, but at least they know this one is unsafe.
			} else if ($this->protection_level === 0) {
				// Fine - Programmer aware, and is choosing to disable this check everywhere.
			} else if ($this->protection_level === 1) {
				trigger_error('Non-literal detected!', E_USER_WARNING);
			} else {
				throw new Exception('Non-literal detected!');
			}
		}
		function enforce_injection_protection() {
			$this->protection_level = 2;
		}
		function unsafe_disable_injection_protection() {
			$this->protection_level = 0; // Not recommended, try a `new unsafe_sql('XXX')` for special cases.
		}

		function where($sql, $parameters = [], $aliases = []) {
			$this->literal_check($sql); // Used any time an argument should be checked.
			// ...
		}

		function query($sql, $parameters = [], $aliases = []) {

			if (!$this->pdo) {
				 $this->pdo = new PDO('mysql:dbname=...;host=...', '...', '...', [PDO::ATTR_EMULATE_PREPARES => false]);
			}

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

			$statement = $this->pdo->prepare($sql);
			$statement->execute($parameters);
			return $statement->fetchAll();

		}

	}

	$db = new db();

//--------------------------------------------------
// While you should never need it; the example
// library above will also accept this value-object:

	class unsafe_sql {
		private $value = '';
		function __construct($unsafe_sql) {
			$this->value = $unsafe_sql;
		}
		function __toString() {
			return $this->value;
		}
	}

//--------------------------------------------------
// Normal use:

	$id = sprintf('1'); // Using sprintf() so it's not marked as a literal, e.g. $_GET['id']

	var_dump($db->query('SELECT name FROM user WHERE id = ?', [$id]));

	try {
		var_dump($db->query('SELECT name FROM user WHERE id = ' . $id));
	} catch (exception $e) {
		var_dump('Caught SQLi vulnerability!');
	}

	// Doctrine can protect itself with DQL and SQL:
	//   https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/security.html
	//	 https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/security.html
	//
	// For example:
	//   $em->createQuery("SELECT u FROM User u WHERE u.id = $_GET[id]");
	//   $qb->where("u.id = $_GET[id]");
	//
	// Same with Drupal:
	//	https://www.drupal.org/node/101496

	echo "\n";

//--------------------------------------------------
// Complex example, and still doesn't use unsafe_sql:

	$parameters = [];

	$where_sql = 'u.deleted IS NULL';



	$name = ($_GET['name'] ?? NULL);
	if ($name) {
		$where_sql .= ' AND u.name LIKE ?';
		$parameters[] = '%' . $name . '%';
	}



	$ids = ($_GET['ids'] ?? '1,2,3');
	$ids = array_filter(explode(',', $ids));

	if (count($ids) > 0) {
		 $in_sql = '?';
		 for ($k = count($ids); $k > 1; $k--) {
			 $in_sql .= ',?'; // Could also use implode()
		 }
		 $where_sql .= ' AND u.id IN (' . $in_sql . ')'; // Database abstractions can simplify this.
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



	$order_fields = ['name', 'email'];
	$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);
	$sql .= '
		ORDER BY ' . $order_fields[$order_id]; // Limited to known-safe fields.



	$sql .= '
		LIMIT
			 ?, ?';
	$parameters[] = 0;
	$parameters[] = 3;




	var_dump($sql, $parameters);
	var_dump($db->query($sql, $parameters));

	echo "\n";

//--------------------------------------------------
// And if table/field/etc names cannot be included
// in your PHP script (e.g. a CMS like Drupal), it's
// still possible to use:

	$parameters = [];

	$aliases = [
			'with_1'  => sprintf('w1'), // Using sprintf() so it's not marked as a literal.
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

	var_dump($sql, $parameters, $aliases);
	var_dump($db->query($sql, $parameters, $aliases));

	echo "\n";

//--------------------------------------------------
// Use in other contexts

	$query = ($_GET['q'] ?? NULL);

	function html_template() { return NULL; }
	function run_command()   { return NULL; }
	function run_eval()	     { return NULL; }

	var_dump(html_template('<input name="q" value="?" />', [
			$query
		]));

	var_dump(run_command('/my/script.sh ?', [
			$query,
		]));

	var_dump(run_eval('echo ?;', [
			$query,
		]));

	// $additional_params in mail()
	// $expression in $xpath->query()
	// $pattern in preg_match()
	// etc...

?>