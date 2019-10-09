<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/StandaloneCore.php
*  Usage: Standalone Logic
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: StandaloneCore.php 247 2006-04-25 10:40:11Z livid $
*  $LastChangedDate: 2006-04-25 18:40:11 +0800 (Tue, 25 Apr 2006) $
*  $LastChangedRevision: 247 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/StandaloneCore.php $
*/

if (V2EX_BABEL == 1) {
	/* most important thing */
	require('core/Settings.php');
	
	/* 3rdParty PEAR cores */
	ini_set('include_path', BABEL_PREFIX . '/libs/pear' . ':' . ini_get('include_path'));
	require_once('Cache/Lite.php');
	require_once('HTTP/Request.php');
	require_once('Mail.php');
	require_once('Benchmark/Timer.php');
	
	/* 3rdParty cores */
	require(BABEL_PREFIX . '/libs/smarty/libs/Smarty.class.php');
	
	/* built-in cores */
	require('core/Vocabularies.php');
	require('core/Utilities.php');
	require('core/NodeCore.php');
} else {
	die('<strong>Project Babel</strong><br /><br />Made by V2EX | software for internet');
}

/* S Feed class */

class Feed {
	var $db;
	var $s;
	
	/* S module: constructor and destructor */

	public function __construct() {
		$this->db = mysql_connect(BABEL_DB_HOSTNAME . ':' . BABEL_DB_PORT, BABEL_DB_USERNAME, BABEL_DB_PASSWORD);
		mysql_select_db(BABEL_DB_SCHEMATA);
		mysql_query("SET NAMES utf8");
		mysql_query("SET CHARACTER SET utf8");
		mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");
	
		$this->s = new Smarty();
		$this->s->template_dir = BABEL_PREFIX . '/tpl';
		$this->s->compile_dir = BABEL_PREFIX . '/tplc';
		$this->s->cache_dir = BABEL_PREFIX . '/cache/smarty';
		$this->s->config_dir = BABEL_PREFIX . '/cfg';
		$this->s->caching = SMARTY_CACHING;
		
		$this->s->assign('site_lang', BABEL_LANG);
		$this->s->assign('site_base', 'http://' . BABEL_DNS_NAME . '/');
		header('Content-Type: text/xml;charset=utf-8');
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	/* E module: constructor and destructor */
	
	/* S public modules */

	public function vxFeed() {
		$this->s->assign('site_url', 'http://' . BABEL_DNS_NAME . '/');
		$sql = 'SELECT usr_id, usr_nick, usr_gender, usr_portrait, tpc_id, tpc_title, tpc_content, tpc_posts, tpc_created, nod_id, nod_title, nod_name FROM babel_user, babel_topic, babel_node WHERE tpc_uid = usr_id AND tpc_pid = nod_id ORDER BY tpc_lasttouched DESC LIMIT 20';
		$rs = mysql_query($sql);
		$Topics = array();
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$Topics[] = $Topic;
			$Topics[$i]->tpc_title = htmlspecialchars($Topics[$i]->tpc_title, ENT_NOQUOTES);
			$Topics[$i]->tpc_content = htmlspecialchars(format_ubb($Topics[$i]->tpc_content), ENT_NOQUOTES);
			$Topics[$i]->tpc_pubdate = date('r', $Topics[$i]->tpc_created);
			$i++;
		}
		$this->s->assign('feed_title', 'Latest from ' . Vocabulary::site_name);
		$this->s->assign('feed_description', Vocabulary::meta_description);
		$this->s->assign('feed_category', Vocabulary::meta_category);
		$this->s->assign('a_topics', $Topics);
		$this->s->display('feed/rss2.tpl');
	}
	
	public function vxFeedBoard($Node) {
		$this->s->assign('site_url', 'http://' . BABEL_DNS_NAME . '/go/' . $Node->nod_name);
		switch ($Node->nod_level) {
			case 2:
			default:
				$sql = "SELECT usr_id, usr_nick, usr_gender, usr_portrait, tpc_id, tpc_title, tpc_content, tpc_posts, tpc_created, nod_id, nod_title, nod_name FROM babel_user, babel_topic, babel_node WHERE tpc_uid = usr_id AND tpc_pid = nod_id AND tpc_pid = {$Node->nod_id} ORDER BY tpc_lasttouched DESC LIMIT 20";
				break;
			case 1:
				$sql = "SELECT usr_id, usr_nick, usr_gender, usr_portrait, tpc_id, tpc_title, tpc_content, tpc_posts, tpc_created, nod_id, nod_title, nod_name FROM babel_user, babel_topic, babel_node WHERE tpc_uid = usr_id AND tpc_pid = nod_id AND tpc_pid IN (SELECT nod_id FROM babel_node WHERE nod_pid = {$Node->nod_id}) ORDER BY tpc_lasttouched DESC LIMIT 20";
				break;
				
		}
		$rs = mysql_query($sql) or die(mysql_error());
		$Topics = array();
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$Topics[] = $Topic;
			$Topics[$i]->tpc_title = htmlspecialchars($Topics[$i]->tpc_title, ENT_NOQUOTES);
			$Topics[$i]->tpc_content = htmlspecialchars(format_ubb($Topics[$i]->tpc_content), ENT_NOQUOTES);
			$Topics[$i]->tpc_pubdate = date('r', $Topics[$i]->tpc_created);
			$i++;
		}
		$this->s->assign('feed_title', 'Latest from ' . Vocabulary::site_name . "'s " . $Node->nod_title);
		$this->s->assign('feed_description', Vocabulary::meta_description);
		$this->s->assign('feed_category', Vocabulary::meta_category);
		$this->s->assign('a_topics', $Topics);
		$this->s->display('feed/rss2.tpl');
	}
	
	/* E public modules */
	
}

/* E Feed class */
?>