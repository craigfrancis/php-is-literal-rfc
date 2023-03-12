<?php

//--------------------------------------------------
// Ignore, would be implemented within PHP

	class TemplateLiteral {
		private $strings = [];
		private $values = [];
		function __construct($strings, $values = []) {
			$this->strings = $strings;
			$this->values = $values;
			if (count($strings) == count($values)) {
				$this->strings[] = '';
			}
		}
		function getStringParts() {
			return $this->strings;
		}
		function getValues() {
			return $this->values;
		}
		function __concatenate($b) {
			$a_strings = $this->strings;
			$b_strings = $b->getStringParts();
			$join_string = array_pop($a_strings) . array_shift($b_strings);
			$new_strings = array_merge($a_strings, [$join_string], $b_strings);
			return new TemplateLiteral($new_strings, array_merge($this->values, $b->getValues()));
		}
		function __toString() {
			$return = '';
			foreach ($this->strings as $i => $string) {
				$return .= $string;
				$return .= ($this->values[$i] ?? '');
			}
			return $return;
		}
	}

//--------------------------------------------------
// Basic examples

	/*
		$t = ```Hi $name```;
	*/

		$t = new TemplateLiteral(['Hi '], ['NAME']);
		echo $t . "\n\n";

	/*
		$t = ```Hi $name, today it is $weather hope you enjoy yourself.```;
	*/

		$t = new TemplateLiteral(['Hi ', ', today it is ', ', hope you enjoy yourself.'], ['NAME', 'WEATHER']);
		echo $t . "\n\n";

	/*
		$a = ```SELECT * FROM $table WHERE deleted IS NULL```;
		if (true) {
			$a .= ``` AND id = $id```;
		}
	*/

		$a = new TemplateLiteral(['SELECT * FROM ', ' WHERE deleted IS NULL'], ['table']);
		if (true) {
			$a = $a->__concatenate(new TemplateLiteral([' AND id = '], [123]));
		}
		echo $a . "\n\n";

	/*
		$a = ```SELECT * FROM table WHERE deleted```;
		if (true) {
			$a .= ``` IS NULL```;
		} else {
			$a .= ``` IS NOT NULL```;
		}
	*/

		$a = new TemplateLiteral(['SELECT * FROM table WHERE deleted']);
		if (true) {
			$a = $a->__concatenate(new TemplateLiteral([' IS NULL']));
		} else {
			$a = $a->__concatenate(new TemplateLiteral([' IS NOT NULL']));
		}
		echo $a . "\n\n";

//--------------------------------------------------
// A basic value-object for MySQL identifiers.

	class Identifier {
		private $value = null;
		function __construct($value) {
			$this->value = $value;
		}
		function __toString() {
			return '`' . str_replace('`', '``', $this->value) . '`';
		}
	}

//--------------------------------------------------
// Example

	//--------------------------------------------------

		$where_sql = 'true';

	//--------------------------------------------------

		$archive = (intval($_GET['archive'] ?? 0) === 1);

		/*
			$where_sql .= ``` AND
				u.deleted ``` . ($archive ? ```IS NOT NULL``` : ```IS NULL```);
		*/

		$where_sql .= new TemplateLiteral([' AND
				u.deleted ', ($archive ? 'IS NOT NULL' : 'IS NULL')]); // Close enough

	//--------------------------------------------------

		/*
			$where_sql = ```u.deleted IS NULL```;
		*/

		$where_sql = new TemplateLiteral(['u.deleted IS NULL']);

	//--------------------------------------------------

		$name = ($_GET['name'] ?? 'example');
		if ($name) {

			$name_wildcard = '%' . $name . '%';

			/*
				$where_sql .= ``` AND
					u.name LIKE $name_wildcard```;
			*/

			$where_sql = $where_sql->__concatenate(new TemplateLiteral([' AND
				u.name LIKE '], [$name_wildcard]));

		}

	//--------------------------------------------------

		/*
			$sql = ```
				SELECT
					u.name,
					u.email
				FROM
					user AS u
				WHERE
					$where_sql```;
		*/

		$sql = new TemplateLiteral(['
			SELECT
				u.name,
				u.email
			FROM
				user AS u
			WHERE
				']);

		$sql = $sql->__concatenate($where_sql);

	//--------------------------------------------------

		$order = new Identifier($_GET['sort'] ?? 'email');

		/*
			$sql .= ```
				ORDER BY
					$order```;
		*/

		$sql = $sql->__concatenate(new TemplateLiteral(['
			ORDER BY
				'], [$order]));

	//--------------------------------------------------

		$page = intval($_GET['page'] ?? 0);

		/*
			$sql .= ```
				LIMIT
					$page, 10```;
		*/

		$sql = $sql->__concatenate(new TemplateLiteral(['
			LIMIT
				', ', 10'], [$page]));

	//--------------------------------------------------

		echo $sql;

?>