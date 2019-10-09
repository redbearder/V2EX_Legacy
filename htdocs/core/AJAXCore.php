<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/AJAXCore.php
*  Usage: AJAX related stuff
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: AJAXCore.php 506 2006-07-14 11:32:36Z livid $
*  $LastChangedDate: 2006-07-14 19:32:36 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 506 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/AJAXCore.php $
*/

if (V2EX_BABEL == 1) {
	/* most important thing */
	require('core/Settings.php');

	/* 3rdParty PEAR cores */
	ini_set('include_path', BABEL_PREFIX . '/libs/pear' . ':' . ini_get('include_path'));
	require_once('Cache/Lite.php');
	require_once('HTTP/Request.php');
	require_once('Crypt/Blowfish.php');
	
	/* 3rdParty cores */
	require(BABEL_PREFIX . '/libs/magpierss/rss_fetch.inc');
	
	/* built-in cores */
	require('core/Vocabularies.php');
	require('core/Utilities.php');
	require('core/UserCore.php');
	require('core/NodeCore.php');
	require('core/TopicCore.php');
	require('core/ChannelCore.php');
	require('core/FavoriteCore.php');
	require('core/URLCore.php');
	require('core/ValidatorCore.php');
} else {
	die('<strong>Project Babel</strong><br /><br />Made by V2EX | software for internet');
}

/* S AJAXServer class */

class AJAXServer {
	var $User;

	var $db;
/* S module: constructor and destructor */

	public function __construct() {
		header('Content-type: text/xml; charset=utf-8');
		header('Cache-control: no-cache, must-revalidate');
		$this->db = mysql_connect(BABEL_DB_HOSTNAME . ':' . BABEL_DB_PORT, BABEL_DB_USERNAME, BABEL_DB_PASSWORD);
		mysql_select_db(BABEL_DB_SCHEMATA);
		mysql_query("SET NAMES utf8");
		mysql_query("SET CHARACTER SET utf8");
		mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");
		session_set_cookie_params(2592000);
		session_start();
		$this->User = new User('', '', $this->db);
		$this->Validator =  new Validator($this->db, $this->User);
		if (!isset($_SESSION['babel_ua'])) {
			$_SESSION['babel_ua'] = $this->Validator->vxGetUserAgent();
		}
		$this->URL = new URL();
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	/* E module: constructor and destructor */
	
	/* S public modules */

	/* S module: Null */
	
	public function vxNull() {
		$this->vxXML();
		$this->vxMessage(0, 'Null object called');
	}
	
	/* E module: Null */
	
	/* S module: Denied */
	
	public function vxDenied() {
		$this->vxXML();
		$this->vxMessage(999, 'Access denied');
	}
	
	/* E module: Denied */
	
	/* S module: Mismatched */
	
	public function vxMismatched() {
		$this->vxXML();
		$this->vxMessage(998, 'Request parameters mismatched');
	}
	
	/* E module: Mismatched */
	
	/* S module: Duplicated */
	
	public function vxDuplicated() {
		$this->vxXML();
		$this->vxMessage(997, 'Substantial object duplicated');
	}
	
	/* E module: Duplicated */
	
	/* S module: Fav Topic Add */
	
	public function vxFavTopicAdd($Topic) {
		$this->vxXML();
		$t = time();
		$tpc_title = mysql_real_escape_string($Topic->tpc_title, $this->db);
		$tpc_author = mysql_real_escape_string($Topic->usr_nick, $this->db);
		/* fav_type:
		0 -> internal: Topic
		1 -> internal: Node
		2 -> internal: Channel
		2 -> external: URL
		*/
		$sql = "INSERT INTO babel_favorite(fav_uid, fav_title, fav_author, fav_res, fav_type, fav_created, fav_lastupdated) VALUES({$this->User->usr_id}, '{$tpc_title}', '{$tpc_author}', '{$Topic->tpc_id}', 0, {$t}, {$t})";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			$this->vxMessage(1, 'Substantial object created');
		} else {
			$this->vxMessage(996, 'Substantial object failed');
		}
	}
	
	/* E module: Fav Topic Add */
	
	/* S module: Fav Node Add */
	
	public function vxFavNodeAdd($Node, $Section) {
		$this->vxXML();
		$t = time();
		$nod_board = mysql_real_escape_string($Node->nod_title, $this->db);
		$nod_section = mysql_real_escape_string($Section->nod_title, $this->db) . ':' . $Section->nod_id;
		/* fav_type:
		0 -> internal: Topic
		1 -> internal: Node
		2 -> internal: Channel
		3 -> external: URL
		*/
		$sql = "INSERT INTO babel_favorite(fav_uid, fav_title, fav_author, fav_res, fav_type, fav_created, fav_lastupdated) VALUES({$this->User->usr_id}, '{$nod_board}', '{$nod_section}', '{$Node->nod_id}', 1, {$t}, {$t})";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			$Node->vxUpdateFavs();
			$this->vxMessage(1, 'Substantial object created');
		} else {
			$this->vxMessage(996, 'Substantial object failed');
		}
	}
	
	/* E module: Fav Node Add */
	
	/* S module: Fav Channel Add */
	
	public function vxFavChannelAdd($Channel) {
		$this->vxXML();
		$Node = new Node($Channel->chl_pid, $this->db);
		$t = time();
		$chl_board = mysql_real_escape_string($Node->nod_title, $this->db) . ':' . $Node->nod_id;
		$chl_title = mysql_real_escape_string($Channel->chl_title, $this->db);
		/* fav_type:
		0 -> internal: Topic
		1 -> internal: Node
		2 -> internal: Channel
		3 -> external: URL
		*/
		$sql = "INSERT INTO babel_favorite(fav_uid, fav_title, fav_author, fav_res, fav_type, fav_created, fav_lastupdated) VALUES({$this->User->usr_id}, '{$chl_title}', '{$chl_board}', '{$Channel->chl_id}', 2, {$t}, {$t})";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			$this->vxMessage(1, 'Substantial object created');
		} else {
			$this->vxMessage(996, 'Substantial object failed');
		}
	}
	
	/* E module: Fav Channel Add */
	
	/* S module: Fav Item Remove */
	
	public function vxFavRemove($Favorite) {
		$this->vxXML();
		$sql = "DELETE FROM babel_favorite WHERE fav_id = {$Favorite->fav_id} LIMIT 1";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			if ($Favorite->fav_type == 1) {
				$Node = new Node(intval($Favorite->fav_res), $this->db);
				$Node->vxUpdateFavs();
				$Node = null;
			}
			die($Favorite);
			$Favorite = null;
			$this->vxMessage(2, 'Substantial object removed');
		} else {
			$this->vxMessage(996, 'Substantial object failed');
		}
	}
	
	/* E module: Fav Item Remove */
	
	/* E public modules */
	
	/* S private modules */
	
	private function vxXML() {
		echo('<?xml version="1.0" encoding="UTF-8"?>');
	}
	
	private function vxMessage($code, $message) {
		$t = time();
		$prefix = 'V2EX XML Server: ';
		echo("<babel><code>{$code}</code><time>{$t}</time><message>{$prefix}{$message}</message></babel>");
	}
	
	/* E private modules */
}

/* E AJAXServer class */
?>
