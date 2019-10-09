<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/FavoriteCore.php
*  Usage: Favorite Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: FavoriteCore.php 252 2006-04-26 13:01:38Z livid $
*  $LastChangedDate: 2006-04-26 21:01:38 +0800 (Wed, 26 Apr 2006) $
*  $LastChangedRevision: 252 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/FavoriteCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S Node class */

class Favorite {
	var $db;

	var $fav_id;
	var $fav_uid;
	var $fav_title;
	var $fav_author;
	var $fav_res;
	var $fav_brief;
	var $fav_type;
	var $fav_created;
	var $fav_lastupdated;
	
	var $usr_id;
	var $usr_nick;
	
	public function __construct($favorite_id, $db) {
		$this->db = $db;
		$sql = "SELECT fav_id, fav_uid, fav_title, fav_author, fav_res, fav_brief, fav_type, fav_created, fav_lastupdated, usr_id, usr_nick FROM babel_favorite, babel_user WHERE fav_uid = usr_id AND fav_id = {$favorite_id}";
		$rs = mysql_query($sql, $this->db);
		$O = mysql_fetch_object($rs);
		mysql_free_result($rs);
		$this->fav_id = $O->fav_id;
		$this->fav_uid = $O->fav_uid;
		$this->fav_title = $O->fav_title;
		$this->fav_author = $O->fav_author;
		$this->fav_res = $O->fav_res;
		$this->fav_brief = $O->fav_brief;
		$this->fav_type = $O->fav_type;
		$this->fav_created = $O->fav_created;
		$this->fav_lastupdated = $O->fav_lastupdated;
		$this->usr_id = $O->usr_id;
		$this->usr_nick = $O->usr_nick;
		$O = null;
	}
	
	public function __destruct() {
	}
}

/* E Favorite class */
?>