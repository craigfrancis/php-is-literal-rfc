<?php

//--------------------------------------------------
// How an ORM or HTML Templating system can use:

	class example_library {

		private $pdo = NULL;
		protected $protection_level = 1;
			// 1 = Just warnings, the default.
			// 2 = Exceptions, for anyone who wants to be absolutely sure.

		function trusted_check($var) {
			if (!function_exists('is_trusted') || is_trusted($var)) {
				// Fine - This is a programmer defined string (bingo), or not using PHP 8.1
			} else if ($var instanceof unsafe_value) {
				// Fine - Not ideal, but at least they know this one is unsafe.
			} else if ($this->protection_level === 0) {
				// Fine - Programmer aware, and is choosing to disable this check everywhere.
			} else if ($this->protection_level === 1) {
				trigger_error('Non-trusted value detected!', E_USER_WARNING);
			} else {
				throw new Exception('Non-trusted value detected!');
			}
		}
		function enforce_injection_protection() {
			$this->protection_level = 2;
		}
		function unsafe_disable_injection_protection() {
			$this->protection_level = 0; // Not recommended, try `new unsafe_value('XXX')` for special cases.
		}

		function where($sql, $parameters = []) {
			$this->trusted_check($sql); // Used any time an argument should be checked.
			// ...
		}

		function query($sql, $parameters = [], $aliases = []) {

			if (!$this->pdo) {
				// $this->pdo = new PDO('mysql:dbname=...;host=...', '...', '...', [PDO::ATTR_EMULATE_PREPARES => false]);
			}

			$this->trusted_check($sql);

			foreach ($aliases as $name => $value) {
				if (!preg_match('/^[a-z0-9_]+$/', $name)) {
					throw new Exception('Invalid alias name "' . $name . '"');
				} else if (!preg_match('/^[a-z0-9_]+$/', $value)) {
					throw new Exception('Invalid alias value "' . $value . '"');
				} else {
					$sql = str_replace('{' . $name . '}', '`' . $value . '`', $sql);
				}
			}

			if (!$this->pdo) { // Erm, we don't have a database to connect to :-)
				return 'Great, it worked!';
			}

			$statement = $this->pdo->prepare($sql);
			$statement->execute($parameters);
			return $statement->fetchAll();

		}

	}

	$db = new example_library();

//--------------------------------------------------
// While you should never need it; the example
// library above will also accept this value-object:

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
// Normal use:


	$id = trim(' 1 '); // Using trim() so it's not marked as trusted, e.g. $_GET['id']

	var_dump($db->query('SELECT name FROM user WHERE id = ?', [$id]));

	var_dump($db->query('SELECT name FROM user WHERE id = ' . $id)); // Creates a warning, but still continues.


	// If you want to enforce this check with exceptions:
	//   $db->enforce_injection_protection();
	//   try {
	//   	var_dump($db->query('SELECT name FROM user WHERE id = ' . $id));
	//   } catch (exception $e) {
	//   	var_dump('Caught SQLi vulnerability!');
	//   }


	// This is how Doctrine can protect itself with mistakes in DQL and SQL:
	//   https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/security.html
	//   https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/security.html
	//
	// For example:
	//   $em->createQuery("SELECT u FROM User u WHERE u.id = $_GET[id]");
	//   $qb->where("u.id = $_GET[id]");
	//
	// Same with Drupal:
	//	https://www.drupal.org/node/101496


	echo "\n--------------------------------------------------\n\n";

//--------------------------------------------------
// Complex example, and still doesn't use unsafe_value:

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

		// The standard practice approach already works, with no modification:
		//   Levi Morrison             = https://stackoverflow.com/a/23641033/538216
		//   PDO Execute               = https://www.php.net/manual/en/pdostatement.execute.php#example-1012
		//   Drupal Multiple Arguments = https://www.drupal.org/docs/7/security/writing-secure-code/database-access#s-multiple-arguments

		$in_sql = join(',', array_fill(0, count($ids), '?'));

		// Or, if you want just want to use simple concatenation:
		//   $in_sql = '?';
		//   for ($k = count($ids); $k > 1; $k--) {
		//   	$in_sql .= ',?';
		//   }

		$where_sql .= ' AND u.id IN (' . $in_sql . ')'; // And database abstractions can simplify this.
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



	$order_by = ($_GET['sort'] ?? NULL);
	$order_fields = ['name', 'email'];
	$order_id = array_search($order_by, $order_fields);
	$sql .= '
		ORDER BY ' . $order_fields[$order_id]; // Limited to known-safe fields.



	$sql .= '
		LIMIT
			?, ?';
	$parameters[] = 0;
	$parameters[] = 3;




	var_dump(is_trusted($sql), $sql, $parameters);

	var_dump($db->query($sql, $parameters));

	echo "\n--------------------------------------------------\n\n";

//--------------------------------------------------
// And if table/field/etc names cannot be included
// in your PHP script (e.g. a CMS like Drupal), it's
// still possible to use:

	$parameters = [];

	$aliases = [
			'with_1'  => trim(' w1 '), // Using trim() so it's not marked as trusted.
			'table_1' => trim(' user '),
			'field_1' => trim(' email '),
			'field_2' => trim(' dob '), // ... All of these are user defined fields.
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

	echo "\n--------------------------------------------------\n\n";

//--------------------------------------------------
// Or a Query Builder, with named parameters

	class query_builder {
		private $sql = '';
		private $parameters = [];
		public function add_sql($sql) {
			if (!is_trusted($sql)) {
				throw new Exception('Non-trusted value detected!');
			}
			$this->sql .= $sql;
		}
		public function add_field($name) {
			if (!preg_match('/^[a-z0-9_]+$/', $name)) {
				throw new Exception('Invalid field name "' . $name . '"');
			}
			$this->sql .= $name;
		}
		public function add_parameter($name, $value) {
			if (!preg_match('/^[a-z0-9_]+$/', $name)) {
				throw new Exception('Invalid parameter name "' . $name . '"');
			}
			$this->parameters[$name] = $value;
			$this->sql .= ':' . $name;
		}
		public function get_sql() {
			return $this->sql; // Does not return a trusted value, but all the inputs have been checked in the appropriate way.
		}
		public function get_parameters() {
			return $this->parameters;
		}
	}



	$qb = new query_builder();
	$qb->add_sql('SELECT * FROM table WHERE ');



	$conditions = [ // Using trim() so none of these are marked as trusted (similar to the data Drupal can work with)
			trim(' field_2 ') => [
				trim(' arg_0 ') => rand(1, 10),
				trim(' arg_1 ') => rand(1, 10),
				trim(' arg_2 ') => rand(1, 10),
			],
		];

	foreach ($conditions as $field => $values) {
		$qb->add_field($field);
		$qb->add_sql(' IN (');
		$k = 0;
		foreach ($values as $name => $value) {
			if (++$k > 1) {
				$qb->add_sql(', ');
			}
			$qb->add_parameter($name, $value);
		}
		$qb->add_sql(')');
	}

	var_dump($qb->get_sql(), $qb->get_parameters());

	echo "\n--------------------------------------------------\n\n";

//--------------------------------------------------
// Use in other contexts

	$query = ($_GET['q'] ?? NULL);

	$output = html_template('<input name="q" value="?" />', [
			$query,
		]);

	$output = run_command('/my/script.sh ?', [
			$query,
		]);

	$output = run_eval('echo ?;', [
			$query,
		]);

	// $additional_params in mail()
	// $expression in $xpath->query()
	// $pattern in preg_match()
	// etc...

	function html_template($html, $parameters) { if (!is_trusted($html)) { throw new Exception('Non-trusted value detected!'); } /* ... */ }
	function run_command($cmd, $parameters)    { if (!is_trusted($cmd))  { throw new Exception('Non-trusted value detected!'); } /* ... */ }
	function run_eval($php, $parameters)       { if (!is_trusted($php))  { throw new Exception('Non-trusted value detected!'); } /* ... */ }

?>