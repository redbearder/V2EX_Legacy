#!/usr/bin/php
<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/cron/benefit.php
*  Usage: Cron task calculates the benefits
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: benefit.php 175 2006-04-09 21:35:49Z livid $
*  $LastChangedDate: 2006-04-10 05:35:49 +0800 (Mon, 10 Apr 2006) $
*  $LastChangedRevision: 175 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/cron/benefit.php $
*/

require('core/Settings.php');

$db = mysql_connect(BABEL_DB_HOSTNAME . ':' . BABEL_DB_PORT, BABEL_DB_USERNAME, BABEL_DB_PASSWORD);
mysql_select_db(BABEL_DB_SCHEMATA);
mysql_query("SET NAMES utf8");
mysql_query("SET CHARACTER SET utf8");
mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");

$rs = mysql_query("SELECT COUNT(tpc_id) FROM babel_topic");
$tpc_count = mysql_result($rs, 0, 0);
mysql_free_result($rs);

$rs = mysql_query("SELECT COUNT(pst_id) FROM babel_post");
$pst_count = mysql_result($rs, 0, 0);
mysql_free_result($rs);

$benefit_total = (($tpc_count * 10) + ($pst_count * 2)) * BABEL_BF_RATE;

$Users = mysql_query("SELECT usr_id, usr_nick, usr_email, usr_money, count(tpc_id) AS usr_topics FROM babel_user, babel_topic WHERE tpc_uid = usr_id GROUP BY usr_id ORDER BY usr_topics ASC");

$o = '';
while ($User = mysql_fetch_object($Users)) {
	$percent = $User->usr_topics / $tpc_count;
	$benefit = $benefit_total * $percent;
	$o = $o . $User->usr_email . ', ' . $User->usr_topics . ', ' . $User->usr_money . ', ' . $percent . ', ' . '$' . $benefit . "\n";
	$usr_money = $User->usr_money + $benefit;
	$share = $percent * 100;
	if ($share > 10) {
		$share = substr($share, 0, 6);
	} else {
		$share = substr($share, 0, 5);
	}
	$exp_memo = "你的 {$User->usr_topics} 篇主题所占比率 {$share}%";
	$sql = "UPDATE babel_user SET usr_money = {$usr_money} WHERE usr_id = {$User->usr_id} LIMIT 1";
	mysql_query($sql);
	if (mysql_affected_rows() == 1) {
		$sql = "INSERT INTO babel_expense(exp_uid, exp_amount, exp_type, exp_memo, exp_created) VALUES({$User->usr_id}, {$benefit}, 7, '{$exp_memo}', " . time() . ")";
		mysql_query($sql);
	}
}

echo $o;
echo ("Total Benefit: " . $benefit_total . "\n");

/* Then I'd like to get the database optimized. */
function optz($name) {
	$sql = "OPTIMIZE TABLE babel_{$name};";
	mysql_query($sql);
	echo ("Table: babel_{$name} is optimized.\n");
}

optz('user');
optz('group');
optz('node');
optz('topic');
optz('post');
optz('expense');
optz('online');
?>
