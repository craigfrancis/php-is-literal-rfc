<?php

	// $pdo = new PDO('mysql:dbname=test;host=localhost', 'test', 'test', [PDO::ATTR_EMULATE_PREPARES => false]);
	//
	// $statement = $pdo->prepare('SELECT * FROM user LIMIT ?');
	// $statement->execute([1]);
	//
	// foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
	// 	print_r($row);
	// }




	$db = new mysqli('localhost', 'test', 'test', 'test');

	// $rows = $db->query('SELECT * FROM user WHERE id = ' . $_GET['id']);
	//
	// foreach ($rows as $row) {
	// 	print_r($row);
	// }
	//
	//
	//
	//
	// $rows = $db->query('SELECT * FROM user WHERE id = ' . $db->real_escape_string($_GET['id']));
	//
	// foreach ($rows as $row) {
	// 	print_r($row);
	// }
	//
	//
	//
	//
	// $rows = $db->query('SELECT * FROM user WHERE id = "' . $db->real_escape_string($_GET['id']) . '"');
	//
	// foreach ($rows as $row) {
	// 	print_r($row);
	// }




	// $ids = [1,2,3];
	//
	// $statement = $db->prepare('SELECT * FROM user WHERE id IN (?,?,?)');
	//
	// array_unshift($ids, str_repeat('i', count($ids)));
	//
	// call_user_func_array([$statement, 'bind_result'], $ids);
	//
	// $statement->execute();
	//
	// $result = $statement->get_result();
	//
	// while ($row = mysqli_fetch_assoc($result)) {
	// 	print_r($row);
	// }


	// $statement = $db->prepare('SELECT * FROM user WHERE id = ?');
	// $statement->bind_param('i', 1);
	// $statement->execute();
	//
	// $result = $statement->get_result();
	//
	// while ($row = mysqli_fetch_assoc($result)) {
	// 	print_r($row);
	// }




	// $statement = $db->prepare('SELECT * FROM user WHERE type IN (?,?,?)');
	// $statement->bind_param('sss', $type1, $type2, $type3);
	// $statement->execute();
	//
	// $result = $statement->get_result();
	//
	// while ($row = mysqli_fetch_assoc($result)) {
	// 	print_r($row);
	// }




	// $type1 = 'admin';
	// $type2 = 'admin';
	// $type3 = 'admin';
	//
	//
	// $statement = $db->prepare('SELECT * FROM user WHERE type IN (?,?,?)');
	// $statement->execute([$type1, $type2, $type3]);
	//
	// $result = $statement->get_result();
	//
	// while ($row = mysqli_fetch_assoc($result)) {
	// 	print_r($row);
	// }


	// $statement = $db->prepare('SELECT * FROM user WHERE id = ?');
	// $statement->execute([$_GET['id']]);
	//
	// $result = $statement->get_result();
	//
	// while ($row = mysqli_fetch_assoc($result)) {
	// 	print_r($row);
	// }



	// $rows = $db->execute_query('SELECT * FROM user WHERE id = ?', [$_GET['id']]);
	//
	// foreach ($rows as $row) {
	// 	print_r($row);
	// }

	$ids = [1,2,3];

	$rows = $db->execute_query('SELECT * FROM user WHERE id IN (?,?,?)', $ids);

	foreach ($rows as $row) {
		print_r($row);
	}


?>