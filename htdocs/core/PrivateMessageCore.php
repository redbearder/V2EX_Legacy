<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/PMCore.php
*  Usage: Private Message Core Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: PrivateMessageCore.php 505 2006-07-14 11:30:52Z livid $
*  $LastChangedDate: 2006-07-14 19:30:52 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 505 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/PrivateMessageCore.php $
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
	require('core/MessageCore.php');
	require('core/ChannelCore.php');
	require('core/URLCore.php');
	require('core/ImageCore.php');
	require('core/ValidatorCore.php');
} else {
	die('<strong>Project Babel</strong><br /><br />Made by V2EX | software for internet');
}

/* S PrivateMessage class */

class PrivateMessage {
	var $User;

	var $db;
	
	/* S module: constructor and destructor */

	public function __construct() {
		header('Content-type: text/html; charset=utf-8');
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
		$sql = 'DELETE FROM babel_online WHERE onl_lastmoved < ' . (time() - BABEL_USR_ONLINE_DURATION);
		mysql_query($sql, $this->db);
		$sql = "SELECT onl_hash FROM babel_online WHERE onl_hash = '" . session_id() . "'";
		$rs = mysql_query($sql, $this->db);
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = mysql_real_escape_string($_SERVER['HTTP_REFERER']);
		} else {
			$referer = '';
		}
		if (mysql_num_rows($rs) == 1) {
			$s = mysql_fetch_object($rs);
			mysql_free_result($rs);
			$sql = "UPDATE babel_online SET onl_nick = '" . $this->User->usr_nick . "', onl_ua = '" . $_SESSION['babel_ua']['ua'] . "', onl_ip = '" . $_SERVER['REMOTE_ADDR'] . "', onl_uri = '" . mysql_real_escape_string($_SERVER['REQUEST_URI']) . "', onl_ref = '" . $referer . "', onl_lastmoved = " . time() . " WHERE onl_hash = '" . session_id() . "'";
			mysql_query($sql, $this->db);
		} else {
			mysql_free_result($rs);
			$sql = "INSERT INTO babel_online(onl_hash, onl_nick, onl_ua, onl_ip, onl_uri, onl_ref, onl_created, onl_lastmoved) VALUES('" . session_id() . "', '" . mysql_real_escape_string($this->User->usr_nick) . "', '" . $_SESSION['babel_ua']['ua'] . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . mysql_real_escape_string($_SERVER['REQUEST_URI']) . "', '" . $referer . "', " . time() . ', ' . time() . ')';
			mysql_query($sql, $this->db);
		}
		$this->URL = new URL();	
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	/* S base blocks */
	
	public function vxHead($msgSiteTitle = '') {
		if ($msgSiteTitle != '') {
			$msgSiteTitle = Vocabulary::site_name . ' | ' . $msgSiteTitle;
		} else {
			$msgSiteTitle = Vocabulary::site_title;
		}
		
		echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">');
		echo('<head>');
		echo('<meta http-equiv="content-type" content="text/html;charset=utf-8" />');
		echo('<meta http-equiv="cache-control" content="no-cache" />');
		echo('<meta name="keywords" content="' . Vocabulary::meta_keywords . '" />');
		echo('<meta name="description" content="' . Vocabulary::meta_description . '" />');
		echo('<meta name="author" content="Livid Torvalds" />');
		echo('<title>' . $msgSiteTitle . '</title>');
		echo('<link href="/favicon.ico" rel="shortcut icon" />');
		echo('<link rel="stylesheet" type="text/css" href="/css_pm.css" />');
		echo('</head>');
	}
	
	public function vxBodyStart() {
		echo('<body>');
	}
	
	public function vxBodyEnd() {
		echo('</body></end>');
	}
	
	public function vxToolbar() {
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>');
		echo('<td width="68"><a href="/message/home.vx"><img src="/img/msg_01.gif" width="68" height="30" border="0" alt="概况" /></td>');
		echo('<td width="72"><a href="/message/compose.vx"><img src="/img/msg_02.gif" width="72" height="30" border="0" alt="撰写" /></td>');
		echo('<td width="86"><a href="/message/inbox.vx"><img src="/img/msg_03.gif" width="86" height="30" border="0" alt="收件箱" /></td>');
		echo('<td width="80"><a href="/message/sent.vx"><img src="/img/msg_04.gif" width="80" height="30" border="0" alt="已发送" /></td>');
		echo('<td width="100%" background="/img/msg_06.gif"></td>');
		echo('</tr></table>');
	}
	
	public function vxBottom() {
		echo('<div id="bottom"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>');
		echo('<td width="100%" background="/img/msg_vms_01.png"></td>');
		echo('<td width="90"><img src="/img/msg_vms_02.png" width="90" height="20" border="0" alt="V2EX MailServer 2006" /></td>');
		echo('</tr></table></div>');
	}
	
	public function vxDenied() {
		$this->vxHead($msgSiteTitle = Vocabulary::term_accessdenied);
		$this->vxBodyStart();
		$this->vxToolbar();
		echo('<div id="main"><h1>Access Denied</h1></div>');
		$this->vxBottom();
		$this->vxBodyEnd();
	}
	
	/* E base blocks */
	
	/* S Home module */
	
	public function vxHome() {
		if ($this->User->vxIsLogin()) {
			$this->vxHead($msgSiteTitle = Vocabulary::term_privatemessage);
			$this->vxBodyStart();
			$this->vxToolbar();
			echo('<div id="main">');
			if ($this->User->usr_portrait != '') {
				$img_p = '/img/p/'. $this->User->usr_portrait . '.' . BABEL_PORTRAIT_EXT;
			} else {
				$img_p = '/img/portrait.png';
			}
			echo('<img src="' . $img_p . '" align="left" style="margin: 5px 5px 0px 5px;" class="portrait" />');
			echo('<h1>' . make_plaintext($this->User->usr_nick) . ' | 概况</h1>');
			echo('<h6>' . $this->User->usr_email . '</h6>');
			echo('<h5><br />你口袋里有 ' . $this->User->usr_money_a['str'] . '<h5>');
			
			$sql = 'SELECT COUNT(msg_id) FROM babel_message WHERE msg_rid = ' . $this->User->usr_id . ' AND msg_draft = 0';
			$rs = mysql_query($sql, $this->db);
			$msg_inbox_count_total = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			$sql = 'SELECT COUNT(msg_id) FROM babel_message WHERE msg_rid = ' . $this->User->usr_id . ' AND msg_draft = 0 AND msg_opened = 0';
			$rs = mysql_query($sql, $this->db);
			$msg_inbox_count_fresh = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			echo('<span class="text"><br /><br /><img src="/img/mico_get.gif" align="absmiddle" />&nbsp;&nbsp;收件箱中共有 ' . $msg_inbox_count_total . ' 条短消息');
			if ($msg_inbox_count_fresh > 0) {
				echo('，<a href="/message/inbox.vx">其中 ' . $msg_inbox_count_fresh . ' 条新消息！</a>');
			}
			
			$sql = 'SELECT COUNT(msg_id) FROM babel_message WHERE msg_sid = ' . $this->User->usr_id . ' AND msg_draft = 0';
			$rs = mysql_query($sql, $this->db);
			$msg_sent_count_total = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			$sql = 'SELECT COUNT(msg_id) FROM babel_message WHERE msg_sid = ' . $this->User->usr_id . ' AND msg_draft = 0 AND msg_opened = 0';
			$rs = mysql_query($sql, $this->db);
			$msg_sent_count_fresh = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			echo('<hr color="#8C8C6E" size="1"><img src="/img/mico_msg.gif" align="absmiddle" />&nbsp;&nbsp;你总共发送过 ' . $msg_sent_count_total . ' 条短消息');
			if ($msg_sent_count_fresh > 0) {
				echo('，其中 ' . $msg_sent_count_fresh . ' 条对方尚未打开。');
			}
			echo('</span>');
			echo('</div>');
			$this->vxBottom();
			$this->vxBodyEnd();
		} else {
			$this->vxDenied();
		}
	}
	
	/* E Home module */
	
	/* S Compose module */
	
	public function vxCompose() {
		if ($this->User->vxIsLogin()) {
			$this->vxHead($msgSiteTitle = Vocabulary::action_composemessage);
			$this->vxBodyStart();
			$this->vxToolbar();
			if (isset($_GET['user_id'])) {
				$user_id = intval($_GET['user_id']);
				if ($this->Validator->vxExistUser($user_id)) {
					if ($user_id != $this->User->usr_id) {
						$User = $this->User->vxGetUserInfo($user_id);
						
						echo('<div id="main"><span class="text"><table width="385" cellpadding="0" cellspacing="0" border="0">');
						echo('<form action="/message/create.vx" method="post">');
						echo('<tr><td width="385" height="28" colspan="2" valign="middle"><h3><img src="/img/mtico_mail_new.gif" align="absmiddle" onload="document.getElementById(' . "'msgBody'" . ').focus()" />&nbsp;&nbsp;' . Vocabulary::action_composemessage . '</h3></td></tr>');
						echo('<tr><td width="65" height="20" align="right">收件人&nbsp;&nbsp;</td><td width="320" height="20" align="left"><input type="text" name="msg_receivers" class="sl" value="' . $User->usr_nick . '" /></td></tr>');
						echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">填入你的好友的昵称，用半角逗号分割多个好友</span></td></tr>');
						echo('<tr><td width="65" height="20" align="right" valign="top">消息内容&nbsp;&nbsp;</td><td width="320" height="120" align="left"><span class="tip"><textarea id="msgBody" name="msg_body" class="ml"></textarea></td></tr>');
						echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">消息内容字数限制在 200 内</span></td></tr>');
						echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="right"><input type="image" src="/img/mbtn_send.gif" />&nbsp;</td></tr>');
						echo('</form>');
						echo('</table></span>');
						echo('</div>');
					} else {
						$this->vxComposeBlank();
					}
				} else {
					$this->vxComposeBlank();
				};
			} else {
				$this->vxComposeBlank();
			}
			$this->vxBottom();
			$this->vxBodyEnd();
		} else {
			$this->vxDenied();
		}
	}
	
	private function vxComposeBlank() {
		echo('<div id="main"><span class="text"><table width="385" cellpadding="0" cellspacing="0" border="0">');
		echo('<form action="/message/create.vx" method="post">');
		echo('<tr><td width="385" height="28" colspan="2" valign="middle"><h3><img src="/img/mtico_mail_new.gif" align="absmiddle" />&nbsp;&nbsp;' . Vocabulary::action_composemessage . '</h3></td></tr>');
		echo('<tr><td width="65" height="20" align="right">收件人&nbsp;&nbsp;</td><td width="320" height="20" align="left"><input type="text" name="msg_receivers" class="sl" value="" /></td></tr>');
		echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">填入你的好友的昵称，用半角逗号分割多个好友</span></td></tr>');
		echo('<tr><td width="65" height="20" align="right" valign="top">消息内容&nbsp;&nbsp;</td><td width="320" height="120" align="left"><span class="tip"><textarea id="msgBody" name="msg_body" class="ml"></textarea></td></tr>');
		echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">消息内容字数限制在 200 内</span></td></tr>');
		echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="right"><input type="image" src="/img/mbtn_send.gif" />&nbsp;</td></tr>');
		echo('</form>');
		echo('</table></span>');
		echo('</div>');
	}
	
	/* E Compose module */
	
	/* S Create module */
	
	public function vxCreate() {
		if ($this->User->vxIsLogin()) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
				/* render the composer */
				$this->vxCompose();
			} else {
				$rt = $this->Validator->vxMessageCreateCheck();
				if ($rt['errors'] == 0) {
					/* insert new messages into the database */
					foreach ($rt['msg_receivers_v'] as $recv_id => $receiver) {
						$exp_memo = mysql_real_escape_string($rt['msg_receivers_n'][$recv_id], $this->db);
						$this->Validator->vxMessageCreateInsert($this->User->usr_id, $receiver, $rt['msg_body_value'], $exp_memo);
					}
				}
				$this->vxHead($msgSiteTitle = Vocabulary::action_composemessage);
				$this->vxBodyStart();
				$this->vxToolbar();
				$this->vxCreateSubstance($rt);
				$this->vxBottom();
				$this->vxBodyEnd();
			}
		} else {
			$this->vxDenied();
		}
	}
	
	/* E Create module */
	
	/* S Create Substance module */
	
	private function vxCreateSubstance($rt) {
		/* when error */
		if ($rt['errors'] > 0) {
			echo('<div id="main"><span class="text"><table width="385" cellpadding="0" cellspacing="0" border="0">');
			echo('<form action="/message/create.vx" method="post">');
			echo('<tr><td width="385" height="28" colspan="2" valign="middle"><h3><img src="/img/mtico_mail_new.gif" align="absmiddle" />&nbsp;&nbsp;' . Vocabulary::action_composemessage . ' | 有些错误</h3></td></tr>');
			echo('<tr><td width="65" height="20" align="right">收件人&nbsp;&nbsp;</td><td width="320" height="20" align="left"><input type="text" name="msg_receivers" class="sl" value="' . make_single_return($rt['msg_receivers_value']) . '" /></td></tr>');
			echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">');
			if ($rt['msg_receivers_error'] > 0) {
				echo($rt['msg_receivers_error_msg'][$rt['msg_receivers_error']]);
			} else {
				echo('&nbsp;');
			}
			echo('</span></td></tr>');
			echo('<tr><td width="65" height="20" align="right" valign="top">消息内容&nbsp;&nbsp;</td><td width="320" height="120" align="left"><span class="tip"><textarea id="msgBody" name="msg_body" class="ml">' . make_multi_return($rt['msg_body_value']) .'</textarea></td></tr>');
			echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="left"><span class="tip">');
			if ($rt['msg_body_error'] > 0) {
				echo($rt['msg_body_error_msg'][$rt['msg_body_error']]);
			} else {
				echo('&nbsp;');
			}
			echo('</span></td></tr>');
			echo('<tr><td width="65" height="20" align="right"></td><td width="320" height="20" align="right"><input type="image" src="/img/mbtn_send.gif" />&nbsp;</td></tr>');
			echo('</form>');
			echo('</table></span></div>');
		} else {
		/* when ok */
			echo('<div id="main">');
			if ($this->User->usr_portrait != '') {
				$img_p = '/img/p/'. $this->User->usr_portrait . '.' . BABEL_PORTRAIT_EXT;
			} else {
				$img_p = '/img/portrait.png';
			}
			echo('<img src="' . $img_p . '" align="left" style="margin: 5px 5px 0px 5px;" class="portrait" />');
			echo('<h1>' . make_plaintext($this->User->usr_nick) . ' | 短消息成功发送！</h1>');
			echo('<h6>' . $this->User->usr_email . '</h6>');
			echo('<h5><br />你口袋里有 ' . $this->User->usr_money_a['str'] . '<h5>');
			echo('<span class="text"><br /><br /><img src="/img/mico_msg.gif" align="absmiddle" />&nbsp;&nbsp;短消息已经被成功发送到以下收件人：<br /><br />');
			foreach ($rt['msg_receivers_n'] as $receiver) {
				echo('&nbsp;&nbsp;<input type="button" class="recv" onclick="location.href=' . "'/message/sent.vx';" . '" value="' . make_single_return($receiver) . '" />');
			}
			$count = count($rt['msg_receivers_v']);
			$expense = BABEL_MSG_PRICE * $count;
			echo('<hr size="1" color="#CACB98" /><img src="/img/mico_info.gif" align="absmiddle" />&nbsp;&nbsp;发送这 ' . $count . ' 条短消息花费了 ' . $expense . ' 个铜币。</span>');
			echo('</div>');
		}
	}
	
	/* E Create Substance module */
	
	/* S View module */
	
	public function vxView() {
		if ($this->User->vxIsLogin()) {
			if (isset($_GET['message_id'])) {
				$message_id = intval($_GET['message_id']);
				if ($this->Validator->vxExistMessage($message_id)) {
					$Message = new Message($message_id, $this->db);
					$mode = '';
					if ($Message->msg_sid == $this->User->usr_id) {
						if ($Message->msg_draft == 1) {
							$mode = 'draft';
						} else {
							$mode = 'sent';
						}
					} else {
						if ($Message->msg_rid == $this->User->usr_id) {
							$mode = 'inbox';
						} else {
							$mode = 'mismatch';
						}
					}
					if ($mode == 'mismatch') {
						$this->vxHome();
					} else {
						switch ($mode) {
							case 'inbox':
								$Sender = $this->User->vxGetUserInfo($Message->msg_sid);
								$this->vxHead($msgSiteTitle = '收件箱');
								$this->vxBodyStart();
								$this->vxToolbar();
								echo('<div id="main">');
								echo('<div id="msg"><span class="text">');
								echo('<table cellpadding="0" cellspacing="0" border="0" class="msg">');
								echo('<tr>');
								echo('<td width="150" height="20">来自于 - ' . make_plaintext($Sender->usr_nick) . '</td><td width="220" height="20" align="right">接收时间 - ' . make_descriptive_time($Message->msg_sent) . '</td>');
								echo('</tr>');
								echo('<tr>');
								echo('<td height="20" colspan="2" align="right"><small class="tip">');
								if ($Message->msg_opened == 0) {
									$t = time();
									$sql = "UPDATE babel_message SET msg_hits = msg_hits + 1, msg_opened = {$t}, msg_lastaccessed = {$t} WHERE msg_id = {$Message->msg_id}";
									mysql_query($sql, $this->db);
									echo('这是你第 1 次打开这条消息！');
								} else {
									$t = time();
									$sql = "UPDATE babel_message SET msg_hits = msg_hits + 1, msg_lastaccessed = {$t} WHERE msg_id = {$Message->msg_id} LIMIT 1";
									mysql_query($sql, $this->db);
									echo('这是你第 ' . ($Message->msg_hits + 1) . ' 次打开这条消息');
								}
								echo('</small></td>');
								echo('</tr>');
								echo('</table>');
								echo($Message->msg_body);
								echo('</span>');
								echo('</div>');
								if (isset($_SERVER['HTTP_REFERER'])) {
									$url_return = $_SERVER['HTTP_REFERER'];
								} else {
									$url_return = '/message/inbox.vx';
								}
								echo('<div id="tool"><a href="' . $url_return . '" class="tool">&lt; 返回</a><a href="/message/compose/' . $Message->msg_sid . '.vx" class="tool">回复 &gt;</a></div>');
								echo('</div>');
								$this->vxBottom();
								$this->vxBodyEnd();
								$Sender = null;
								break;
							case 'sent':
								$Receiver = $this->User->vxGetUserInfo($Message->msg_rid);
								$this->vxHead($msgSiteTitle = '已发送');
								$this->vxBodyStart();
								$this->vxToolbar();
								echo('<div id="main">');
								echo('<div id="msg"><span class="text">');
								echo('<table cellpadding="0" cellspacing="0" border="0" class="msg">');
								echo('<tr>');
								echo('<td width="150" height="20">发送到 - ' . make_plaintext($Receiver->usr_nick) . '</td><td width="220" height="20" align="right">发送时间 - ' . make_descriptive_time($Message->msg_sent) . '</td>');
								echo('</tr>');
								echo('<tr>');
								echo('<td height="20" colspan="2" align="right"><small class="tip">');
								if ($Message->msg_opened == 0) {
									echo('对方尚未打开这条短消息');
								} else {
									echo('对方在 ' . make_descriptive_time($Message->msg_lastaccessed) . '打开过这条短消息，共看过 ' . $Message->msg_hits . ' 次');
								}
								echo('</small></td>');
								echo('</tr>');
								echo('</table>');
								echo($Message->msg_body);
								echo('</span>');
								echo('</div>');
								if (isset($_SERVER['HTTP_REFERER'])) {
									$url_return = $_SERVER['HTTP_REFERER'];
								} else {
									$url_return = '/message/sent.vx';
								}
								echo('<div id="tool"><a href="' . $url_return . '" class="tool">&lt; 返回</a></div>');
								echo('</div>');
								$this->vxBottom();
								$this->vxBodyEnd();
								$Receiver = null;
								break;
							default:
								$this->vxHome();
								break;
						}
					}
				} else {
					$this->vxHome();
				}
			} else {
				$this->vxHome();
			}
		} else {
			$this->vxDenied();
		}
	}
	
	/* E View module */
	
	/* S Inbox module */
	
	public function vxInbox() {
		if ($this->User->vxIsLogin()) {
			$this->vxHead($msgSiteTitle = '收件箱');
			$this->vxBodyStart();
			$this->vxToolbar();
			
			$p = array();
			$p['base'] = '/message/inbox/';
			$p['ext'] = '.vx';
			$sql = "SELECT COUNT(msg_id) FROM babel_message WHERE msg_rid = {$this->User->usr_id} AND msg_draft = 0";
			$rs = mysql_query($sql, $this->db);
			$p['items'] = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			if ($p['items'] > 0) {
				$p['size'] = BABEL_MSG_PAGE;
				$p['span'] = BABEL_PG_SPAN;
				if (($p['items'] % $p['size']) == 0) {
					$p['total'] = $p['items'] / $p['size'];
				} else {
					$p['total'] = floor($p['items'] / $p['size']) + 1;
				}
				if (isset($_GET['p'])) {
					$p['cur'] = intval($_GET['p']);
				} else {
					$p['cur'] = 1;
				}
				if ($p['cur'] < 1) {
					$p['cur'] = 1;
				}
				if ($p['cur'] > $p['total']) {
					$p['cur'] = $p['total'];
				}
				if (($p['cur'] - $p['span']) >= 1) {
					$p['start'] = $p['cur'] - $p['span'];
				} else {
					$p['start'] = 1;
				}
				if (($p['cur'] + $p['span']) <= $p['total']) {
					$p['end'] = $p['cur'] + $p['span'];
				} else {
					$p['end'] = $p['total'];
				}
				$p['sql'] = ($p['cur'] - 1) * $p['size'];
				$sql = "SELECT msg_id, msg_body, msg_created, msg_opened, usr_nick FROM babel_message, babel_user WHERE msg_rid = {$this->User->usr_id} AND msg_draft = 0 AND msg_sid = usr_id ORDER BY msg_sent DESC LIMIT " . $p['sql'] . "," . BABEL_MSG_PAGE;
				$rs = mysql_query($sql, $this->db);
				echo('<div id="main">');
				echo('<div id="tool">');
				if ($p['total'] > 1) {
					echo('<span class="tip">已收到短消息 ' . $p['items'] . ' 条 </span>');
					$this->vxDrawPages($p);
				} else {
					echo('<span class="tip">已收到短消息 ' . $p['items'] . ' 条</span>');
				}
				echo('</div>');
				echo('<div id="msg"><span class="text"><table cellpadding="0" cellspacing="0" border="0" class="items">');
				while ($Message = mysql_fetch_object($rs)) {
					echo('<tr>');
					echo('<td width="100" height="20" class="recv" align="right">' . make_plaintext($Message->usr_nick) . '&nbsp;</td><td width="190" height="20">&nbsp;<a href="/message/view/' . $Message->msg_id . '.vx">');
					$msg_excerpt = mb_substr($Message->msg_body, 0, 12, 'UTF-8'); 
					if ($Message->msg_opened == 0) {
						echo('<strong>' . $msg_excerpt . '</strong>');
					} else {
						echo($msg_excerpt);
					}
					if (mb_strlen($Message->msg_body, 'UTF-8') > mb_strlen($msg_excerpt, 'UTF-8')) {
						echo('...');
					}
					echo('</a></td><td width="90" height="20">');
					echo('<small>' . make_desc_time($Message->msg_created) . '</small>');
					if ($Message->msg_opened == 0) {
						echo(' *');
					}
					echo('</td>');
					echo('</tr>');
				}
				echo('</table></span>');
				echo('</div></div>');
			} else {
				echo('<div id="main"><div id="msg"><span class="text">你还没有收到过任何人发的短消息。</span></div></div>');
			}
			$this->vxBottom();
			$this->vxBodyEnd();
		} else {
			$this->vxDenied();
		}
	}
	
	/* E Inbox module */
	
	/* S Sent module */
	
	public function vxSent() {
		if ($this->User->vxIsLogin()) {
			$this->vxHead($msgSiteTitle = '已发送');
			$this->vxBodyStart();
			$this->vxToolbar();
			
			$p = array();
			$p['base'] = '/message/sent/';
			$p['ext'] = '.vx';
			$sql = "SELECT COUNT(msg_id) FROM babel_message WHERE msg_sid = {$this->User->usr_id} AND msg_draft = 0";
			$rs = mysql_query($sql, $this->db) or die(mysql_error());
			$p['items'] = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			if ($p['items'] > 0) {
				$p['size'] = BABEL_MSG_PAGE;
				$p['span'] = BABEL_PG_SPAN;
				if (($p['items'] % $p['size']) == 0) {
					$p['total'] = $p['items'] / $p['size'];
				} else {
					$p['total'] = floor($p['items'] / $p['size']) + 1;
				}
				if (isset($_GET['p'])) {
					$p['cur'] = intval($_GET['p']);
				} else {
					$p['cur'] = 1;
				}
				if ($p['cur'] < 1) {
					$p['cur'] = 1;
				}
				if ($p['cur'] > $p['total']) {
					$p['cur'] = $p['total'];
				}
				if (($p['cur'] - $p['span']) >= 1) {
					$p['start'] = $p['cur'] - $p['span'];
				} else {
					$p['start'] = 1;
				}
				if (($p['cur'] + $p['span']) <= $p['total']) {
					$p['end'] = $p['cur'] + $p['span'];
				} else {
					$p['end'] = $p['total'];
				}
				$p['sql'] = ($p['cur'] - 1) * $p['size'];
				$sql = "SELECT msg_id, msg_body, msg_created, msg_opened, usr_nick FROM babel_message, babel_user WHERE msg_sid = {$this->User->usr_id} AND msg_draft = 0 AND msg_rid = usr_id ORDER BY msg_created DESC LIMIT " . $p['sql'] . "," . BABEL_MSG_PAGE;
				$rs = mysql_query($sql, $this->db);
				echo('<div id="main">');
				echo('<div id="tool">');
				if ($p['total'] > 1) {
					echo('<span class="tip">已发送短消息 ' . $p['items'] . ' 条 </span>');
					$this->vxDrawPages($p);
					
				} else {
					echo('<span class="tip">已发送短消息 ' . $p['items'] . ' 条</span>');
					
				}
				echo('</div>');
				echo('<div id="msg"><span class="text"><table cellpadding="0" cellspacing="0" border="0" class="items">');
				while ($Message = mysql_fetch_object($rs)) {
					echo('<tr>');
					echo('<td width="100" height="20" class="recv" align="right">' . make_plaintext($Message->usr_nick) . '&nbsp;</td><td width="190" height="20">&nbsp;<a href="/message/view/' . $Message->msg_id . '.vx">');
					$msg_excerpt = mb_substr($Message->msg_body, 0, 12, 'UTF-8');
					if ($Message->msg_opened == 0) {
						echo('<strong>' . $msg_excerpt . '</strong>');
					} else {
						echo($msg_excerpt);
					}
					if (mb_strlen($Message->msg_body, 'UTF-8') > mb_strlen($msg_excerpt, 'UTF-8')) {
						echo('...');
					}
					echo('</a></td><td width="90" height="20">');
					echo('<small>' . make_desc_time($Message->msg_created) . '</small>');
					if ($Message->msg_opened == 0) {
						echo(' *');
					}
					echo('</td>');
					echo('</tr>');
				}
				echo('</table></span></div>');
				echo('</div>');
			} else {
				echo('<div id="main"><div id="msg"><span class="text">你还没有向任何人发送过短消息，<a href="/message/compose.vx">现在写一条？</a></span></div></div>');
			}
			$this->vxBottom();
			$this->vxBodyEnd();
		} else {
			$this->vxDenied();
		}
	}
	
	/* E Sent module */
	
	/* S module: Draw Pages logic */
	
	private function vxDrawPages($p) {
		if ($p['start'] != 1) {
			echo('<a href="' . $p['base'] . '1' . $p['ext'] . '" class="p_edge">1</a>');
		}
		for ($i = $p['start']; $i <= $p['end']; $i++) {
			if ($p['cur'] == $i) {
				echo('<strong class="p_cur">' . $i . '</strong>');
			} else {
				echo('<a href="' . $p['base'] . $i . $p['ext'] . '" class="p">' . $i . '</a>');
			}
		}
		if ($p['end'] != $p['total']) {
			echo('<a href="' . $p['base'] . $p['total'] . $p['ext'] . '" class="p_edge">' . $p['total'] . '</a>');
		}
		echo('<strong class="p_info">' . $p['items'] . ' ITEMS / ' . $p['size'] . ' PER PAGE</strong>');
	}
	
	/* E module: Draw Pages logic */
}
?>
