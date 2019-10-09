<?php
header('Content-type: text/html; charset=utf-8');
header('Cache-control: no-cache, must-revalidate');
$q = $_GET['q'];
if (strlen($q) > 0) {
	$q = urlencode(str_replace("/", " ", $q));
	header('Location: /q/' . $q);
} else {
	header('Location: /search.vx');
}
?>