<?php

require_once 'lib/VTO/VTO.php';
require_once 'lib/MySQL.php';

$sql = new MySQL('', '', '', 'my_bookrewind');
$vtm = new VTM($sql, 'vtos/', false);

$start_time = microtime(TRUE);

$res = $vtm->get('book.get', [
	'fields' => ['owner.mail', 'owner.surname', VTA::sum('category.price')],
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
	'fields' => ['book.status' => 0, 'mail' => 'pincopallino@gmail.com'],
	'where' => 'user.id = ?',
	'params' => [8553]
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';

$start_time = microtime(TRUE);

$res = $vtm->post('book', [ 
  'fields' => ['owner' => '203110', 'category.ISBN' => '9788858328002', 'buyer.mail' => 'guegal@libero.it'],
  'duplicate' => 'book.ID = book.ID'
	]);

$end_time = microtime(TRUE);
echo '<br>Time: ' . ($end_time - $start_time).'<br>';