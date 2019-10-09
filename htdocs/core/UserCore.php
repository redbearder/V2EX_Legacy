<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/UserCore.php
*  Usage: User Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: UserCore.php 291 2006-05-14 15:10:23Z livid $
*  $LastChangedDate: 2006-05-14 23:10:23 +0800 (Sun, 14 May 2006) $
*  $LastChangedRevision: 291 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/UserCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S User class */

class User {
	var $db;
	var $bf;

	var $usr_id;
	var $usr_gid;
	var $usr_email;
	var $usr_password;
	var $usr_nick;
	var $usr_full;
	var $usr_addr;
	var $usr_telephone;
	var $usr_identity;
	var $usr_gender;
	var $usr_brief;
	var $usr_portrait;
	var $usr_money;
	var $usr_width;
	var $usr_hits;
	var $usr_created;
	var $usr_money_a;
	var $usr_gender_a;
	var $usr_expense_type_msg;
	
	public function __construct($usr_email, $usr_password, $db, $session = true) {
		$this->usr_id = 0;
		$this->usr_gid = 0;
		$this->usr_email = '';
		$this->usr_password = '';
		$this->usr_nick = '';
		$this->usr_full = '';
		$this->usr_addr = '';
		$this->usr_telephone = '';
		$this->usr_identity = '';
		$this->usr_gender = 0;
		$this->usr_brief = '';
		$this->usr_portrait = '';
		$this->usr_money = 0;
		$this->usr_width = 1024;
		$this->usr_hits = 0;
		$this->usr_created = 0;
		$this->usr_money_a = array();
		$this->usr_gender_a = array(0 => '未知', 1 => '男性', 2 => '女性', 5 => '女性改（变）为男性', 6 => '男性改（变）为女性', 9 => '未说明');
		/* exp_type:
		0 => Mystery Payment
		1 => Signup Initial
		2 => Topic Create
		3 => Post Create
		4 => Gain From Replied Topic
		5 => Loopback
		999 => Mystery Income */
		$this->usr_expense_type_msg = array(0 => '神秘的支出', 1 => '注册得到启动资金', 2 => '创建新主题', 3 => '回复别人创建的主题', 4 => '主题被别人回复', 5 => '回复自己创建的主题', 6 => '修改主题', 7 => '主题利息收入', 8 => '发送社区短消息', 999 => '神秘的收入');
		
		$this->db = $db;
		
		$this->bf = new Crypt_Blowfish(BABEL_BLOWFISH_KEY);
		
		$e = 0;

		if (strlen($usr_email) > 0 && strlen($usr_password) > 0) {
			$sql = "SELECT usr_id, usr_gid, usr_email, usr_password, usr_nick, usr_full, usr_addr, usr_telephone, usr_identity, usr_gender, usr_brief, usr_portrait, usr_money, usr_width, usr_hits, usr_created FROM babel_user WHERE usr_email = '{$usr_email}' AND usr_password = '{$usr_password}'";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$O = mysql_fetch_object($rs);
				$this->usr_id = $O->usr_id;
				$this->usr_gid = $O->usr_gid;
				$this->usr_email = $O->usr_email;
				$this->usr_password = $O->usr_password;
				$this->usr_nick = $O->usr_nick;
				$this->usr_full = $O->usr_full;
				$this->usr_addr = $O->usr_addr;
				$this->usr_telephone = $O->usr_telephone;
				$this->usr_identity = $O->usr_identity;
				$this->usr_gender = $O->usr_gender;
				$this->usr_brief = $O->usr_brief;
				$this->usr_portrait = $O->usr_portrait;
				$this->usr_money = $O->usr_money;
				$this->usr_width = $O->usr_width;
				$this->usr_hits = $O->usr_hits;
				$this->usr_created = $O->usr_created;
				$this->usr_money_a = $this->vxParseMoney();
				if ($session) {
					$this->vxSessionStart();
				}
				$O = null;
			} else {
				$e++;
			}
		} else {
			if (isset($_COOKIE['babel_usr_email']) && isset($_COOKIE['babel_usr_password'])) {
				if (!strlen($_COOKIE['babel_usr_email']) > 0) {
					$e++;
				}
				if (!strlen($_COOKIE['babel_usr_password']) > 0) {
					$e++;
				}
			} else {
				$e++;
			}
		
			if ($e == 0) {
				if (get_magic_quotes_gpc()) {
					$real_usr_email = mysql_real_escape_string(stripslashes($_COOKIE['babel_usr_email']));
					$real_usr_password = mysql_real_escape_string($this->bf->decrypt(stripslashes($_COOKIE['babel_usr_password'])));
				} else {
					$real_usr_email = mysql_real_escape_string($_COOKIE['babel_usr_email']);
					$real_usr_password = mysql_real_escape_string($this->bf->decrypt($_COOKIE['babel_usr_password']));
				}
				$sql = "SELECT usr_id, usr_gid, usr_email, usr_password, usr_nick, usr_full, usr_addr, usr_telephone, usr_identity, usr_gender, usr_brief, usr_portrait, usr_money, usr_width, usr_hits, usr_created FROM babel_user WHERE usr_email = '" . $real_usr_email . "' AND usr_password = '" . $real_usr_password . "'";
				$rs = mysql_query($sql, $this->db);
				if (mysql_num_rows($rs) == 1) {
					$O = mysql_fetch_object($rs);
					$this->usr_id = $O->usr_id;
					$this->usr_gid = $O->usr_gid;
					$this->usr_email = $O->usr_email;
					$this->usr_password = $O->usr_password;
					$this->usr_nick = $O->usr_nick;
					$this->usr_full = $O->usr_full;
					$this->usr_addr = $O->usr_addr;
					$this->usr_telephone = $O->usr_telephone;
					$this->usr_identity = $O->usr_identity;
					$this->usr_gender = $O->usr_gender;
					$this->usr_brief = $O->usr_brief;
					$this->usr_portrait = $O->usr_portrait;
					$this->usr_money = $O->usr_money;
					$this->usr_width = $O->usr_width;
					$this->usr_hits = $O->usr_hits;
					$this->usr_created = $O->usr_created;
					$this->usr_money_a = $this->vxParseMoney();
					if ($session) {
						$this->vxSessionStart();
					}
					$O = null;
				} else {
					session_destroy();
				}
				mysql_free_result($rs);
			}
		}
	}
	
	public function __destruct() {
	}
	
	public function vxSessionStart() {
		setcookie('babel_usr_email', $this->usr_email, time() + 2678400, '/');
		setcookie('babel_usr_password', $this->bf->encrypt($this->usr_password), time() + 2678400, '/');
		$_SESSION['babel_usr_email'] = $this->usr_email;
		$_SESSION['babel_usr_password'] = $this->usr_password;
	}
	
	public function vxLogout() {
		$this->usr_id = 0;
		$this->usr_gid = 0;
		$this->usr_email = '';
		$this->usr_password = '';
		$this->usr_nick = '';
		$this->usr_full = '';
		$this->usr_addr = '';
		$this->usr_telephone = '';
		$this->usr_identity = '';
		$this->usr_gender = 0;
		$this->usr_brief = '';
		$this->usr_portrait = '';
		$this->usr_money = 0;
		$this->usr_width = 1024;
		$this->usr_money_a = array();
		setcookie('babel_usr_email', '', 0);
		setcookie('babel_usr_password', '', 0);
		session_destroy();
	}
	
	public function vxIsLogin() {
		if ($this->usr_id != 0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function vxGetUserInfo($user_id) {
		$sql = "SELECT usr_id, usr_nick, usr_email FROM babel_user WHERE usr_id = {$user_id}";
		$rs = mysql_query($sql, $this->db);
		$User = mysql_fetch_object($rs);
		mysql_free_result($rs);
		return $User;
	}
	
	public function vxAddHits($user_id) {
		$sql = "SELECT COUNT(*) FROM babel_online WHERE onl_ip = '" . $_SERVER['REMOTE_ADDR'] . "'";
		$rs = mysql_query($sql);
		if (mysql_result($rs, 0, 0) < 3) {
			mysql_free_result($rs);
			$sql = "UPDATE babel_user SET usr_hits = (usr_hits + 1) WHERE usr_id = {$user_id} LIMIT 1";
			mysql_query($sql, $this->db);
			if (mysql_affected_rows($this->db) == 1) {
				return true;
			} else {
				return false;
			}
		} else {
			mysql_free_result($rs);
		}
	}
	
	public function vxParseMoney($money = '') {
		if ($money == '') {
			$money = $this->usr_money;
		}
		
		$usr_money_a = array();
		
		$usr_money_a['total'] = $money;
		
		/* now start parsing:
		g -> Gold
		s -> Silver
		c -> Copper */
		if ($money >= 10000) {
			$g = intval($money / 10000);
			$usr_money_a['g'] = $g;
			$r = $money - ($g * 10000);
			if ($r > 100) {
				$s = intval($r / 100);
				$usr_money_a['s'] = $s;
				$r = $r - ($s * 100);
				if ($r > 10) {
					$usr_money_a['c'] = substr($r, 0, 5);
				} else {
					$usr_money_a['c'] = substr($r, 0, 4);
				}
			} else {
				$usr_money_a['s'] = 0;
				if ($r > 10) {
					$usr_money_a['c'] = substr($r, 0, 5);
				} else {
					$usr_money_a['c'] = substr($r, 0, 4);
				}
			}
		} else {
			$usr_money_a['g'] = 0;
			if ($money >= 100) {
				$s = intval($money / 100);
				$usr_money_a['s'] = $s;
				$r = $money - ($s * 100);
				if ($r > 10) {
					$usr_money_a['c'] = substr($r, 0, 5);
				} else {
					$usr_money_a['c'] = substr($r, 0, 4);
				}
			} else {
				$usr_moeny_a['g'] = 0;
				$usr_money_a['s'] = 0;
				if ($money > 10) {
					$usr_money_a['c'] = substr($money, 0, 5);
				} else {
					$usr_money_a['c'] = substr($money, 0, 4);
				}
			}
		}
		
		/* translate it into a descriptive string */
		if ($usr_money_a['g'] > 0) {
			$g_str = ' ' . $usr_money_a['g'] . ' 金币';
		} else {
			$g_str = '';
		}
		
		if ($usr_money_a['s'] > 0) {
			$s_str = ' ' . $usr_money_a['s'] . ' 银币';
		} else {
			$s_str = '';
		}
		
		if ($usr_money_a['c'] > 0) {
			$c_str = ' ' . $usr_money_a['c'] . ' 铜币';
		} else {
			$c_str = '';
		}
		
		$usr_money_a['str'] = $g_str . $s_str . $c_str;
		if ($usr_money_a['total'] == 0) {
			$usr_money_a['str'] = '身无分文';
		}
		
		return $usr_money_a;
	}
	
	/* S expense modules */
	
	/* S module: Pay Logic */
	
	/* exp_type:
	0 => Mystery Payment
	1 => Signup Initial
	2 => Topic Create
	3 => Post Create
	4 => Gain From Replied Topic
	5 => Loopback
	999 => Mystery Income */
	
	public function vxPay($user_id, $amount, $type, $memo = '', $other_id = 0) {
		if ($amount != 0) {
			$sql = "SELECT usr_id, usr_money FROM babel_user WHERE usr_id = {$user_id}";
			$rs = mysql_query($sql, $this->db);
			$User = mysql_fetch_object($rs);
			mysql_free_result($rs);
			$usr_money = $User->usr_money + $amount;
			$sql = "UPDATE babel_user SET usr_money = {$usr_money} WHERE usr_id = {$user_id} LIMIT 1";
			mysql_query($sql, $this->db);
			if (mysql_affected_rows($this->db) == 1) {
				$sql = "INSERT INTO babel_expense(exp_uid, exp_amount, exp_type, exp_memo, exp_created) VALUES({$user_id}, {$amount}, {$type}, '{$memo}', " . time() . ")";
				mysql_query($sql, $this->db);
				if (mysql_affected_rows($this->db) == 1) {
					if ($type != 3) {
						return true;
					} else {
						$amount = abs($amount);
						$sql = "SELECT usr_id, usr_money FROM babel_user WHERE usr_id = {$other_id}";
						$rs = mysql_query($sql, $this->db);
						$User = mysql_fetch_object($rs);
						mysql_free_result($rs);
						$usr_money = $User->usr_money + $amount;
						$sql = "UPDATE babel_user SET usr_money = {$usr_money} WHERE usr_id = {$User->usr_id} LIMIT 1";
						mysql_query($sql, $this->db);
						if (mysql_affected_rows($this->db) == 1) {
							$sql = "INSERT INTO babel_expense(exp_uid, exp_amount, exp_type, exp_created) VALUES({$User->usr_id}, {$amount}, 4, " . time() . ")";
							mysql_query($sql, $this->db);
							if (mysql_affected_rows($this->db) == 1) {
								return true;
							} else {
								return false;
							}
						} else {
							return false;
						}
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}
	
	/* E expense modules */
}

/* E User class */
?>
