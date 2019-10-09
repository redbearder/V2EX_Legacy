<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/ChannelCore.php
*  Usage: Channel Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: ChannelCore.php 252 2006-04-26 13:01:38Z livid $
*  $LastChangedDate: 2006-04-26 21:01:38 +0800 (Wed, 26 Apr 2006) $
*  $LastChangedRevision: 252 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/ChannelCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S Channel class */

class Channel {
	var $db;
	
	var $chl_id;
	var $chl_pid;
	var $chl_title;
	var $chl_url;
	var $chl_created;
	
	var $rss;
	
	public function __construct($channel_id = '', $db = '') {
		$this->db = $db;
		if ($channel_id != '') {
			$sql = "SELECT chl_id, chl_pid, chl_title, chl_url, chl_created FROM babel_channel WHERE chl_id={$channel_id}";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$O = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$this->chl_id = $O->chl_id;
				$this->chl_pid = $O->chl_pid;
				$this->chl_title = $this->vxCleanKijijiTitle($O->chl_title);
				$this->chl_url = $O->chl_url;
				$this->chl_created = $O->chl_created;
				$this->rss = fetch_rss($this->chl_url);
				$O = null;
				if ($this->chl_title == '') {
					$this->chl_title = $this->rss->channel['title'];
					$sql = "UPDATE babel_channel SET chl_title = '" . mysql_real_escape_string($this->chl_title, $this->db) . "' WHERE chl_id = {$this->chl_id} LIMIT 1";
					mysql_query($sql, $this->db);
				}
			} else {
				mysql_free_result($rs);
				$this->chl_id = 0;
				$this->chl_pid = 0;
				$this->chl_title = '';
				$this->chl_url = '';
				$this->chl_created = 0;
				$this->rss = null;
			}
		} else {
			$this->chl_id = 0;
			$this->chl_pid = 0;
			$this->chl_title = '';
			$this->chl_url = '';
			$this->chl_created = 0;
			$this->rss = null;
		}
	}
	
	public function __destruct() {
	}
	
	public function vxCleanKijijiTitle($title) {
		if (mb_ereg_match('最新的客齐集广告', $title)) {
			mb_ereg('最新的客齐集广告 所在地：(.+) 分类：(.+)', $title, $m);
			return '最新的客齐集广告' . ' - ' . $m[1] . ' - ' . $m[2];
		} else {
			return $title;
		}
	}
	
	public function vxTrimKijijiTitle($title) {
		if (mb_ereg_match('最新的客齐集广告 - (.+) - (.+)', $title)) {
			mb_ereg('最新的客齐集广告 - (.+) - (.+)', $title, $m);
			return $m[1] . $m[2];
		} else {
			return $title;
		}
	}
}

/* E Channel class */
?>