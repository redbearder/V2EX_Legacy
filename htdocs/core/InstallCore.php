<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/InstallCore.php
*  Usage: a Quick and Dirty script
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: InstallCore.php 500 2006-07-14 10:37:41Z livid $
*  $LastChangedDate: 2006-07-14 18:37:41 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 500 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/InstallCore.php $
*/

// Don't touch these code, go to line 160:

define('V2EX_BABEL', 1);
require('Settings.php');

class Install {
	var $db;
	
	public function __construct() {
		$this->db = mysql_connect(BABEL_DB_HOSTNAME, BABEL_DB_USERNAME, BABEL_DB_PASSWORD);
		mysql_select_db(BABEL_DB_SCHEMATA, $this->db);
		mysql_query("SET NAMES utf8", $this->db);
		mysql_query("SET CHARACTER SET utf8", $this->db);
		mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'", $this->db);
		header('Content-type: text/html;charset=UTF-8');
		echo('Install Core init<br /><br />');
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	public function vxSetupWeight() {
		mysql_unbuffered_query("UPDATE babel_node SET nod_weight = 10000 WHERE nod_name = 'limbo'");
	}
	
	public function vxSetupSections() {
		$this->vxSetupSection("UPDATE babel_node SET nod_sid = 1, nod_level = 0, nod_title = '异域', nod_header = '异域', nod_footer = '' WHERE nod_id = 1 LIMIT 1");
		$this->vxSetupSection("UPDATE babel_node SET nod_sid = 1, nod_title = '混沌海', nod_header = '', nod_footer = '' WHERE nod_id = 2 LIMIT 1");
	}
	
	public function vxSetupSectionExtra($name, $title, $description = '', $header = '', $footer = '') {
		$sql = "SELECT nod_id FROM babel_node WHERE nod_name = '{$name}' LIMIT 1";
		$rs = mysql_query($sql);
		if (mysql_num_rows($rs) == 1) {
			$_t = time();
			$sql = "UPDATE babel_node SET nod_title = '{$title}', nod_description = '{$description}', nod_header = '{$header}', nod_footer = '{$footer}', nod_lastupdated = {$_t} WHERE nod_name = '{$name}' LIMIT";
			mysql_query($sql, $this->db);
			if (mysql_affected_rows($this->db) == 1) {
				echo ('OK: ' . $sql . '<br />');
				return true;
			} else {
				echo('NU: ' . $sql . '<br />');
				return false;
			}
		} else {
			$_t = time();
			$sql = "INSERT INTO babel_node(nod_pid, nod_uid, nod_sid, nod_level, nod_name, nod_title, nod_description, nod_header, nod_footer, nod_created, nod_lastupdated) VALUES(1, 1, 5, 1, '{$name}', '{$title}', '{$description}', '{$header}', '{$footer}', {$_t}, {$_t})";
			mysql_query($sql, $this->db);
			if (mysql_affected_rows($this->db) == 1) {
				echo ('OK: ' . $sql . '<br />');
				return true;
			} else {
				echo ('NU: ' . $sql . '<br />');
				return false;
			}
		}
	}
	
	public function vxSetupSection($stmt) {
		$sql = $stmt;
		mysql_query($sql);
		if (mysql_affected_rows() == 1) {
			echo 'OK: ' . $sql . '<br />';
		} else {
			echo 'NU ' . mysql_affected_rows() . ': ' . $sql . '<br />';
		}
	}
	
	public function vxSetupChannelById($board_id, $url) {
		$url = mysql_real_escape_string($url);
		$t = time();
		$sql = "INSERT INTO babel_channel(chl_pid, chl_url, chl_created) VALUES({$board_id}, '{$url}', {$t})";
		$sql_exist = "SELECT chl_id FROM babel_channel WHERE chl_url = '{$url}' AND chl_pid = {$board_id}";
		$rs = mysql_query($sql_exist);
		if (mysql_num_rows($rs) == 0) {
			mysql_query($sql) or die(mysql_error());
			if (mysql_affected_rows() == 1) {
				echo('OK: ' . $sql . '<br />');
			} else {
				echo('FD: ' . $sql . '<br />');
			}
		} else {
			echo('EX: ' . $sql . '<br />');
		}
	}
	
	public function vxSetupChannelByName($board_name, $url) {
		$url = mysql_real_escape_string($url);
		$t = time();
		$sql = "SELECT nod_id FROM babel_node WHERE nod_name = '{$board_name}' LIMIT 1";
		$board_id = mysql_result(mysql_query($sql), 0, 0);
		$sql = "INSERT INTO babel_channel(chl_pid, chl_url, chl_created) VALUES({$board_id}, '{$url}', {$t})";
		$sql_exist = "SELECT chl_id FROM babel_channel WHERE chl_url = '{$url}' AND chl_pid = {$board_id}";
		$rs = mysql_query($sql_exist);
		if (mysql_num_rows($rs) == 0) {
			mysql_query($sql) or die(mysql_error());
			if (mysql_affected_rows() == 1) {
				echo('OK: ' . $sql . '<br />');
			} else {
				echo('FD: ' . $sql . '<br />');
			}
		} else {
			echo('EX: ' . $sql . '<br />');
		}
	}
	
	public function vxSetupBoard($board_name, $board_title, $board_pid, $board_sid, $board_uid, $board_level, $board_header = '', $board_footer = '', $board_description = '') {
		$board_name = mysql_real_escape_string($board_name);
		$board_title = mysql_real_escape_string($board_title);
		$board_header = mysql_real_escape_string($board_header);
		$board_footer = mysql_real_escape_string($board_footer);
		$board_description = mysql_real_escape_string($board_description);
		$board_created = time();
		$board_lastupdated = time();
		
		$sql = "INSERT INTO babel_node(nod_name, nod_title, nod_pid, nod_sid, nod_uid, nod_level, nod_header, nod_footer, nod_description, nod_created, nod_lastupdated) VALUES('{$board_name}', '{$board_title}', {$board_pid}, {$board_sid}, {$board_uid}, {$board_level}, '{$board_header}', '{$board_footer}', '{$board_description}', {$board_created}, {$board_lastupdated})";
		$sql_exist = "SELECT nod_id FROM babel_node WHERE nod_name = '{$board_name}'";
		$rs = mysql_query($sql_exist);
		if (mysql_num_rows($rs) > 0) {
			$Node = mysql_fetch_object($rs);
			mysql_free_result($rs);
			$sql_update = "UPDATE babel_node SET nod_title = '{$board_title}', nod_pid = {$board_pid}, nod_sid = {$board_sid}, nod_uid = {$board_uid}, nod_level = {$board_level}, nod_header = '{$board_header}', nod_footer = '{$board_footer}', nod_description = '{$board_description}' WHERE nod_id = {$Node->nod_id}";
			mysql_query($sql_update);
			if (mysql_affected_rows() == 1) {
				echo 'UD: ' . $sql_update . '<br />';
			} else {
				echo 'EX: ' . $sql_update . '<br />';
			}
		} else {
			mysql_query($sql) or die(mysql_error());
			if (mysql_affected_rows() == 1) {
				echo 'OK: ' . $sql . '<br />';
			} else {
				echo 'FD: ' . $sql . '<br />';
			}
		}
	}
}

$i = new Install();
$i->vxSetupWeight();

// You can set up your own world from here:

$i->vxSetupBoard('board', 'Board', 2, 2, 1, 2, 'Hello World!', 'This is an example board in Limbo.');
	$i->vxSetupChannelByName('board', 'http://www.livid.cn/rss.php');
?>