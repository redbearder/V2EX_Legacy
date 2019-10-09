#!/usr/bin/php
<?php
require('core/Settings.php');

$mails = array('lividecay@gmail.com', 'v2ex.livid@gmail.com');
$today = time();
shell_exec('/usr/bin/mysqldump -u' . BABEL_DB_USERNAME . ' -p' . BABEL_DB_PASSWORD . ' ' . BABEL_DB_SCHEMATA . '>/bak/' . BABEL_DB_SCHEMATA . '.' . $today . '.sql');
shell_exec('cd /bak/ && tar czf ' . BABEL_DB_SCHEMATA . '.' . $today . '.tgz ' . BABEL_DB_SCHEMATA . '.' . $today . '.sql');

foreach ($mails as $m) {
	shell_exec('/usr/bin/mutt -a /bak/' . BABEL_DB_SCHEMATA . '.' . $today . '.tgz -s "KIJIJI DB/BAK: ' . date('Y-n-j', $today) . '" ' . $m . '</dev/null');
}
?>