<?php

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
// While DQL is an SQL abstraction, it can have injection vulnerabilities as well.

	$_GET['id'] = 'id';

	$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ' . $_GET['id']); // INSECURE

	$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ?1')->setParameter(1, $_GET['id']);

//--------------------------------------------------
// Laravel, DB::select()
// https://laravel.com/docs/8.x/database#running-a-select-query
// Plain SQL, where user values *should* be provided separately.

	$_GET['active'] = 'active';

	$users = DB::select('SELECT * FROM users WHERE active = ' . $_GET['active']); // INSECURE

	$users = DB::select('SELECT * FROM users WHERE active = ?', [$_GET['active']]);

//--------------------------------------------------
// Laravel, QueryBuilder::where()
// https://laravel.com/docs/9.x/queries

	// Developer starts with this example from the documentation:
	//
	//   $users = DB::table('users')->where('name', 'like', $search . '%')->get();
	//
	// But their database uses first/last name fields, so they try `CONCAT()`
	//
	//   $users = DB::table('user')->where('CONCAT(name_first, " ", name_last)', 'LIKE', $search . '%');
	//
	// As they are using a function, this creates a parse error (gets quoted), so they use whereRaw():

	$users = DB::table('user')->whereRaw('CONCAT(name_first, " ", name_last) LIKE "' . $search . '%"'); // INSECURE

	$users = DB::table('user')->whereRaw('CONCAT(name_first, " ", name_last) LIKE ?', $search . '%');

//--------------------------------------------------
// Laravel, QueryBuilder::orderBy()
// https://laravel.com/docs/9.x/queries

	// Developer starts with this example from the documentation:
	//
	//   $users = DB::table('user')->orderBy('name');
	//
	// But their database uses first/last name fields, so they try:
	//
	//   $users = DB::table('user')->orderBy('name_first, name_last');
	//
	// This creates a parse error (quoted as a single value); they could use:
	//
	//   $users = DB::table('user')->orderBy('name_first')->orderBy('name_last');
	//
	//   $users = DB::table('user')->orderByRaw('name_first, name_last');
	//
	// They use the second, because Raw methods can be used quite often, e.g.
	//
	//   $users = DB::table('user')->orderByRaw('LENGTH(email)');
	//
	// And because it's "easier" for a user provided value to specify the sort order:

	$_GET['sort'] = 'name_first, name_last';

	$users = DB::table('user')->orderByRaw($_GET['sort'] ?? 'created'); // INSECURE

	// Attacker exploits via:
	//   'id'
	//   'id = (SELECT 2 FROM admin WHERE id = 2)'
	//   'id = (SELECT 2 FROM admin WHERE id = 2 AND password LIKE "a%")'
	//   'id = (SELECT 2 FROM admin WHERE id = 2 AND password LIKE "b%")'
	//   'id = (SELECT 2 FROM admin WHERE id = 2 AND password LIKE "c%")'

//--------------------------------------------------
// CakePHP, Query Builder
// https://book.cakephp.org/3/en/orm/query-builder.html
// Parts of the where() array need to be a literal, because those string are added to the SQL:

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
// The $sql argument, in methods like `query()` shouldn't allow non-literal values.
// https://github.com/cakephp/cakephp/blob/master/src/Database/Connection.php#L353

	$_GET['id'] = 'id';

	$statement = $connection->query('UPDATE articles SET published = 1 WHERE id = ' . $_GET['id']); // INSECURE

	$statement = $connection->query('UPDATE articles SET published = 1 WHERE id = ?', [$_GET['id']]);

//--------------------------------------------------
// RedBean, find
// https://redbeanphp.com/index.php?p=/finding
// "Never put user input directly in your query!"
// The `$sql` argument needs to be literal.
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

//--------------------------------------------------
// Twig, Context issue

	$_GET['url'] = 'javascript:alert()';

	$html = $twig->createTemplate('<a href="{{ url }}">My Link</a>')->render(['url' => $_GET['url']]); // INSECURE

//--------------------------------------------------
// Twig, Missing quotes

	$_GET['url'] = '/ onerror=alert()';

	$html = $twig->createTemplate('<img src={{ url }} alt="Alt Text" />')->render(['url' => $_GET['url']]); // INSECURE

//--------------------------------------------------
// Zeta Components, Database Handler
// http://zetacomponents.org/documentation/trunk/Database/tutorial.html#handler-usage

	$stmt = $db->prepare( 'SELECT * FROM quotes WHERE author = "' . $_GET['author'] . '"' ); // INSECURE

	$stmt = $db->prepare( 'SELECT * FROM quotes WHERE author = :author' );
	$stmt->bindValue( ':author', $_GET['author'] );

//--------------------------------------------------
// Zeta Components, Database "Query Abstraction"
// http://zetacomponents.org/documentation/trunk/Database/tutorial.html#bind-parameters

	$_GET['sort'] = 'IF((SELECT 1 FROM user WHERE id = 1 AND name LIKE "a%"), name, id)';

	$q->select( '*' )->from( 'quotes' )
	  ->orderBy( $_GET['sort'] ); // INSECURE

?>