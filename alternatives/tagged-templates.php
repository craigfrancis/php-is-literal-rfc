<?php

//--------------------------------------------------
// Doctrine QueryBuilder

	$qb->select('u')
		->from('User', 'u')
		->where(```u.id = ?1```)
		->setParameter(1, $_GET['id']);

	$qb->select('u')
		->from('User', 'u')
		->where($qb->expr()->andX(
			$qb->expr()->eq(```u.type_id```, ```?1```),
			$qb->expr()->isNull(```u.deleted```),
		))
		->setParameter(1, $_GET['type_id']);

//--------------------------------------------------
// Laravel

	DB::table('user')->whereRaw(```CONCAT(name_first, " ", name_last) LIKE ?```, $search . '%');

//--------------------------------------------------
// Plain SQL

	//--------------------------------------------------

		$where_sql = 'true';

	//--------------------------------------------------

		$archive = (intval($_GET['archive'] ?? 0) === 1);

		$where_sql .= ``` AND
				u.deleted ``` . ($archive ? ```IS NOT NULL``` : ```IS NULL```);

	//--------------------------------------------------

		$name = ($_GET['name'] ?? '');
		if ($name) {

			$name_wildcard = '%' . $name . '%';

			$where_sql .= ``` AND
				u.name LIKE $name_wildcard```;

		}

	//--------------------------------------------------

		$sql = ```
			SELECT
				u.name,
				u.email
			FROM
				user AS u
			WHERE
				``` . $where_sql;

	//--------------------------------------------------

		$order = new Identifier($_GET['sort'] ?? 'email');

		$sql .= ```
			ORDER BY
				$order```;

	//--------------------------------------------------

		$page = intval($_GET['page'] ?? 0);

		$sql .= ```
			LIMIT
				$page, 10```;

	//--------------------------------------------------

		print_r($sql);

		// $db->query($sql);

?>