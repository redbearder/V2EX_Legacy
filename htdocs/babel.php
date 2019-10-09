<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/babel.php
*  Usage: Loader
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel.php 490 2006-07-13 21:33:20Z livid $
*  $LastChangedDate: 2006-07-14 05:33:20 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 490 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/babel.php $
*/

DEFINE('V2EX_BABEL', 1);
$GOOGLE_AD_LEGAL = false;

require('core/V2EXCore.php');

if (isset($_GET['m'])) {
	$m = strtolower(trim($_GET['m']));
} else {
	$m = 'home';
}

$p =& new Page();

switch ($m) {
	default:
	case 'home':
		$GOOGLE_AD_LEGAL = true;		
		if (isset($_GET['style'])) {
			switch ($_GET['style']) {
				case 'shuffle':
					$p->vxHomeBundle('shuffle');
					break;
				case 'remix':
					$p->vxHomeBundle('remix');
					break;
				default:
					$p->vxHomeBundle(BABEL_HOME_STYLE_DEFAULT);
					break;
			}
		} else {
			if (isset($_SESSION['babel_home_style'])) {
				if ($_SESSION['babel_home_style'] != '') {
					$p->vxHomeBundle($_SESSION['babel_home_style']);
				} else {
					$p->vxHomeBundle(BABEL_HOME_STYLE_DEFAULT);
				}
			} else {
				$p->vxHomeBundle(BABEL_HOME_STYLE_DEFAULT);
			}
		}
		break;
		
	case 'search':
		$p->vxSearchBundle();
		break;

	case 'login':
		if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
			$rt = $p->Validator->vxLoginCheck();
			$p->User = new User($rt['usr_email_value'], sha1($rt['usr_password_value']), $p->db);
			/* start the session now */
			$p->User->vxSessionStart();
			if ($p->User->vxIsLogin()) {
				if (isset($rt['return'])) {
					if (strlen($rt['return']) > 0) {
						$p->URL->vxToRedirect($rt['return']);
					}
				}
			}
		} else {
			if ($p->User->vxIsLogin()) {
				$rt = array('target' => 'me');
			} else {
				$rt = array('target' => 'welcome');
				if (isset($_GET['r'])) {
					$rt['return'] = $_GET['r'];
				} else {
					$rt['return'] = '';
				}
			}
		}
		if ($rt['target'] == 'me') {
			$p->URL->vxToRedirect($p->URL->vxGetUserOwnHome());
		} else {
			$p->vxHead($msgSiteTitle = Vocabulary::action_login);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('login', $options = $rt);
		}
		break;
		
	case 'passwd':
		$p->vxHead($msgSiteTitle = Vocabulary::action_passwd);
		$p->vxBodyStart();
		$p->vxTop();
		$options = array();
		if (isset($_GET['k'])) {
			$k = mysql_real_escape_string(trim($_GET['k']), $p->db);
			if (strlen($k) > 0) {
				$_oneday = time() - 86400;
				$sql = "SELECT pwd_id, pwd_uid, usr_id, usr_email, usr_password FROM babel_passwd, babel_user WHERE pwd_uid = usr_id AND pwd_hash = '{$k}' AND pwd_created > {$_oneday} ORDER BY pwd_created DESC LIMIT 1";
				$rs = mysql_query($sql);
				
				if ($O = mysql_fetch_object($rs)) {
					mysql_free_result($rs);
					$options['mode'] = 'key';
					$options['key'] = $k;
					$options['target'] = new User($O->usr_email, $O->usr_password, $p->db, false);
					$O = null;
				} else {
					mysql_free_result($rs);
					$options['mode'] = 'get';
				}
			} else {
				$options['mode'] = 'get';
			}
		} else {
			if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
				$options['mode'] = 'get';
			} else {
				if (isset($_POST['key'])) {
					$k = mysql_real_escape_string(trim($_POST['key']), $p->db);
					if (strlen($k) > 0) {
						$_oneday = time() - 86400;
						$sql = "SELECT pwd_id, pwd_uid, usr_id, usr_email, usr_password FROM babel_passwd, babel_user WHERE pwd_hash = '{$k}' AND pwd_created > {$_oneday} AND pwd_uid = usr_id ORDER BY pwd_created DESC LIMIT 1";
						$rs = mysql_query($sql);
						
						if ($O = mysql_fetch_object($rs)) {
							mysql_free_result($rs);
							$options['mode'] = 'reset';
							$options['key'] = $k;
							$options['target'] = new User($O->usr_email, $O->usr_password, $p->db, false);
							$O = null;
							
							$options['rt'] = $p->Validator->vxUserPasswordUpdateCheck();
							
							if ($options['rt']['errors'] == 0) {
								$p->Validator->vxUserPasswordUpdateUpdate($options['target']->usr_id, sha1($options['rt']['usr_password_value']));
							}
						} else {
							mysql_free_result($rs);
							$options['mode'] = 'post';
						}
					} else {
						$options['mode'] = 'post';
					}
				} else {
					$options['mode'] = 'post';
				}
			}
		}
		$p->vxContainer('passwd', $options);
		break;

	case 'logout':
		$p->User->vxLogout();
		$p->vxHead($msgSiteTitle = Vocabulary::action_logout);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('logout');
		break;
	
	case 'signup':
		if ($p->User->vxIsLogin()) {
			$p->URL->vxToRedirect($p->URL->vxGetUserOwnHome());
		} else {
			$p->vxHead($msgSiteTitle = Vocabulary::action_signup);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('signup');
		}
		break;

	case 'status':
		$p->vxHead($msgSiteTitle = Vocabulary::term_status);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('status');
		break;
	
	case 'jobs':
		$p->vxHead($msgSiteTitle = Vocabulary::term_jobs);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('jobs');
		break;
		
	case 'rules':
		$p->vxHead($msgSiteTitle = Vocabulary::term_rules);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('rules');
		break;

	case 'terms':
		$p->vxHead($msgSiteTitle = Vocabulary::term_terms);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('terms');
		break;

	case 'privacy':
		$p->vxHead($msgSiteTitle = Vocabulary::term_privacy);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('privacy');
		break;
		
	case 'policies':
		$p->vxHead($msgSiteTitle = Vocabulary::term_policies);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('policies');
		break;
		
	case 'out_of_money':
		$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('out_of_money');
		break;

	case 'user_home':
		$GOOGLE_AD_LEGAL = true;
		$options = array();
		
		if (isset($_GET['do'])) {
			$do = strtolower(trim($_GET['do']));
			if ($do == 'me') {
				if ($p->User->vxIsLogin()) {
					$options['mode'] = 'fixed';
					$options['target'] = $p->User;
				} else {
					$options['mode'] = 'random';
				}
			} else {
				if (isset($_GET['user_nick'])) {
					$user_nick = mysql_real_escape_string(trim($_GET['user_nick']), $p->db);
					if (strlen($user_nick) > 0) {
						$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user WHERE usr_nick = '{$user_nick}'";
						$rs = mysql_query($sql, $p->db);
						if ($O = mysql_fetch_object($rs)) {
							$options['mode'] = 'fixed';
							$options['target'] = $O;
							$O = null;
						} else {
							$options['mode'] = 'random';
						}
						mysql_free_result($rs);
					} else {
						$options['mode'] = 'random';
					}
				} else {
					$options['mode'] = 'random';
				}
			}
		} else {
			if (isset($_GET['user_nick'])) {
				$user_nick = mysql_real_escape_string(trim($_GET['user_nick']), $p->db);
				if (strlen($user_nick) > 0) {
					$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user WHERE usr_nick = '{$user_nick}'";
					$rs = mysql_query($sql, $p->db);
					if ($O = mysql_fetch_object($rs)) {
						$options['mode'] = 'fixed';
						$options['target'] = $O;
						$O = null;
					} else {
						$options['mode'] = 'random';
					}
					mysql_free_result($rs);
				} else {
					$options['mode'] = 'random';
				}
			} else {
				$options['mode'] = 'random';
			}
		}
			
		if ($options['mode'] == 'random') {
			$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user ORDER BY rand() LIMIT 1";
			$rs = mysql_query($sql, $p->db);
			$options['target'] = mysql_fetch_object($rs);
			mysql_free_result($rs);
			$p->vxHead($msgSiteTitle = Vocabulary::term_user_random);
		} else {
			$p->vxHead($msgSiteTitle = make_plaintext($options['target']->usr_nick));
		}

		$p->vxBodyStart();
		$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $options['target']->usr_nick);
		$p->vxContainer('user_home', $options);
		break;

	case 'user_create':
		if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
			$p->vxHead($msgSiteTitle = Vocabulary::action_signup);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('signup');
			break;
		} else {
			$rt = $p->Validator->vxUserCreateCheck();
			if ($rt['errors'] == 0) {
				$O = $p->Validator->vxUserCreateInsert($rt['usr_nick_value'], $rt['usr_password_value'], $rt['usr_email_value'], $rt['usr_gender_value']);
				$p->User = new User($O->usr_email, $O->usr_password, $p->db);
				$p->User->vxPay($p->User->usr_id, BABEL_USR_INITIAL_MONEY, 1);
				$p->User->vxSessionStart();
			}
			$p->vxHead($msgSiteTitle = Vocabulary::action_signup);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('user_create', $options = $rt);
			break;
		}
	
	case 'user_modify':
		if ($p->User->vxIsLogin()) {
			$p->vxHead($msgSiteTitle = Vocabulary::action_modifyprofile);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('user_modify');
			break;
		} else {
			$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetUserModify()));
			break;
		}
		
	case 'user_update':
		if ($p->User->vxIsLogin()) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
				$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetUserModify()));
			} else {
				$rt = $p->Validator->vxUserUpdateCheck();
				if ($rt['errors'] == 0) {
					$p->Validator->vxUserUpdateUpdate($rt['usr_full_value'], $rt['usr_nick_value'], $rt['usr_brief_value'], $rt['usr_gender_value'], $rt['usr_addr_value'], $rt['usr_telephone_value'], $rt['usr_identity_value'], $rt['usr_width_value'], $rt['usr_password_value']);
					if ($rt['pswitch'] == 'b') {
						$p->User->vxLogout();
					}
				}
				$p->vxHead($msgSiteTitle = Vocabulary::action_modifyprofile);
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('user_update', $options = $rt);
			}
			break;
		} else {
			$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetUserModify()));
			break;
		}
		
	case 'topic_archive_user':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['user_nick'])) {
			$user_nick = mysql_real_escape_string(trim($_GET['user_nick']), $p->db);
			$sql = "SELECT usr_id, usr_nick, usr_gender, usr_portrait, usr_hits, usr_gender, usr_created FROM babel_user WHERE usr_nick = '{$user_nick}' LIMIT 1";
			$rs = mysql_query($sql, $p->db);
			if ($User = mysql_fetch_object($rs)) {
				mysql_free_result($rs);
				$p->vxHead($msgSiteTitle = make_plaintext($User->usr_nick) . ' 的所有主题');
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('topic_archive_user', $options = $User);
			} else {
				mysql_free_result($rs);
				$p->vxHomeBundle(BABEL_HOME_STYLE_DEFAULT);
			}
			break;
		} else {
			$p->vxHomeBundle(BABEL_HOME_STYLE_DEFAULT);
			break;
		}
		
	case 'channel_view':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['channel_id'])) {
			$channel_id = intval($_GET['channel_id']);
			if ($p->Validator->vxExistChannel($channel_id)) {
				$Channel = new Channel($channel_id, $p->db);
				$p->vxHead($msgSiteTitle = make_plaintext($Channel->chl_title));
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('channel_view', $options = $Channel);
				break;
			} else {
				$p->vxHomeBundle();
				break;
			}
		} else {
			$p->vxHomeBundle();
			break;
		}

	case 'board_view':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['board_id'])) {
			$board_id = intval($_GET['board_id']);
			$sql = "SELECT nod_id, nod_name, nod_title FROM babel_node WHERE nod_id = {$board_id} AND nod_level > 1";
			$rs = mysql_query($sql, $p->db);
			if (mysql_num_rows($rs) == 1) {
				$O = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$p->vxHead($msgSiteTitle = make_plaintext($O->nod_title), '', 'http://' . BABEL_DNS_NAME . '/feed/board/' . $O->nod_name . '.rss');
				$p->vxBodyStart();
				$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $O->nod_title);
				$p->vxContainer('board_view', $options = array('board_id' => $O->nod_id));
				break;
			} else {
				mysql_free_result($rs);
				$p->vxHomeBundle();
				break;
			}		
		} else {
			if (isset($_GET['board_name'])) {
				$board_name = strtolower(trim($_GET['board_name']));
				$sql = "SELECT nod_id, nod_name, nod_title, nod_level FROM babel_node WHERE nod_name = '{$board_name}' and nod_level > 0";
				$rs = mysql_query($sql, $p->db);
				if (mysql_num_rows($rs) == 1) {
					$O = mysql_fetch_object($rs);
					mysql_free_result($rs);
					$p->vxHead($msgSiteTitle = make_plaintext($O->nod_title), '', 'http://' . BABEL_DNS_NAME . '/feed/board/' . $O->nod_name . '.rss');
					$p->vxBodyStart();
					$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $O->nod_title);
					switch ($O->nod_level) {
						case 2:
						default:
							$p->vxContainer('board_view', $options = array('board_id' => $O->nod_id));
							break;
						case 1:
							$p->vxContainer('section_view', $options = array('section_id' => $O->nod_id));
							break;
					}
					break;
				} else {
					mysql_free_result($rs);
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		}
		
	case 'topic_fresh':
		$GOOGLE_AD_LEGAL = true;
		$p->vxHead($msgSiteTitle = Vocabulary::action_freshtopic);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('topic_fresh');
		break;
		
	case 'topic_favorite':
		$GOOGLE_AD_LEGAL = true;
		if ($p->User->vxIsLogin()) {
			$p->vxHead($msgSiteTitle = Vocabulary::term_favorite);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('topic_favorite');
			break;
		} else {
			$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicFavorite()));
			break;
		}
	
	case 'topic_top':
		$GOOGLE_AD_LEGAL = true;
		$p->vxHead($msgSiteTitle = Vocabulary::term_toptopic);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('topic_top');
		break;
	
	case 'topic_new':
		if ($p->User->vxIsLogin()) {
			$exp = -(BABEL_TPC_PRICE);
			if ((abs($exp) * 1.2) > $p->User->usr_money) {
				$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('out_of_money');
				break;
			} else {
				if (isset($_GET['board_id'])) {
					$board_id = intval($_GET['board_id']);
					if (strlen($board_id) > 0) {
						$sql = "SELECT nod_id, nod_level FROM babel_node WHERE nod_id = {$board_id}";
						$rs = mysql_query($sql, $p->db);
						if (mysql_num_rows($rs) == 1) {
							$O = mysql_fetch_object($rs);
							mysql_free_result($rs);
							$p->vxHead($msgSiteTitle = Vocabulary::action_newtopic);
							$p->vxBodyStart();
							$p->vxTop();
							if ($O->nod_level > 1) {
								$p->vxContainer('topic_new', $options = array('mode' => 'board', 'board_id' => $O->nod_id));
							} else {
								$p->vxContainer('topic_new', $options = array('mode' => 'section', 'section_id' => $O->nod_id));	
							}
							$O = null;
							break;
						} else {
							mysql_free_result($rs);
							$p->vxHomeBundle();
							break;
						}
					} else {
						$p->vxHomeBundle();
						break;
					}
				} else {
					$p->vxHomeBundle();
					break;
				}
			}
		} else {
			if (isset($_GET['board_id'])) {
				$board_id = intval(trim($_GET['board_id']));
				$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicNew($board_id)));
			} else {
				$p->vxHomeBundle();
				break;
			}
		}

	case 'topic_create':
		if ($p->User->vxIsLogin()) {
			$exp = -(BABEL_TPC_PRICE);
			if ((abs($exp) * 1.2) > $p->User->usr_money) {
				$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('out_of_money');
				break;
			} else {
				if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
					if (isset($_GET['board_id'])) {
						$board_id = intval($_GET['board_id']);
						$sql = "SELECT nod_id, nod_level FROM babel_node WHERE nod_id = {$board_id} AND nod_level > 0";
						$rs = mysql_query($sql, $p->db);
						if (mysql_num_rows($rs) == 1) {
							$O = mysql_fetch_object($rs);
							mysql_free_result($rs);
							$p->vxHead($msgSiteTitle = Vocabulary::action_newtopic);
							$p->vxBodyStart();
							$p->vxTop();
							if ($O->nod_level > 1) {
								$p->vxContainer('topic_new', $options = array('mode' => 'board', 'board_id' => $O->nod_id));
							} else {
								$p->vxContainer('topic_new', $options = array('mode' => 'section', 'section_id' => $O->nod_id));	
							}
							$O = null;
							break;
						} else {
							mysql_free_result($rs);
							$p->vxHomeBundle();
							break;
						}				
					} else {
						$p->vxHomeBundle();
						break;
					}
				} else {
					if (isset($_GET['board_id'])) {
						$board_id = intval($_GET['board_id']);
						$sql = "SELECT nod_id, nod_level FROM babel_node WHERE nod_id = {$board_id} AND nod_level > 0";
						$rs = mysql_query($sql, $p->db);
						if (mysql_num_rows($rs) == 1) {
							$O = mysql_fetch_object($rs);
							mysql_free_result($rs);
							if ($O->nod_level > 1) {
								$rt = $p->Validator->vxTopicCreateCheck($options = array('mode' => 'board', 'board_id' => $O->nod_id), $p->User);
							} else {
								$rt = $p->Validator->vxTopicCreateCheck($options = array('mode' => 'section', 'section_id' => $O->nod_id), $p->User);
							}
							$O = null;
							if ($rt['out_of_money']) {
								$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
								$p->vxBodyStart();
								$p->vxTop();
								$p->vxContainer('out_of_money');
							} else {
								if ($rt['errors'] == 0) {
									$O = $p->Validator->vxTopicCreateInsert($rt['tpc_pid_value'], $p->User->usr_id, $rt['tpc_title_value'], $rt['tpc_description_value'], $rt['tpc_content_value'], $rt['exp_amount']);
									$sql = "SELECT tpc_id, tpc_title FROM babel_topic WHERE tpc_uid = {$p->User->usr_id} ORDER BY tpc_created DESC LIMIT 1";
									$rs = mysql_query($sql, $p->db);
									$Topic = mysql_fetch_object($rs);
									mysql_free_result($rs);
									$p->vxHead($msgSiteTitle = Vocabulary::action_newtopic, $return = '/topic/view/' . $Topic->tpc_id . '.html');
								} else {
									$p->vxHead($msgSiteTitle = Vocabulary::action_newtopic);
								}
								$p->vxBodyStart();
								$p->vxTop();
								$p->vxContainer('topic_create', $options = $rt);
							}
							break;
						} else {
							mysql_free_result($rs);
							$p->vxHomeBundle();
							break;
						}
					} else {
						mysql_free_result($rs);
						$p->vxHomeBundle();
						break;
					}
				}
			}
		} else {
			if (isset($_GET['board_id'])) {
				$board_id = intval($_GET['board_id']);
				$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicNew($board_id)));
				break;
			} else {
				$p->vxHomeBundle();
				break;
			}
		}

	case 'topic_view':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['topic_id'])) {
			$topic_id = intval(trim($_GET['topic_id']));
			if ($p->Validator->vxIsDangerousTopic($topic_id, $p->cs)) {
				$sql = "UPDATE babel_topic SET tpc_flag = 1 WHERE tpc_id = {$topic_id}";
				mysql_unbuffered_query($sql);
				$p->URL->vxToRedirect($p->URL->vxGetHome());
				die("I'm sorry for the self policing.");
			}
			$sql = "SELECT tpc_id, tpc_title FROM babel_topic WHERE tpc_id = {$topic_id}";
			$rs = mysql_query($sql, $p->db);
			if (mysql_num_rows($rs) == 1) {
				$Topic = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$p->vxHead($msgSiteTitle = make_plaintext($Topic->tpc_title));
				$p->vxBodyStart();
				$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $Topic->tpc_title);
				$p->vxContainer('topic_view', $options = array('topic_id' => $Topic->tpc_id));
			} else {
				$p->vxHomeBundle();
			}
			break;
		} else {
			$p->vxHomeBundle();
			break;
		}
	
	case 'section_view':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['section_id'])) {
			$section_id = intval(trim($_GET['section_id']));
			$sql = "SELECT nod_id, nod_title FROM babel_node WHERE nod_id = {$section_id} AND nod_level = 1";
			$rs = mysql_query($sql, $p->db);
			if (mysql_num_rows($rs) == 1) {
				$Section = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$p->vxHead($msgSiteTitle = $Section->nod_title);
				$p->vxBodyStart();
				$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $Section->nod_title);
				$p->vxContainer('section_view', $options = array('section_id' => $section_id));
				break;
			} else {
				mysql_free_result($rs);
				$p->vxHomeBundle();
				break;
			}
		} else {
			$section_id = rand(2, 5);
			$sql = "SELECT nod_id, nod_title FROM babel_node WHERE nod_id = {$section_id} AND nod_level = 1";
			$rs = mysql_query($sql, $p->db);
			if (mysql_num_rows($rs) == 1) {
				$Section = mysql_fetch_object($rs);
				mysql_free_result($rs);
				$p->vxHead($msgSiteTitle = ' | ' . $Section->nod_title);
				$p->vxBodyStart();
				$p->vxTop($msgBanner = Vocabulary::site_banner, $keyword = $Section->nod_title);
				$p->vxContainer('section_view', $options = array('section_id' => $Section->nod_id));
				break;
			} else {
				mysql_free_result($rs);
				$p->vxHomeBundle();
				break;
			}
		}

	case 'topic_modify':
		if ($p->User->vxIsLogin()) {
			$exp = -(BABEL_TPC_UPDATE_PRICE);
			if ((abs($exp) * 1.2) > $p->User->usr_money) {
				$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
				$p->vxBodyStart();
				$p->vxTop();
				$p->vxContainer('out_of_money');
				break;
			} else {
				if (isset($_GET['topic_id'])) {
					$topic_id = intval(trim($_GET['topic_id']));
					if (strlen($topic_id) > 0) {
						if ($p->Validator->vxExistTopic($topic_id)) {
							$Topic = new Topic($topic_id, $p->db, 0);
							$p->vxHead($msgSiteTitle = Vocabulary::action_modifytopic . ' | ' . make_plaintext($Topic->tpc_title));
							$p->vxBodyStart();
							$p->vxTop();
							$p->vxContainer('topic_modify', $options = $Topic);
							break;
						} else {
							$p->HomeBundle();
							break;
						}
					} else {
						$p->HomeBundle();
						break;
					}
				} else {
					$p->HomeBundle();
					break;
				}
			}
		} else {
			if (isset($_GET['topic_id'])) {
				$topic_id = intval(trim($_GET['topic_id']));
				$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicModify($topic_id)));
				break;
			} else {
				$p->vxHomeBundle();
				break;
			}
		}
		
	case 'topic_update':
		if ($p->User->vxIsLogin()) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
				if (isset($_GET['topic_id'])) {
					if ($p->Validator->vxExistTopic($topic_id)) {
						$p->URL->vxToRedirect($p->URL->vxGetTopicModify($topic_id));
						break;
					} else {
						$p->vxHomeBundle();
						break;
					}
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				if (isset($_GET['topic_id'])) {
					$topic_id = intval(trim($_GET['topic_id']));
					if ($p->Validator->vxExistTopic($topic_id)) {
						$rt = $p->Validator->vxTopicUpdateCheck($topic_id, $p->User);
						if ($rt['out_of_money']) {
							$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
							$p->vxBodyStart();
							$p->vxTop();
							$p->vxContainer('out_of_money');
						} else {
							if ($rt['errors'] == 0) {
								$p->Validator->vxTopicUpdateUpdate($rt['topic_id'], $rt['tpc_title_value'], $rt['tpc_description_value'], $rt['tpc_content_value'], $rt['exp_amount']);
								$p->vxHead($msgSiteTitle = Vocabulary::action_modifytopic . ' | ' . make_plaintext($rt['tpc_title_value']), $return = '/topic/view/' . $rt['topic_id'] . '.html');
							} else {
								$p->vxHead($msgSiteTitle = Vocabulary::action_modifytopic . ' | ' . make_plaintext($rt['tpc_title_value']));
							}
							$p->vxBodyStart();
							$p->vxTop();
							$p->vxContainer('topic_update', $options = $rt);
						}
						break;
					} else {
						$p->vxHomeBundle();
						break;
					}
				} else {
					$p->vxHomeBundle();
					break;
				}
			}
		} else {
			if (isset($_GET['topic_id'])) {
				$topic_id = intval(trim($_GET['topic_id']));
				if ($p->Validator->vxExistTopic($topic_id)) {
					$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicModify($topic_id)));
					break;
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		}
		
	case 'topic_erase':
		if ($p->User->vxIsLogin()) {
			if (isset($_GET['topic_id'])) {
				$topic_id = intval($_GET['topic_id']);
				if ($p->Validator->vxExistTopic($topic_id)) {
					if ($p->User->usr_id == 1) {
						$Topic = new Topic($topic_id, $p->db);
						$Topic->vxEraseTopic($topic_id);
						$Topic->vxUpdateTopics($Topic->tpc_pid);
						$p->URL->vxToRedirect($p->URL->vxGetBoardView($Topic->tpc_pid));
						break;
					} else {
						$p->vxDeniedBundle();
						break;
					}
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		} else {
			if (isset($_GET['topic_id'])) {
				$post_id = intval($_GET['topic_id']);
				if ($p->Validator->vxExistTopic($topic_id)) {
					$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicErase($topic_id)));
					break;
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		}
		
	case 'post_erase':
		if ($p->User->vxIsLogin()) {
			if (isset($_GET['post_id'])) {
				$post_id = intval($_GET['post_id']);
				if ($p->Validator->vxExistPost($post_id)) {
					if ($p->User->usr_id == 1) {
						$Post = new Post($post_id, $p->db);
						$Post->vxErasePost($post_id);
						$Post->vxUpdatePosts($Post->pst_tid);
						$p->URL->vxToRedirect($p->URL->vxGetTopicView($Post->pst_tid));
						break;
					} else {
						$p->vxDeniedBundle();
						break;
					}
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		} else {
			if (isset($_GET['post_id'])) {
				$post_id = intval($_GET['post_id']);
				if ($p->Validator->vxExistPost($post_id)) {
					$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetPostErase($post_id)));
					break;
				} else {
					$p->vxHomeBundle();
					break;
				}
			} else {
				$p->vxHomeBundle();
				break;
			}
		}

	case 'post_create':
		if ($p->User->vxIsLogin()) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
				$p->vxHomeBundle();
				break;
			} else {
				$topic_id = intval($_GET['topic_id']);
				if ($p->Validator->vxExistTopic($topic_id)) {
					$rt = $p->Validator->vxPostCreateCheck($topic_id, $p->User);
					
					if ($rt['out_of_money']) {
						$p->vxHead($msgSiteTitle = Vocabulary::term_out_of_money);
						$p->vxBodyStart();
						$p->vxTop();
						$p->vxContainer('out_of_money');
					} else {
						if ($rt['errors'] == 0) {
							$p->Validator->vxPostCreateInsert($rt['topic_id'], $p->User->usr_id, $rt['pst_title_value'], $rt['pst_content_value'], $rt['exp_amount']);
							$p->vxHead($msgSiteTitle = Vocabulary::action_replytopic, $return = '/topic/view/' . $topic_id . '.html');
						} else {
							$p->vxHead($msgSiteTitle = Vocabulary::action_replytopic);
						}
						$p->vxBodyStart();
						$p->vxTop();
						$p->vxContainer('post_create', $options = $rt);
					}
					break;
				} else {
					$p->vxHomeBundle();
					break;
				}
			}
		} else {
			if (isset($_GET['topic_id'])) {
				$topic_id = intval(trim($_GET['topic_id']));
				if ($p->Validator->vxExistTopic($topic_id)) {
					$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetTopicView($topic_id)));
				} else {
					$p->vxHomeBundle();
				}
				break;
			} else {
				$p->vxHomeBundle();
				break;
			}
		}

	case 'expense_view':
		if ($p->User->vxIsLogin()) {
			$p->vxHead($msgSiteTitle = Vocabulary::action_viewexpense);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('expense_view');
			break;
		} else {
			$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetExpenseView()));
			break;
		}
		
	case 'online_view':
		if ($p->User->vxIsLogin()) {
			$p->vxHead($msgSiteTitle = Vocabulary::action_viewonline);
			$p->vxBodyStart();
			$p->vxTop();
			$p->vxContainer('online_view');
			break;
		} else {
			$p->URL->vxToRedirect($p->URL->vxGetLogin($p->URL->vxGetOnlineView()));
			break;
		}
		
	case 'mobile':
		$GOOGLE_AD_LEGAL = true;
		$p->vxHead($msgSiteTitle = Vocabulary::action_mobile_search);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('mobile');
		break;
		
	case 'man':
		$GOOGLE_AD_LEGAL = true;
		if (isset($_GET['q'])) {
			$_q = urldecode(substr($_SERVER['REQUEST_URI'], 5, (strlen($_SERVER['REQUEST_URI']) - 5)));
			$p->vxHead($msgSiteTitle = $_q);
		} else {
			$p->vxHead($msgSiteTitle = Vocabulary::action_man_search);
		}
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('man');
		break;
		
	case 'zen':
		$GOOGLE_AD_LEGAL = true;
		
		if (isset($_GET['user_nick'])) {
			$user_nick = mysql_real_escape_string(trim($_GET['user_nick']), $p->db);
			if (strlen($user_nick) > 0) {
				$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user WHERE usr_nick = '{$user_nick}'";
				$rs = mysql_query($sql, $p->db);
				if ($O = mysql_fetch_object($rs)) {
					$options['mode'] = 'fixed';
					$options['target'] = $O;
					$O = null;
				} else {
					if ($p->User->vxIsLogin()) {
						$options['mode'] = 'self';
					} else {
						$options['mode'] = 'random';
					}
				}
				mysql_free_result($rs);
			} else {
				if ($p->User->vxIsLogin()) {
					$options['mode'] = 'self';
				} else {
					$options['mode'] = 'random';
				}
			}
		} else {
			if ($p->User->vxIsLogin()) {
				$options['mode'] = 'self';
			} else {
				$options['mode'] = 'random';
			}
		}
			
		if ($options['mode'] == 'random') {
			$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user ORDER BY rand() LIMIT 1";
			$rs = mysql_query($sql, $p->db);
			$options['target'] = mysql_fetch_object($rs);
			mysql_free_result($rs);	
		}
		
		if ($options['mode'] == 'self') {
			$sql = "SELECT usr_id, usr_nick, usr_brief, usr_gender, usr_portrait, usr_hits, usr_created FROM babel_user WHERE usr_id = {$p->User->usr_id}";
			$rs = mysql_query($sql, $p->db);
			$options['target'] = mysql_fetch_object($rs);
			mysql_free_result($rs);	
		}
		
		$p->vxHead($msgSiteTitle = make_plaintext($options['target']->usr_nick) . ' - ' . Vocabulary::term_zen);
		$p->vxBodyStart();
		$p->vxTop();
		$p->vxContainer('zen', $options);
		break;
}

$p->vxBottom();
$p->vxBodyEnd();
?>