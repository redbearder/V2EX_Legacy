<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/StandaloneCore.php
*  Usage: Standalone Logic
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: StandaloneCore.php 505 2006-07-14 11:30:52Z livid $
*  $LastChangedDate: 2006-07-14 19:30:52 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 505 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/StandaloneCore.php $
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

class Standalone {
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

	public function vxGoHome() {
		header('Location: /');
	}
	
	public function vxRecvPortrait() {
		if ($this->User->vxIsLogin()) {
			if (isset($_FILES['usr_portrait'])) {
				$ul = $_FILES['usr_portrait'];
				
				if (substr($ul['type'], 0, 5) == 'image') {
					switch ($ul['type']) {
						case 'image/jpeg':
						case 'image/jpg':
						case 'image/pjpeg':
							$ext = '.jpg';
							break;
						case 'image/gif':
							$ext = '.gif';
							break;
						case 'image/png':
						case 'image/x-png':
							$ext = '.png';
							break;
						default:
							header('Content-type: text/html; charset=UTF-8');
							echo("<script>alert('你传的不是照片吧？');location.href='" . $this->URL->vxGetUserModify() . "'</script>");
							die('REDIRECTING...');
							break;
					}
					move_uploaded_file($ul["tmp_name"], BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext);

					if (isset($_POST['fx'])) {
						$fx = strtolower(trim($_POST['fx']));
						if (IM_ENABLED) {
							switch ($fx) {
								default:
									break;
								case 'lividark':
									Image::vxLividark(BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext);
									break;
							}
						}
					}
					
					Image::vxResize(BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext, BABEL_PREFIX . '/htdocs/img/p/' . $this->User->usr_id . '.' . BABEL_PORTRAIT_EXT, 75, 75, 1|4, 2);
					Image::vxResize(BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext, BABEL_PREFIX . '/htdocs/img/p/' . $this->User->usr_id . '_s.' . BABEL_PORTRAIT_EXT, 32, 32, 1|4, 2);
					Image::vxResize(BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext, BABEL_PREFIX . '/htdocs/img/p/' . $this->User->usr_id . '_n.' . BABEL_PORTRAIT_EXT, 16, 16, 1|4, 2);
					
					unlink(BABEL_PREFIX . '/tmp/' . $this->User->usr_id . $ext);
					if ($this->User->usr_portrait == '') {
						$sql = "UPDATE babel_user SET usr_portrait = usr_id WHERE usr_id = {$this->User->usr_id} LIMIT 1";
						mysql_query($sql, $this->db);
					}
					header('Content-type: text/html; charset=UTF-8');
					echo("<script>alert('你的头像已经成功上传！');location.href='" . $this->URL->vxGetUserModify() . "'</script>");
				} else {
					header('Content-type: text/html; charset=UTF-8');
					echo("<script>alert('你传的不是照片吧？');location.href='" . $this->URL->vxGetUserModify() . "'</script>");
					die('REDIRECTING...');
				}
			} else {
				header('Location: ' . $this->URL->vxGetUserModify());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetUserModify()));
		}
	}
	
	public function vxRecvSavepoint() {
		if ($this->User->vxIsLogin()) {
			if (isset($_POST['url'])) {
				$url = trim($_POST['url']);
				if (strlen($url) == 0) {
					return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome());
				}
				if (substr(strtolower($url), 0, 7) == 'http://') {
					$url = substr($url, 7, strlen($url) - 7);
				}
				$url = mysql_real_escape_string($url, $this->db);
				$sql = "SELECT svp_id FROM babel_savepoint WHERE svp_uid = {$this->User->usr_id} AND svp_url = '{$url}'";
				$rs = mysql_query($sql, $this->db);
				if (mysql_num_rows($rs) == 0) {
					mysql_free_result($rs);
					$sql = "SELECT svp_id FROM babel_savepoint WHERE svp_uid = {$this->User->usr_id}";
					$rs = mysql_query($sql, $this->db);
					if (mysql_num_rows($rs) < BABEL_SVP_LIMIT) {
						mysql_free_result($rs);
						$sql = "INSERT INTO babel_savepoint (svp_uid, svp_url, svp_created, svp_lastupdated) VALUES({$this->User->usr_id}, '{$url}', " . time() . ", " . time() . ")";
						mysql_query($sql, $this->db);
						if (mysql_affected_rows($this->db) == 1) {
							return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(1));
						} else {
							return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(0));
						}
					} else {
						mysql_free_result($rs);
						return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(3));
					}
				} else {
					mysql_free_result($rs);
					return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(2));
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome());
		}
	}
	
	public function vxSavepointErase() {
		if ($this->User->vxIsLogin()) {
			if (isset($_GET['savepoint_id'])) {
				$savepoint_id = intval($_GET['savepoint_id']);
				$sql = "SELECT svp_id, svp_uid FROM babel_savepoint WHERE svp_id = {$savepoint_id}";
				$rs = mysql_query($sql, $this->db);
				if (mysql_num_rows($rs) == 1) {
					
					$S = mysql_fetch_object($rs);
					mysql_free_result($rs);
					if ($S->svp_uid == $this->User->usr_id) {
						$S = null;
						$sql = "DELETE FROM babel_savepoint WHERE svp_id = {$savepoint_id} LIMIT 1";
						
						mysql_query($sql, $this->db);
						if (mysql_affected_rows($this->db)) {
							return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(6));
						} else {
							return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(7));
						}
					} else {
						$S = null;
						return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(5));
					}
				} else {
					mysql_free_result($rs);
					return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(4));
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetUserOwnHome(4));
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetUserOwnHome()));
		}
	}
	
	public function vxRecvZENProject() {
		if ($this->User->vxIsLogin()) {
			if (isset($_POST['zpr_title'])) {
				$zpr_title = make_single_safe($_POST['zpr_title']);
				if (mb_strlen($zpr_title, 'UTF-8') > 80) {
					$_SESSION['babel_zen_message'] = '项目标题太长了';
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				} else {
					if (mb_strlen($zpr_title, 'UTF-8') == 0) {
						$_SESSION['babel_zen_message'] = '你忘记填写新项目的标题了';
						return $this->URL->vxToRedirect($this->URL->vxGetZEN());
					} else {
						$sql = "SELECT COUNT(*) FROM babel_zen_project WHERE zpr_uid = {$this->User->usr_id}";
						$rs = mysql_query($sql, $this->db);
						$count = mysql_result($rs, 0, 0);
						mysql_free_result($rs);
						if ($count > (BABEL_ZEN_PROJECT_LIMIT - 1)) {
							$_SESSION['babel_zen_message'] = '目前我们的系统只能支持每个会员创建最多 ' . BABEL_ZEN_PROJECT_LIMIT . ' 个项目，我们正在积极地扩展系统的能力，以支持存储更多的项目';
						} else {
							if (get_magic_quotes_gpc()) {
								$zpr_title = stripslashes($zpr_title);
							}
							$zpr_title = mysql_real_escape_string($zpr_title, $this->db);
							$t = time();
							$sql = "INSERT INTO babel_zen_project(zpr_uid, zpr_private, zpr_title, zpr_progress, zpr_created, zpr_lastupdated, zpr_lasttouched, zpr_completed) VALUES({$this->User->usr_id}, 0, '{$zpr_title}', 0, {$t}, {$t}, 0, 0)";
							mysql_query($sql, $this->db) or die(mysql_error());
							if (mysql_affected_rows($this->db) == 1) {
								$_SESSION['babel_zen_message'] = '新项目添加成功';
							} else {
								$_SESSION['babel_zen_message'] = '新项目添加失败';
							}
						}
						return $this->URL->vxToRedirect($this->URL->vxGetZEN());
					}
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetZEN());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
		}
	}
	
	public function vxEraseZENProject() {
		if (isset($_GET['zen_project_id'])) {
			$zen_project_id = intval($_GET['zen_project_id']);
			$sql = "SELECT zpr_id, zpr_uid FROM babel_zen_project WHERE zpr_id = {$zen_project_id}";
			$rs = mysql_query($sql, $this->db);
			if (!$Project = mysql_fetch_object($rs)) {
				$zen_project_id = 0;
			}
		} else {
			$zen_project_id = 0;
		}
		if ($this->User->vxIsLogin()) {
			if ($zen_project_id != 0) {
				if ($Project->zpr_uid == $this->User->usr_id) {
					$sql = "DELETE FROM babel_zen_project WHERE zpr_id = {$zen_project_id}";
					mysql_query($sql, $this->db);
					if (mysql_affected_rows($this->db) == 1) {
						$sql = "DELETE FROM babel_zen_task WHERE zta_pid = {$zen_project_id}";
						mysql_query($sql, $this->db);
						$_SESSION['babel_zen_message'] = '项目删除成功';
					} else {
						$_SESSION['babel_zen_message'] = '项目删除失败';
					}
				} else {
					$_SESSION['babel_zen_message'] = '你不能也无法删除别人的项目';
				}
			} else {
				$_SESSION['babel_zen_message'] = '你要删除的项目不存在';
			}
			return $this->URL->vxToRedirect($this->URL->vxGetZEN());
		} else {
			if ($zen_project_id != 0) {
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetEraseZENProject($zen_project_id)));
			} else {
				$_SESSION['babel_zen_message'] = '你要删除的项目不存在';
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
			}
		}
	}
	
	public function vxRecvZENTask() {
		if ($this->User->vxIsLogin()) {
			if (isset($_POST['zta_title']) && isset($_GET['zen_project_id'])) {
				$zta_title = make_single_safe($_POST['zta_title']);
				$zen_project_id = intval($_GET['zen_project_id']);
				
				$sql = "SELECT zpr_id FROM babel_zen_project WHERE zpr_id = {$zen_project_id} AND zpr_uid = {$this->User->usr_id}";
				$rs = mysql_query($sql);
				if (mysql_num_rows($rs) == 1) {
					mysql_free_result($rs);
					if (mb_strlen($zta_title, 'UTF-8') > 80) {
						$_SESSION['babel_zen_message'] = '任务标题太长了';
						return $this->URL->vxToRedirect($this->URL->vxGetZEN());
					} else {
						if (mb_strlen($zta_title, 'UTF-8') == 0) {
							$_SESSION['babel_zen_message'] = '你忘记填写新任务的标题了';
							return $this->URL->vxToRedirect($this->URL->vxGetZEN());
						} else {
							$sql = "SELECT COUNT(*) FROM babel_zen_task WHERE zta_pid = {$zen_project_id} AND zta_progress = 0";
							$rs = mysql_query($sql, $this->db);
							$count = mysql_result($rs, 0, 0);
							mysql_free_result($rs);
							if ($count > (BABEL_ZEN_TASK_LIMIT - 1)) {
								$_SESSION['babel_zen_message'] = '目前我们的系统只能支持每个会员为单独一个项目创建最多 ' . BABEL_ZEN_TASK_LIMIT . ' 个待办任务，我们正在积极地扩展系统的能力，以支持存储更多的任务';
							} else {
								if (get_magic_quotes_gpc()) {
									$zta_title = stripslashes($zta_title);
								}
								$zta_title = mysql_real_escape_string($zta_title, $this->db);
								$t = time();
								$sql = "INSERT INTO babel_zen_task(zta_uid, zta_pid, zta_title, zta_progress, zta_created, zta_lastupdated, zta_completed) VALUES({$this->User->usr_id}, {$zen_project_id}, '{$zta_title}', 0, {$t}, {$t}, 0)";
								mysql_query($sql, $this->db);
								if (mysql_affected_rows($this->db) == 1) {
									$sql = "UPDATE babel_zen_project SET zpr_lasttouched = {$t}, zpr_progress = 0 WHERE zpr_id = {$zen_project_id}";
									mysql_unbuffered_query($sql, $this->db);
									$_SESSION['babel_zen_message'] = '新任务添加成功';
								} else {
									$_SESSION['babel_zen_message'] = '新任务添加失败';
								}
							}
							return $this->URL->vxToRedirect($this->URL->vxGetZEN());
						}
					}
				} else {
					mysql_free_result($rs);
					$_SESSION['babel_zen_message'] = '指定的项目不存在，无法添加任务';
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetZEN());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
		}
	}
	
	public function vxChangeZENTaskDone() {
		if ($this->User->vxIsLogin()) {
			if (isset($_GET['zen_task_id'])) {
				$zen_task_id = intval($_GET['zen_task_id']);
				$sql = "SELECT zta_id, zta_pid FROM babel_zen_task WHERE zta_id = {$zen_task_id} AND zta_uid = {$this->User->usr_id}";
				$rs = mysql_query($sql);
				if (mysql_num_rows($rs) == 1) {
					$Task = mysql_fetch_object($rs);
					mysql_free_result($rs);
					$t = time();
					$sql = "UPDATE babel_zen_task SET zta_progress = 1, zta_completed = {$t} WHERE zta_id = {$zen_task_id}";
					mysql_unbuffered_query($sql);
					$_SESSION['babel_zen_message'] = '一个任务已经完成！';
					$sql = "SELECT zta_id FROM babel_zen_task WHERE zta_pid = {$Task->zta_pid} AND zta_progress = 0";
					$rs = mysql_query($sql, $this->db);
					if (mysql_num_rows($rs) == 0) {
						mysql_free_result($rs);
						$sql = "UPDATE babel_zen_project SET zpr_progress = 1, zpr_completed = {$t} WHERE zpr_id = {$Task->zta_pid} LIMIT 1";
						mysql_unbuffered_query($sql);
						$_SESSION['babel_zen_message'] = '恭喜，你完成了一个项目！';
					} else {
						mysql_free_result($rs);
						$sql = "UPDATE babel_zen_project SET zpr_lasttouched = {$t} WHERE zpr_id = {$Task->zta_pid} LIMIT 1";
						mysql_unbuffered_query($sql);
					}
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				} else {
					mysql_free_result($rs);
					$_SESSION['babel_zen_message'] = '指定的任务不存在，无法改变任务进度';
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetZEN());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
		}
	}
	
	public function vxChangeZENProjectPermission() {
		if ($this->User->vxIsLogin()) {
			if (isset($_GET['zen_project_id'])) {
				$_zen_project_id = intval($_GET['zen_project_id']);
				$sql = "SELECT zpr_id, zpr_uid, zpr_private FROM babel_zen_project WHERE zpr_id = {$_zen_project_id} AND zpr_uid = {$this->User->usr_id}";
				$rs = mysql_query($sql);
				if (mysql_num_rows($rs) == 1) {
					$Project = mysql_fetch_object($rs);
					mysql_free_result($rs);
					$_t = time();
					if ($Project->zpr_private == 1) {
						$sql = "UPDATE babel_zen_project SET zpr_private = 0 WHERE zpr_id = {$_zen_project_id} LIMIT 1";
						$_SESSION['babel_zen_message'] = '项目已经设置为公开';
					} else {
						$sql = "UPDATE babel_zen_project SET zpr_private = 1 WHERE zpr_id = {$_zen_project_id} LIMIT 1";
						$_SESSION['babel_zen_message'] = '项目已经设置为隐藏';
					}
					mysql_unbuffered_query($sql);
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				} else {
					mysql_free_result($rs);
					$_SESSION['babel_zen_message'] = '指定的项目不存在，无法改变';
					return $this->URL->vxToRedirect($this->URL->vxGetZEN());
				}
			} else {
				return $this->URL->vxToRedirect($this->URL->vxGetZEN());
			}
		} else {
			return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
		}
	}
	
	public function vxEraseZENTask() {
		if (isset($_GET['zen_task_id'])) {
			
			$zen_task_id = intval($_GET['zen_task_id']);
			$sql = "SELECT zta_id, zta_pid, zta_uid FROM babel_zen_task WHERE zta_id = {$zen_task_id}";
			
			$rs = mysql_query($sql, $this->db);
			if (!$Task = mysql_fetch_object($rs)) {
				$zen_task_id = 0;
			}
		} else {
			$zen_task_id = 0;
		}
		if ($this->User->vxIsLogin()) {
			if ($zen_task_id != 0) {
				if ($Task->zta_uid == $this->User->usr_id) {
					$sql = "DELETE FROM babel_zen_task WHERE zta_id = {$zen_task_id}";
					mysql_query($sql, $this->db);
					if (mysql_affected_rows($this->db) == 1) {
						$_SESSION['babel_zen_message'] = '任务删除成功';
					} else {
						$_SESSION['babel_zen_message'] = '任务删除失败';
					}
					$sql = "SELECT zta_id FROM babel_zen_task WHERE zta_pid = {$Task->zta_pid} AND zta_progress = 0";
					$rs_todo = mysql_query($sql, $this->db);
					$sql = "SELECT zta_id FROM babel_zen_task WHERE zta_pid = {$Task->zta_pid} AND zta_progress = 1";
					$rs_done = mysql_query($sql, $this->db);
					if ((mysql_num_rows($rs_todo) == 0) && (mysql_num_rows($rs_done) > 0)) {
						$sql = "UPDATE babel_zen_project SET zpr_progress = 1 WHERE zpr_id = {$Task->zta_pid}";
						mysql_unbuffered_query($sql);
					}
					mysql_free_result($rs_todo);
					mysql_free_result($rs_done);
				} else {
					$_SESSION['babel_zen_message'] = '你不能也无法删除别人的任务';
				}
			} else {
				$_SESSION['babel_zen_message'] = '你要删除的任务不存在';
			}
			return $this->URL->vxToRedirect($this->URL->vxGetZEN());
		} else {
			if ($zen_task_id != 0) {
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetEraseZENProject($zen_project_id)));
			} else {
				$_SESSION['babel_zen_message'] = '你要删除的任务不存在';
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
			}
		}
	}
	
	public function vxUndoneZENTask() {
		if (isset($_GET['zen_task_id'])) {
			
			$zen_task_id = intval($_GET['zen_task_id']);
			$sql = "SELECT zta_id, zta_pid, zta_uid FROM babel_zen_task WHERE zta_id = {$zen_task_id}";
			
			$rs = mysql_query($sql, $this->db);
			if (!$Task = mysql_fetch_object($rs)) {
				$zen_task_id = 0;
			}
		} else {
			$zen_task_id = 0;
		}
		if ($this->User->vxIsLogin()) {
			if ($zen_task_id != 0) {
				if ($Task->zta_uid == $this->User->usr_id) {
					$sql = "UPDATE babel_zen_task SET zta_progress = 0 WHERE zta_id = {$zen_task_id}";
					mysql_query($sql, $this->db);
					if (mysql_affected_rows($this->db) == 1) {
						$_SESSION['babel_zen_message'] = '任务回到待办状态';
					} else {
						$_SESSION['babel_zen_message'] = '任务状态没有改变';
					}
					$sql = "UPDATE babel_zen_project SET zpr_progress = 0 WHERE zpr_id = {$Task->zta_pid}";
					mysql_unbuffered_query($sql, $this->db);
				} else {
					$_SESSION['babel_zen_message'] = '你不能也无法改变别人的任务进度';
				}
			} else {
				$_SESSION['babel_zen_message'] = '你要改变的任务不存在';
			}
			return $this->URL->vxToRedirect($this->URL->vxGetZEN());
		} else {
			if ($zen_task_id != 0) {
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetEraseZENProject($zen_project_id)));
			} else {
				$_SESSION['babel_zen_message'] = '你要改变的任务不存在';
				return $this->URL->vxToRedirect($this->URL->vxGetLogin($this->URL->vxGetZEN()));
			}
		}
	}
	
	/* E public modules */
	
}

/* E Standalone class */
?>