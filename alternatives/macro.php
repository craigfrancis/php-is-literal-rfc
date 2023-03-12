<?php

//--------------------------------------------------
// Doctrine QueryBuilder

	$qb->select('u')
		->from('User', 'u')
		->where(sql!('u.id = ?1'))
		->setParameter(1, $_GET['id']);

	$qb->select('u')
		->from('User', 'u')
		->where($qb->expr()->andX(
			$qb->expr()->eq(sql!('u.type_id'), sql!('?1')),
			$qb->expr()->isNull(sql!('u.deleted')),
		))
		->setParameter(1, $_GET['type_id']);

//--------------------------------------------------
// Laravel

	DB::table('user')->whereRaw(sql!('CONCAT(name_first, " ", name_last) LIKE ?'), $search . '%');

//--------------------------------------------------
// Plain SQL

	//--------------------------------------------------

		$where_sql = 'true';

	//--------------------------------------------------

		$archive = (intval($_GET['archive'] ?? 0) === 1);

		$where_sql = sql!($where_sql . ' AND
				u.deleted ' . ($archive ? 'IS NOT NULL' : 'IS NULL'));

	//--------------------------------------------------

		$name = ($_GET['name'] ?? '');
		if ($name) {

			$name_wildcard = '%' . $name . '%';

			$where_sql = sql!($where_sql . ' AND
				u.name LIKE ' . $name_wildcard);

		}

	//--------------------------------------------------

		$sql = sql!('
			SELECT
				u.name,
				u.email
			FROM
				user AS u
			WHERE
				' . $where_sql);

	//--------------------------------------------------

		$order = new Identifier($_GET['sort'] ?? 'email');

		$sql = sql!($sql . '
			ORDER BY
				' . $order);

	//--------------------------------------------------

		$page = intval($_GET['page'] ?? 0);

		$sql = sql!($sql . '
			LIMIT
				' . $page . ', 10');

	//--------------------------------------------------

		print_r($sql);

		// $db->query($sql);

?>