<?php
header('Content-type: text/html; charset=utf-8');
header('Cache-control: no-cache, must-revalidate');
$q = $_GET['q'];
if (strlen($q) > 0) {
	if (preg_match('/^([0-9]+)$/', $q)) {
		if (strlen($q) == 11) {
			header('Location: /mobile/' . $q);
		} else {
			header('Location: /mobile.html');
		}
	} else {
		header('Location: /mobile.html');
	}
} else {
	header('Location: /mobile.html');
}
?>