<?php

require_once 'lib/VTO/VTO.php';
require_once 'lib/MySQL.php';

$sql = new MySQL('', '', '', 'my_bookrewind');
$vtm = new VTM($sql);

$start_time = microtime(TRUE);

$res = $vtm->get('book.get', [
	'fields' => ['owner.mail', 'owner.surname', VTA::concat('buyer.book.id')],
	'where' => 'book.owner = ?',
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
	'fields' => ['book.category.ISBN' => 0, 'mail' => 'pincopallino@gmail.com'],
	'where' => 'user.id = ?',
	'params' => [8553]
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';

$start_time = microtime(TRUE);

$res = $vtm->post('book', [ 
  'fields' => ['owner' => '203110', 'ID_adozione' => 12],
  'duplicate' => 'book.ID = book.ID'
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';

$start_time = microtime(TRUE);

$res = $vtm->delete('book', [
	'where' => 'book.owner = ?',
	'params' => [203110]
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';