<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/ValidatorCore.php
*  Usage: Validator Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: ValidatorCore.php 439 2006-06-30 16:13:12Z livid $
*  $LastChangedDate: 2006-07-01 00:13:12 +0800 (Sat, 01 Jul 2006) $
*  $LastChangedRevision: 439 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/ValidatorCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S Validator class */

class Validator {
	var $db;
	
	var $User;
	
	public function __construct($db, $User) {
		$this->db = $db;
		$this->User = $User;
	}
	
	public function vxExistNode($node_id) {
		$sql = "SELECT nod_id FROM babel_node WHERE nod_id = {$node_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	public function vxExistBoardName($node_name) {
		$node_name = mysql_real_escape_string(trim($node_name));
		
		if ($node_name != '') {
			$sql = "SELECT nod_id, nod_pid, nod_title, nod_name, nod_level, nod_header, nod_footer FROM babel_node WHERE nod_name = '{$node_name}' AND nod_level = 2";
			$rs = mysql_query($sql);
			if (mysql_num_rows($rs) == 1) {
				$Node = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$sql = "SELECT nod_id, nod_title, nod_name FROM babel_node WHERE nod_id = {$Node->nod_pid}";
				$rs = mysql_query($sql);
				$Section = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$Node->sect_id = $Section->nod_id;
				$Node->sect_name = $Section->nod_name;
				$Node->sect_title = $Section->nod_title;
				$Section = null;
				return $Node;
			} else {
				mysql_free_result($rs);
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function vxIsDangerousTopic($topic_id, $c) {
		if ($d = $c->get('dangerous_topics')) {
			$d = unserialize($d);
		} else {
			$xml = simplexml_load_file(BABEL_PREFIX . '/res/dangerous.xml');
			$d = array();
			foreach ($xml->topics as $topic) {
				$d[] = intval($topic->topic);
			}
			$c->save(serialize($d), 'dangerous_topics');
		}
		if (in_array($topic_id, $d)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function vxExistTopic($topic_id) {
		$sql = "SELECT tpc_id FROM babel_topic WHERE tpc_id = {$topic_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	public function vxExistPost($post_id) {
		$sql = "SELECT pst_id FROM babel_post WHERE pst_id = {$post_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	public function vxExistUser($user_id) {
		$sql = "SELECT usr_id FROM babel_user WHERE usr_id = {$user_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	public function vxExistChannel($channel_id) {
		$sql = "SELECT chl_id FROM babel_channel WHERE chl_id = {$channel_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	public function vxExistMessage($message_id) {
		$sql = "SELECT msg_id FROM babel_message WHERE msg_id = {$message_id}";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			mysql_free_result($rs);
			return true;
		} else {
			mysql_free_result($rs);
			return false;
		}
	}
	
	/* S module: Message Create Check logic */
	
	public function vxMessageCreateCheck() {
		$rt = array();
		
		$rt['errors'] = 0;
		
		$rt['msg_receivers_value'] = '';
		/* receivers: raw */
		$rt['msg_receivers_a'] = array();
		/* receivers: validated */
		$rt['msg_receivers_v'] = array();
		/* receivers: validated names */
		$rt['msg_receivers_n'] = array();
		/* msg_receivers_error:
		0 => no error
		1 => empty
		2 => not exist
		999 => unspecific */
		$rt['msg_receivers_error'] = 0;
		$rt['msg_receivers_error_msg'] = array(1 => '你忘记写收件人了', 2 => '你写的一位或多位收件人不存在');
		
		if (isset($_POST['msg_receivers'])) {
			$rt['msg_receivers_value'] = make_single_safe($_POST['msg_receivers']);
			if (strlen($rt['msg_receivers_value']) > 0) {
				$rt['msg_receivers_a'] = explode(',', $rt['msg_receivers_value']);
				foreach ($rt['msg_receivers_a'] as $msg_receiver) {
					$msg_receiver = trim($msg_receiver);
					$sql = "SELECT usr_id, usr_nick FROM babel_user WHERE usr_nick = '{$msg_receiver}'";
					$rs = mysql_query($sql, $this->db);
					if (mysql_num_rows($rs) == 1) {
						$User = mysql_fetch_object($rs);
						mysql_free_result($rs);
						if ($User->usr_id != $this->User->usr_id) {
							if (!in_array($User->usr_id, $rt['msg_receivers_v'])) {
								$rt['msg_receivers_v'][] = $User->usr_id;
								$rt['msg_receivers_n'][] = $User->usr_nick;
							}
						}
					} else {
						mysql_free_result($rs);
						$rt['msg_receivers_error'] = 2;
						$rt['errors']++;
						break;
					}
				}
				if ($rt['msg_receivers_error'] == 0) {
					if (count($rt['msg_receivers_v']) == 0) {
						$rt['msg_receivers_value'] = '';
						$rt['msg_receivers_error'] = 1;
						$rt['errors']++;
					} else {
						$rt['msg_receivers_value'] = implode(',', $rt['msg_receivers_n']);
					}
				}
			} else {
				$rt['msg_receivers_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['msg_receivers_error'] = 1;
			$rt['errors']++;
		}
		
		$rt['msg_body_value'] = '';
		$rt['msg_body_error'] = 0;
		$rt['msg_body_error_msg'] = array(1 => '你忘记写消息内容了', 2 => '你写的消息内容超出长度限制了');
		
		if (isset($_POST['msg_body'])) {
			$rt['msg_body_value'] = make_multi_safe($_POST['msg_body']);
			$rt['msg_body_length'] = mb_strlen($rt['msg_body_value'], 'UTF-8');
			if ($rt['msg_body_length'] > 0) {
				if ($rt['msg_body_length'] > 200) {
					$rt['msg_body_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['msg_body_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['msg_body_error'] = 1;
			$rt['errors']++;
		}
		
		return $rt;
	}
	
	/* E module: Message Create Check logic */
	
	/* S module: Message Create Insert logic */
	
	public function vxMessageCreateInsert($sender_id, $receiver_id, $msg_body, $exp_memo, $expense_amount = BABEL_MSG_PRICE) {
		$t = time();
		if (get_magic_quotes_gpc()) {
			$msg_body = mysql_real_escape_string(stripslashes($msg_body));
		} else {
			$msg_body = mysql_real_escape_string($msg_body);
		}
		$sql = "INSERT INTO babel_message(msg_sid, msg_rid, msg_body, msg_created, msg_sent) VALUES({$sender_id}, {$receiver_id}, '{$msg_body}', {$t}, {$t})";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			return $this->User->vxPay($this->User->usr_id, -$expense_amount, 8, $exp_memo);
		} else {
			die(mysql_error());
			return false;
		}
	}
	
	/* E module: Message Create Insert logic */
	
	/* S module: Login Check logic */
	
	public function vxLoginCheck() {
		$rt = array();
		
		$rt['target'] = 'welcome';
		$rt['return'] = '';
		
		$rt['errors'] = 0;
		
		$rt['usr_value'] = '';
		$rt['usr_email_value'] = '';
		/* usr_error:
		0 => no error
		1 => empty
		999 => unspecific */
		$rt['usr_error'] = 0;
		$rt['usr_error_msg'] = array(1 => '你忘记填写名字了');
		
		$rt['usr_password_value'] = '';
		/* usr_password_error:
		0 => no error
		1 => empty
		2 => mismatch
		999 => unspecific */
		$rt['usr_password_error'] = 0;
		$rt['usr_password_error_msg'] = array(1 => '你忘记填写密码了', 2 => '名字或者密码有错误');

		if (isset($_POST['return'])) {
			$rt['return'] = trim($_POST['return']);
		}
		
		if (isset($_POST['usr'])) {
			$rt['usr_value'] = strtolower(make_single_safe($_POST['usr']));
			if (strlen($rt['usr_value']) == 0) {
				$rt['usr_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['usr_error'] = 1;
			$rt['errors']++;
		}
		
		if ($rt['errors'] > 0) {
			$rt['target'] = 'error';
			return $rt;
		}
		
		if (isset($_POST['usr_password'])) {
			$rt['usr_password_value'] = make_single_safe($_POST['usr_password']);
			if (strlen($rt['usr_password_value']) == 0) {
				$rt['usr_password_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['usr_password_error'] = 1;
			$rt['errors']++;
		}
		
		if ($rt['errors'] > 0) {
			$rt['target'] = 'error';
			return $rt;
		}
		
		$sql = "SELECT usr_id FROM babel_user WHERE usr_email = '" . $rt['usr_value'] . "' AND usr_password = '" . sha1($rt['usr_password_value']) . "'";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			$rt['usr_email_value'] = $rt['usr_value'];
			$rt['target'] = 'ok';
		} else {
			$sql = "SELECT usr_id, usr_email FROM babel_user WHERE usr_nick = '" . $rt['usr_value'] . "' AND usr_password = '" . sha1($rt['usr_password_value']) . "'";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$O = mysql_fetch_object($rs);
				$rt['usr_email_value'] = $O->usr_email;
				$rt['target'] = 'ok';
			} else {
				$rt['target'] = 'error';
				$rt['usr_password_error'] = 2;
				$rt['errors']++;
			}
		}
		mysql_free_result($rs);
		
		return $rt;
	}
	
	/* E module: Login Check logic */
	
	/* S module: URL Classified logic */
	
	public function vxGetURLHost($url) {
		$o = array();
		
		$o['type'] = 'web';
		$o['url'] = strtolower($url);

		if (preg_match('/flickr\.com/', $url)) {
			$o['type'] = 'flickr';
			return $o;
		}
		
		if (preg_match('/feedburner\.com/', $url)) {
			$o['type'] = 'feedburner';
			return $o;
		}
		
		if (preg_match('/buzznet\.com/', $url)) {
			$o['type'] = 'buzznet';
			return $o;
		}
		
		
		if (preg_match('/technorati\.com/', $url)) {
			$o['type'] = 'technorati';
			return $o;
		}
		
		if (preg_match('/douban\.com/', $url)) {
			$o['type'] = 'douban';
			return $o;
		}
		
		if (preg_match('/mac\.com/', $url)) {
			$o['type'] = 'mac';
			return $o;
		}
		
		if (preg_match('/spaces\.msn\.com/', $url)) {
			$o['type'] = 'spaces';
			return $o;
		}
		
		if (preg_match('/blinklist\.com/', $url)) {
			$o['type'] = 'blinklist';
			return $o;
		}
		
		if (preg_match('/bulaoge\.com/', $url)) {
			$o['type'] = 'bulaoge';
			return $o;
		}
		
		if (preg_match('/box\.net/', $url)) {
			$o['type'] = 'box';
			return $o;
		}
		
		if (preg_match('/deviantart\.com/', $url)) {
			$o['type'] = 'deviantart';
			return $o;
		}
		
		if (preg_match('/(google\.com)|(googlepages\.com)|(gfans\.org)/', $url)) {
			$o['type'] = 'google';
			return $o;
		}
		
		if (preg_match('/(blogspot\.com)/', $url)) {
			$o['type'] = 'blogspot';
			return $o;
		}
		
		
		if (preg_match('/del\.icio\.us/', $url)) {
			$o['type'] = 'delicious';
			return $o;
		}
		
		if (preg_match('/livid\.cn/', $url)) {
			$o['type'] = 'livid';
			return $o;
		}
		
		if (preg_match('/v2ex/', $url)) {
			$o['type'] = 'v2ex';
			return $o;
		}
		
		return $o;
	}
	
	/* E module: URL Classified logic */
	
	/* S module: User Agent Check logic */
	
	public function vxGetUserAgent($ua = '') {
		if ($ua == '') {
			$ua = $_SERVER['HTTP_USER_AGENT'];
		}
		
		$o = array();
		
		$o['ua'] = $ua;
		$o['platform'] = '';
		$o['name'] = '';
		$o['version'] = '';
		$o['PSP_DETECTED'] = 0;
		$o['MSIE_DETECTED'] = 0;
		$o['GECKO_DETECTED'] = 0;
		$o['KHTML_DETECTED'] = 0;
		$o['OPERA_DETECTED'] = 0;
		$o['LEGACY_ENCODING'] = 0;
		/* DEVICE_LEVEL
		0 => bot
		1 => plaintext
		2 => handheld (limited display and processor)
		3 => pc (full capable)
		4 => fetcher (just file access)
		5 => tv (various features supported)
		*/
		$o['DEVICE_LEVEL'] = 0;
		
		/* PSP Internet Browser 
		 * Example: Mozilla/4.0 (PSP (PlayStation Portable); 2.00) */
		if (preg_match('/Mozilla\/4\.0 \(PSP \(PlayStation Portable\); ([2-9]?\.[0-9]*)\)/', $ua, $z)) {
			$o['platform'] = 'PSP';
			$o['name'] = 'PSP Internet Browser';
			$o['version'] = $z[1];
			$o['PSP_DETECTED'] = 1;
			$o['DEVICE_LEVEL'] = 2;
			return $o;
		}
		
		/* PalmOne Blazer */
		if (preg_match('/Blazer\/([1-9]+\.[0-9a-zA-Z]*)/', $ua, $z) && preg_match('/Palm/', $ua)) {
			$o['platform'] = 'PalmOS';
			$o['name'] = 'Blazer';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 2;
			return $o;
		}
		
		/* Xiino
		 * Example: Xiino/3.4E [en] (v.5.4.8; 153x130; c16/d) */
		if (preg_match('/Xiino\/([0-9a-zA-Z\.]*)/', $ua, $z)) {
			$o['platform'] = 'PalmOS';
			$o['name'] = 'Xiino';
			$o['version'] = $z[1];
			$o['LEGACY_ENCODING'] = 1;
			$o['DEVICE_LEVEL'] = 2;
			return $o;
		}
		
		/* PocketLink
		 * Example: Mozilla/5.0 (compatible; PalmOS) PLink 2.56c */
		if (preg_match('/Mozilla\/5\.0 \(compatible; PalmOS\) PLink ([0-9a-zA-Z\.]*)/', $ua, $z)) {
			$o['platform'] = 'PalmOS';
			$o['name'] = 'PocketLink';
			$o['version'] = $z[1];
			$o['LEGACY_ENCODING'] = 1;
			$o['DEVICE_LEVEL'] = 2;
			return $o;
		}
		
		/* Opera (Identify as Opera)
		 * Example: Opera/8.5 (Macintosh; PPC Mac OS X; U; zh-cn)
		 * Example: Opera/8.50 (Windows NT 5.0; U; en) */
		if (preg_match('/Opera\/([0-9]+\.[0-9]+) \(([a-zA-Z0-9\.\- ]*); ([a-zA-Z0-9\.\- ]*); ([a-zA-Z0-9\.\-\; ]*)\)/', $ua, $z)) {
			if (preg_match('/(Linux|Mac OS X)/', $ua, $y)) {
				$o['platform'] = $y[1];
			} else {
				$o['platform'] = $z[2];
			}
			$o['name'] = 'Opera';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 3;
			$o['OPERA_DETECTED'] = 1;
			return $o;
		}

		/* Opera (Identify as MSIE 6.0)
		 * Example: Mozilla/4.0 (compatible; MSIE 6.0; X11; Linux i686; en) Opera 8.5
		 * Example: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; en) Opera 8.50 */
		if (preg_match('/Mozilla\/4\.0 \(compatible; MSIE ([0-9\.]*); ([a-zA-Z0-9\-\.;_ ]*); ([a-zA-Z0-9\-\.;_ ]*)\) Opera ([0-9\.]*)/', $ua, $z)) {
			if (preg_match('/(Linux|Mac OS X)/', $z[2], $y)) {
				$o['platform'] = $y[1];
			} else {
				$o['platform'] = $z[2];
			}
			$o['name'] = 'Opera';
			$o['version'] = $z[4];
			$o['DEVICE_LEVEL'] = 3;
			$o['OPERA_DETECTED'] = 1;
			return $o;
		}

		/* Opera (Identify as Mozilla/5.0)
		 * Example: Mozilla/5.0 (X11; Linux i686; U; en) Opera 8.5 
		 * Example: Mozilla/5.0 (Windows NT 5.0; U; en) Opera 8.50 */
		if (preg_match('/Mozilla\/5\.0 \(([a-zA-Z0-9\-\. ]*); ([a-zA-Z0-9\-\. ]*); ([a-zA-Z0-9\-\.; ]*)\) Opera ([0-9\.]*)/', $ua, $z)) {
			if (preg_match('/Windows ([a-zA-Z0-9\.\- ]*)/', $z[1], $y)) {
				$o['platform'] = $y[0];
			} else {
				if (preg_match('/(Linux|Mac OS X)/', $z[2], $y)) {
					$o['platform'] = $y[1];
				} else {
					$o['platform'] = $z[2];
				}
			}
			$o['name'] = 'Opera';
			$o['version'] = $z[4];
			$o['DEVICE_LEVEL'] = 3;
			$o['OPERA_DETECTED'] = 1;
			return $o;
		}
	
		/* Apple Safari 
		 * Example: Mozilla/5.0 (Macintosh; U; PPC Mac OS X; zh-cn) AppleWebKit/412.7 (KHTML, like Gecko) Safari/412.5 */
		if (preg_match('/Mozilla\/5\.0 \(Macintosh; U;([a-zA-Z0-9\s]+); [a-z\-]+\) AppleWebKit\/([0-9]+\.[0-9]+) \(KHTML, like Gecko\) Safari\/([0-9]+\.[0-9]+)/', $ua, $z)) {
			$o['platform'] = 'Mac OS X';
			$o['name'] = 'Safari';
			$o['version'] = $z[2];
			$o['DEVICE_LEVEL'] = 3;
			$o['KHTML_DETECTED'] = 1;
			return $o;
		}
		
		/* Apple WebKit 
		 * Example: Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Safari/417.9.2 */
		if (preg_match('/Mozilla\/5\.0 \(Macintosh; U;([a-zA-Z0-9\s]+); [a-z\-]+\) AppleWebKit\/([0-9\+\.]+) \(KHTML, like Gecko\) Safari\/([0-9]+\.[0-9]+)/', $ua, $z)) {
			$o['platform'] = 'Mac OS X';
			$o['name'] = 'WebKit';
			$o['version'] = $z[2];
			$o['DEVICE_LEVEL'] = 3;
			$o['KHTML_DETECTED'] = 1;
			return $o;
		}
	
		/* KDE Konqueror
		 * Example: Mozilla/5.0 (compatible; Konqueror/3.4; Linux) KHTML/3.4.2 (like Gecko) (Debian package 4:3.4.2-4) */
		if (preg_match('/Mozilla\/5\.0 \(compatible; Konqueror\/([0-9\.]*); ([a-zA-Z]*)\) KHTML\/([0-9\.]*)/', $ua, $z)) {
			$o['platform'] = $z[2];
			$o['name'] = 'Konqueror';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 3;
			$o['KHTML_DETECTED'] = 1;
			return $o;
		}

		/* iCab
		 * Example: Mozilla/5.0 (compatible; iCab 3.0.1; Macintosh; U; PPC Mac OS X)*/
		if (preg_match('/Mozilla\/5\.0 \(compatible; iCab ([0-9\.]+); Macintosh; U; PPC Mac OS X\)/', $ua, $z)) {
			$o['platform'] = 'Macintosh';
			$o['name'] = 'iCab';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 3;
			return $o;
		}
	
		/* Microsoft Internet Explorer 
		 * Example: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50215) */
		if (preg_match('/Mozilla\/4\.0 \([a-z]+; MSIE ([0-9]+\.[0-9]+); ([a-zA-Z0-9\.\- ]+)/', $ua, $z)) {
			$o['platform'] = $z[2];
			$o['name'] = 'Internet Explorer';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 3;
			$o['MSIE_DETECTED'] = 1;
			return $o;
		}

		/* Chimera
		 * Example: Chimera/2.0alpha */
		if (preg_match('/^Chimera\/([0-9a-zA-Z\.]*)/', $ua, $z)) {
			$o['platform'] = 'Unix';
			$o['name'] = 'Chimera';
			$o['version'] = $z[1];
			$o['DEVICE_LEVEL'] = 3;
			return $o;
		}

		/* Mozilla Camino | Firefox | Firebird | Thunderbird | SeaMonkey | Sunbird | Epiphany
		 * Camino Example: Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.8b4) Gecko/20050914 Camino/1.0a1
		 * Firefox Example: Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.8b4) Gecko/20050908 Firefox/1.4 
		 * Firefox Example: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.7.12) Gecko/20050922 Firefox/1.0.7 (Debian package 1.0.7-1) */
		if (preg_match('/Mozilla\/5\.0 \(([a-zA-Z0-9]+); U; ([0-9a-zA-Z\.\- ]+); [a-zA-Z\- ]*; rv:([0-9a-z\.]+)\) Gecko\/([0-9]+) (Camino|Firefox|Firebird|SeaMonkey|Thunderbird|Sunbird|Epiphany)\/([0-9]+\.[0-9a-zA-Z\.]*)/', $ua, $z)) {
			if ($z[1] == 'Windows' | preg_match('/X11/', $z[1])) {
				$o['platform'] = $z[2];
				if (preg_match('/(Linux)/', $o['platform'], $y)) {
					$o['platform'] = $y[1];
				}
			} else {
				$o['platform'] = $z[1];
			}
			$o['name'] = $z[5];
			$o['version'] = $z[6];
			$o['DEVICE_LEVEL'] = 3;
			$o['GECKO_DETECTED'] = 1;
			return $o;
		}

		/* Mozilla Suite
		 * Example: Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.7.12) Gecko/20050915 */
		if (preg_match('/Mozilla\/5\.0 \(([a-zA-Z0-9]+); U; ([0-9a-zA-Z\.\- ]*); [a-zA-Z\- ]*; rv:([0-9a-z\.]+)\) Gecko\/([0-9]+)/', $ua, $z)) {
			if ($z[1] == 'Windows' | preg_match('/X11/', $z[1])) {
				$o['platform'] = $z[2];
				if (preg_match('/(Linux)/', $o['platform'], $y)) {
					$o['platform'] = $y[1];
				}
			} else {
				$o['platform'] = $z[1];
			}
			$o['name'] = 'Mozilla';
			$o['version'] = $z[3];
			$o['DEVICE_LEVEL'] = 3;
			$o['GECKO_DETECTED'] = 1;
			return $o;
		}

		/* Unknown Vendor Unknown Browser */
		if ($o['name'] == '') {
			$o['platform'] = 'Unknown Platform';
			$o['name'] = 'Unknown Browser';
			$o['version'] = 'Unknown Version';
			$o['DEVICE_LEVEL'] = 0;
			return $o;
		}
	}
	
	/* E module: User Agent Check logic */
	
	/* S module: User Create Check logic */
	
	public function vxUserCreateCheck() {
		$rt = array();
		
		$rt['errors'] = 0;
		
		$rt['usr_email_value'] = '';
		/* usr_email_error:
		0 => no error
		1 => empty
		2 => overflow (100 sbs)
		3 => mismatch
		4 => conflict
		999 => unspeicific */
		$rt['usr_email_error'] = 0;
		$rt['usr_email_error_msg'] = array(1 => '你忘记填写电子邮件地址了', 2 => '你的电子邮件地址太长了', 3 => '你的电子邮件地址看起来有问题', 4 => '这个电子邮件地址已经注册过了');
		
		$rt['usr_nick_value'] = '';
		/* usr_nick_error:
		0 => no error
		1 => empty
		2 => overflow (20 mbs)
		3 => invalid characters
		4 => conflict
		999 => unspecific */
		$rt['usr_nick_error'] = 0;
		$rt['usr_nick_error_msg'] = array(1 => '你忘记填写昵称了', 2 => '你的昵称太长了，精简一下吧', 3 => '你的昵称中包含了不被允许的字符', 4 => '你填写的这个昵称被别人用了');
		
		$rt['usr_password_value'] = '';
		$rt['usr_confirm_value'] = '';
		/* usr_password_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters
		4 => not identical
		999 => unspecific */
		$rt['usr_password_error'] = 0;
		$rt['usr_password_error_msg'] = array(1 => '你忘记填写密码了', 2 => '你的这个密码太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配');
		/* usr_confirm_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters(should not reach here in final rendering)
		4 => not identical
		999 => unspecific */
		$rt['usr_confirm_error'] = 0;
		$rt['usr_confirm_error_msg'] = array(1 => '你忘记填写密码确认了', 2 => '你的这个密码确认太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配');
		
		$rt['c_value'] = 0;
		$rt['c_error'] = 0;
		$rt['c_error_msg'] = array(1 => '你忘记填写确认码了', 4 => '你填写的确认码是错的');
		
		/* check: c */
		if (isset($_POST['c'])) {
			$rt['c_value'] = strtolower(trim($_POST['c']));
			if (strlen($rt['c_value']) > 0) {
				if ($rt['c_value'] != strtolower($_SESSION['c'])) {
					$rt['c_error'] = 4;
					$rt['errors']++;
				}
			} else {
				$rt['c_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['c_error'] = 1;
			$rt['errors']++;
			
		}
		
		/* check: usr_email */
		
		if (isset($_POST['usr_email'])) {
			$rt['usr_email_value'] = strtolower(make_single_safe($_POST['usr_email']));
			if (strlen($rt['usr_email_value']) == 0) {
				$rt['usr_email_error'] = 1;
				$rt['errors']++;
			} else {
				if (strlen($rt['usr_email_value']) > 100) {
					$rt['usr_email_error'] = 2;
					$rt['errors']++;
				} else {
					if (!is_valid_email($rt['usr_email_value'])) {
						$rt['usr_email_error'] = 3;
						$rt['errors']++;
					}
				}
			}
		} else {
			$rt['usr_email_error'] = 1;
			$rt['errors']++;
		}
		
		
		if ($rt['usr_email_error'] == 0) {
			$sql = "SELECT usr_email FROM babel_user WHERE usr_email = '" . $rt['usr_email_value'] . "'";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) > 0) {
				$rt['usr_email_error'] = 4;
				$rt['errors']++;
			}
			mysql_free_result($rs);
		}
		
		/* check: usr_nick */
		
		if (isset($_POST['usr_nick'])) {
			$rt['usr_nick_value'] = make_single_safe($_POST['usr_nick']);
			if (strlen($rt['usr_nick_value']) == 0) {
				$rt['usr_nick_error'] = 1;
				$rt['errors']++;
			} else {
				if (mb_strlen($rt['usr_nick_value']) > 20) {
					$rt['usr_nick_error'] = 2;
					$rt['errors']++;
				} else {
					if (!is_valid_nick($rt['usr_nick_value'])) {
						$rt['usr_nick_error'] = 3;
						$rt['errors']++;
					}
				}
			}
		} else {
			$rt['usr_nick_error'] = 1;
			$rt['errors']++;
		}
		
		if ($rt['usr_nick_error'] == 0) {
			$sql = "SELECT usr_nick FROM babel_user WHERE usr_nick = '" . $rt['usr_nick_value'] . "'";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) > 0) {
				$rt['usr_nick_error'] = 4;
				$rt['errors']++;
			}
			mysql_free_result($rs);
		}
		
		/* check: usr_gender */
		if (isset($_POST['usr_gender'])) {
			$rt['usr_gender_value'] = intval($_POST['usr_gender']);
			if (!in_array($rt['usr_gender_value'], array(0,1,2,5,6,9))) {
				$rt['usr_gender_value'] = 9;
			}
		} else {
			$rt['usr_gender_value'] = 9;
		}
		
		/* check: usr_password and usr_confirm */
		
		if (isset($_POST['usr_password'])) {
			$rt['usr_password_value'] = $_POST['usr_password'];
			if (strlen($rt['usr_password_value']) == 0) {
				$rt['usr_password_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['usr_password_error'] = 1;
			$rt['errors']++;
		}
		
		if (isset($_POST['usr_confirm'])) {
			$rt['usr_confirm_value'] = $_POST['usr_confirm'];
			if (strlen($rt['usr_confirm_value']) == 0) {
				$rt['usr_confirm_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['usr_confirm_error'] = 1;
			$rt['errors']++;
		}
		
		if (($rt['usr_password_error'] == 0) && ($rt['usr_confirm_error'] == 0)) {
			if (strlen($rt['usr_password_value']) > 32) {
				$rt['usr_password_error'] = 2;
				$rt['errors']++;
			}
			if (strlen($rt['usr_confirm_value']) > 32) {
				$rt['usr_confirm_error'] = 2;
				$rt['errors']++;
			}
		}
		
		if (($rt['usr_password_error'] == 0) && ($rt['usr_confirm_error'] == 0)) {
			if ($rt['usr_password_value'] != $rt['usr_confirm_value']) {
				$rt['usr_confirm_error'] = 4;
				$rt['errors']++;
			}
		}
		
		return $rt;
	}
	
	/* E module: User Create Check logic */
	
	/* S module: User Create Insert logic */
	
	public function vxUserCreateInsert($usr_nick, $usr_password, $usr_email, $usr_gender) {
		if (get_magic_quotes_gpc()) {
			$usr_nick = stripslashes($usr_nick);
			$usr_nick = mysql_real_escape_string($usr_nick);
			
			$usr_password = stripslashes($usr_password);
			$usr_password = mysql_real_escape_string($usr_password);
		} else {
			$usr_nick = mysql_real_escape_string($usr_nick);
			$usr_password = mysql_real_escape_string($usr_password);
		}
		$usr_password_encrypted = sha1($usr_password);
		$usr_created = time();
		
		/* insert new user */
		$sql = "INSERT INTO babel_user(usr_nick, usr_password, usr_email, usr_gender, usr_created, usr_lastupdated) VALUES('{$usr_nick}', '{$usr_password_encrypted}', '{$usr_email}', {$usr_gender}, {$usr_created}, {$usr_created})";
		mysql_query($sql, $this->db);
		
		$sql = "SELECT usr_id, usr_nick, usr_password, usr_email, usr_money FROM babel_user WHERE usr_email = '{$usr_email}'";
		$User = mysql_fetch_object(mysql_query($sql, $this->db));
		
		$grp_created = time();
		$sql = "INSERT INTO babel_group(grp_oid, grp_nick, grp_created, grp_lastupdated) VALUES({$User->usr_id}, '{$User->usr_nick}', {$grp_created}, {$grp_created})";
		mysql_query($sql, $this->db);
		
		$sql = "SELECT grp_id, grp_nick FROM babel_group WHERE grp_nick = '{$User->usr_nick}'";
		$Group = mysql_fetch_object(mysql_query($sql, $this->db));
		
		$sql = "UPDATE babel_user SET usr_gid = {$Group->grp_id} WHERE usr_id = {$User->usr_id} LIMIT 1";
		mysql_query($sql, $this->db);
		
		return $User;
	}
	
	/* E module: User Create Insert logic */
	
	/* S module: User Password Update Check logic */
	
	public function vxUserPasswordUpdateCheck() {
		$rt = array();
		
		$rt['errors'] = 0;
		
		$rt['pswitch'] = 'a';
		
		$rt['usr_password_value'] = '';
		$rt['usr_confirm_value'] = '';
		/* usr_password_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters
		4 => not identical
		5 => modify empty
		999 => unspecific */
		$rt['usr_password_error'] = 0;
		$rt['usr_password_touched'] = 0;
		$rt['usr_password_error_msg'] = array(1 => '你忘记填写密码了', 2 => '你的这个密码太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配', 5 => '你修改密码时需要将新密码输入两遍');
		/* usr_confirm_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters(should not reach here in final rendering)
		4 => not identical
		5 => modify empty
		999 => unspecific */
		$rt['usr_confirm_error'] = 0;
		$rt['usr_confirm_touched'] = 0;
		$rt['usr_confirm_error_msg'] = array(1 => '你忘记填写密码确认了', 2 => '你的这个密码确认太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配', 5 => '你修改密码时需要将新密码输入两遍');

		/* S check: usr_password and usr_confirm */
		
		if (isset($_POST['usr_password'])) {
			$rt['usr_password_value'] = $_POST['usr_password'];
			if (strlen($rt['usr_password_value']) == 0) {
				$rt['usr_password_touched'] = 0;
				$rt['usr_password_error'] = 1;
				$rt['errors']++;
			} else {
				$rt['usr_password_touched'] = 1;
			}
		} else {
			$rt['usr_password_touched'] = 0;
			$rt['usr_password_error'] = 1;
			$rt['errors']++;
		}
		
		if (isset($_POST['usr_confirm'])) {
			$rt['usr_confirm_value'] = $_POST['usr_confirm'];
			if (strlen($rt['usr_confirm_value']) == 0) {
				$rt['usr_confirm_touched'] = 0;
				$rt['usr_confirm_error'] = 1;
				$rt['errors']++;
			} else {
				$rt['usr_confirm_touched'] = 1;
			}
		} else {
			$rt['usr_confirm_touched'] = 0;
			$rt['usr_confirm_error'] = 1;
			$rt['errors']++;
		}
		
		if (($rt['usr_password_touched'] == 0) && ($rt['usr_confirm_touched'] == 0)) {
			$rt['pswitch'] = 'a'; /* both blank */
		}
		
		if (($rt['usr_password_touched'] == 1) && ($rt['usr_confirm_touched'] == 1)) {
			$rt['pswitch'] = 'b'; /* both touched */
		}
		
		if (($rt['usr_password_touched'] == 1) && ($rt['usr_confirm_touched'] == 0)) {
			$rt['pswitch'] = 'c'; /* first touched */
		}
			
		if (($rt['usr_password_touched'] == 0) && ($rt['usr_confirm_touched'] == 1)) {
			$rt['pswitch'] = 'd'; /* second touched */
		}
		
		switch ($rt['pswitch']) {
			default:
			case 'a':
				/* nothing will happen */
				break;
			case 'b':
				/* a lot check here */
				if (strlen($rt['usr_password_value']) > 32) {
					$rt['usr_password_error'] = 2;
					$rt['errors']++;
				}
			
				if (strlen($rt['usr_confirm_value']) > 32) {
					$rt['usr_confirm_error'] = 2;
					$rt['errors']++;
				}
				
				if (($rt['usr_password_error'] == 0) && ($rt['usr_confirm_error'] == 0)) {
					if ($rt['usr_password_value'] != $rt['usr_confirm_value']) {
						$rt['usr_confirm_error'] = 4;
						$rt['errors']++;
					}
				}
				break;
			case 'c':
				$rt['usr_confirm_error'] = 5;
				$rt['errors']++;
				break;
			case 'd':
				$rt['usr_password_error'] = 5;
				$rt['errors']++;
				break;
		}
		
		return $rt;
	}
	
	/* E module: User Password Update Check logic */
	
	/* S module: User Password Update Update logic */
	
	public function vxUserPasswordUpdateUpdate($usr_id, $usr_password) {
		$sql = "DELETE FROM babel_passwd WHERE pwd_uid = {$usr_id}";
		mysql_query($sql, $this->db);
		
		$sql = "UPDATE babel_user SET usr_password = '{$usr_password}' WHERE usr_id = {$usr_id} LIMIT 1";
		mysql_query($sql, $this->db);
		
		if (mysql_affected_rows($this->db) == 1) {	
			return true;
		} else {
			return true;
		}
	}
	
	/* E module: User Password Update Update logic */
	
	/* S module: User Update Check logic */
	
	public function vxUserUpdateCheck() {
		$rt = array();
		
		$rt['errors'] = 0;
		
		$rt['usr_nick_value'] = '';
		/* usr_nick_error:
		0 => no error
		1 => empty
		2 => overflow (20 mbs)
		3 => invalid characters
		4 => conflict
		999 => unspecific */
		$rt['usr_nick_error'] = 0;
		$rt['usr_nick_error_msg'] = array(1 => '你忘记填写昵称了', 2 => '你的昵称太长了，精简一下吧', 3 => '你填写的昵称中包含了不被允许的字符', 4 => '你填写的这个昵称被别人用了');

		$rt['usr_full_value'] = '';
		/* usr_full_error:
		0 => no error
		1 => empty
		2 => overflow (30 mbs)
		999 => unspecific */
		$rt['usr_full_error'] = 0;
		$rt['usr_full_error_msg'] = array(2 => '你的真实姓名长度超过了系统限制');
		
		$rt['usr_brief_value'] = '';
		/* usr_brief_error:
		0 => no error
		2 => overflow (100 mbs)
		*/
		$rt['usr_brief_error'] = 0;
		$rt['usr_brief_error_msg'] = array(2 => '你的自我简介太长了，精简一下吧');
		
		$rt['usr_gender_value'] = 9;
		
		$rt['usr_addr_value'] = '';
		/* usr_addr_error:
		0 => no error
		2 => overflow (100 mbs)
		*/
		$rt['usr_addr_error'] = 0;
		$rt['usr_addr_error_msg'] = array(2 => '你的家庭住址长度超过了系统限制');
		
		$rt['usr_telephone_value'] = '';
		/* usr_addr_error:
		0 => no error
		2 => overflow (40 mbs)
		*/
		$rt['usr_telephone_error'] = 0;
		$rt['usr_telephone_error_msg'] = array(2 => '你的电话号码长度超过了系统限制');
		
		$rt['usr_identity_value'] = '';
		/* usr_identity_error:
		0 => no error
		3 => invalid
		*/
		$rt['usr_identity_error'] = 0;
		$rt['usr_identity_error_msg'] = array(3 => '身份证号码无效');
		
		$rt['pswitch'] = 'a';
		
		$rt['usr_password_value'] = '';
		$rt['usr_confirm_value'] = '';
		/* usr_password_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters
		4 => not identical
		5 => modify empty
		999 => unspecific */
		$rt['usr_password_error'] = 0;
		$rt['usr_password_touched'] = 0;
		$rt['usr_password_error_msg'] = array(1 => '你忘记填写密码了', 2 => '你的这个密码太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配', 5 => '你修改密码时需要将新密码输入两遍');
		/* usr_confirm_error:
		0 => no error
		1 => empty
		2 => overflow (32 sbs)
		3 => invalid characters(should not reach here in final rendering)
		4 => not identical
		5 => modify empty
		999 => unspecific */
		$rt['usr_confirm_error'] = 0;
		$rt['usr_confirm_touched'] = 0;
		$rt['usr_confirm_error_msg'] = array(1 => '你忘记填写密码确认了', 2 => '你的这个密码确认太长了，缩减一下吧', 3 => '你填写的密码中包含了不被允许的字符', 4 => '你所填写的两个密码不匹配', 5 => '你修改密码时需要将新密码输入两遍');
		
		/* S check: usr_width */
		
		$rt['usr_width_value'] = 0;
		$x = simplexml_load_file(BABEL_PREFIX . '/res/valid_width.xml');
		$w = $x->xpath('/array/width');
		$ws = array();
		while(list( , $width) = each($w)) {
			$ws[] = strval($width);
		}
		$rt['usr_width_array'] = $ws;
		if (isset($_POST['usr_width'])) {
			$rt['usr_width_value'] = intval($_POST['usr_width']);
			if (!in_array($rt['usr_width_value'], $ws)) {
				$rt['usr_width_value'] = 800;
			}
		} else {
			$rt['usr_width_value'] = 800;
		}
		
		/* E check: usr_width */
		
		/* S check: usr_nick */
		
		if (isset($_POST['usr_nick'])) {
			$rt['usr_nick_value'] = make_single_safe($_POST['usr_nick']);
			if (strlen($rt['usr_nick_value']) == 0) {
				$rt['usr_nick_error'] = 1;
				$rt['errors']++;
			} else {
				if (mb_strlen($rt['usr_nick_value'], 'UTF-8') > 20) {
					$rt['usr_nick_error'] = 2;
					$rt['errors']++;
				} else {
					if (!is_valid_nick($rt['usr_nick_value'])) {
						$rt['usr_nick_error'] = 3;
						$rt['errors']++;
					}
				}
			}
		} else {
			$rt['usr_nick_error'] = 1;
			$rt['errors']++;
		}
		
		if ($rt['usr_nick_error'] == 0) {
			$sql = "SELECT usr_nick FROM babel_user WHERE usr_nick = '" . $rt['usr_nick_value'] . "' AND usr_id != " . $this->User->usr_id;
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) > 0) {
				$rt['usr_nick_error'] = 4;
				$rt['errors']++;
			}
			mysql_free_result($rs);
		}
		
		/* E check: usr_nick */

		/* S check: usr_full */
		
		if (isset($_POST['usr_full'])) {
			$rt['usr_full_value'] = make_single_safe($_POST['usr_full']);
			if (mb_strlen($rt['usr_full_value'], 'UTF-8') > 30) {
				$rt['usr_full_error'] = 2;
				$rt['errors']++;
			}
		}
		
		/* E check: usr_full */
		
		/* S check: usr_gender */
		
		if (isset($_POST['usr_gender'])) {
			$rt['usr_gender_value'] = intval($_POST['usr_gender']);
			if (!in_array($rt['usr_gender_value'], array(0,1,2,5,6,9))) {
				$rt['usr_gender_value'] = 9;
			}
		} else {
			$rt['usr_gender_value'] = 9;
		}
		
		/* E check: usr_gender */
		
		/* S check: usr_addr */
		
		if (isset($_POST['usr_addr'])) {
			$rt['usr_addr_value'] = make_single_safe($_POST['usr_addr']);
			if (mb_strlen($rt['usr_addr_value'], 'UTF-8') > 100) {
				$rt['usr_addr_error'] = 2;
				$rt['errors']++;
			}
		}
		
		/* E check: usr_addr */
		
		/* S check: usr_telephone */
		
		if (isset($_POST['usr_telephone'])) {
			$rt['usr_telephone_value'] = make_single_safe($_POST['usr_telephone']);
			if (mb_strlen($rt['usr_telephone_value'], 'UTF-8') > 40) {
				$rt['usr_telephone_error'] = 2;
				$rt['errors']++;
			}
		}
		
		/* E check: usr_telephone */
		
		/* S check: usr_identity */
		
		if (isset($_POST['usr_identity'])) {
			$rt['usr_identity_value'] = make_single_safe($_POST['usr_identity']);
			if (mb_strlen($rt['usr_identity_value'], 'UTF-8') > 0) {
				if (in_array(mb_strlen($rt['usr_identity_value'], 'UTF-8'), array(15, 18))) {
					if (!preg_match('/[a-zA-Z0-9]+/', $rt['usr_identity_value'])) {
						$rt['usr_identity_error'] = 3;
						$rt['errors']++;
					}
				}
			}
		}
		
		/* E check: usr_identity */
		
		/* S check: usr_brief */
		
		if (isset($_POST['usr_brief'])) {
			$rt['usr_brief_value'] = make_single_safe($_POST['usr_brief']);
			if (mb_strlen($rt['usr_brief_value'], 'UTF-8') > 0) {
				if (mb_strlen($rt['usr_brief_value'], 'UTF-8') > 100) {
					$rt['usr_brief_error'] = 2;
					$rt['errors']++;
				}
			}
		}
		
		/* E check: usr_brief */
		
		/* S check: usr_password and usr_confirm */
		
		if (isset($_POST['usr_password'])) {
			$rt['usr_password_value'] = $_POST['usr_password'];
			if (strlen($rt['usr_password_value']) == 0) {
				$rt['usr_password_touched'] = 0;
			} else {
				$rt['usr_password_touched'] = 1;
			}
		} else {
			$rt['usr_password_touched'] = 0;
		}
		
		if (isset($_POST['usr_confirm'])) {
			$rt['usr_confirm_value'] = $_POST['usr_confirm'];
			if (strlen($rt['usr_confirm_value']) == 0) {
				$rt['usr_confirm_touched'] = 0;
			} else {
				$rt['usr_confirm_touched'] = 1;
			}
		} else {
			$rt['usr_confirm_touched'] = 0;
		}
		
		if (($rt['usr_password_touched'] == 0) && ($rt['usr_confirm_touched'] == 0)) {
			$rt['pswitch'] = 'a';
		}
		
		if (($rt['usr_password_touched'] == 1) && ($rt['usr_confirm_touched'] == 1)) {
			$rt['pswitch'] = 'b';
		}
		
		if (($rt['usr_password_touched'] == 1) && ($rt['usr_confirm_touched'] == 0)) {
			$rt['pswitch'] = 'c';
		}
			
		if (($rt['usr_password_touched'] == 0) && ($rt['usr_confirm_touched'] == 1)) {
			$rt['pswitch'] = 'd';
		}
		
		switch ($rt['pswitch']) {
			default:
			case 'a':
				/* nothing will happen */
				break;
			case 'b':
				/* a lot check here */
				if (strlen($rt['usr_password_value']) > 32) {
					$rt['usr_password_error'] = 2;
					$rt['errors']++;
				}
			
				if (strlen($rt['usr_confirm_value']) > 32) {
					$rt['usr_confirm_error'] = 2;
					$rt['errors']++;
				}
				
				if (($rt['usr_password_error'] == 0) && ($rt['usr_confirm_error'] == 0)) {
					if ($rt['usr_password_value'] != $rt['usr_confirm_value']) {
						$rt['usr_confirm_error'] = 4;
						$rt['errors']++;
					}
				}
				break;
			case 'c':
				$rt['usr_confirm_error'] = 5;
				$rt['errors']++;
				break;
			case 'd':
				$rt['usr_password_error'] = 5;
				$rt['errors']++;
				break;
		}
		
		return $rt;
	}
	
	/* E module: User Update Check logic */
	
	/* S module: User Update Update logic */
	
	public function vxUserUpdateUpdate($usr_full, $usr_nick, $usr_brief, $usr_gender, $usr_addr, $usr_telephone, $usr_identity, $usr_width = 800, $usr_password = '') {
		$usr_id = $this->User->usr_id;
		
		if (get_magic_quotes_gpc()) {
			$usr_nick = stripslashes($usr_nick);
			$usr_nick = mysql_real_escape_string($usr_nick);
			
			if (strlen($usr_password) > 0) {
				$usr_password = stripslashes($usr_password);
				$usr_password = mysql_real_escape_string($usr_password);
			}
			
			$usr_full = stripslashes($usr_full);
			$usr_full = mysql_real_escape_string($usr_full);
			
			$usr_brief = stripslashes($usr_brief);
			$usr_brief = mysql_real_escape_string($usr_brief);
			
			$usr_addr = stripslashes($usr_addr);
			$usr_addr = mysql_real_escape_string($usr_addr);
			
			$usr_telephone = stripslashes($usr_telephone);
			$usr_telephone = mysql_real_escape_string($usr_telephone);
		} else {
			$usr_nick = mysql_real_escape_string($usr_nick);
			
			if (strlen($usr_password) > 0) {
				$usr_password = mysql_real_escape_string($usr_password);
			}
			
			$usr_full = mysql_real_escape_string($usr_full);
			
			$usr_brief = mysql_real_escape_string($usr_brief);
			
			$usr_addr = mysql_real_escape_string($usr_addr);
			
			$usr_telephone = mysql_real_escape_string($usr_telephone);
		}
		
		$usr_identity = mysql_real_escape_string($usr_identity);
		
		if (strlen($usr_password) > 0) {
			$usr_password = sha1($usr_password);
		}
		$usr_lastupdated = time();
		
		if (strlen($usr_password) > 0) {
			$sql = "UPDATE babel_user SET usr_full = '{$usr_full}', usr_nick = '{$usr_nick}', usr_brief = '{$usr_brief}', usr_gender = '{$usr_gender}', usr_addr = '{$usr_addr}', usr_telephone = '{$usr_telephone}', usr_identity = '{$usr_identity}', usr_width = {$usr_width}, usr_password = '{$usr_password}', usr_lastupdated = {$usr_lastupdated} WHERE usr_id = {$usr_id} LIMIT 1";
		} else {
			$sql = "UPDATE babel_user SET usr_full = '{$usr_full}', usr_nick = '{$usr_nick}', usr_brief = '{$usr_brief}', usr_gender = '{$usr_gender}', usr_addr = '{$usr_addr}', usr_telephone = '{$usr_telephone}', usr_identity = '{$usr_identity}', usr_width = {$usr_width}, usr_lastupdated = '{$usr_lastupdated}' WHERE usr_id = {$usr_id} LIMIT 1";
		}
		
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db)) {
			$sql = "UPDATE babel_group SET grp_nick = '{$usr_nick}', grp_lastupdated = {$usr_lastupdated} WHERE grp_oid = {$usr_id} LIMIT 1";
			mysql_query($sql, $this->db);
			if (mysql_affected_rows($this->db)) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	
	/* E module: User Update Update logic */
	
	/* S module: Topic Create Check logic */
	
	public function vxTopicCreateCheck($options, $User) {
		$rt = array();
		
		$rt['out_of_money'] = false;
		
		switch ($options['mode']) {
			case 'board':
				$rt['mode'] = 'board';
				
				$board_id = $rt['board_id'] = $options['board_id'];
				
				$Node = new Node($rt['board_id'], $this->db);
				$Section = new Node($Node->nod_pid, $this->db);
				break;

			case 'section':
				$rt['mode'] = 'section';
				
				$section_id = $rt['section_id'] = $options['section_id'];
				
				$Section = new Node($rt['section_id'], $this->db);
				break;
		}
		
		$rt['exp_amount'] = 0;
		$rt['errors'] = 0;
		
		$rt['tpc_title_value'] = '';
		/* tpc_title_error:
		0 => no error
		1 => empty
		2 => overflow
		3 => invalid characters
		999 => unspecific */
		$rt['tpc_title_error'] = 0;
		$rt['tpc_title_error_msg'] = array(1 => '你忘记写标题了', 2 => '你的这个标题太长了', 3 => '你的标题中含有不被允许的字符');
		
		$rt['tpc_pid_value'] = 0;
		$rt['tpc_pid_error'] = 0;
		$rt['tpc_pid_error_msg'] = array(1 => '请选择一个讨论区');
		
		$rt['tpc_description_value'] = '';
		/* tpc_description_error:
		0 => no error
		2 => overflow
		999 => unspecific */
		$rt['tpc_description_error'] = 0;
		$rt['tpc_description_error_msg'] = array(2 => '你的这个描述太长了');
		
		$rt['tpc_content_value'] = '';
		/* tpc_content_error:
		0 => no error
		1 => empty
		2 => overflow
		999 => unspecific */
		$rt['tpc_content_length'] = 0;
		$rt['tpc_content_error'] = 0;
		$rt['tpc_content_error_msg'] = array(1 => '你忘记写内容了', 2 => '你的这篇主题的内容太长了');
		
		if (isset($_POST['tpc_title'])) {
			$rt['tpc_title_value'] = make_single_safe($_POST['tpc_title']);
			if (strlen($rt['tpc_title_value']) > 0) {
				if (mb_strlen($rt['tpc_title_value'], 'utf-8') > 50) {
					$rt['tpc_title_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['tpc_title_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_title_error'] = 1;
			$rt['errors']++;
		}
		
		
		if ($rt['mode'] == 'section') {
			if (isset($_POST['tpc_pid'])) {
				$rt['tpc_pid_value'] = intval($_POST['tpc_pid']);
				$sql = "SELECT nod_id FROM babel_node WHERE nod_pid = {$rt['section_id']} AND nod_id = {$rt['tpc_pid_value']}";
				$rs = mysql_query($sql, $this->db);
				if (mysql_num_rows($rs) != 1) {
					$rt['tpc_pid_error'] = 1;
					$rt['errors']++;
				}
				mysql_free_result($rs);
			} else {
				$rt['tpc_pid_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_pid_value'] = $rt['board_id'];
		}
		
		
		if (isset($_POST['tpc_description'])) {
			$rt['tpc_description_value'] = make_multi_safe($_POST['tpc_description']);
			if (strlen($rt['tpc_description_value']) > 1000) {
				$rt['tpc_description_error'] = 2;
				$rt['errors']++;
			}
		}
		
		if (isset($_POST['tpc_content'])) {
			$rt['tpc_content_value'] = make_multi_safe($_POST['tpc_content']);
			$rt['tpc_content_length'] = mb_strlen($rt['tpc_content_value'], 'UTF-8');
			if ($rt['tpc_content_length'] > 0) {
				if ($rt['tpc_content_length'] > 10240) {
					$rt['tpc_content_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['tpc_content_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_content_error'] = 1;
			$rt['errors']++;
		}
		
		if ($rt['tpc_content_error'] == 0) {
			$tpc_content_length  = mb_strlen($rt['tpc_content_value'], 'utf-8');
			if ($tpc_content_length > 500) {
				$rt['exp_amount'] = -(intval(($tpc_content_length / 500) * (BABEL_TPC_PRICE)));
			} else {
				$rt['exp_amount'] = -(BABEL_TPC_PRICE);
			}
		} else {
			$rt['exp_amount'] = -(BABEL_TPC_PRICE);
		}
		
		if ((abs($rt['exp_amount']) * 1.2) > $User->usr_money) {
			$rt['errors']++;
			$rt['out_of_money'] = true; 
		}
		
		return $rt;
	}
	
	/* E module: Topic Create Check logic */
	
	/* S module: API Topic Create Check logic */
	
	public function vxAPITopicCreateCheck($tpc_title, $tpc_content, $tpc_description = '') {
		$rt = array();
		
		$rt['errors'] = 0;
		
		$rt['tpc_title_value'] = '';
		/* tpc_title_error:
		0 => no error
		1 => empty
		2 => overflow
		3 => invalid characters
		999 => unspecific */
		$rt['tpc_title_error'] = 0;
		$rt['tpc_title_error_msg'] = array(1 => '你忘记写标题了', 2 => '你的这个标题太长了', 3 => '你的标题中含有不被允许的字符');
		
		$rt['tpc_description_value'] = '';
		/* tpc_description_error:
		0 => no error
		2 => overflow
		999 => unspecific */
		$rt['tpc_description_error'] = 0;
		$rt['tpc_description_error_msg'] = array(2 => '你的这个描述太长了');
		
		$rt['tpc_content_value'] = '';
		/* tpc_content_error:
		0 => no error
		1 => empty
		2 => overflow
		999 => unspecific */
		$rt['tpc_content_length'] = 0;
		$rt['tpc_content_error'] = 0;
		$rt['tpc_content_error_msg'] = array(1 => '你忘记写内容了', 2 => '你的这篇主题的内容太长了');
		
		$rt['tpc_title_value'] = $tpc_title;
		if (strlen($rt['tpc_title_value']) > 0) {
			if (mb_strlen($rt['tpc_title_value'], 'utf-8') > 50) {
				$rt['tpc_title_error'] = 2;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_title_error'] = 1;
			$rt['errors']++;
		}
	
		$rt['tpc_description_value'] = $tpc_description;
		if (strlen($rt['tpc_description_value']) > 1000) {
			$rt['tpc_description_error'] = 2;
			$rt['errors']++;
		}
	
		$rt['tpc_content_value'] = $tpc_content;
		$rt['tpc_content_length'] = mb_strlen($rt['tpc_content_value'], 'UTF-8');
		if ($rt['tpc_content_length'] > 0) {
			if ($rt['tpc_content_length'] > 10240) {
				$rt['tpc_content_error'] = 2;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_content_error'] = 1;
			$rt['errors']++;
		}
		
		return $rt;
	}
	
	/* E module: Topic Create API Check logic */

	/* S module: Topic Create Insert logic */
	
	public function vxTopicCreateInsert($board_id, $user_id, $tpc_title, $tpc_description, $tpc_content, $expense_amount) {
		if (get_magic_quotes_gpc()) {
			$tpc_title = stripslashes($tpc_title);
			$tpc_title = mysql_real_escape_string($tpc_title);
			
			$tpc_description = stripslashes($tpc_description);
			$tpc_description = mysql_real_escape_string($tpc_description);
			
			$tpc_content = stripslashes($tpc_content);
			$tpc_content = mysql_real_escape_string($tpc_content);
		} else {
			$tpc_title = mysql_real_escape_string($tpc_title);
			$tpc_description = mysql_real_escape_string($tpc_description);
			$tpc_content = mysql_real_escape_string($tpc_content);
		}
		$sql = "INSERT INTO babel_topic(tpc_pid, tpc_uid, tpc_title, tpc_description, tpc_content, tpc_created, tpc_lastupdated, tpc_lasttouched) VALUES({$board_id}, {$user_id}, '{$tpc_title}', '{$tpc_description}', '{$tpc_content}', " . time() . ", " . time() . ', ' . time() . ')';
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			return $this->User->vxPay($this->User->usr_id, $expense_amount, 2);
		} else {
			return false;
		}
	}
	
	/* E module: Topic Create Insert logic */
	
	/* S module: Topic Update Check logic */
	
	public function vxTopicUpdateCheck($topic_id, $User) {
		$rt = array();
		
		$rt['out_of_money'] = false;
		
		$rt['topic_id'] = $topic_id;
		$rt['exp_amount'] = 0;
		$rt['errors'] = 0;
		
		$rt['permit'] = 1;
		
		$rt['tpc_title_value'] = '';
		/* tpc_title_error:
		0 => no error
		1 => empty
		2 => overflow
		3 => invalid characters
		999 => unspecific */
		$rt['tpc_title_error'] = 0;
		$rt['tpc_title_error_msg'] = array(1 => '你忘记写标题了', 2 => '你的这个标题太长了', 3 => '你的标题中含有不被允许的字符');
		
		$rt['tpc_description_value'] = '';
		/* tpc_description_error:
		0 => no error
		2 => overflow
		999 => unspecific */
		$rt['tpc_description_error'] = 0;
		$rt['tpc_description_error_msg'] = array(2 => '你的这个描述太长了');
		
		$rt['tpc_content_value'] = '';
		/* tpc_content_error:
		0 => no error
		1 => empty
		2 => overflow
		999 => unspecific */
		$rt['tpc_content_length'] = 0;
		$rt['tpc_content_error'] = 0;
		$rt['tpc_content_error_msg'] = array(1 => '你忘记写内容了', 2 => '你的这篇主题的内容太长了');
		
		if (isset($_POST['tpc_title'])) {
			$rt['tpc_title_value'] = make_single_safe($_POST['tpc_title']);
			if (strlen($rt['tpc_title_value']) > 0) {
				if (mb_strlen($rt['tpc_title_value'], 'UTF-8') > 50) {
					$rt['tpc_title_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['tpc_title_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_title_error'] = 1;
			$rt['errors']++;
		}
		
		if (isset($_POST['tpc_description'])) {
			$rt['tpc_description_value'] = make_multi_safe($_POST['tpc_description']);
			if (strlen($rt['tpc_description_value']) > 1000) {
				$rt['tpc_description_error'] = 2;
				$rt['errors']++;
			}
		}
		
		if (isset($_POST['tpc_content'])) {
			$rt['tpc_content_value'] = make_multi_safe($_POST['tpc_content']);
			$rt['tpc_content_length'] = mb_strlen($rt['tpc_content_value'], 'UTF-8');
			if ($rt['tpc_content_length'] > 0) {
				if ($rt['tpc_content_length'] > 10240) {
					$rt['tpc_content_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['tpc_content_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['tpc_content_error'] = 1;
			$rt['errors']++;
		}
		
		$rt['exp_amount'] = -(BABEL_TPC_UPDATE_PRICE);
		
		$Topic = new Topic($rt['topic_id'], $this->db);
		if ($this->User->usr_id != 1) {
			if ($Topic->tpc_uid != $this->User->usr_id) {
				$rt['permit'] = 0;
				$rt['errors']++;
			}
		}
		
		if ((abs($rt['exp_amount']) * 1.2) > $User->usr_money) {
			$rt['errors']++;
			$rt['out_of_money'] = true; 
		}
		
		return $rt;
	}
	
	/* E module: Topic Update Check logic */

	/* S module: Topic Update Update logic */
	
	public function vxTopicUpdateUpdate($tpc_id, $tpc_title, $tpc_description, $tpc_content, $expense_amount) {
		if (get_magic_quotes_gpc()) {
			$tpc_title = stripslashes($tpc_title);
			$tpc_title = mysql_real_escape_string($tpc_title);
			
			$tpc_description = stripslashes($tpc_description);
			$tpc_description = mysql_real_escape_string($tpc_description);
			
			$tpc_content = stripslashes($tpc_content);
			$tpc_content = mysql_real_escape_string($tpc_content);
		} else {
			$tpc_title = mysql_real_escape_string($tpc_title);
			$tpc_description = mysql_real_escape_string($tpc_description);
			$tpc_content = mysql_real_escape_string($tpc_content);
		}
		$sql = "UPDATE babel_topic SET tpc_title = '{$tpc_title}', tpc_description = '{$tpc_description}', tpc_content = '{$tpc_content}', tpc_lastupdated = " . time() . " WHERE tpc_id = {$tpc_id} LIMIT 1";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			return $this->User->vxPay($this->User->usr_id, $expense_amount, 6);
		} else {
			return false;
		}
	}
	
	/* E module: Topic Update Update logic */
	
	/* S module: Post Create Check logic */
	
	public function vxPostCreateCheck($topic_id, $User) {
		$rt = array();
		
		$rt['out_of_money'] = false;
	
		$rt['topic_id'] = $topic_id;	
		$rt['exp_amount'] = 0;
		$rt['errors'] = 0;
		
		$rt['pst_title_value'] = '';
		/* pst_title_error:
		0 => no error
		1 => empty
		2 => overflow
		999 => unspecific */
		$rt['pst_title_error'] = 0;
		$rt['pst_title_error_msg'] = array(1 => '你忘记写标题了', 2 => '你写的标题太长了');
		
		$rt['pst_content_value'] = '';
		/* pst_content_error:
		0 => no error
		1 => empty
		2 => overflow
		999 => unspecific */
		$rt['pst_content_error'] = 0;
		$rt['pst_content_error_msg'] = array(1 => '你忘记写内容了', 2 => '你写的内容太长了');
		
		if (isset($_POST['pst_title'])) {
			$rt['pst_title_value'] = make_single_safe($_POST['pst_title']);
			if (strlen($rt['pst_title_value']) > 0) {
				if (mb_strlen($rt['pst_title_value'], 'UTF-8') > 80) {
					$rt['pst_title_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['pst_title_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['pst_title_error'] = 1;
			$rt['errors']++;
		}
		
		
		if (isset($_POST['pst_content'])) {
			$rt['pst_content_value'] = make_multi_safe($_POST['pst_content']);
			if (strlen($rt['pst_content_value']) > 0) {
				if (mb_strlen($rt['pst_content_value'], 'utf-8') > (10240)) {
					$rt['pst_content_error'] = 2;
					$rt['errors']++;
				}
			} else {
				$rt['pst_content_error'] = 1;
				$rt['errors']++;
			}
		} else {
			$rt['pst_content_error'] = 1;
			$rt['errors']++;
		}
		
		$sql = "SELECT tpc_uid FROM babel_topic WHERE tpc_id = {$topic_id}";
		$rs = mysql_query($sql, $this->db);
		$Topic = mysql_fetch_object($rs);
		mysql_free_result($rs);
		if ($Topic->tpc_uid != $this->User->usr_id) {
			$pst_price = BABEL_PST_PRICE;
		} else {
			$pst_price = BABEL_PST_SELF_PRICE;
		}
		
		if ($rt['pst_content_error'] == 0) {
			$pst_content_length  = mb_strlen($rt['pst_content_value'], 'utf-8');
			if ($pst_content_length > 200) {
				$rt['exp_amount'] = -(intval(($pst_content_length / 200) * $pst_price));
			} else {
				$rt['exp_amount'] = -($pst_price);
			}
		} else {
			$rt['exp_amount'] = -($pst_price);
		}
		
		if ((abs($rt['exp_amount']) * 1.2) > $User->usr_money) {
			$rt['errors']++;
			$rt['out_of_money'] = true;
		}
		
		return $rt;
	}
	
	/* E module: Post Create Check logic */
	
	/* S module: Post Create Insert logic */
	
	public function vxPostCreateInsert($topic_id, $user_id, $pst_title, $pst_content, $expense_amount) {
		if (get_magic_quotes_gpc()) {
			$pst_title = stripslashes($pst_title);
			$pst_title = mysql_real_escape_string($pst_title);
			
			$pst_content = stripslashes($pst_content);
			$pst_content = mysql_real_escape_string($pst_content);
		} else {
			$pst_title = mysql_real_escape_string($pst_title);
			$pst_content = mysql_real_escape_string($pst_content);
		}
		$sql = "INSERT INTO babel_post(pst_tid, pst_uid, pst_title, pst_content, pst_created, pst_lastupdated) VALUES({$topic_id}, {$user_id}, '{$pst_title}', '{$pst_content}', " . time() .", " . time() . ")";
		mysql_query($sql, $this->db);
		if (mysql_affected_rows($this->db) == 1) {
			$sql = "SELECT tpc_uid FROM babel_topic WHERE tpc_id = {$topic_id}";
			$rs = mysql_query($sql, $this->db);
			$Topic = mysql_fetch_object($rs);
			mysql_free_result($rs);
			if ($Topic->tpc_uid != $this->User->usr_id) {
				return $this->User->vxPay($this->User->usr_id, $expense_amount, 3, '', $Topic->tpc_uid);
			} else {
				return $this->User->vxPay($this->User->usr_id, $expense_amount, 5);
			}
		} else {
			return false;
		}
	}
	
	/* E module: Post Create Insert logic */
}

/* E Validator class */

?>
