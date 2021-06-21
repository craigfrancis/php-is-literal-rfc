<?php

//--------------------------------------------------
// Eloquent, DB::select
// https://laravel.com/docs/8.x/database#running-a-select-query
// Can only assume the programmer has written the SQL, and provided the user values separately.

	$_GET['active'] = 'active';

	$users = DB::select('select * from users where active = ' . $_GET['active']); // INSECURE

	$users = DB::select('select * from users where active = ?', [$_GET['active']]);

//--------------------------------------------------
// Doctrine, Query Builder
// https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/query-builder.html#high-level-api-methods

	$_GET['id'] = 'id';

	$qb->select('u')
	   ->from('User', 'u')
	   ->where('u.id = ' . $_GET['id']); // INSECURE

	$qb->select('u')
	   ->from('User', 'u')
	   ->where('u.id = :identifier')
	   ->setParameter('identifier', $_GET['id']);

//--------------------------------------------------
// Doctrine, DQL
// https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/dql-doctrine-query-language.html
// An abstraction from SQL, with the same issues

	$_GET['id'] = 'id';

	$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ' . $_GET['id']); // INSECURE

	$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ?1')->setParameter(1, $_GET['id']);

//--------------------------------------------------
// CakePHP, Query Builder
// https://book.cakephp.org/3/en/orm/query-builder.html
// Parts of the where() array need to be trusted (literals), because those string are added to the SQL:

	$_GET['id'] = 'id';

	$query = $articles->find()->where(['id != ' . $_GET['id']]); // INSECURE

	$query = $articles->find()->where(['id !=' => $_GET['id']]);

//--------------------------------------------------
// CakePHP, Retrieving Data
// https://book.cakephp.org/3/en/orm/retrieving-data-and-resultsets.html
// https://book.cakephp.org/4/en/orm/query-builder.html#advanced-conditions
// Similar to the last one, but seeing these on production sites is, worrying:

	$_GET['category'] = 'category_id';

	$query = $articles->find('all', [
		'conditions' => [
			'OR' => [
				'category_id IS NULL',
				'category_id = ' . $_GET['category'], // INSECURE
			],
		],
	]);

//--------------------------------------------------
// CakePHP, Connection Class
// https://book.cakephp.org/3/en/orm/database-basics.html#connection-classes
// The $sql argument, in methods like `query()` shouldn't allow non-trusted values.
// https://github.com/cakephp/cakephp/blob/master/src/Database/Connection.php#L353

	$_GET['id'] = 'id';

	$statement = $connection->query('UPDATE articles SET published = 1 WHERE id = ' . $_GET['id']); // INSECURE

	$statement = $connection->query('UPDATE articles SET published = 1 WHERE id = ?', [$_GET['id']]);

//--------------------------------------------------
// RedBean, find
// https://redbeanphp.com/index.php?p=/finding
// "Never put user input directly in your query!"
// The `$sql` argument needs to be trusted.
// https://github.com/gabordemooij/redbean/blob/master/RedBeanPHP/Finder.php#L191

	$_GET['id'] = 'id';

	$users = R::find('user', 'id = ' . $_GET['id']); // INSECURE

	$users = R::find('user', 'id = ? ', [$_GET['id']]);

//--------------------------------------------------
// Propel, Model Criteria
// http://propelorm.org/Propel/reference/model-criteria.html

	$_GET['id'] = 'id';

	$users = UserQuery::create()->where('id = ' . $_GET['id'])->find(); // INSECURE

	$users = UserQuery::create()->where('id = ?', $_GET['id'])->find();

//--------------------------------------------------
// Twig, createTemplate
// https://twig.symfony.com/doc/2.x/recipes.html#loading-a-template-from-a-string

	$_GET['name'] = '<script>alert(1)</script>';

	$html = $twig->createTemplate('<p>Hi ' . $_GET['name'] . '</p>')->render(); // INSECURE

	$html = $twig->createTemplate('<p>Hi {{ name }}</p>')->render(['name' => $_GET['name']]);

?>