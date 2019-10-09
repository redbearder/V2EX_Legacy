<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/APICore.php
*  Usage: API Logic
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: APICore.php 505 2006-07-14 11:30:52Z livid $
*  $LastChangedDate: 2006-07-14 19:30:52 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 505 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/APICore.php $
*/

if (V2EX_BABEL == 1) {
	require('core/Settings.php');
	/* 3rdParty PEAR cores */
	ini_set('include_path', BABEL_PREFIX . '/libs/pear' . ':' . ini_get('include_path'));
	require_once('Cache/Lite.php');
	require_once('HTTP/Request.php');
	require_once('Crypt/Blowfish.php');
	/* built-in cores */
	require('core/Vocabularies.php');
	require('core/Utilities.php');
	require('core/UserCore.php');
	require('core/NodeCore.php');
	require('core/TopicCore.php');
	require('core/ChannelCore.php');
	require('core/URLCore.php');
	require('core/ImageCore.php');
	require('core/ValidatorCore.php');
} else {
	die('<strong>Project Babel</strong><br /><br />Made by V2EX | software for internet');
}

/* S Standalone class */

class API {
	var $User;
	
	var $db;
	
	/* S module: constructor and destructor */

	public function __construct() {
		$this->db = mysql_connect(BABEL_DB_HOSTNAME . ':' . BABEL_DB_PORT, BABEL_DB_USERNAME, BABEL_DB_PASSWORD);
		mysql_select_db(BABEL_DB_SCHEMATA);
		mysql_query("SET NAMES utf8");
		mysql_query("SET CHARACTER SET utf8");
		mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");
		session_set_cookie_params(2592000);
		session_start();
		$this->User = new User('', '', $this->db);
		$this->URL = new URL();
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	/* E module: constructor and destructor */
	
	/* S public modules */

	public function vxTopicCreate() {
		if (isset($_POST['xml'])) {
			$xml = trim($_POST['xml']);
			$x = simplexml_load_string($xml);
			$usr_email = make_single_safe($x->user->email);
			$usr_password = make_single_safe($x->user->pass);
			$tpc_title = make_single_safe($x->topic->title);
			$tpc_description = make_multi_safe($x->topic->description);
			$tpc_content = make_multi_safe($x->topic->content);
			$nod_name = make_single_safe($x->topic->target);
			if (strlen($usr_email) == 0 | strlen($usr_password) == 0 | strlen($tpc_title) == 0 | strlen($tpc_content) == 0 | strlen($nod_name) == 0) {
				return $this->vxMessage(999);
			}
			$sql = "SELECT usr_id FROM babel_user WHERE usr_email = '{$usr_email}' AND usr_password = '{$usr_password}' AND usr_api = 1";
			$rs = mysql_query($sql);
			if (mysql_num_rows($rs) == 1) {
				mysql_free_result($rs);
				$this->User = new User($usr_email, $usr_password, $this->db);
				$this->Validator =  new Validator($this->db, $this->User);
				$sql = "SELECT nod_id FROM babel_node WHERE nod_name = '{$nod_name}' AND nod_level > 1";
				$rs = mysql_query($sql);
				if (mysql_num_rows($rs) == 1) {
					$O = mysql_fetch_object($rs);
					$Node = new Node($O->nod_id, $this->db);
					$O = null;
					mysql_free_result($rs);
					$rt = $this->Validator->vxAPITopicCreateCheck($tpc_title, $tpc_content, $tpc_description);
					if ($rt['errors'] > 0) {
						return $this->vxMessage(998);
					} else {
						if ($this->User->usr_money > BABEL_API_TOPIC_PRICE) {
							$this->Validator->vxTopicCreateInsert($Node->nod_id, $this->User->usr_id, $rt['tpc_title_value'], $rt['tpc_description_value'], $rt['tpc_content_value'], -(BABEL_API_TOPIC_PRICE));
							$Node->vxUpdateTopics();
							$sql = "SELECT tpc_id FROM babel_topic WHERE tpc_pid = {$Node->nod_id} AND tpc_uid = {$this->User->usr_id} ORDER BY tpc_created DESC LIMIT 1";
							$rs = mysql_query($sql);
							$O = mysql_fetch_object($rs);
							return $this->vxMessage(1, $O);
						} else {
							return $this->vxMessage(600);
						}
					}
				} else {
					return $this->vxMessage(996);
				}
			} else {
				mysql_free_result($rs);
				return $this->vxMessage(997);
			}
			return $this->vxMessage(100);
		} else {
			return $this->vxMessage(999);
		}
	}
	
	public function vxMessage($code = 500, $object = '') {
		$messages = array(
							1		=> 'Topic Object Created',
							100		=> 'Internal Debugging',
							999		=> 'Mismatched Parameters',
							998		=> 'Content Overflow',
							997		=> 'Unauthenticated',
							996		=> 'Node Not Found',
							600		=> 'Out Of Money',
							500		=> 'Invalid Operation');
		header('Content-Type: text/xml; charset=UTF-8');
		echo('<?xml version="1.0" standalone="yes"?>' . "\n");
		echo("<babel>\n");
		echo("<code>". $code . "</code>\n");
		echo("<message>V2EX XML Server: " . $messages[$code] . "</message>\n");
		if (is_object($object) && $code == 1) {
			echo("\t<topic>\n");
			echo("\t\t<id>{$object->tpc_id}</id>\n");
			echo("\t\t<url>http://bbs.kijiji.com.cn/topic/view/{$object->tpc_id}.html</url>\n");
			echo("\t</topic>\n");
			$object = null;
		}
		echo('</babel>');
		if ($code >= 500) {
			return false;
		} else {
			return true;
		}
	}
	
	/* E public modules */
}

/* E Standalone class */
?>
