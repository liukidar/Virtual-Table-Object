<?php

require_once 'lib/VTO.php';
require_once 'lib/MySQL.php';

function groupBy(&$_resource, $_field) {
	$result = [];
	while($row = $_resource->next()) {
		$target = $row[$_field];
		unset($row[$_field]);
		$result[$target][] = $row;
	}

	return $result;
}

$sql = new MySQL('', '', '', 'my_bookrewind');
$vtm = new VTM($sql, false);

$start_time = microtime(TRUE);

$res = $vtm->get('book.get', [
	'fields' => ['owner.mail', 'owner.name', sum('category.price')],
	'where' => 'owner = ?',
	'params' => [8553]
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';

while($r = $res->next()) {
	print_r($r);
	echo '<br>';
}

$start_time = microtime(TRUE);

$res = $vtm->put('user', [ 
	'fields' => ['book.status' => 0],
	'where' => 'user.id = ?',
	'params' => [8553]
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';