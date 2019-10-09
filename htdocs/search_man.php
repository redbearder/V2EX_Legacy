<?php
header('Content-type: text/html; charset=utf-8');
header('Cache-control: no-cache, must-revalidate');
if (isset($_GET['q'])) {
	$q = trim($_GET['q']);
	if (strlen($q) > 0) {
		header('Location: /ref/' . urlencode($q));
	} else {
		header('Location: /man.html');
	}
} else {
	header('Location: /man.html');
}
?>