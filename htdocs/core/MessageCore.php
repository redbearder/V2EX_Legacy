<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/MessageCore.php
*  Usage: Message Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: MessageCore.php 252 2006-04-26 13:01:38Z livid $
*  $LastChangedDate: 2006-04-26 21:01:38 +0800 (Wed, 26 Apr 2006) $
*  $LastChangedRevision: 252 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/MessageCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S Message class */
/* massive work to do with this class... */

class Message {
	var $db;
	
	var $msg_id;
	var $msg_sid;
	var $msg_rid;
	var $msg_body;
	var $msg_draft;
	var $msg_hits;
	var $msg_created;
	var $msg_sent;
	var $msg_opened;
	var $msg_sdeleted;
	var $msg_rdeleted;
	var $msg_lastaccessed;
	var $msg_lastupdated;
	
	public function __construct($message_id, $db, $flag_format = 1) {
		$this->db = $db;
		$t = time();
		
		$sql = "SELECT msg_id, msg_sid, msg_rid, msg_body, msg_draft, msg_hits, msg_created, msg_sent, msg_opened, msg_sdeleted, msg_rdeleted, msg_lastaccessed, msg_lastupdated FROM babel_message WHERE msg_id = {$message_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			$O = mysql_fetch_object($rs);
			mysql_free_result($rs);
			$this->msg_id = $O->msg_id;
			$this->msg_sid = $O->msg_sid;
			$this->msg_rid = $O->msg_rid;
			if ($flag_format == 1) {
				$this->msg_body = format_ubb($O->msg_body);
			} else {
				$this->msg_body = $O->msg_body;
			}
			$this->msg_draft = $O->msg_draft;
			$this->msg_hits = $O->msg_hits;
			$this->msg_created = $O->msg_created;
			$this->msg_sent = $O->msg_sent;
			$this->msg_opened = $O->msg_opened;
			$this->msg_sdeleted = $O->msg_sdeleted;
			$this->msg_rdeleted = $O->msg_rdeleted;
			$this->msg_lastaccessed = $O->msg_lastaccessed;
			$this->msg_lastupdated = $O->msg_lastupdated;
			$O = null;
		} else {
			mysql_free_result($rs);
			$this->msg_id = 0;
			$this->msg_sid = 0;
			$this->msg_rid = 0;
			$this->msg_body = '';
			$this->msg_draft = 0;
			$this->msg_hits = 0;
			$this->msg_created = 0;
			$this->msg_sent = 0;
			$this->msg_opened = 0;
			$this->msg_sdeleted = 0;
			$this->msg_rdeleted = 0;
			$this->msg_lastaccessed = 0;
			$this->msg_lastupdated = 0;
		}
	}

	public function __destruct() {
	}

	public function vxSDeleteMessage($message_id = '') {
		return false;
	}
	
	public function vxRDeleteMessage($message_id = '') {
		return false;
	}
}

/* E Message class */
?>