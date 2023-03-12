<?php

	class FragmentSQL {
		private $value = [];
		function __construct($value) {
			$this->value = implode('', $value);
		}
		function __toString() {
			return $this->value;
		}
	}

	function sql_(...$sql) {
		return new FragmentSQL($sql);
	}

//--------------------------------------------------

	$where_sql = 'true';

//--------------------------------------------------

	$archive = (intval($_GET['archive'] ?? 0) === 1);

	$where_sql = sql_($where_sql . ' AND
			u.deleted ' . ($archive ? 'IS NOT NULL' : 'IS NULL'));

//--------------------------------------------------

	$name = ($_GET['name'] ?? 'example');
	if ($name) {

		$name_wildcard = '%' . $name . '%';

		// Operator overloading outside of the macro:
		//   https://wiki.php.net/rfc/user_defined_operator_overloads
		//   https://wiki.php.net/rfc/userspace_operator_overloading

			$where_sql .= sql_(' AND
				u.name LIKE ', $name_wildcard);

		// Operator overloading inside; the macro won't know
		// the contents of $where_sql, but the variable can
		// be passed to FragmentSQL, which can check the
		// variable type at runtime.

			$where_sql = sql_($where_sql, ' AND
				u.name LIKE ', $name_wildcard);

	}

//--------------------------------------------------

	$sql = sql_('
		SELECT
			u.name,
			u.email
		FROM
			user AS u
		WHERE
			', $where_sql);

//--------------------------------------------------

	$order = ($_GET['sort'] ?? 'email');

	$sql .= sql_('
		ORDER BY
			', $order);

//--------------------------------------------------

	$page = intval($_GET['page'] ?? 0);

	$sql .= sql_('
		LIMIT
			', $page, ', 10');

//--------------------------------------------------

	print_r($sql);

?>