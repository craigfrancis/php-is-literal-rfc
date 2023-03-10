<?php

//--------------------------------------------------
// Doctrine QueryBuilder

	$qb->select('u')
		->from('User', 'u')
		->where('u.id = ?1')
		->setParameter(1, $_GET['id']);

	$qb->select('u')
		->from('User', 'u')
		->where($qb->expr()->andX(
			$qb->expr()->eq('u.type_id', '?1'),
			$qb->expr()->isNull('u.deleted'),
		))
		->setParameter(1, $_GET['type_id']);

//--------------------------------------------------
// Laravel

	DB::table('user')->whereRaw('CONCAT(name_first, " ", name_last) LIKE ?', $search . '%');

//--------------------------------------------------
// Plain SQL

	//--------------------------------------------------

		$parameters = [];
		$identifiers = [];
		$where_sql = 'u.deleted IS NULL';

	//--------------------------------------------------

		$name = ($_GET['name'] ?? '');
		if ($name) {

			$parameters[] = '%' . $name . '%';

			$where_sql .= ' AND
				u.name LIKE ?';

		}

	//--------------------------------------------------

		$sql = '
			SELECT
				u.name,
				u.email
			FROM
				user AS u
			WHERE
				' . $where_sql;

	//--------------------------------------------------

		$identifiers['o'] = ($_GET['sort'] ?? 'email');

		$sql .= '
			ORDER BY
				{o}';

	//--------------------------------------------------

		$parameters[] = intval($_GET['page'] ?? 0);

		$sql .= '
			LIMIT
				?, 10';

	//--------------------------------------------------

		print_r([$sql, $parameters, $identifiers]);

		// $db->query($sql, $parameters, $identifiers);

?>