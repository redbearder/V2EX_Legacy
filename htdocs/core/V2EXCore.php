<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/V2EXCore.php
*  Usage: V2EX Page Core Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*  
*  Subversion Keywords:
*
*  $Id: V2EXCore.php 510 2006-07-15 12:40:04Z livid $
*  $LastChangedDate: 2006-07-15 20:40:04 +0800 (Sat, 15 Jul 2006) $
*  $LastChangedRevision: 510 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/V2EXCore.php $
*/

if (V2EX_BABEL == 1) {
	/* The most important file */
	require('core/Settings.php');
	
	/* 3rdparty PEAR cores */
	ini_set('include_path', BABEL_PREFIX . '/libs/pear' . ':' . ini_get('include_path'));
	require_once('Cache/Lite.php');
	require_once('HTTP/Request.php');
	require_once('Crypt/Blowfish.php');
	require_once('Net/Dict.php');
	require_once('Mail.php');
	if (BABEL_DEBUG) {
		require_once('Benchmark/Timer.php');
	}
	
	/* 3rdparty Zend Framework cores */
	ini_set('include_path', BABEL_PREFIX . '/libs/zf/' . ZEND_FRAMEWORK_VERSION . ':' . ini_get('include_path'));
	require_once('Zend/Search/Lucene.php');
	
	/* 3rdparty cores */
	require(BABEL_PREFIX . '/libs/magpierss/rss_fetch.inc');
	require(BABEL_PREFIX . '/libs/smarty/libs/Smarty.class.php');
	require(BABEL_PREFIX . '/libs/kses/kses.php');
	
	/* built-in cores */
	require('core/Vocabularies.php');
	require('core/Utilities.php');
	require('core/AirmailCore.php');
	require('core/UserCore.php');
	require('core/NodeCore.php');
	require('core/TopicCore.php');
	require('core/ChannelCore.php');
	require('core/URLCore.php');
	require('core/FunCore.php');
	require('core/ImageCore.php');
	require('core/ValidatorCore.php');
} else {
	die('<strong>Project Babel</strong><br /><br />Made by V2EX | software for internet');
}

/* S Page class */

class Page {
	var $User;

	var $db;
	
	var $cs;
	var $cl;
	
	var $Validator;

	var $online_count;
	var $online_count_anon;
	var $online_count_reg;
	
	var $tpc_count;
	var $pst_count;
	var $fav_count;
	var $svp_count;
	
	var $p_msg_count;
	
	var $usr_share;
	
	/* S module: constructor and destructor */

	public function __construct() {
		if (BABEL_DEBUG) {
			$this->timer = new Benchmark_Timer();
			$this->timer->start();
		}

		check_env();
		
		if (@$this->db = mysql_connect(BABEL_DB_HOSTNAME . ':' . BABEL_DB_PORT, BABEL_DB_USERNAME, BABEL_DB_PASSWORD)) {
			mysql_select_db(BABEL_DB_SCHEMATA);
			mysql_query("SET NAMES utf8");
			mysql_query("SET CHARACTER SET utf8");
			mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");
			$rs = mysql_query('SELECT nod_id FROM babel_node WHERE nod_id = 1');
			if (@mysql_num_rows($rs) == 1) {
			} else {
				exception_message('world');
			}
		} else {
			exception_message('db');
		}		
		
		global $CACHE_LITE_OPTIONS_SHORT;
		$this->cs = new Cache_Lite($CACHE_LITE_OPTIONS_SHORT);
		global $CACHE_LITE_OPTIONS_LONG;
		$this->cl = new Cache_Lite($CACHE_LITE_OPTIONS_LONG);
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
		if ($count_a = $this->cs->get('count')) {
			$count_a = unserialize($count_a);
			$this->pst_count = $count_a['pst_count'];
			$this->tpc_count = $count_a['tpc_count'];
			$this->fav_count = $count_a['fav_count'];
			$this->svp_count = $count_a['svp_count'];
		} else {
			$sql = "SELECT COUNT(pst_id) FROM babel_post";
			$rs = mysql_query($sql, $this->db);
			$this->pst_count = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
		
			$sql = "SELECT COUNT(tpc_id) FROM babel_topic";
			$rs = mysql_query($sql, $this->db);
			$this->tpc_count = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			$sql = "SELECT COUNT(fav_id) FROM babel_favorite";
			$rs = mysql_query($sql, $this->db);
			$this->fav_count = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			$sql = "SELECT COUNT(svp_id) FROM babel_savepoint";
			$rs = mysql_query($sql, $this->db);
			$this->svp_count = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			$count_a = array();
			$count_a['pst_count'] = $this->pst_count;
			$count_a['tpc_count'] = $this->tpc_count;
			$count_a['fav_count'] = $this->fav_count;
			$count_a['svp_count'] = $this->svp_count;
			
			$this->cs->save(serialize($count_a), 'count');
		}
		if ($this->User->vxIsLogin()) {
			$sql = "SELECT COUNT(msg_id) FROM babel_message WHERE msg_rid = {$this->User->usr_id} AND msg_opened = 0";
			$rs = mysql_query($sql, $this->db);
			$this->p_msg_count = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
		}
		
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-control: no-cache, must-revalidate');
	}
	
	public function __destruct() {
		if (@$this->db) {
			mysql_close($this->db);
		}
		if (BABEL_DEBUG) {
			$this->timer->stop();
			echo('<div id="debug">');
			$this->timer->display();
			echo('</div>');
		}
	}
	
	/* E module: constructor and destructor */
	
	/* S public modules */

	/* S module: meta tags */
	
	public function vxMeta($msgMetaKeywords = Vocabulary::meta_keywords, $msgMetaDescription = Vocabulary::meta_description, $return = '') {
		echo('<meta http-equiv="content-type" content="text/html;charset=utf-8" />');
		echo('<meta http-equiv="cache-control" content="no-cache" />');
		echo('<meta name="keywords" content="' . $msgMetaKeywords . '" />');
		if (strlen($return) > 0) {
			echo('<meta http-equiv="refresh" content="3;URL=' . $return . '" />');
		}
	}
	
	/* E module: meta tags */
	
	/* S module: title tag */
	
	public function vxTitle($msgSiteTitle = '') {
		if ($msgSiteTitle != '') {
			$msgSiteTitle = $msgSiteTitle . ' - ' . Vocabulary::site_name;
		} else {
			$msgSiteTitle = Vocabulary::site_title;
		}
		echo('<title>' . $msgSiteTitle . '</title>');
	}
	
	/* E module: title tag */
	
	/* S module: body tag start */
	
	public function vxBodyStart() {
		echo('<body>');
	}
	
	/* E module: body tag end */
	
	/* S module: body tag end */
	
	public function vxBodyEnd() {
		echo('</body></html>');
	}
	
	/* E module: body tag end */
	
	/* S module: link and script tags */
	
	public function vxLink($feedURL = BABEL_FEED_URL) {
		echo('<link href="/favicon.ico" rel="shortcut icon" />');
		echo('<link rel="stylesheet" type="text/css" href="/css/themes/' . BABEL_THEME . '/css_babel.css" />');
		echo('<link rel="stylesheet" type="text/css" href="/css/themes/' . BABEL_THEME . '/css_extra.css" />');
		echo('<link rel="stylesheet" type="text/css" href="/css/themes/' . BABEL_THEME . '/css_zen.css" />');
		echo('<link rel="alternate" type="application/rss+xml" title="' . Vocabulary::site_name . ' RSS" href="' . $feedURL . '" />');
		echo('<script src="/js/babel.js" type="text/javascript"></script>');
		echo('<script src="/js/babel_zen.js" type="text/javascript"></script>');
	}
	
	/* E module: link and script tags */
	
	/* S module: page headers */
	
	public function vxHead($msgSiteTitle = '', $return = '', $feedURL = BABEL_FEED_URL) {
		echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">');
		echo('<head>');
		$this->vxMeta(Vocabulary::meta_keywords, Vocabulary::meta_description, $return);
		$this->vxTitle($msgSiteTitle);
		$this->vxLink($feedURL);
		echo('</head>');
	}
	
	/* E module: page headers */
	
	/* S module: div#top tag */
	
	public function vxTop($msgBanner = Vocabulary::site_banner, $keyword = '') {
		global $GOOGLE_AD_LEGAL;
		if ($this->User->usr_id == 0) {
			echo('<div id="top"><div id="top_left">' . $msgBanner . '</div><div id="top_right"><a name="top"></a><a href="/login.vx" class="top">登录</a>&nbsp;|&nbsp;<a href="/passwd.vx" class="top">找回密码</a>&nbsp;|&nbsp;<a href="/signup.html" class="top">注册</a></div>');
		} else {
			$sql = "SELECT COUNT(tpc_id) FROM babel_topic WHERE tpc_uid = {$this->User->usr_id}";
			$rs = mysql_query($sql, $this->db);
			if ($this->tpc_count == 0) {
				$this->usr_share = 0;
			} else {
				$this->usr_share = (mysql_result($rs, 0, 0) / $this->tpc_count) * 100;
			}
			mysql_free_result($rs);
			echo('<div id="top"><div id="top_left">' . $msgBanner . '</div><div id="top_right"><a name="top"></a>欢迎，<small><a href="/u/' . $this->User->usr_nick . '" class="top">' . $this->User->usr_nick . '</a></small>&nbsp;|&nbsp;<a href="/user/modify.vx" target="_self" class="top">修改信息</a>&nbsp;|&nbsp;<a href="/logout.vx" class="top" target="_self">登出</a><br /><br />你口袋里有' . $this->User->usr_money_a['str'] . '&nbsp;|&nbsp;<a href="/expense/view.vx" class="top" target="_self">消费记录</a><br /><br />');
			printf("你的主题数在社区所占比率 %.3f%%", $this->usr_share);
			echo('</div>');
		}
		echo('<div id="top_center">');
		if (GOOGLE_AD_ENABLED && $GOOGLE_AD_LEGAL) {
			echo('<iframe src="/cts/468x60.html" width="468" height="60" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe>');
		}
		echo('</div>');
		
		// echo('<div id="top_blimp"><img src="/img/blimp.png" border="0" /></div>');
		echo('</div>');
		echo('<div id="nav">');
		if ($nav = $this->cs->get('nav')) {
		} else {
			$sql = "SELECT nod_id, nod_name, nod_title from babel_node WHERE nod_level = 1 ORDER BY nod_weight DESC";
			$rs = mysql_query($sql);
			$nav = '';
			while ($Section = mysql_fetch_array($rs)) {
				$nav .= '<a href="/go/' . $Section['nod_name'] . '" class="nav">' . make_plaintext($Section['nod_title']) . '</a> ';
				$Section = null;
			}
			$this->cs->save($nav, 'nav');
			mysql_free_result($rs);
		}
		echo $nav;
		echo('.. <a href="http://www.v2ex.com/remix/babel" class="nav">开发者中心</a> <a href="http://www.v2ex.com/man.html" class="nav">参考文档藏经阁</a> .. <small><a href="/home/style/remix.html" class="nav">home/remix</a> <a href="/home/style/shuffle.html" class="nav">home/shuffle</a></small></div>');
		echo('<div id="search">');
		include(BABEL_PREFIX . '/res/google_search.php');
		echo('</div>');
	}
	
	/* E module: div#top tag */
	
	/* S module: div#bottom tag */
	
	public function vxBottom($msgCopyright = Vocabulary::site_copyright) {
		echo('<div id="bottom">' . $msgCopyright . '<br /><a href="/rules.vx">' . Vocabulary::term_rules . '</a> | <a href="/terms.vx">' . Vocabulary::term_terms . '</a> | <a href="/privacy.vx">' . Vocabulary::term_privacy . '</a> | <a href="/policies.vx">' . Vocabulary::term_policies . '</a><br /><small class="fade">$Id: V2EXCore.php 510 2006-07-15 12:40:04Z livid $</small><br /><br /></div>');
	}
	
	/* E module: div#bottom tag */
	
	/* S module: Menu block */
	
	public function vxMenu() {
		global $GOOGLE_AD_LEGAL;
		
		echo('<div id="menu" align="center">');
		if ($this->User->vxIsLogin()) {
			echo('<div class="menu_inner" align="left"><ul class="menu">');
		
			echo('<li><img src="' . CDN_IMG . 'pico_message.gif" align="absmiddle" />&nbsp;<a href="javascript:openMessage();" class="menu">我的消息');
			if ($this->p_msg_count > 0) {
				echo(' <small class="fade">(' . $this->p_msg_count . ')</small>');
			}
			echo('</a></li>');
			echo('<li><img src="' . CDN_IMG . 'pico_topics.gif" align="absmiddle" />&nbsp;<a href="/topic/archive/user/' . $this->User->usr_nick . '" class="menu">我创建的所有主题</a></li>');
			echo('<li><img src="' . CDN_IMG . 'pico_zen.gif" align="absmiddle">&nbsp;<a href="/zen/' . $this->User->usr_nick . '" class="menu">ZEN</a> <span class="tip_i"><small>alpha</small></span></li>');
			echo('<li><img src="' . CDN_IMG . 'pico_home.gif" align="absmiddle" />&nbsp;<a href="/u/' . $this->User->usr_nick . '" class="menu">我的 ' . Vocabulary::site_name . ' 主页</a></li>');
			echo('</ul></div>');
		}
		if ($this->User->vxIsLogin()) {
			if ($this->User->usr_portrait != '') {
				$fimg = '<img src="' . CDN_IMG . 'p/' . $this->User->usr_portrait . '_n.' . BABEL_PORTRAIT_EXT . '" align="absmiddle" class="portrait" />&nbsp;';
			} else {
				$fimg = '<img src="' . CDN_IMG . '/p_' . $this->User->usr_gender . '_n.gif" align="absmiddle" class="portrait" />&nbsp;';
			}
			$sql = "SELECT COUNT(*) FROM babel_favorite WHERE fav_uid = {$this->User->usr_id}";
			$rs = mysql_query($sql, $this->db);
			$my_fav_total = mysql_result($rs, 0, 0);
			mysql_free_result($rs);

			$sql = "SELECT fav_title, fav_res, fav_type FROM babel_favorite WHERE fav_uid = {$this->User->usr_id} AND fav_type IN (1,2) ORDER BY fav_created DESC";
			$rs = mysql_query($sql, $this->db);
			$my_fav_nodes = mysql_num_rows($rs);
			if ($my_fav_nodes > 0) {
				echo('<div class="menu_fav" align="left">');
				echo($fimg);
				echo('&nbsp;<a href="/topic/favorite.vx" class="menu">我的收藏夹</a><table cellpadding="0" cellspacing="0" border="0" class="fav">');
				while ($Fav = mysql_fetch_object($rs)) {
					switch ($Fav->fav_type) {
						case 1:
							echo('<tr><td><a href="/board/view/' . $Fav->fav_res . '.html"><img src="' . CDN_IMG . 'gt.gif" align="absmiddle" border="0" /></a>&nbsp;<a href="/board/view/' . $Fav->fav_res . '.html">' . make_plaintext($Fav->fav_title) . '</a></td></tr>');
							break;
						case 2:
							echo('<tr><td><a href="/channel/view/' . $Fav->fav_res . '.html"><img src="' . CDN_IMG . 'gt.gif" align="absmiddle" border="0" /></a>&nbsp;<a href="/channel/view/' . $Fav->fav_res . '.html">' . Channel::vxTrimKijijiTitle(make_plaintext($Fav->fav_title)) . '</a></td></tr>');
							break;
					}
					$Fav = null;
				}
				echo('</table></div>');
			} else {
				echo('<div class="menu_fav" align="left">');
				echo($fimg);
				echo('<a href="/topic/favorite.vx" class="menu">我的收藏夹</a></div>');
			}
			mysql_free_result($rs);
		}
		
		
		if ($this->User->vxIsLogin()) {
			
			$sql = "SELECT usr_id, usr_gender, usr_nick, usr_portrait FROM babel_user WHERE usr_id IN (SELECT frd_fid FROM babel_friend WHERE frd_uid = {$this->User->usr_id}) ORDER BY usr_nick";
			$rs = mysql_query($sql);
			
			if (mysql_num_rows($rs)) {
				echo('<div class="menu_inner" align="left">');
				echo($fimg);
				echo('&nbsp;我的朋友们');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="fav">');
				echo('<tr><td>');
				$i = 0;
				while ($Friend = mysql_fetch_object($rs)) {
					$i++;
					$img_p = $Friend->usr_portrait ? CDN_IMG . 'p/' . $Friend->usr_portrait . '_n.jpg' : CDN_IMG . 'p_' . $Friend->usr_gender . '_n.gif';
					echo ('<a href="/u/' . $Friend->usr_nick . '" class="var" title="' . $Friend->usr_nick . '"><img src="' . $img_p . '" align="absmiddle" class="mp" /></a>&nbsp;');
					if (($i % 5) == 0) {
						echo('<br />');
					}
				}
				echo('</td></tr>');
				echo('</table>');
				echo('</div>');
			}
		}
		
		echo('<div class="menu_inner" align="left"><ul class="menu">');
		echo('<li><img src="' . CDN_IMG . 'pico_search.gif" align="absmiddle" />&nbsp;<a href="/search.vx" class="menu">搜索</a></li>');
		echo('<li><img src="/img/pico_feed.gif" align="absmiddle" />&nbsp;<a href="' . BABEL_FEED_URL . '" class="menu" target="_blank">RSS 2.0 聚合</a></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_fresh.gif" align="absmiddle" />&nbsp;<a href="/topic/fresh.html" class="menu">最新未回复主题</a></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_top.gif" align="absmiddle" />&nbsp;<a href="/topic/top.html" class="menu">' . Vocabulary::term_toptopic . '</a></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_user.gif" align="absmiddle" />&nbsp;最新注册会员<ul class="items">');
		$sql = 'SELECT usr_id, usr_nick, usr_gender, usr_portrait, usr_created FROM babel_user ORDER BY usr_created DESC LIMIT 5';
		$rs = mysql_query($sql, $this->db);
		$c = '';
		while ($User = mysql_fetch_object($rs)) {
			$img_p = $User->usr_portrait ? '/img/p/' . $User->usr_portrait . '_n.jpg' : CDN_IMG . 'p_' . $User->usr_gender . '_n.gif';
			$c = $c . '<li><a href="/u/' . $User->usr_nick . '"><img src="' . $img_p . '" align="absmiddle" border="0" class="portrait" /> ' . $User->usr_nick . '</a>&nbsp;<small class="fade">' . make_desc_time($User->usr_created) . '</small></li>';
		}
		mysql_free_result($rs);
		echo $c;	
		echo('</ul></li>');
		$sql = "SELECT onl_hash FROM babel_online WHERE onl_nick = ''";
		$rs_a = mysql_query($sql, $this->db);
		$sql = "SELECT onl_hash, onl_nick FROM babel_online WHERE onl_nick != ''";
		$rs_b = mysql_query($sql, $this->db);
		$this->online_count_anon = mysql_num_rows($rs_a);
		$this->online_count_reg = mysql_num_rows($rs_b);
		mysql_free_result($rs_a);
		mysql_free_result($rs_b);
		$this->online_count = $this->online_count_anon + $this->online_count_reg;
		echo('<li><img src="' . CDN_IMG . 'pico_online.gif" align="absmiddle" />&nbsp;<a href="/online/view.vx" class="menu">在线会员总数 <small>' . $this->online_count . '</small></a><ul class="items">');
		echo('<li>游客 <small>' . $this->online_count_anon . '</small></li>');
		echo('<li>注册会员 <small>' . $this->online_count_reg . '</small></li></ul></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_web.gif" align="absmiddle" />&nbsp;友情链接<ul class="items">');
		$x = simplexml_load_file(BABEL_PREFIX . '/res/links.xml');
		foreach ($x->xpath('//link') as $link) {
			echo '<li><a href="' . $link->url . '" target="_blank" class="menu">' . $link->name . '</a></li>';
		}
		echo('</ul>');
		$sql = "SELECT COUNT(usr_id) FROM babel_user";
		$rs = mysql_query($sql, $this->db);
		$usr_count = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		echo('<li><img src="' . CDN_IMG . 'pico_tuser.gif" align="absmiddle" />&nbsp;注册会员总数 <small>' . $usr_count . '</small><ul class="items">');
		echo('<li>讨论 <small>' . ($this->tpc_count + $this->pst_count) . '</small></li>');
		echo('<li>收藏 <small>' . $this->fav_count . '</small></li>');
		echo('<li>据点 <small>' . $this->svp_count . '</small></li>');
		echo('</ul></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_exec.gif" align="absmiddle" />&nbsp;<a href="/remix/babel" class="menu" target="_self"><small>Developer Zone</small></a></li>');
		echo('<li><img src="' . CDN_IMG . 'pico_exec.gif" align="absmiddle" />&nbsp;<small><a href="http://technorati.com/claim/5qwbf37cs2" class="menu" rel="me">Technorati Profile</a></small></li>');
		if ($this->User->usr_id == 1) {
			echo('<li><img src="' . CDN_IMG . 'pico_exec.gif" align="absmiddle" />&nbsp;<a href="/status.vx" class="menu" target="_self"><small>System Status</small></a></li>');
		}
		echo('</ul><br />');
		if (GOOGLE_AD_ENABLED && $GOOGLE_AD_LEGAL) {
			echo('<iframe src="/cts/110x32.html" width="110" height="32" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe><br /><br />');
			echo('<iframe src="/cts/120x240.html" width="120" height="240" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe>');		
		}
		echo('</div>');
		
		echo('<script language="javascript" src="/js/awstats_misc_tracker.js" type="text/javascript"></script>
<noscript><img src="/js/awstats_misc_tracker.js?nojs=y" height="0" width="0" border="0" style="display: none" alt="Made By Livid" /></noscript>');
		echo('</div>');
	}
	
	/* E module: Menu block */
	
	/* S module: Main Container block logic */
	
	public function vxContainer($module, $options = array()) {
		echo('<div id="wrap">');
		switch ($module) {
			default:
			case 'home':
				$this->vxSidebar($show = false);
				$this->vxMenu();
				$this->vxHome($options);
				break;
				
			case 'search':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxSearchSubstance();
				break;
				
			case 'denied':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxDenied();
				break;

			case 'login':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxLogin($options);
				break;
				
			case 'logout':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxLogout();
				break;
				
			case 'passwd':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxPasswd($options);
				break;
			
			case 'status':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxStatus();
				break;
			
			case 'jobs':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxJobs();
				break;
				
			case 'rules':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxRules();
				break;
			
			case 'terms':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTerms();
				break;

			case 'privacy':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxPrivacy();
				break;

			case 'policies':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxPolicies();
				break;
				
			case 'out_of_money':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxSorry('money');
				break;

			case 'signup':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxSignup();
				break;

			case 'user_home':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxUserHome($options);
				break;

			case 'user_create':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxUserCreate($options);
				break;
				
			case 'user_modify':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxUserModify();
				break;
				
			case 'user_update':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxUserUpdate($options);
				break;
				
			case 'user_topics':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxUserTopics($options);
				break;
				
			case 'topic_top':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicTop();
				break;

			case 'topic_fresh':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicFresh();
				break;
				
			case 'topic_favorite':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicFavorite();
				break;
				
			case 'channel_view':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxChannelView($options);
				break;

			case 'board_view':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxBoardView($options['board_id']);
				break;
				
			case 'topic_new':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicNew($options);
				break;
				
			case 'topic_create':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicCreate($options);
				break;
			
			case 'topic_modify':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicModify($options);
				break;
				
			case 'topic_update':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxTopicUpdate($options);
				break;
				
			case 'post_create':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxPostCreate($options);
				break;

			case 'topic_view':
				$this->vxSidebar();
				$this->vxMenu(array('links' => false));
				$this->vxTopicView($options['topic_id']);
				break;
				
			case 'topic_archive_user':
				$this->vxSidebar();
				$this->vxMenu(array('links' => false));
				$this->vxTopicArchiveUser($options);
				break;
				
			case 'section_view':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxSectionView($options['section_id']);
				break;
				
			case 'expense_view':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxExpenseView();
				break;
				
			case 'online_view':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxOnlineView();
				break;
			
			case 'mobile':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxMobile();
				break;
				
			case 'man':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxMan();
				break;
				
			case 'zen':
				$this->vxSidebar();
				$this->vxMenu();
				$this->vxZen($options);
		}
		echo('</div>');
	}
	
	/* E module: Main Container block logic */
	
	/* S module: div#sidebar tag */
	
	public function vxSidebar($show = true) {
		echo('<div id="sidebar"></div>');
	}
	
	/* E module: div#sidebar tag */
	
	/* S module: Home bundle */
	
	public function vxHomeBundle($style) {
		$this->vxHead();
		$this->vxBodyStart();
		$this->vxTop();
		$this->vxContainer('home', $options = $style);
	}
	
	/* E module: Home bundle */

	/* S module: Home block */
	
	public function vxHome($style) {
		$o = '<div id="main">';

		$o = $o . '<div class="blank" align="left">';
		
		if ($this->User->vxIsLogin()) {
			$img_p = $this->User->usr_portrait ? CDN_IMG . 'p/' . $this->User->usr_portrait . '_n.jpg' : CDN_IMG . 'p_' . $this->User->usr_gender . '_n.gif';
			
			$o = $o . '<span class="text"><img src="' . $img_p . '" align="absmiddle" class="portrait" /> 欢迎，<strong>' . $this->User->usr_nick . '</strong>！';
			
			if ($url = $this->cs->get('fsu_' . $this->User->usr_id)) {
				$F = unserialize($url);
				$o .= (rand(0, 1) == 1) ? '<span class="tip">或许你会对来自朋友 [ <small><a href="/u/' . $F['usr_nick'] . '">' . $F['usr_nick'] . '</a></small> ] 的 [ <small><a href="http://' . $F['svp_url'] . '" target="_blank" re="nofollow external">http://' . $F['svp_url'] . '</a></small> ] 感兴趣吧？</span>' : '';
			} else {
				$sql = "SELECT usr_nick, svp_url FROM babel_user, babel_savepoint WHERE usr_id = svp_uid AND svp_uid IN (SELECT frd_fid FROM babel_friend WHERE frd_uid = {$this->User->usr_id}) ORDER BY rand() LIMIT 1";
				$rs = mysql_query($sql);
				if ($F = mysql_fetch_array($rs)) {
					mysql_free_result($rs);
					$this->cs->save(serialize($F), 'fsu_' . $this->User->usr_id);
					$o .= (rand(0, 1) == 1) ? '<span class="tip">或许你会对来自朋友 [ <small><a href="/u/' . $F['usr_nick'] . '">' . $F['usr_nick'] . '</a></small> ] 的 [ <small><a href="http://' . $F['svp_url'] . '" target="_blank" re="nofollow external">http://' . $F['svp_url'] . '</a></small> ] 感兴趣吧？</span>' : '';
				} else {
					mysql_free_result($rs);
					$sql = "SELECT COUNT(*) FROM babel_savepoint WHERE svp_uid = {$this->User->usr_id}";
					$this->User->svp_count = mysql_result(mysql_query($sql), 0, 0);
					if ($this->User->svp_count == 0) {
						$o .= '<span class="tip_i">还没有添加自己的网上据点？现在 [ <a href="/u/' . $this->User->usr_nick . '#svp">添加一个</a> ] 吧，让更多人知道你的网站！</span>';
					}
				}
			}
			
			$o .= '</span>';
		} else {
			$o = $o . '<span class="text">欢迎来到 ' . Vocabulary::site_name . '！如果你已经注册，请<a href="/login.vx">登录</a>，如果还没有，' . Vocabulary::site_name . ' 欢迎你的<a href="/signup.html">加入</a> ...</span>';
		}

		switch ($style) {
			default:
			case 'remix':
				if (isset($_GET['go'])) {
					$go = strtolower($_GET['go']);
					$go = $this->Validator->vxExistBoardName($go);
					if ($go) {
						$o .= $this->vxHomeGenerateRemix($go);
					} else {
						$o .= $this->vxHomeGenerateRemix();
					}
				} else {
					$_SESSION['babel_home_style'] = 'remix';
					$go = false;
					$o .= $this->vxHomeGenerateRemix();
				}
				break;
			case 'shuffle':
				$go = false;
				$_seed = rand(1, 200);		
				$_SESSION['babel_home_style'] = 'shuffle';
				if ($_o = $this->cl->get('home_' . $_seed)) {
					$o = $o . $_o;
				} else {
					$_o = $this->vxHomeGenerateV2EX();
					$o = $o . $_o;
					$this->cl->save($_o, 'home_' . $_seed);
				}
				break;
		}
		
		$o = $o . '</div>';
		
		if (!$go) {
			$o .= $this->vxHomePortraits();
		}
		
		$o .= $this->vxHomeLatest();
		
		// latest favorite
		
		if (!$go) {
			$o = $o . '<div class="blank"><img src="' . CDN_IMG . 'pico_star.gif" class="portrait" align="absmiddle" /> 最过去的几分钟里，我们在 ' . Vocabulary::site_name . ' 收藏了 ...';
			
			if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
				$hack_width = 'width="100%" ';
			} else {
				$hack_width = '';
			}
			$o = $o . '<table ' . $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="fav">';
			
			$sql = 'SELECT usr_id, usr_gender, usr_nick, usr_portrait, fav_id, fav_type, fav_title, fav_author, fav_res, fav_created FROM babel_favorite, babel_user WHERE fav_uid = usr_id ORDER BY fav_created DESC LIMIT 5';
			
			$rs = mysql_query($sql, $this->db);
			
			$items = array(0 => '主题', 1 => '讨论区', 2 => '频道');
			$items_p = array(0 => 'mico_topic.gif', 1 => 'mico_gear.gif', 2 => 'mico_news.gif');
			$items_n = array(0 => 'topic', 1 => 'board', 2 => 'channel');
	
			while ($Fav = mysql_fetch_object($rs)) {
				
				$img_p = $Fav->usr_portrait ? CDN_IMG . 'p/' . $Fav->usr_portrait . '_n.jpg' : CDN_IMG . 'p_' . $Fav->usr_gender . '_n.gif';
				
				$css_color = rand_color();
				
				$o = $o . '<tr><td align="left">&nbsp;<img src="' . $img_p . '" alt="' . $Fav->usr_nick . '" align="absmiddle" class="portrait" />&nbsp;<a href="/u/' . $Fav->usr_nick . '" class="t">' . make_plaintext($Fav->usr_nick) . '</a> 收藏了' . $items[$Fav->fav_type] . ': <img src="' . CDN_IMG . $items_p[$Fav->fav_type] . '" align="absmiddle" /> <a href="/' . $items_n[$Fav->fav_type] . '/view/' . $Fav->fav_res . '.html" style="color: ' . $css_color . ';" class="var">' . make_plaintext($Fav->fav_title) . '</a> <span class="tip_i">... ' . make_descriptive_time($Fav->fav_created) . '</span></td></tr>';
				
				$Fav = null;
			}
			
			mysql_free_result($rs);
			
			$o = $o . '</table>';
			
			$o = $o . '</div>';
			
			$o .= $this->vxHomeTools();
		}
		$o = $o . '</div>';
		
		echo $o;
		
	}
	
	/* E module: Home block */
	
	/* S module: Home Tools */
	
	private function vxHomeTools() {
		$o = '<div class="blank"><img src="/img/pico_tux.gif" class="portrait" align="absmiddle" />&nbsp;实用工具 <span class="tip_i">... <a href="http://www.v2ex.com/mobile.html">手机号码所在地查询</a> ... <a href="http://www.v2ex.com/man.html">参考文档藏经阁</a></span></div>';
		return $o;
	}
	
	/* E module: Home Tools */
	
	/* S module: Home Latest */
	
	private function vxHomeLatest() {
		$l = '<div class="blank"><img src="' . CDN_IMG . 'pico_fresh.gif" class="portrait" align="absmiddle" /> 所有讨论区的最新主题 ...';
		
		if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
			$hack_width = 'width="100%" ';
		} else {
			$hack_width = '';
		}
		$l = $l . '<table ' . $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="fav">';
		
		$sql = 'SELECT usr_id, usr_nick, usr_gender, usr_portrait, tpc_id, tpc_title, tpc_posts, tpc_created, nod_id, nod_title, nod_name FROM babel_user, babel_topic, babel_node WHERE tpc_uid = usr_id AND tpc_pid = nod_id ORDER BY tpc_created DESC LIMIT 9';
		
		$rs = mysql_query($sql, $this->db);
		
		while ($Fresh = mysql_fetch_object($rs)) {
			$img_p = $Fresh->usr_portrait ? CDN_IMG . 'p/' . $Fresh->usr_portrait . '_n.jpg' : CDN_IMG . 'p_' . $Fresh->usr_gender . '_n.gif';
			$css_color = rand_color();
			$l = $l . '<tr><td align="left">&nbsp;<img src="' . $img_p . '" alt="' . $Fresh->usr_nick . '" align="absmiddle" class="portrait" />&nbsp;<a href="/u/' . $Fresh->usr_nick . '" class="t">' . make_plaintext($Fresh->usr_nick) . '</a> 在 <a href="/go/' . $Fresh->nod_name . '">' . make_plaintext($Fresh->nod_title) . '</a> 发表了: <a href="/topic/view/' . $Fresh->tpc_id . '.html" style="color: ' . $css_color . ';" class="var">' . make_plaintext($Fresh->tpc_title) . '</a> <span class="tip_i">... ' . make_descriptive_time($Fresh->tpc_created);
			
			$_o = $Fresh->tpc_posts ? '，' . $Fresh->tpc_posts . ' 篇回复' : '，尚无回复';
			
			$l = $l . $_o;
			
			$l = $l . '</span></td></tr>';
		
			$Fresh = null;
		}
		
		mysql_free_result($rs);
		
		$l = $l . '</table>';
		$l = $l . '</div>';

		return $l;
	}
	
	
	/* E module: Home Latest */
	
	/* S module: Home Portraits */
	
	private function vxHomePortraits() {
		$o = '';
		
		$o .= '<div class="blank" align="left"><img src="' . CDN_IMG . 'pico_show.gif" class="portrait" align="absmiddle" /> 会员头像展示 ...';
		if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
			$hack_width = 'width="100%" ';
		} else {
			$hack_width = '';
		}
		$o .= '<table ' . $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="fav">';
		$o .= '<tr><td>';
		switch ($this->User->usr_width) {
			case 800:
			default:
				$p_count = 4;
				break;
			case 640:
				$p_count = 3;
				break;
			case 1024:
				$p_count = 7;
				break;
			case 1280:
			case 1400:
			case 1600:
			case 1920:
			case 2560:
				$p_count = 9;
				break;
		}
		$sql = "SELECT usr_id, usr_nick, usr_portrait FROM babel_user WHERE usr_portrait != '' AND usr_hits > 100 ORDER BY rand() LIMIT {$p_count}";
		$rs = mysql_query($sql);
		
		$i = 0;
		
		
		while ($User = mysql_fetch_object($rs)) {
			$i++;
			$img_p = $User->usr_portrait ? '/img/p/' . $User->usr_portrait . '.jpg' : '/img/p_' . $User->usr_gender . '.gif';
			$o .= '<a href="/u/' . $User->usr_nick . '" class="friend"><img src="' . $img_p . '" class="portrait" /><br />' . $User->usr_nick . '</a>';
		}
		
		mysql_free_result($rs);
		$o .= '</td></tr>';
		$o .= '</table>';
		$o .= '</div>';
		
		return $o;
	}
	
	/* E module: Home Portrait Show */
	
	/* S module: Home Generate logic for V2EX Remix */
	
	private function vxHomeGenerateRemix($go = false) {
		$o = '';
		
		$o = $o . '<table cellpadding="0" cellspacing="0" border="0" class="fav">';
		if ($go) {
			$o .= '<tr><td align="left" class="section_odd"><span class="text_large"><img src="' . CDN_IMG . 'ico_board.gif" align="absmiddle" class="home" /><a href="/">V2EX</a> / <a href="/go/' . $go->sect_name . '">' . $go->sect_title . '</a> / ' . $go->nod_title . '&nbsp;<a href="/go/' . $go->nod_name . '"><img src="/img/tico_rw.gif" border="0" align="absmiddle" /></a>&nbsp;&nbsp;</span><span class="tip_i">' . $go->nod_header . '</span><br />';
			
			$o .= $this->vxHomeSectionRemix($go->nod_id, $go->nod_level);
			
			$o .= '阅读讨论区 <a href="/go/' . $go->nod_name . '" class="t">' . $go->nod_title . '</a> 的全部主题 | <a href="/topic/new/' . $go->nod_id . '.vx" rel="nofollow" class="t">创建新主题</a> | 使用 <a href="/feed/board/' . $go->nod_name . '.rss" class="t">RSS</a> 订阅 | <a href="/go/' . $go->nod_name . '" class="var"><img src="/img/pico_rw.gif" align="absmiddle" border="0" /></a>&nbsp;<a href="/go/' . $go->nod_name . '" class="t">切换到正常模式</a>';
			$o .= '</td></tr>';
		} else {
			$sql = 'SELECT nod_id, nod_name, nod_title FROM babel_node WHERE nod_level = 1 ORDER BY nod_weight DESC, nod_id ASC';
			
			$rs = mysql_query($sql);
			
			$i = 0;
			while ($Node = mysql_fetch_object($rs)) {
				$i++;
				$class = 'section_odd';
			
				$o .= '<tr><td align="left" class="' . $class . '"><span class="text_large"><img src="' . CDN_IMG . 's/' . $Node->nod_name . '.gif" align="absmiddle" class="home" /><a href="/go/' . $Node->nod_name . '" target="_self" class="section">' . $Node->nod_title . '</a>&nbsp;|&nbsp;</span><span class="text">';
				
				$sql = "SELECT nod_id, nod_name, nod_title, nod_topics FROM babel_node WHERE nod_pid = {$Node->nod_id} ORDER BY nod_topics DESC LIMIT 6";
				
				$rs_boards = mysql_query($sql);
				
				while ($Board = mysql_fetch_object($rs_boards)) {
					$o .= '&nbsp;&nbsp;<a href="/remix/' . $Board->nod_name . '" class="g">' . $Board->nod_title . '</a>';
				}
				
				$o .= '</span><br />' . $this->vxHomeSectionRemix($Node->nod_id) . '</td></tr>';
				if ($i == 1) {
					$o .= '<tr><td align="center" class="' . $class . '"><iframe src="/cts/468x15.html" width="468" height="15" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe></td></tr>';
				}
			}
		}
		
		$o .= '</table>';
		return $o;
	}
	
	/* E module: Home Generate logic for V2EX Remix */
	
	/* S module: Home Generate logic for V2EX */
	
	private function vxHomeGenerateV2EX() {
		$o = '';
		
		$o = $o . '<table cellpadding="0" cellspacing="0" border="0" class="fav">';
		
		$sql = 'SELECT nod_id, nod_name, nod_title FROM babel_node WHERE nod_level = 1 ORDER BY nod_weight DESC, nod_id ASC';
		
		$rs = mysql_query($sql);
		
		$i = 0;
		while ($Node = mysql_fetch_object($rs)) {
			$i++;
			if (($i % 2) == 0) {
				$class = 'section_even';
			} else {
				$class = 'section_odd';
			}
		
			$o .= '<tr><td align="left" class="' . $class . '"><span class="text_large"><img src="' . CDN_IMG . 's/' . $Node->nod_name . '.gif" align="absmiddle" class="home" /><a href="/go/' . $Node->nod_name . '" target="_self" class="section">' . $Node->nod_title . '</a>&nbsp;|&nbsp;</span><span class="text">';
			
			$sql = "SELECT nod_id, nod_name, nod_title, nod_topics FROM babel_node WHERE nod_pid = {$Node->nod_id} ORDER BY nod_topics DESC LIMIT 6";
			
			$rs_boards = mysql_query($sql);
			
			while ($Board = mysql_fetch_object($rs_boards)) {
				$o .= '&nbsp;&nbsp;<a href="/go/' . $Board->nod_name . '" class="g">' . $Board->nod_title . '</a>';
			}
			
			$o .= '</span><br />' . $this->vxHomeSection($Node->nod_id, 40) . '</td></tr>';
		}
		
		$o .= '</table>';
		return $o;
	}
	
	/* E module: Home Generate logic for V2EX */
	
	/* S module: Search bundle */
	
	public function vxSearchBundle() {
		$this->vxHead($msgSiteTitle = Vocabulary::action_search);
		$this->vxBodyStart();
		$this->vxTop();
		$this->vxContainer('search');
	}
	
	/* E module: Search bundle */
	
	/* S module: Search Substance block */
	
	public function vxSearchSubstance() {
		$err = array();
		$err['too_common'] = 0;
		
		$stage = 0;
		
		$query_verified = array();
		$query_task = array();
		$query_common = array();
		
		$style_search_highlight = '<span class="text_matched">\1</span>';
		
		$stop_words = array('the', '的', '我');

		if (isset($_GET['q'])) {
			$query = trim($_GET['q']);
			if (strlen($query) > 0) {
				if (get_magic_quotes_gpc()) {
					$query = stripslashes($query);
				}
				$stage = 1;
			}
		}
		
		if ($stage == 1) {
			$query_hash = md5($query);
			$query_splitted = explode(' ', $query);
			foreach ($query_splitted as $query_keyword) {
				if (!in_array($query_keyword, $query_verified)) {
					$query_verified[] = $query_keyword;
					if (in_array($query_keyword, $stop_words)) {
						$query_common[] = $query_keyword;
					} else {
						$query_task[] = $query_keyword;
					}
				}
			}
			$count_verified = count($query_verified);
			$count_task = count($query_task);
			$count_common = count($query_common);
			
			if ($count_task > 0) {
				$stage = 2;
			} else {
				if ($count_common > 0) {
					$stage = 3;
				}
			}
		}
		
		if ($stage == 2) {
			if ($result_a = $this->cl->get('k_search_' . $query_hash)) {
				$time_start = microtime_float();
				$result_a = unserialize($result_a);
				$count_result = count($result_a);
			} else {
				$time_start = microtime_float();
				
				// get topics
				$i = 0;
				$sql = "SELECT DISTINCT tpc_id, tpc_title, tpc_description, tpc_content, tpc_uid, tpc_lasttouched, usr_nick FROM babel_topic, babel_post, babel_user WHERE (";
				foreach ($query_task as $task) {
					$task = mysql_real_escape_string($task, $this->db);
					$i++;
					if ($i == 1) {
						$sql = $sql . '(';
					} else {
						$sql = $sql . ' OR (';
					}
					$sql = $sql . "tpc_title LIKE '%{$task}%'"; 
					$sql = $sql . " OR tpc_description LIKE '%{$task}%'"; 
					$sql = $sql . " OR tpc_content LIKE '%{$task}%'";
					$sql = $sql . ')';
					$sql = $sql . ' OR (';
					$sql = $sql . "pst_content LIKE '%{$task}%'";
					$sql = $sql . ')';
				}
				$sql = $sql . ")";
				$sql = $sql . " AND (tpc_uid = usr_id AND tpc_id = pst_tid)";
				$sql = $sql . " ORDER BY tpc_created DESC";
				$rs = mysql_query($sql, $this->db);
				$count_matched = mysql_num_rows($rs);
			
				// get ads
				if (KIJIJI_LEGACY_API_SEARCH_ENABLED) {
					if ($x = $this->cl->get('k_search_ads_' . $query_hash)) {
						$x = simplexml_load_string($x);
						$count_ads = $x->Body->return_ad_count;
					} else {
						if (preg_match('/[a-z0-9]/i', $query)) {
							$count_ads = 0;
						} else {
							$req_kijiji =& new HTTP_Request("http://shanghai.kijiji.com.cn/classifieds/ClassiApiSearchAdExCommand");
							$req_kijiji_input = '<?xml version="1.0" encoding="UTF-8" ?><SOAP:Envelope xmlns:SOAP="http://www.w3.org/2003/05/soap-envelope" ><SOAP:Header ><m:command xmlns:m="http://www.kijiji.com/soap">search_ad_ex</m:command><m:version xmlns:m="http://www.kijiji.com/soap">1</m:version></SOAP:Header><SOAP:Body><m:search_options xmlns:m="http://www.kijiji.com/soap">					<sub_area_id></sub_area_id><neighborhood_id></neighborhood_id><date_duration>40</date_duration><category_id></category_id><load_image>true</load_image><keyword>' . $query . '</keyword><return_ad_count>100</return_ad_count></m:search_options></SOAP:Body></SOAP:Envelope>';
							
							$req_kijiji->setMethod(HTTP_REQUEST_METHOD_POST);
							$req_kijiji->addPostData("xml", $req_kijiji_input);
							if (!PEAR::isError($req_kijiji->sendRequest())) {
								$rt_kijiji = $req_kijiji->getResponseBody();
								$rt_kijiji = str_replace('SOAP:', '', $rt_kijiji);					
								$rt_kijiji = str_replace('m:', '', $rt_kijiji);
							} else {
								$rt_kijiji = '';
							}
							
							if ($rt_kijiji != '') {
								$this->cl->save($rt_kijiji, 'k_search_ads_' . $query_hash);
								$x = simplexml_load_string($rt_kijiji);
								$count_ads = $x->Body->return_ad_count;
							} else {
								$count_ads = 0;
							}
						}
					}
				} else {
					$count_ads = 0;
				}
				
				// total
				$count_result = $count_ads + $count_matched;
				
				// the remix
				
				if ($count_result > 0) {
					$result_a = array();
					
					// db
					$unique_a = array();
					if ($count_matched > 0) {
						while ($Topic = mysql_fetch_object($rs)) {
							$Result = null;
							$Result->title = $Topic->tpc_title;
							$Result->type = 0;
							$Result->author = $Topic->usr_nick;
							$Result->excerpt = make_excerpt_topic($Topic, $query_task, $style_search_highlight);
							$Result->url = '/topic/view/' . $Topic->tpc_id . '.html';
							$Result->timestamp = $Topic->tpc_lasttouched;
							$Result->uid = $Topic->tpc_uid;
							$Result->hash = sha1($Result->title . $Result->author . $Result->excerpt);
							if (!in_array($Result->hash, $unique_a)) {
								$result_a[$Result->timestamp] = $Result;
								$unique_a[$Result->timestamp] = $Result->hash;
							}
						}
						mysql_free_result($rs);
						
						krsort($result_a, SORT_NUMERIC);
					} else {
						mysql_free_result($rs);
					}
					// xml
					
					if ($count_ads > 0) {
						
						for ($i = 0; $i < $count_ads; $i++) {
							$Result = null;
							$Result->title = strval($x->Body->ad[$i]->title);
							
							$Result->type = 1;
							$Result->author = '客齐集';
							$Result->excerpt = make_excerpt_ad($x->Body->ad[$i]->description, $query_task, $style_search_highlight);
							$Result->url = strval($x->Body->ad[$i]->view_ad_url);
							$Result->timestamp = format_api_date($x->Body->ad[$i]->start_date);
							$Result->uid = 0;
							$Result->hash = sha1($Result->title . $Result->author . $Result->excerpt);
							if (isset($x->Body->ad[$i]->img_url[0])) {
								$_excerpt = '<img src="' . strval($x->Body->ad[$i]->img_url[0]) . '" width="75" height="75" class="thumbnail" align="left" />' . $Result->excerpt;
								$Result->excerpt = $_excerpt;
							}
							if (!in_array($Result->hash, $unique_a)) {
								$result_a[$Result->timestamp] = $Result;
								$unique_a[$Result->timestamp] = $Result->hash;
							}
						}
						
						krsort($result_a, SORT_NUMERIC);
					}
					$this->cl->save(serialize($result_a), 'k_search_' . $query_hash);
				}
			}

			// page
			
			$p = array();
			$p['base'] = '/q/' . implode('+', $query_task) .'/';
			$p['ext'] = '.html';
			$p['items'] = $count_result;
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
			
			$p['offset'] = ($p['cur'] - 1) * $p['size'];
			
			if ($count_result > $p['size']) {
				$result_b = array_slice($result_a, $p['offset'], $p['size'], true);
			} else {
				$result_b = $result_a;
			}
			
			$time_end = microtime_float();
			$time_elapsed = $time_end - $time_start;
		}
		echo('<div id="main"><div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_search . '</div><div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_search.gif" onload="document.getElementById('. "'k_search_q'" . ').focus()" class="home" align="absmiddle" />' . Vocabulary::action_search . '</span><form action="/search.php" method="get">');
		if ($stage == 2) {
			$query_return = make_single_return($query);
			echo('<input type="text" name="q" id="k_search_q" onmouseover="this.focus()" class="search" value="' . $query_return . '"/>');
		} else {
			echo('<input type="text" name="q" id="k_search_q" onmouseover="this.focus()" class="search" />');
		}
		switch ($stage) {
			case 2:
				printf("<br /><span class=\"tip\">搜索为你找到了 %d 条匹配“%s”的结果，耗时 %.3f 秒</span>", $count_result, make_plaintext(implode(' ', $query_verified)), $time_elapsed);
				break;
			case 3:
				echo('<br /><span class="tip">你所查询的关键字“' . implode(' ', $query_common) . '”太常见</span>');
				break;
			case 0:
			case 1:
			default:
				echo('<span class="tip"></span>');
				break;
		}
		echo('<br /><br /><input type="image" src="' . CDN_IMG . 'silver/btn_search.gif" /></form></div>');
		
		if (($stage == 2) && ($count_result > 0)) {
			echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
			if (DICT_API_ENABLED == 'yes') {
				if (preg_match('/[a-z0-9]/i', $query)) {
					$d = new Net_Dict;
					$d->setCache(true, 'file', array('cache_dir' => BABEL_PREFIX . '/cache/dict/'));
					$defs_a = $d->define($query, 'xdict');
					if (!PEAR::isError($defs_a)) {
						if (count($defs_a) > 0) {
							echo('<tr><td colspan="2" height="18" class="shead">&nbsp;');
							echo(format_def(mb_convert_encoding($defs_a[0]['definition'], 'UTF-8', 'GBK')));
							if (preg_match('/^[a-zA-Z]+$/', $query)) {
								echo('<span class="tip_i"><small> ... learn more on <a href="http://' . strtolower($query) . '.livid.cn/" target="_blank" class="t">http://' . strtolower($query) . '.livid.cn/</a></small></span>');
							}
							echo('</td></tr>');
						} else {
							$this->vxSearchSubstanceSpell($query, $d);
						}
					} else {
						$this->vxSearchSubstanceSpell($query, $d, 1);
					}
				}
			}
			if ($p['total'] > 1) {
				echo('<tr><td align="left" height="30" class="hf" colspan="2" style="border-bottom: 1px solid #CCC;">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			
			$j = 0;
			foreach ($result_b as $Result) {
				$j++;
				if ($j == 1) {
					echo('<tr><td colspan="2" height="10"></td></tr>');
				}
				if ($Result->type == 1) {
					$img = 'mico_ad.gif';
				} else {
					if ($Result->uid == $this->User->usr_id) {
						$img = 'star_active.png';
					} else {
						$img = 'mico_topic.gif';
					}
				}
				echo('<tr><td width="24" height="18" valign="top" align="center" class="star"><img src="' . CDN_IMG . $img . '" /></td>');
				if ($Result->type == 1) {
					$_target = '_blank';
				} else {
					$_target = '_self';
				}
				echo('<td height="18" class="star"><a href="' . $Result->url . '" class="blue" target="' . $_target . '">' . make_plaintext($Result->title) . '</a> - <a href="/u/' . make_plaintext($Result->author) . '">' . make_plaintext($Result->author) . '</a></td></tr>');
				if (strlen($Result->excerpt) > 0) {
					echo('<tr><td width="24"></td><td class="hf"><span class="excerpt">');
					echo ($Result->excerpt);
					echo('</span></td></tr>');
				}
				echo('<tr><td width="24"></td><td valign="top"><span class="tip"><span class="green">');
				if ($Result->type == 0) {
					echo($_SERVER['SERVER_NAME'] . $Result->url);
				} else {
					echo($Result->url);
				}
				echo(' - ' . date('Y年n月j日', $Result->timestamp) . '</span></td></tr>');
				echo('<tr><td colspan="2" height="10"></td></tr>');
			}
			if ($p['total'] > 1) {
				echo('<tr><td align="left" height="30" class="hf" colspan="2" style="border-top: 1px solid #CCC;">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			echo('</table>');
		} else {
			if (isset($query)) {
				if (DICT_API_ENABLED == 'yes') {
					if (preg_match('/[a-z0-9]/i', $query)) {
						$d = new Net_Dict;
						$d->setCache(true, 'file', array('cache_dir' => BABEL_PREFIX . '/cache/dict/'));
						$defs_a = @$d->define($query);
						if (!PEAR::isError($defs_a)) {
							if (count($defs_a) > 0) {
								echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
								echo('<tr><td class="shead">&nbsp;' . format_def(mb_convert_encoding($defs_a[0]['definition'], 'UTF-8', 'GBK')));
								if (preg_match('/^[a-zA-Z]+$/', $query)) {
									echo('<span class="tip_i"><small> ... learn more on <a href="http://' . strtolower($query) . '.livid.cn/" target="_blank" class="top">http://' . strtolower($query) . '.livid.cn/</a></small></span>');
								}
								echo('</td></tr>');
								echo('</table>');
							} else {
								$this->vxSearchSubstanceSpell($query, $d, 0);
							}
						} else {
							$this->vxSearchSubstanceSpell($query, $d, 0);
						}
					}
				}
			}
		}
		echo('</div>');
	}
	
	private function vxSearchSubstanceSpell($word, $d, $style = 1, $s = 'lev', $stop = 0) {
		$words_a = $d->match($word, $s);
		if (!PEAR::isError($words_a)) {
			if ($style == 0) {
				echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
				echo('<tr><td class="shead"><span class="tip">或许你要拼的单词是');
				$i = 0;
				foreach ($words_a as $w) {
					$i++;
					if ($i < 5) {
						$css_color = rand_color();
						if ($i == 1) {$sign = '&nbsp;&gt;&nbsp;';} else {$sign = '&nbsp;/&nbsp;';}
						echo($sign . '<a href="/q/' . $w['word'] . '" style="color: ' . $css_color . '" class="var">' . $w['word'] . '</a>');
					}
				}
				echo('</span></td></tr>');
				echo('</table>');
			} else {
				echo('<tr><td colspan="2" class="shead"><span class="tip">或许你要拼的单词是');
				$i = 0;
				foreach ($words_a as $w) {
					$i++;
					if ($i < 5) {
						$css_color = rand_color();
						if ($i == 1) {$sign = '&nbsp;&gt;&nbsp;';} else {$sign = '&nbsp;/&nbsp;';}
						echo($sign . '<a href="/q/' . $w['word'] . '" style="color: ' . $css_color . '" class="var">' . $w['word'] . '</a>');
					}
				}
				echo('</span></td></tr>');
			}
		} else {
			if ($stop != 1) {
				$this->vxSearchSubstanceSpell($word, $d, $style, 'soundex', 1);
			}
		}
	}
	
	/* E module: Search Substance block */
	
	/* S module: Denied bundle */
	
	public function vxDeniedBundle() {
		$this->vxHead($msgSiteTitle = Vocabulary::term_accessdenied);
		$this->vxBodyStart();
		$this->vxTop();
		$this->vxContainer('denied');
	}
	
	/* E module: Denied bundle */
	
	/* S module: Denied block */
	
	public function vxDenied() {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <strong>' . Vocabulary::term_accessdenied . '</strong></div>');
		echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_bomb.gif" align="absmiddle" class="home" />Access Denied</span><br />你在一个你不应该到达的地方，停止你的任何无意义的尝试吧<br /><br />我知道我正位于一个战场，因此我将会为一切的杀戮和战争做好准备</div>');
		echo('</div>');
	}
	
	/* E module: Denied block */
	
	/* S module: Section View block */
	
	public function vxSectionView($section_id) {
		global $GOOGLE_AD_LEGAL;
		$Node = new Node($section_id, $this->db);
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . $Node->nod_title . '</div>');
		echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 's/' . $Node->nod_name . '.gif" align="absmiddle" align="" class="ico" />' . $Node->nod_title . '</span></div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		echo('<tr><td width="360" align="left" class="hf" valign="top">' . $Node->nod_header . '</td><td align="right" class="hf" colspan="2"><a href="/topic/new/' . $Node->nod_id . '.vx" target="_self" class="img"><img src="' . CDN_IMG . 'silver/btn_topic_new.gif" alt="创建新主题" border="0" /></a></td></tr>');
		echo('<tr>');
		
		// The latest topics
		
		$sql = "SELECT nod_id FROM babel_node WHERE nod_sid = {$section_id}";
		$rs = mysql_query($sql, $this->db);
		$board_count = mysql_num_rows($rs);
		$board_ids = '';
		$i = 0;
		while ($Board = mysql_fetch_object($rs)) {
			$i++;
			if ($i == $board_count) {
				$board_ids = $board_ids . $Board->nod_id;
			} else {
				$board_ids = $board_ids . $Board->nod_id . ', ';
			}
		}
		mysql_free_result($rs);
		
		echo('<td align="left" valign="top" class="container">');
		echo('<table width="100%" cellpadding="0" cellspacing="0" border="0" class="drawer">');
		
		echo('<tr><td height="18" class="blue">最新主题 Top 30</td></tr>');
		$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created FROM babel_topic WHERE tpc_pid IN ({$board_ids}) AND tpc_flag IN (0, 2) ORDER BY tpc_lasttouched DESC LIMIT 30";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$i++;
			$css_font_size = $this->vxGetItemSize($Topic->tpc_posts);
			if ($Topic->tpc_posts > 3) {
				$css_color = rand_color();
			} else {
				$css_color = rand_gray(2, 4);
			}
			if ((time() - $Topic->tpc_created) < 43200) {
				$img_star = 'bunny.gif';
			} else {
				if ($Topic->tpc_uid == $this->User->usr_id) {
					$img_star = 'star_active.png';
				} else {
					$img_star = 'star_inactive.png';
				}
			}
			$feedback = '<small class="aqua">' . $Topic->tpc_hits . '</small>/<small class="fade">' . $Topic->tpc_posts . '</small>';
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><img src="' . CDN_IMG . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				echo('<span class="tip_i"><small class="aqua">... ' . $feedback . '</small></span>');
			} else {
				echo('<tr><td class="odd" height="20"><img src="' . CDN_IMG . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				echo('<span class="tip_i"><small class="aqua">... ' . $feedback . '</small></span>');
			}
			echo('</td></tr>');
		}
		mysql_free_result($rs);
		
		echo('<tr><td height="18" class="orange">最热主题 Top 10</td></tr>');
		$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts FROM babel_topic WHERE tpc_pid IN ({$board_ids}) AND tpc_flag IN (0, 2) ORDER BY tpc_posts DESC LIMIT 10";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$i++;
			$css_font_size = $this->vxGetItemSize($Topic->tpc_posts);
			if ($Topic->tpc_posts > 3) {
				$css_color = rand_color();
			} else {
				$css_color = rand_gray(2, 4);
			}
			if ($Topic->tpc_uid == $this->User->usr_id) {
				$img_star = 'star_active.png';
			} else {
				$img_star = 'star_inactive.png';
			}
			$feedback = '<small class="aqua">' . $Topic->tpc_hits . '</small>/<small class="fade">' . $Topic->tpc_posts . '</small>';
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><img src="' . CDN_IMG . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>');
				echo('<span class="tip_i"><small class="aqua">... ' . $feedback . '</small></span>');
			} else {
				echo('<tr><td class="odd" height="20"><img src="' . CDN_IMG . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>');
				echo('<span class="tip_i"><small class="aqua">... ' . $feedback . '</small></span>');
			}
			echo('</td></tr>');
		}
		mysql_free_result($rs);
		echo('</table></td>');
		// The best boards
		
		echo('<td width="25%" align="left" valign="top" class="container" style="border-left: 1px solid #CCC;"><table width="100%" cellpadding="0" cellspacing="0" border="0" class="drawer"><tr><td height="18" class="orange">热门讨论区</td></tr>');
		$sql = "SELECT nod_id, nod_name, nod_title, nod_topics FROM babel_node WHERE nod_sid = {$section_id} ORDER BY nod_topics DESC, nod_created ASC LIMIT 80";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Board = mysql_fetch_object($rs)) {
			$css_font_size = $this->vxGetMenuSize($Board->nod_topics);
			$css_color = rand_color();
			$i++;
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><a href="/go/' . $Board->nod_name . '" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" target="_self" class="var">' . $Board->nod_title . '</a>&nbsp;<small class="grey">... ' . $Board->nod_topics . '</small></td></tr>');
			} else {
				echo('<tr><td class="odd" height="20"><a href="/go/' . $Board->nod_name . '" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" target="_self" class="var">' . $Board->nod_title . '</a>&nbsp;<small class="grey">... ' . $Board->nod_topics . '</small></td></tr>');
			}
		}
		mysql_free_result($rs);
		echo('</table></td>');
		
		// Random boards
		
		echo('<td width="25%" align="left" valign="top" class="container" style="border-left: 1px solid #CCC;"><table width="100%" cellpadding="0" cellspacing="0" border="0" class="drawer"><tr><td height="18" class="apple">随机讨论区</td></tr>');
		$sql = "SELECT nod_id, nod_title, nod_name, nod_topics FROM babel_node WHERE nod_sid = {$section_id} ORDER BY rand() LIMIT 80";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Board = mysql_fetch_object($rs)) {
			$css_font_size = $this->vxGetMenuSize($Board->nod_topics);
			$css_color = rand_color();
			$i++;
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><a href="/go/' . $Board->nod_name . '" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var" target="_self">' . $Board->nod_title . '</a>&nbsp;<small class="grey">... ' . $Board->nod_topics . '</small></td></tr>');
			} else {
				echo('<tr><td class="odd" height="20"><a href="/go/' . $Board->nod_name . '" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var" target="_self">' . $Board->nod_title . '</a>&nbsp;<small class="grey">... ' . $Board->nod_topics . '</small></td></tr>');
			}
		}
		mysql_free_result($rs);
		echo('</table></td>');
		echo('</tr>');
		echo('<tr><td colspan="3" align="left" class="hf" valign="top">' . $Node->nod_footer . '</td></tr>');
		
		/* S ultimate cool flickr */
		
		if ($this->User->usr_id == 1) {
			$f = Image::vxFlickrBoardBlock($Node->nod_name, $this->User->usr_width, 3);
			echo $f;
			$this->cl->save($f, 'board_flickr_' . $Node->nod_name);
		} else {
			if ($f = $this->cl->get('board_flickr_' . $Node->nod_name)) {
				echo $f;
			} else {
				$f = Image::vxFlickrBoardBlock($Node->nod_name, $this->User->usr_width, 3);
				echo $f;
				$this->cl->save($f, 'board_flickr_' . $Node->nod_name);
			}
		}
		
		/* E ultimate cool flickr */
		
		if (GOOGLE_AD_ENABLED && $GOOGLE_AD_LEGAL && ($this->User->usr_width > 800)) {
			echo('<tr>');
			echo('<td align="center" class="odd" colspan="3" style="border-top: 1px solid #CCC; padding-top: 10px; padding-bottom: 10px;">');
			echo('<iframe src="/cts/728x90.html" width="728" height="90" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe>');
			echo('</td>');
			echo('</tr>');
		}
		
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Section View block */
	
	/* S module: Status block */
	
	public function vxStatus() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_status . '</div>');
		echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_tv.gif" align="absmiddle" class="home" />' . Vocabulary::term_status . '</span>');
		$rs = mysql_query('SHOW STATUS', $this->db);
		$status = array();
		while ($row = mysql_fetch_assoc($rs)) {
			$status[$row['Variable_name']] = $row['Value'];
		}
		mysql_free_result($rs);
		$rs = mysql_query('SHOW VARIABLES', $this->db);
		while ($row = mysql_fetch_assoc($rs)) {
			$status[$row['Variable_name']] = $row['Value'];
		}
		mysql_free_result($rs);

		if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
			$hack_width = 'width="100%" ';
		} else {
			$hack_width = '';
		}
		
		echo('<table ' . $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="fav">');
		
		echo('<tr><td colspan="2" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_db.gif" align="absmiddle" class="home" />数据库子系统 MySQL ' . mysql_get_server_info($this->db) . '</span></td></tr>');
		
		echo('<tr><td colspan="2" align="left"><span class="tip">数据库系统信息</span></td></tr>');
		
		echo('<tr><td width="150" align="right" class="section_even">服务器字符集</td><td class="section_even">' . $status['collation_server'] . '</td></tr>');
		echo('<tr><td width="150" align="right" class="section_odd">当前数据库字符集</td><td class="section_odd">' . $status['collation_database'] . '</td></tr>');
		echo('<tr><td width="150" align="right" class="section_even">运转时间</td><td class="section_even">' . $status['Uptime'] . ' 秒</td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="tip">性能数据</span></td></tr>');
		
		
		echo('<tr><td width="150" align="right" class="section_even">线程创建数量</td><td class="section_even">' . $status['Threads_created'] . '（每分钟 ');
		printf("%.2f", $status['Threads_created'] / ($status['Uptime'] / 60));
		echo('）</td></tr>');
		
		echo('<tr><td width="150" align="right" class="section_odd">已处理的查询数量</td><td class="section_odd">' . $status['Questions'] . '（每分钟 ');
		printf("%.2f", $status['Questions'] / ($status['Uptime'] / 60));
		echo('）</td></tr>');
		
		echo('<tr><td width="150" align="right" class="section_even">可用缓存内存</td><td class="section_even">' . $status['Qcache_free_memory'] . '</td></tr>');
		echo('<tr><td width="150" align="right" class="section_odd">缓存中的查询数据</td><td class="section_odd">' . $status['Qcache_queries_in_cache'] . '</td></tr>');
		
		echo('<tr><td width="150" align="right" class="section_even">插入缓存的查询数量</td><td class="section_even">' . $status['Qcache_inserts'] . '（每分钟 ');
		printf("%.2f", $status['Qcache_inserts'] / ($status['Uptime'] / 60));
		echo('）</td></tr>');
		
		echo('<tr><td width="150" align="right" class="section_odd">命中缓存的查询数量</td><td class="section_odd">' . $status['Qcache_hits'] . '（每分钟 ');
		printf("%.2f", $status['Qcache_hits'] / ($status['Uptime'] / 60));
		echo('）</td></tr>');

		echo('<tr><td width="150" align="right" class="section_even">无法缓存的查询数量</td><td class="section_even">' . $status['Qcache_not_cached'] . '（每分钟 ');
		printf("%.2f", $status['Qcache_not_cached'] / ($status['Uptime'] / 60));
		echo('）</td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="' . CDN_IMG . 'ico_mac.gif" align="absmiddle" class="home" />基础架构 ' . shell_exec('uname -s') . '</span></td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_even"><small><strong>OS</strong>: ' . shell_exec('uname -a') . '</small></td></tr>');
		echo('<tr><td colspan="2" align="left" class="section_odd"><small><strong>Machine Architecture</strong>: ' . shell_exec('uname -m') . '</small></td></tr>');
		
		echo('</table>');
		echo('</div>');
		echo('</div>');
	}
	
	/* E module: Status block */
	
	/* S module: Jobs block */
	
	public function vxJobs() {
		echo('<div id="main">');
		echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_hiring.gif" align="absmiddle" class="home" />Employment Opportunity</span><br />');
		echo("客齐集是全球电子商务领袖 eBay 于 2005 年初成立的全资子公司，中国区办公室设于上海。<br /><br />");
		echo('客齐集专注于创造一个氛围良好的网上社区，大家居住于这个社区中，互相帮助，免费发布与个人生活息息相关的个人广告，或者是寻求同伴和交流。客齐集为实现这一目标而默默创造着。<br /><br />');
		echo('<img src="' . CDN_IMG . 'open.gif" align="left" style="margin-right: 10px;" />');
		echo('在经过了一年多的发展之后，我们发现为了实现这一目标，我们需要更多的伙伴来加入我们。如果你是一位经验丰富的程序设计师，或者是极具艺术灵感的计算机美术设计师，同时认同我们的奋斗目标，则我们非常欢迎你的加入！<br /><br />');
		echo('以下是目前我们开放招聘的职位的描述及条件，如果你觉得自己能够胜任这份工作，则在每个职位的描述的末尾你可以看到一个电子邮件地址，你可以将你的简历及薪资要求发到那个电子邮件地址。在简历中请附上你的电话号码，如果我们觉得你确实适合某个职位，则我们将在你投简历之后的一个星期内用电话的方式通知你进行面试，感谢你的参与和配合。<br /><br />');
		echo('<span class="tip">特别提示 － 如果你是在客齐集社区中看到下面的职位描述而投的简历，请在邮件中特别注明，我们将优先处理来自客齐集社区的简历</span>');
		echo('</div>');
		$jobs_path = BABEL_PREFIX . '/jobs';
		$jobs = scandir(BABEL_PREFIX . '/jobs');
		foreach ($jobs as $job) {
			if (!in_array($job, array('.', '..', '.svn'))) {
				$x = simplexml_load_file($jobs_path . '/' . $job);
				echo('<div class="blank" align="left">');
				echo('<span class="text_large">' . $x->title . '</span><br /><br />');
				echo($x->description);
				echo('<br /><br />');
				echo('<ul class="menu">');
				echo('<li>Responsibilities Will Include:</li>');
				echo('<ul class="items">');
				foreach ($x->xpath('//rs') as $rs) {
					echo('<li>' . $rs . '</li>');
				}
				echo('</ul>');
				echo('<li>Job Requirements:</li>');
				echo('<ul class="items">');
				foreach ($x->xpath('//rq') as $rs) {
					echo('<li>' . $rs . '</li>');
				}
				echo('</ul>');
				echo('</ul>');
				echo('Please send your resume in English & Chinese to <a href="mailto:' . $x->mailto . '">' . $x->mailto . '</a>');
				if (strval($x->fulltime) == 'yes') {
					echo('<br /><br /><small><span class="tip">This is a full-time position in Shanghai. We do not have internships available.</span></small>');
				}
				echo('</div>');
			}
		}
	}
	
	/* E module: Jobs block */
	
	/* S module: Rules block */
	
	public function vxRules() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_rules . '</div>');
		echo('<div class="blank">');
		include(BABEL_PREFIX . '/res/rules.html');
		echo('</div>');
	}
	
	/* E module: Rules block */
	
	/* S module: Terms block */
	
	public function vxTerms() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_terms . '</div>');
		echo('<div class="blank">');
		include(BABEL_PREFIX . '/res/terms.html');
		echo('</div>');
	}
	
	/* E module: Terms block */
	
	/* S module: Privacy block */
	
	public function vxPrivacy() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_privacy . '</div>');
		echo('<div class="blank">');
		include(BABEL_PREFIX . '/res/privacy.html');
		echo('</div>');
	}
	
	/* E module: Privacy block */
	
	/* S module: Policies block */
	
	public function vxPolicies() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_policies . '</div>');
		echo('<div class="blank">');
		include(BABEL_PREFIX . '/res/policies.html');
		echo('</div>');
	}
	
	/* E module: Policies block */
	
	/* S module: Sorry block */
	
	public function vxSorry($what) {
		echo('<div id="main">');
		switch ($what) {
			default:
			case 'money':
				echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_out_of_money . '</div>');
				echo('<div class="blank">');
				include(BABEL_PREFIX . '/res/sorry_money.html');
				break;
		}
		echo('</div>');
	}
	
	/* E module: Sorry block */
	
	/* S module: Signup block */
	
	public function vxSignup() {
		Image::vxGenConfirmCode();
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_signup . '</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="' . CDN_IMG . 'ico_id.gif" align="absmiddle" class="home" />会员注册信息填写</span>');
		echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
		echo('<form action="/user/create.vx" method="post" id="usrNew">');
		echo('<tr><td width="200" align="right">电子邮件</td><td width="200" align="left"><input tabindex="1" type="text" maxlength="100" class="sl" name="usr_email" /></td>');
		echo('<td width="150" rowspan="8" valign="middle" align="right"><input tabindex="7" type="image" src="' . CDN_IMG . 'silver/btn_signup.gif" alt="' . Vocabulary::action_signup . '" tabindex="5" /></td></tr>');
		echo('<tr><td width="200" align="right">昵称</td><td align="left"><input tabindex="2" type="text" maxlength="20" class="sl" name="usr_nick" /></td></tr>');
		echo('<tr><td width="200" align="right">密码</td><td align="left"><input tabindex="3" type="password" maxlength="32" class="sl" name="usr_password" /></td></tr>');
		echo('<tr><td width="200" align="right">重复密码</td><td align="left"><input tabindex="4" type="password" maxlength="32" class="sl" name="usr_confirm" /></td></tr>');
		echo('<tr><td width="200" align="right" valign="top">性别</td><td align="left"><select tabindex="5" maxlength="20" size="6" name="usr_gender"><option value="0" selected="selected">未知</option><option value="1">男性</option><option value="2">女性</option><option value="5">女性改（变）为男性</option><option value="6">男性改（变）为女性</option><option value="9">未说明</option></select></td></tr>');
		echo('<tr><td width="200" align="right">确认码</td><td align="left"><input tabindex="6" type="password" maxlength="32" class="sl" name="c" /></td></tr><tr><td width="200" align="right"></td><td align="left"><div class="important"><img src="/c/' . rand(1111,9999) . '.' . rand(1111,9999) . '.png" /><ol class="items"><li>请按照上图输入确认码</li><li>确认码不区分大小写</li><li>确认码中不包含数字</li><li>专为人类设计</li></ul></div></td></tr>');
		echo('</form></table></div>');
		echo('<div class="blank"><img src="' . CDN_IMG . 'ico_tip.gif" align="absmiddle" class="ico" />点击“注册新会员”，即表示你同意我们的 [ <a href="/terms.vx">' . Vocabulary::term_terms . '</a> ] 和 [ <a href="/privacy.vx">' . Vocabulary::term_privacy . '</a> ]</div>');
		echo('</div>');
	}
	
	/* E module: Signup block */
	
	/* S module: Login block */
	
	public function vxLogin($rt) {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_login . '</div>');
		switch ($rt['target']) {
		
			// default
			
			default:
			case 'welcome':
				if (strlen($rt['return']) > 0) {
					echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_important.gif" align="absmiddle" class="home" />你所请求的页面需要你先进行登录</span>');
				} else {
					echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_id.gif" align="absmiddle" class="home" />会员登录</span>');
				}
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/login.vx" method="post" id="Login">');
				if (strlen($rt['return']) > 0) {
					echo('<input type="hidden" name="return" value="' . $rt['return'] . '" />');
				}
				echo('<tr><td width="200" align="right">电子邮件或昵称</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="' . CDN_IMG . 'silver/btn_login.gif" alt="' . Vocabulary::action_login . '" /></td></tr><tr><td width="200" align="right">密码</td><td align="left"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" /></td></tr></form></table></div>');
				echo('<div class="blank"><img src="' . CDN_IMG . 'ico_important.gif" align="absmiddle" class="ico" /><a href="/passwd.vx">忘记密码了？点这里找回你的密码</a></div>');
				echo('<div class="blank"><img src="' . CDN_IMG . 'ico_tip.gif" align="absmiddle" class="ico" />会话有效时间为一个月，超过此时间之后你将需要重新登录</div>');
				break;
			
			// ok
			
			case 'ok':
				$p = array();
				$sql = "SELECT COUNT(tpc_id) FROM babel_topic WHERE tpc_uid = {$this->User->usr_id}";
				$rs = mysql_query($sql, $this->db);
				$p['items'] = mysql_result($rs, 0, 0);
				mysql_free_result($rs);
				echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_login.gif" align="absmiddle" class="home" />欢迎回来，' . $this->User->usr_nick . '</span><br />你一共在 ' . Vocabulary::site_name . ' 社区创建了 ' . $p['items'] . ' 个主题，下面是你最新创建或被回复了的一些！</div>');
				if ($p['items'] > 0) {
					echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
					$sql = "SELECT nod_id, nod_title, tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created, tpc_lastupdated, tpc_lasttouched FROM babel_node, babel_topic WHERE tpc_pid = nod_id AND tpc_uid = {$this->User->usr_id} ORDER BY tpc_posts DESC, tpc_lasttouched DESC, tpc_created DESC LIMIT 20";
					$rs = mysql_query($sql, $this->db);
					$i = 0;
					while ($Topic = mysql_fetch_object($rs)) {
						$i++;
						$css_color = rand_color();
						echo('<tr>');
						echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="' . CDN_IMG . 'star_active.png" /></td>');
						if ($i % 2 == 0) {
							$css_class = 'even';
						} else {
							$css_class = 'odd';
						}
						echo('<td class="' . $css_class . '" height="24" align="left"><a href="/topic/view/' . $Topic->tpc_id . '.html" style="color: ' . $css_color . ';" class="var" target="_self">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
						if ($Topic->tpc_posts > 0) {
							echo('<small class="fade">(' . $Topic->tpc_posts . ')</small>');
						}
						echo('<small class="grey">+' . $Topic->tpc_hits . '</small>');
						echo('</td>');
						echo('<td class="' . $css_class . '" width="120" height="24" align="left"><a href="/board/view/' . $Topic->nod_id . '.html">' . $Topic->nod_title . '</a></td>');
						if ($Topic->tpc_lasttouched > $Topic->tpc_created) {
							echo('<td class="' . $css_class . '" width="120" height="24" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_lasttouched) . '</small></td>');
						} else {
							echo('<td class="' . $css_class . '" width="120" height="24" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_created) . '</small></td>');
						}
						echo('</tr>');
					}
					mysql_free_result($rs);
					echo('</table>');
				}
				break;
				
			// something wrong

			case 'error':
				echo('<div class="blank" align="left"><span class="text_large"><img src="' . CDN_IMG . 'ico_important.gif" align="absmiddle" class="home" />对不起，你刚才提交的信息里有些错误</span><table cellpadding="0" cellspacing="0" border="0" class="form"><form action="/login.vx" method="post" id="Login">');
				if (strlen($rt['return']) > 0) {
					echo('<input type="hidden" name="return" value="' . $rt['return'] . '" />');
				}
				if ($rt['usr_error'] != 0) {
					echo('<tr><td width="200" align="right" valign="top">电子邮件或昵称</td><td align="left"><div class="error"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" value="' . make_single_return($rt['usr_value']) . '" />&nbsp;<img src="' . CDN_IMG . 'sico_error.gif" align="absmiddle" /><br />' . $rt['usr_error_msg'][$rt['usr_error']] . '</div>');
				} else {
					echo('<tr><td width="200" align="right">电子邮件或昵称</td><td align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" value="' . make_single_return($rt['usr_value']) .  '" />');
				}
				echo('<td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="' . CDN_IMG . 'silver/btn_login.gif" alt="' . Vocabulary::action_login . '" /></td></tr>');
				if ($rt['usr_password_error'] > 0 && $rt['usr_error'] == 0) {
					echo('<tr><td width="200" align="right" valign="top">密码</td><td align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" />&nbsp;<img src="' . CDN_IMG . 'sico_error.gif" align="absmiddle" /><br />' . $rt['usr_password_error_msg'][$rt['usr_password_error']] . '</div></td></tr>');
				} else {
					echo('<tr><td width="200" align="right">密码</td><td align="left"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" /></td></tr>');
				}
				echo('</form></table></div>');
				echo('<div class="blank"><img src="/img/ico_important.gif" align="absmiddle" class="ico" /><a href="/passwd.vx">忘记密码了？点这里找回你的密码</a></div>');
				echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />会话有效时间为一个月，超过此时间之后你将需要重新登录</div>');
				break;
		}
		echo('</div>');
	}
	
	/* E module: Login block */
	
	/* S module: Logout block */
	
	public function vxLogout() {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_logout . '</div>');
		echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_logout.gif" align="absmiddle" class="home" />你已经从' . Vocabulary::site_name . '登出</span><br />感谢你访问' . Vocabulary::site_name . '，你可以<a href="/login.vx">点击这里</a>重新登录</div>');
		echo('</div>');
	}
	
	/* E module: Logout block */
	
	/* S module: Passwd block */
	
	public function vxPasswd($options) {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_passwd . '</div>');
		switch ($options['mode']) {
			default:
			case 'get':
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_recover.gif" align="absmiddle" class="home" />通过电子邮件找回密码</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/passwd.vx" method="post" id="Passwd">');
				echo('<tr><td width="200" align="right">电子邮件</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="/img/silver/btn_recover.gif" alt="' . Vocabulary::action_login . '" /></td></tr></form></table></div>');
				echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="home" />你可以通过输入注册时候使用的电子邮件地址来找回密码<br />
		如果你输入的电子邮件地址确实存在的话，我们将试着向你注册时候使用的电子邮件地址发送一封包含特殊指令的邮件，点击邮件中的地址将让可以让你复位密码，在每 24 小时内，复位密码功能（包括发送邮件）只能使用 5 次<br /><br />如果你确信无法收到我们发送给你的邮件，请你向我们的技术支持 ' . BABEL_AM_SUPPORT . ' 发送一封邮件详细描述你所遇到的问题</div>');
				break;
			case 'key':
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_recover.gif" align="absmiddle" class="home" />请输入新密码</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/passwd.vx" method="post" id="Passwd">');
				echo('<input type="hidden" value="' . $options['key'] . '" name="key" />');
				echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="100" class="sl" name="usr_password" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr><tr><td width="200" align="right">重复密码</td><td align="left"><input type="password" tabindex="2" maxlength="32" class="sl" name="usr_confirm" /></td></tr></form></table></div>');
				echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />请输入新密码两遍之后，点击 [ 重设密码 ] 为会员 <span class="tip"><em>' . $options['target']->usr_nick . '</em></span> 重新设置密码</div>');
				break;
			case 'reset':
				if ($options['rt']['errors'] == 0) {
					echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_smile.gif" class="home" align="absmiddle" />密码已经更新，现在请使用新密码登录</span>');
					echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
					echo('<form action="/login.vx" method="post" id="Login">');
					echo('<tr><td width="200" align="right">电子邮件或昵称</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="/img/silver/btn_login.gif" alt="' . Vocabulary::action_login . '" /></td></tr><tr><td width="200" align="right">密码</td><td align="left"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" /></td></tr></form></table></div>');
					echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />会话有效时间为一个月，超过此时间之后你将需要重新登录</div>');
				} else {
					echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_recover.gif" align="absmiddle" class="home" />请输入新密码</span>');
					echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
					echo('<form action="/passwd.vx" method="post" id="Passwd">');
					echo('<input type="hidden" value="' . $options['key'] . '" name="key" />');
					/* S result: usr_password and usr_confirm */
					
					/* pswitch:
					a => p0 c0
					b => p1 c1
					c => p1 c0
					d => p0 c1 */
					
					switch ($options['rt']['pswitch']) {
						default:
						case 'a':
							echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="1" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $options['rt']['usr_password_error_msg'][$options['rt']['usr_password_error']] . '</div></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
							echo('<tr><td width="200" align="right">重复密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" tabindex="2" /></td></tr>');
							break;
						case 'b':
							if ($options['rt']['usr_password_error'] == 0) {
								if ($options['rt']['usr_confirm_error'] != 0) {
									echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($options['rt']['usr_password_value']) . '" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
									echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_confirm" tabindex="2" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $options['rt']['usr_confirm_error_msg'][$options['rt']['usr_confirm_error']] . '</div></td></tr>');
								} else {
									echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left""><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($options['rt']['usr_password_value']) . '" tabindex="1" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
									echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left""><input type="password" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($options['rt']['usr_confirm_value']) . '" tabindex="2" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td></tr>');
								}
							} else {
								echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="1" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $options['rt']['usr_password_error_msg'][$options['rt']['usr_password_error']] . '</div></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
							echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" tabindex="2" /></td></tr>');
							}
							break;
						case 'c':
							echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($options['rt']['usr_password_value']) . '" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
							echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_confirm" tabindex="2" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $options['rt']['usr_confirm_error_msg'][$options['rt']['usr_confirm_error']] . '</div></td></tr>');
							break;
						case 'd':
							echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="1" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $options['rt']['usr_password_error_msg'][$options['rt']['usr_password_error']] . '</div></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" tabindex="3" src="/img/silver/btn_passwd.gif" alt="' . Vocabulary::action_passwd_reset . '" /></td></tr>');
							echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($options['rt']['usr_confirm_value']) . '" tabindex="2" /></td></tr>');
							break;
					}
					
					/* E result: usr_password and usr_confirm */
					
					echo('</form></table></div>');
					echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />请输入新密码两遍之后，点击 [ 重设密码 ] 为会员 <span class="tip"><em>' . $options['target']->usr_nick . '</em></span> 重新设置密码</div>');
				}
				break;
			case 'post':
				$rt = array();
				$rt['err'] = 0;
				$rt['ok'] = false;
				$rt['err_msg'] = array(1 => '请输入电子邮件地址', 2 => '只能在 24 小时内取回密码 ' . BABEL_PASSWD_LIMIT . ' 次', 3 => '电子邮件地址不正确');
				
				if (isset($_POST['usr'])) {
					$usr = trim($_POST['usr']);
					if (strlen($usr) > 0) {
						$usr = mysql_real_escape_string(strtolower($usr), $this->db);
						$sql = "SELECT usr_id, usr_email, usr_password FROM babel_user WHERE usr_email = '{$usr}'";
						$rs = mysql_query($sql, $this->db) or die(mysql_error());
						if (mysql_num_rows($rs) == 1) {
							$O = mysql_fetch_object($rs);
							mysql_free_result($rs);
							$rt['target'] = new User($O->usr_email, $O->usr_password, $this->db, false);
							$rt['key'] = $this->vxPasswdKey($rt['target']);
							$_now = time();
							$_oneday = $_now - 86400;
							$sql = "SELECT COUNT(pwd_id) FROM babel_passwd WHERE pwd_uid = {$rt['target']->usr_id} AND pwd_created > {$_oneday}";
							$rs = mysql_query($sql, $this->db);
							$_count = intval(mysql_result($rs, 0, 0)) + 1;
							$rs = mysql_free_result($rs);
							if ($_count > BABEL_PASSWD_LIMIT) {
								$rt['err'] = 2;
							} else {
								$sql = "INSERT INTO babel_passwd(pwd_uid, pwd_hash, pwd_ip, pwd_created) VALUES({$rt['target']->usr_id}, '{$rt['key']}', '{$_SERVER['REMOTE_ADDR']}', {$_now})";
								mysql_query($sql, $this->db);
								
								if (mysql_affected_rows($this->db) == 1) {
									$mail = array();
									$mail['subject'] = '找回你在 ' . Vocabulary::site_name . ' 的密码';
									$mail['body'] = "{$rt['target']->usr_nick}，你好！\n\n你刚才在 " . Vocabulary::site_name . " 申请找回你丢失的密码，因此我们发送此邮件给你。\n\n请点击下面的链接地址（或将此链接地址复制到浏览器地址栏中访问），然后设置你的新密码：\n\nhttp://" . BABEL_DNS_NAME . "/passwd/" . $rt['key'] . "\n\n此链接地址有效时间为 24 小时。\n\n如果这次密码找回申请不是由你提起的，你可以安全地忽略此邮件。这不会对你的原来的密码造成任何影响。\n\n作为一个安全提示，此次密码找回申请是由 IP 地址 " . $_SERVER['REMOTE_ADDR'] . " 提起的。" . BABEL_AM_SIGNATURE;
									$am = new Airmail($rt['target']->usr_email, $mail['subject'], $mail['body']);
									$am->vxSend();
									$am = null;
									$rt['ok'] = true;
								}
							}
							$O = null;
						} else {
							mysql_free_result($rs);
							$rt['err'] = 3;
						}
					} else {
						$rt['err'] = 1;
					}
				} else {
					$rt['err'] = 1;
				}
				
				if ($rt['err'] > 0) {
					echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />出了一点问题</span>');
					echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
					echo('<form action="/passwd.vx" method="post" id="Passwd">');
					echo('<tr><td width="200" align="right" valign="top">电子邮件</td><td width="200" align="left"><div class="error"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['err_msg'][$rt['err']] . '</div></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="/img/silver/btn_recover.gif" alt="' . Vocabulary::action_login . '" /></td></tr></form></table></div>');
					echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="home" />你可以通过输入注册时候使用的电子邮件地址来找回密码<br />
		如果你输入的电子邮件地址确实存在的话，我们将试着向你注册时候使用的电子邮件地址发送一封包含特殊指令的邮件，点击邮件中的地址将让可以让你复位密码，在每 24 小时内，复位密码功能（包括发送邮件）只能使用 5 次<br /><br />由于电子邮件传输存在一些网络方面的延迟，因此如果你在点击了 [ 找回密码 ] 后无法收到邮件，请你稍微多等待几分钟。如果你确信无法收到我们发送给你的邮件，请你向我们的技术支持 ' . BABEL_AM_SUPPORT . ' 发送一封邮件详细描述你所遇到的问题</div>');
				} else {
					echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_recover.gif" align="absmiddle" class="home" />密码找回邮件已经发送</span>');
					echo('<br />现在请到你注册时候使用的电子邮箱中接收一封我们刚刚发送给你的的邮件，点击邮件中的链接地址即可复位密码<br /><br />邮件中的链接地址的有效时间为 24 小时，超过此时间后邮件中的链接地址将变得无效，然后你将需要重新提起密码回复申请');
				}
				break;
		}
		echo('</div>');
	}
	
	private function vxPasswdKey($User) {
		$a = rand(1000, 9999) * $User->usr_id + time();
		$b = rand(1000, 9999) + $User->usr_id;
		$c = rand(1000, 9999) * $User->usr_money;
		$d = rand(1000, 9999) + $User->usr_money;
		
		$e = substr(sha1($a), rand(0, 10), 9) . substr(md5($b), rand(0, 10), 9) . substr(sha1($c), rand(0, 10), 9) . substr(md5($d), rand(0, 10), 9);
		
		$s = strlen($e);
		
		$f = array();
		
		for ($i = 0; $i < $s; $i = $i + 3) {
			$f[] = substr($e, $i, 3);
		}
		
		$e = implode('-', $f);
	
		return $e;
	}
	
	/* E module: Passwd block */
	
	/* S module: User Home block */
	
	public function vxUserHome($options) {	
		$O =& $options['target'];
		if ($O->usr_id != $this->User->usr_id) {
			$this->User->vxAddHits($O->usr_id);
		}
		
		$img_p = $O->usr_portrait ? '/img/p/' . $O->usr_portrait . '.jpg' : '/img/p_' . $O->usr_gender . '.gif';
		
		$img_p_n = $O->usr_portrait ? '/img/p/' . $O->usr_portrait . '_n.jpg' : '/img/p_' . $O->usr_gender . '_n.gif';
		
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 ');
		echo('<a href="/">' . Vocabulary::site_name . '</a> ');
		if ($options['mode'] == 'random') {
			echo('&gt; 茫茫人海 ');
		}
		echo('&gt; ' . $O->usr_nick);
		echo('</div>');
		
		echo('<div class="blank"><span class="text"><img src="' . $img_p_n . '" class="portrait" align="absmiddle" /> ' . Vocabulary::site_name . ' 的第 <strong>' . $O->usr_id . '</strong> 号会员</span>');
		
		if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
			
			$hack_width = 'width="100%" ';
		} else {
			$hack_width = '';
		}
		
		echo('<table ' . $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="fav">');
		echo('<tr>');
		
		$txt_gender = array();
		$txt_gender[0] = '性别未知，';
		$txt_gender[1] = '男，';
		$txt_gender[2] = '女，';
		$txt_gender[5] = '出生的时候是个女孩子，后来把性别改成了男的，';
		$txt_gender[6] = '出生的时候是个男孩子，后来把性别改成了女的，';
		$txt_gender[9] = '她，或者他，想对自己的性别保密，';
		
		$txt = $txt_gender[$O->usr_gender];
		
		if ($count_u = $this->cs->get('count_u_' . $O->usr_id)) {
			$count_u = unserialize($count_u);
		} else {
			$count_u = array();
			$sql = "SELECT count(tpc_id) AS tpc_count FROM babel_topic WHERE tpc_uid = {$O->usr_id}";
			
			$rs_count = mysql_query($sql, $this->db);
			
			$o_count = mysql_fetch_object($rs_count);
			mysql_free_result($rs_count);
			$count_u['tpc_count'] = $o_count->tpc_count;
			$o_count = null;
			
			$sql = "SELECT count(pst_id) AS pst_count FROM babel_post WHERE pst_uid = {$O->usr_id}";
			
			$rs_count = mysql_query($sql, $this->db);
			
			$o_count = mysql_fetch_object($rs_count);
			mysql_free_result($rs_count);
			$count_u['pst_count'] = $o_count->pst_count;
			$o_count = null;
			$this->cs->save(serialize($count_u), 'count_u_' . $O->usr_id);
		}
		
		$sql = "SELECT tpc_id, tpc_title, tpc_posts, tpc_created, nod_id, nod_title FROM babel_topic, babel_node WHERE tpc_pid = nod_id AND tpc_uid = {$O->usr_id} ORDER BY tpc_created DESC LIMIT 10";
		
		$rs_created = mysql_query($sql, $this->db);
		
		$sql = "SELECT usr_id, usr_nick, tpc_id, tpc_title, tpc_posts, tpc_lasttouched, nod_id, nod_title FROM (((babel_topic JOIN babel_node ON tpc_pid = nod_id) JOIN babel_post ON pst_tid = tpc_id) JOIN babel_user ON tpc_uid = usr_id) WHERE pst_uid = {$O->usr_id} ORDER BY tpc_lasttouched DESC LIMIT 20";

		$rs_followed = mysql_query($sql, $this->db);
		
		$txt .= '在' . date(' Y 年 n 月', $O->usr_created) . '的时候来到 ' . Vocabulary::site_name . '，在过去创建了 <a href="/topic/archive/user/' . $O->usr_nick . '">' . $count_u['tpc_count'] . '</a> 个主题，发表了 ' . $count_u['pst_count'] . ' 篇回复。'; 
		
		if ($this->User->usr_id == $O->usr_id) {
			$txt .= '<br /><span class="tip_i">你正在察看的是自己的页面，你可以把它的地址发给你的朋友，和他们共享你在 ' . Vocabulary::site_name . ' 获得的快乐！</span>';
		}
		
		echo('<td width="95" align="left" valign="top" class="section_even"><img src="' . $img_p . '" class="portrait" /></td><td align="left" valign="top" class="section_even"><span class="text_large">' . $O->usr_nick . '</span>');
		
		echo('<span class="excerpt"><br /><br />' . $txt . '</span></td>');
		echo('</tr>');
		
		if ($this->User->usr_id == $O->usr_id) {
			echo('<tr><td colspan="2" align="center" class="section_odd"><img src="/img/pico_web.gif" align="absmiddle" />&nbsp;你的 V2EX 主页地址&nbsp;&nbsp;&nbsp;<input type="text" class="sll" onclick="this.select()" value="http://' . BABEL_DNS_NAME . '/u/' . $O->usr_nick . '" readonly="readonly" />&nbsp;&nbsp;&nbsp;<span class="tip_i">... 本页一共被访问了 ' . $O->usr_hits . ' 次</span></td></tr>');
		}
		
		if ($O->usr_brief != '') {
			echo('<tr><td colspan="2" align="center" class="section_even"><span class="text_large"><img src="/img/quote_left.gif" align="absmiddle" />&nbsp;' . make_plaintext($O->usr_brief) . '&nbsp;<img src="/img/quote_right.gif" align="absmiddle" /></span></td></tr>');
		}
		
		echo('<tr><td colspan="2" align="center" class="section_odd"><span class="tip_i"><img src="' . CDN_IMG . 'pico_zen.gif" align="absmiddle" alt="ZEN" />&nbsp;<a href="/zen/' . $O->usr_nick . '" class="var" style="color: ' . rand_color() . ';">' . $O->usr_nick . ' 的 ZEN</a>&nbsp;&nbsp;|&nbsp;&nbsp;<img src="' . CDN_IMG . 'pico_topics.gif" alt="Topics" align="absmiddle" />&nbsp;<a href="/topic/archive/user/' . $O->usr_nick . '" class="var" style="color: ' . rand_color() . ';">' . $O->usr_nick . ' 的所有主题</a></span></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="/img/ico_savepoint.gif" align="absmiddle" class="home" />' . $O->usr_nick . ' 的网上据点<a name="svp" /></span>');
		
		$sql = "SELECT svp_id, svp_url, svp_rank FROM babel_savepoint WHERE svp_uid = {$O->usr_id} ORDER BY svp_url";
		
		$rs = mysql_query($sql);
		
		$i = 0;
		while ($S = mysql_fetch_object($rs)) {
			$i++;
			$css_color = rand_color();
			$css_class = $i % 2 ? 'section_even' : 'section_odd';
			$o = $this->Validator->vxGetURLHost($S->svp_url);
			echo('<tr><td colspan="2" align="left" class="' . $css_class . '"><span class="svp"><img src="/img/fico_' . $o['type'] . '.gif" align="absmiddle" />&nbsp;&nbsp;<a href="http://' . $S->svp_url . '" target="_blank" rel="external nofollow" style="color: ' . $css_color . '" class="var">http://' . htmlspecialchars($S->svp_url) . '</a>&nbsp;&nbsp;</span>');
			if ($this->User->usr_id == $O->usr_id) {
				echo('<span class="tip_i"> ... <a href="/savepoint/erase/' . $S->svp_id . '.vx" class="g">X</a></span>');
			}
			echo('</td></tr>');
		}
		
		$msgs = array(0 => '新据点添加失败，你可以再试一次，或者是到 <a href="/go/babel">Developer Corner</a> 向我们报告错误', 1 => '新据点添加成功', 2 => '你刚才想添加的据点已经存在于你的列表中', 3 => '目前，每个人只能添加至多 ' . BABEL_SVP_LIMIT . ' 个据点，你可以试着删除掉一些过去添加的，我们正在扩展系统的能力以支持更多的据点', 4 => '要删除的据点不存在', 5 => '你不能删除别人的据点', 6 => '据点删除成功', 7 => '据点删除失败，你可以再试一次，或者是到 <a href="/go/babel">Developer Corner</a> 向我们报告错误', 9 => '不需要输入前面的 http:// 协议名称，直接添加网址就可以了，比如 www.livid.cn 这样的地址');
		if ($this->User->vxIsLogin() && $this->User->usr_id == $O->usr_id) {
			$i++;
			$css_class = $i % 2 ? 'section_even' : 'section_odd';
			echo('<form action="/recv/savepoint.vx" method="post"><tr><td colspan="2" align="left" class="' . $css_class . '">你可以为自己添加一个新的网上据点&nbsp;&nbsp;<span class="tip_i">http://&nbsp;<input type="text" onmouseover="this.focus();" name="url" class="sll" />&nbsp;&nbsp;<input type="image" align="absmiddle" src="/img/silver/sbtn_add.gif" /><br />');
			if (isset($_GET['msg'])) {
				$msg = intval($_GET['msg']);
				switch ($msg) {
					case 0:
						echo $msgs[0];
						break;
					case 1:
						echo $msgs[1];
						break;
					case 2:
						echo $msgs[2];
						break;
					case 3:
						echo $msgs[3];
						break;
					case 4:
						echo $msgs[4];
						break;
					case 5:
						echo $msgs[5];
						break;
					case 6:
						echo $msgs[6];
						break;
					case 7:
						echo $msgs[7];
						break;
					default:
						echo $msgs[9];
						break;
				}
			} else {
				echo $msgs[9];
			}
			echo('</span></td></tr></form>');
		}
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="/img/ico_friends.gif" align="absmiddle" class="home" />' . $O->usr_nick . ' 的朋友们</span>');
		
		if (isset($_GET['do'])) {
			$do = strtolower(trim($_GET['do']));
			if (!in_array($do, array('add', 'remove'))) {
				$do = false;
			}
		} else {
			$do = false;
		}
		
		if ($this->User->usr_id != $O->usr_id && $this->User->vxIsLogin()) {
			if ($do) {
				if ($do == 'add') {
					$sql = "SELECT frd_id, frd_uid, frd_fid FROM babel_friend WHERE frd_uid = {$this->User->usr_id} AND frd_fid = {$O->usr_id}";
					$rs = mysql_query($sql);
					if (mysql_num_rows($rs) == 0) {
						mysql_free_result($rs);
						$sql = "INSERT INTO babel_friend(frd_uid, frd_fid, frd_created, frd_lastupdated) VALUES({$this->User->usr_id}, {$O->usr_id}, " . time() . ", " . time() . ")";
						mysql_query($sql);
						$txt_friend = '<span class="tip_i">&nbsp;&nbsp;&nbsp;你已经把 ' . $O->usr_nick . ' 加为了好友</span>';
					} else {
						mysql_free_result($rs);
						$txt_friend = '<span class="tip">&nbsp;&nbsp;&nbsp;<a href="/friend/remove/' . $O->usr_nick . '" class="g">把 ' . $O->usr_nick . ' 从好友列表中去掉</a></span>';
					}
				}
				if ($do == 'remove') {
					$sql = "SELECT frd_id, frd_uid, frd_fid FROM babel_friend WHERE frd_uid = {$this->User->usr_id} AND frd_fid = {$O->usr_id}";
					$rs = mysql_query($sql);
					if (mysql_num_rows($rs) == 1) {
						mysql_free_result($rs);
						$sql = "DELETE FROM babel_friend WHERE frd_uid = {$this->User->usr_id} AND frd_fid = {$O->usr_id}";
						mysql_query($sql);
						$txt_friend = '<span class="tip_i">&nbsp;&nbsp;&nbsp;你已经把 ' . $O->usr_nick . ' 移出了好友列表</span>';
					} else {
						mysql_free_result($rs);
						$txt_friend = '<span class="tip">&nbsp;&nbsp;&nbsp;<a href="/friend/connect/' . $O->usr_nick . '" class="g">把 ' . $O->usr_nick . ' 加为好友！</a></span>';
					}
				}
			} else {
				$sql = "SELECT frd_id, frd_uid, frd_fid FROM babel_friend WHERE frd_uid = {$this->User->usr_id} AND frd_fid = {$O->usr_id}";
				$rs = mysql_query($sql);
				
				if (mysql_num_rows($rs) == 1) {
					$txt_friend = '<span class="tip">&nbsp;&nbsp;&nbsp;<a href="/friend/remove/' . $O->usr_nick . '" class="g">把 ' . $O->usr_nick . ' 从好友列表中去掉</a></span>';
				} else {
					$txt_friend = '<span class="tip">&nbsp;&nbsp;&nbsp;<a href="/friend/connect/' . $O->usr_nick . '" class="g">把 ' . $O->usr_nick . ' 加为好友！</a></span>';
				}
			}
		} else {
			$txt_friend = '&nbsp;&nbsp;';
		}
		
		if ($this->User->vxIsLogin() && $O->usr_id != $this->User->usr_id) {
			$txt_msg = '<span class="tip">&nbsp;&nbsp;<a href="#;" class="g" onclick="sendMessage(' . $O->usr_id . ');">向 ' . $O->usr_nick . ' 发送短消息</a></span>';
		} else {
			$txt_msg = '&nbsp;&nbsp;';
		}
		echo $txt_friend;
		echo $txt_msg;
		echo('</td></tr>');
		
		echo ('<tr><td colspan="2">');
		
		$edges = array();
		for ($i = 1; $i < 1000; $i++) {
			$edges[] = ($i * 5) + 1;
		}
		
		$sql = "SELECT usr_id, usr_gender, usr_nick, usr_portrait FROM babel_user WHERE usr_id IN (SELECT frd_fid FROM babel_friend WHERE frd_uid = {$O->usr_id}) ORDER BY usr_nick";
		$rs = mysql_query($sql);
		$i = 0;
		$s = 0;
		while ($Friend = mysql_fetch_object($rs)) {
			$i++;
			if (in_array($i, $edges)) {
				echo('<tr><td colspan="2">');
			}
			$img_p = $Friend->usr_portrait ? '/img/p/' . $Friend->usr_portrait . '.jpg' : '/img/p_' . $Friend->usr_gender . '.gif';
			echo('<a href="/u/' . $Friend->usr_nick . '" class="friend"><img src="' . $img_p . '" class="portrait" /><br />' . $Friend->usr_nick . '</a>');
			if (($i % 5) == 0) {
				echo ('</td></tr>');
			}
		}
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="/img/ico_topic.gif" align="absmiddle" class="home"/>' . $O->usr_nick . ' 最近创建的主题</span>');
		echo('<table cellpadding="0" cellspacing="0" border="0" class="fav" width="100%">');
		$i = 0;
		while ($Topic = mysql_fetch_object($rs_created)) {
			$i++;
			$css_color = rand_color();
			$css_td_class = $i % 2 ? 'section_even' : 'section_odd';
			$txt_fresh = $Topic->tpc_posts ? $Topic->tpc_posts . ' 篇回复' : '尚无回复';
			echo('<tr><td align="left" class="' . $css_td_class . '">[ <a href="/board/view/' . $Topic->nod_id . '.html" class="var" style="color: ' . $css_color . '">' . $Topic->nod_title . '</a> ]&nbsp;:&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html">' . $Topic->tpc_title . '</a> <span class="tip_i">... ' . make_descriptive_time($Topic->tpc_created) . '，' . $txt_fresh . '</span></td></tr>');
		}
		echo('</table>');
		echo('</td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="/img/ico_followed.gif" align="absmiddle" class="home"/>' . $O->usr_nick . ' 最近参与的主题</span>');
		echo('<table cellpadding="0" cellspacing="0" border="0" class="fav" width="100%">');
		$i = 0;
		$tpcs = array();
		while ($Topic = mysql_fetch_object($rs_followed)) {
			if (!in_array($Topic->tpc_id, $tpcs)) {
				$tpcs[] = $Topic->tpc_id;
				$i++;
				$css_color = rand_color();
				$css_td_class = $i % 2 ? 'section_odd' : 'section_even';
				$txt_fresh = $Topic->tpc_posts ? $Topic->tpc_posts . ' 篇回复' : '尚无回复';
				echo('<tr><td align="left" class="' . $css_td_class . '">[ <a href="/board/view/' . $Topic->nod_id . '.html" class="var" style="color: ' . $css_color . '">' . $Topic->nod_title . '</a> ]&nbsp;:&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html">' . $Topic->tpc_title . '</a> <span class="tip_i">... ' . make_descriptive_time($Topic->tpc_lasttouched) . '，' . $txt_fresh . '</span></td></tr>');
			}
		}
		
		echo('</table>');
		echo('</td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_odd"><span class="text_large"><img src="/img/ico_cc.gif" align="absmiddle" class="home"/>' . $O->usr_nick . ' 的成分分析</span>');
		echo('</td></tr>');
		
		if ($c = $this->cl->get('cc_' . $O->usr_id)) {
			$c = unserialize($c);
		} else {
			$f = new Fun();
			$c = $f->vxGetComponents($O->usr_nick);
			$f = null;
			$this->cl->save(serialize($c), 'cc_' . $O->usr_id);
		}

		echo('<tr><td colspan="2" align="left" class="section_odd">');
		
		echo('<ul class="items">');
		foreach($c['c'] as $C) {
			echo('<li>' . $C . '</li>');
		}
		echo('</ul>');
		
		echo('</td></tr>');
		
		echo('<tr><td colspan="2" align="left" class="section_even" style="padding-left: 20px;">');
		
		echo('<span class="tip">' . $c['s'] . '</span>');
		
		echo('</td></tr>');
		
		
		echo('</table>');
		echo('</div>');
		
		echo('</div>');
	}
	
	/* E module: User Home block */
	
	/* S module: User Create block */
	
	public function vxUserCreate($rt) {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_signup . '</div>');

		if ($rt['errors'] != 0) {
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />对不起，你刚才提交的信息里有些错误</span><table cellpadding="0" cellspacing="0" border="0" class="form"><form action="/user/create.vx" method="post" id="usrNew">');

			/* result: usr_email */
			if ($rt['usr_email_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">电子邮件</td><td align="left"><div class="error"><input type="text" tabindex="1" maxlength="100" class="sl" name="usr_email" value="' . make_single_return($rt['usr_email_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_email_error_msg'][$rt['usr_email_error']] . '</div></td>');
			} else {
				echo('<tr><td width="200" align="right">电子邮件</td><td align="left"><input type="text" tabindex="1" maxlength="100" class="sl" name="usr_email" value="' . make_single_return($rt['usr_email_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td>');
			}
			
			/* cell: submit button */
			echo('<td width="150" rowspan="8" valign="middle" align="right"><input type="image" tabindex="7" src="/img/silver/btn_signup.gif" alt="' . Vocabulary::action_signup . '" /></td></tr>');
			
			/* result: usr_nick */
			if ($rt['usr_nick_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">昵称</td><td align="left"><div class="error"><input type="text" tabindex="2" maxlength="20" class="sl" name="usr_nick" value="' . make_single_return($rt['usr_nick_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_nick_error_msg'][$rt['usr_nick_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">昵称</td><td align="left"><input type="text" tabindex="2" maxlength="20" class="sl" name="usr_nick" value="' . make_single_return($rt['usr_nick_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_password */
			if ($rt['usr_password_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">密码</td><td align="left"><div class="error"><input type="password" tabindex="3" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '"/>&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_password_error_msg'][$rt['usr_password_error']] . '</td></tr>');
			} else {
				if ($rt['usr_confirm_error'] != 0) {
					echo('<tr><td width="200" align="right">密码</td><td align="left"><input type="password" tabindex="3" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '" /></td></tr>');
				} else {
					echo('<tr><td width="200" align="right">密码</td><td align="left"><input type="password" tabindex="3" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td></tr>');
				}
			}
			
			/* result: usr_confirm */
			if ($rt['usr_password_error'] == 0) {
				if ($rt['usr_confirm_error'] != 0) {
					echo('<tr><td width="200" align="right" valign="top">重复密码</td><td align="left""><div class="error"><input type="password" tabindex="4" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($rt['usr_confirm_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_confirm_error_msg'][$rt['usr_confirm_error']] . '</div></td></tr>');
				} else {
					echo('<tr><td width="200" align="right">重复密码</td><td align="left""><input type="password" tabindex="4" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($rt['usr_confirm_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td></tr>');
				}
			} else {
				echo('<tr><td width="200" align="right">重复密码</td><td align="left""><input type="password" tabindex="4" maxlength="32" class="sl" name="usr_confirm" /></td></tr>');
			}

			/* result: usr_gender */
			echo('<tr><td width="200" align="right" valign="top">性别</td><td align="left"><select tabindex="5" maxlength="20" size="6" name="usr_gender">');
			$gender_a = array(0 => '未知', 1 => '男性', 2 => '女性', 5 => '女性改变为男性', 6 => '男性改变为女性', 9 => '未说明');
			foreach ($gender_a as $c => $g) {
				if ($c == $rt['usr_gender_value']) {
					echo('<option value="' . $c . '" selected="selected">' . $g . '</option>');
				} else {
					echo('<option value="' . $c . '">' . $g . '</option>');
				}
			}
			echo('</select></td></tr>');
			
			/* S result: c */
			
			if ($rt['c_error'] > 0) {
				echo('<tr><td width="200" align="right">确认码</td><td align="left"><input tabindex="6" type="password" maxlength="32" class="sl" name="c" /></td></tr><tr><td width="200" align="right"></td><td align="left"><div class="error"><img src="/c/' . rand(1111,9999) . '.' . rand(1111,9999) . '.png" /><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['c_error_msg'][$rt['c_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">确认码</td><td align="left"><input tabindex="6" type="password" maxlength="32" class="sl" name="c" value="' . $rt['c_value'] . '" />&nbsp;<img src="/img/sico_ok.gif" alt="ok" align="absmiddle" /></td></tr><tr><td width="200" align="right"></td><td align="left"><img src="/c/' . rand(1111,9999) . '.' . rand(1111,9999) . '.png" /></td></tr>');
			}
			/* E result: c */			
			echo('</form></table></div>');
			echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="home" />点击“注册新会员”，即表示你同意 ' . Vocabulary::site_name . ' 的使用条款和隐私权规则<br /><br />电子邮件地址将作为你登录时候使用的识别之一，这里的大部分功能依赖于一个真实的电子邮件地址，因此一个真实的电子邮件地址很有必要，而至于昵称，则可以任意设置随心换</div>');
		} else {
			$mail = array();
			$mail['subject'] = $this->User->usr_nick . '，你好，' . Vocabulary::site_name . ' 欢迎你的到来！';
			$mail['body'] = "{$this->User->usr_nick}，你好！\n\n" . Vocabulary::site_name . " 欢迎你的到来，你或许会对 " . Vocabulary::site_name . " 这个名字感到好奇吧？\n\n" . Vocabulary::site_name . " 是两个短句的缩写，way too extreme 和 way to explore，前者关于一种生活的态度，后者关于我们每天都会产生然后又失去的好奇心。So is V2EX，希望你喜欢。\n\n目前看来，V2EX 是一个普普通通不足为奇的社区（或者说论坛），不过，我们正在修建一个有着透明玻璃的怪物博物馆，不久的将来，每天都会有各种怪物可以玩，也是相当开心的事情吧。\n\nEnjoy!" . BABEL_AM_SIGNATURE;
			
			$am = new Airmail($this->User->usr_email, $mail['subject'], $mail['body']);
			$am->vxSend();
			$am = null;
			
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />' . $this->User->usr_nick . '，恭喜你！注册成功</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form"><tr><td width="200" align="right" valign="top">电子邮件</td><td align="left">' . $this->User->usr_email . '</td></tr><tr><td width="200" align="right" valign="top">昵称</td><td align="left">' . $this->User->usr_nick . '</td></tr><tr><td width="200" align="right" valign="top">密码</td><td align="left"><div class="important">');
			$max = rand(1, 6) * 4;
			for ($i = 1; $i <= $max; $i++) {
				echo($i == 0) ? '':'&nbsp;&nbsp;';
				echo('<strong style="font-weight: ' . rand(1, 8) . '00; font-size: ' . rand(8,28) . 'px; border: 2px solid ' . rand_color(4, 5) . '; background-color: ' . rand_color(3, 5) . '; color: ' . rand_color(0, 2) . ';font-family: ' . rand_font() . ';">' . $rt['usr_password_value'] . '</strong>');
				echo (($i % 4 == 0) && ($i != 1)) ? '<br />':'';
			}
			echo('<br /><br />在你更改密码之前，你将使用这个长度为 ' . mb_strlen($rt['usr_password_value'], 'utf-8') . ' 个字符的密码进行登录，请花些时间记住这个密码</div></td></tr></table></div>');
			
			echo('<div class="blank" align="left">');
			echo('<span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />上传头像</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			echo('<form enctype="multipart/form-data" action="/recv/portrait.vx" method="post" id="usrPortrait">');
			echo('<tr><td width="200" align="right">现在的样子</td><td width="200" align="left">');
			if ($this->User->usr_portrait != '') {
				echo('<img src="/img/p/' . $this->User->usr_portrait . '.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p/' . $this->User->usr_portrait . '_s.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p/' . $this->User->usr_portrait . '_n.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" class="portrait" />');
			} else {
				echo('<img src="/img/p_' . $this->User->usr_gender . '.gif" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p_' . $this->User->usr_gender . '_s.gif" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p_' . $this->User->usr_gender . '_n.gif" alt="' . $this->User->usr_nick . '" class="portrait" />');
			}
			echo('</td>');
			echo('<td width="150" rowspan="2" valign="middle" align="right"><input tabindex="2" type="image" src="/img/silver/btn_pupload.gif" /></td></tr>');
			echo('</tr>');
			echo('<tr><td width="200" align="right">选择一张你最喜欢的图片</td><td width="200" align="left"><input tabindex="1" type="file" name="usr_portrait" /></td>');
			echo('</tr>');
			echo('</form>');
			echo('</table>');
			echo('</div>');
			echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />推荐你选择一张尺寸大于 100 x 100 像素的图片，系统会自动截取中间的部分并调整大小</div>');
			
			echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />你现在已经使用电子邮件地址为 ' . $this->User->usr_email . ' 的会员的身份登录</div>');
		}
		echo('</div>');
	}
	
	/* E module: User Create block */
	
	/* S module: User Modify block */
	
	public function vxUserModify() {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_modifyprofile . '</div>');

		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />上传头像</span>');
		echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
		echo('<form enctype="multipart/form-data" action="/recv/portrait.vx" method="post" id="usrPortrait">');
		echo('<tr><td width="200" align="right">现在的样子</td><td width="200" align="left">');
		if ($this->User->usr_portrait != '') {
			echo('<img src="/img/p/' . $this->User->usr_portrait . '.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p/' . $this->User->usr_portrait . '_s.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p/' . $this->User->usr_portrait . '_n.' . BABEL_PORTRAIT_EXT . '?' . rand(1000, 9999) . '" class="portrait" />');
		} else {
			echo('<img src="/img/p_' . $this->User->usr_gender . '.gif" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p_' . $this->User->usr_gender . '_s.gif" alt="' . $this->User->usr_nick . '" class="portrait" />&nbsp;&nbsp;<img src="/img/p_' . $this->User->usr_gender . '_n.gif" alt="' . $this->User->usr_nick . '" class="portrait" />');
		}
		echo('</td>');

		echo('<td width="150" rowspan="4" valign="middle" align="right"><input type="image" src="/img/silver/btn_pupload.gif" /></td></tr>');
		
		echo('<tr><td width="200" align="right">选择一张你最喜欢的图片</td><td width="200" align="left"><input tabindex="1" type="file" name="usr_portrait" /></td></tr>');
		
		echo('<tr><td width="200" align="right">对上传的图片做特效处理</td><td width="200" align="left"><input checked="checked" type="radio" name="fx" value="none" />&nbsp;&nbsp;不做任何修改</td></tr>');
		echo('<tr><td width="200" align="right"></td><td width="200" align="left"><input type="radio" name="fx" value="lividark" />&nbsp;&nbsp;Lividark 特效 <span class="tip_i"><a href="http://www.livid.cn/img/lividark.gif" target="_blank">查看例图</a></span></td></tr>');
		
		echo('</form>');
		echo('</table>');
		echo('</div>');
		echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />推荐你选择一张尺寸大于 100 x 100 像素的图片，系统会自动截取中间的部分并调整大小</div>');
		
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_conf.gif" align="absmiddle" class="home" />会员信息修改</span>');
		echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
		echo('<form action="/user/update.vx" method="post" id="usrModify">');
		echo('<tr><td width="200" align="right">真实姓名</td><td width="200" align="left"><input tabindex="1" type="text" maxlength="80" class="sl" name="usr_full" value="' . make_single_return($this->User->usr_full) . '" /></td>');
		echo('<td width="150" rowspan="9" valign="middle" align="right"><input tabindex="11" type="image" src="/img/silver/btn_user_modify.gif" alt="' . Vocabulary::action_signup . '" /></td></tr>');
		echo('<tr><td width="200" align="right">昵称</td><td align="left"><input tabindex="2" type="text" maxlength="20" class="sl" name="usr_nick" value="' . make_single_return($this->User->usr_nick) . '" /></td></tr>');
		echo('<tr><td width="200" align="right">自我简介</td><td align="left"><input tabindex="3" type="text" maxlength="100" class="sl" name="usr_brief" value="' . make_single_return($this->User->usr_brief) . '" /></td></tr>');
		echo('<tr><td width="200" align="right">家庭住址</td><td align="left"><input tabindex="4" type="text" maxlength="100" class="sl" name="usr_addr" value="' . make_single_return($this->User->usr_addr) . '" /></td></tr>');
		echo('<tr><td width="200" align="right">电话</td><td align="left"><input tabindex="5" type="text" maxlength="40" class="sl" name="usr_telephone" value="' . make_single_return($this->User->usr_telephone) . '" /></td></tr>');
		echo('<tr><td width="200" align="right">身份证号码</td><td align="left"><input tabindex="6" type="text" maxlength="18" class="sl" name="usr_identity" value="' . make_single_return($this->User->usr_identity) . '" /></td></tr>');
		/* result: usr_gender */
		echo('<tr><td width="200" align="right" valign="top">性别</td><td align="left"><select tabindex="7" maxlength="20" size="6" name="usr_gender">');
		$gender_a = array(0 => '未知', 1 => '男性', 2 => '女性', 5 => '女性改（变）为男性', 6 => '男性改（变）为女性', 9 => '未说明');
		foreach ($gender_a as $c => $g) {
			if ($c == $this->User->usr_gender) {
				echo('<option value="' . $c . '" selected="selected">' . $g . '</option>');
			} else {
				echo('<option value="' . $c . '">' . $g . '</option>');
			}
		}
		echo('</select></td></tr>');
		/* result: usr_width */
		$x = simplexml_load_file(BABEL_PREFIX . '/res/valid_width.xml');
		$w = $x->xpath('/array/width');
		$ws = array();
		while(list( , $width) = each($w)) {
			$ws[] = strval($width);
		}
		echo('<tr><td width="200" align="right" valign="top">常用屏幕宽度</td><td align="left"><select tabindex="8" maxlength="20" size="' . count($ws) . '" name="usr_width">');
		foreach ($ws as $width) {
			if ($width == $this->User->usr_width) {
				echo('<option value="' . $width . '" selected="selected">' . $width . '</option>');
			} else {
				echo('<option value="' . $width . '">' . $width . '</option>');
			}
		}
		echo('</select></td></tr>');
		echo('<tr><td width="200" align="right">新密码</td><td align="left"><input tabindex="9" type="password" maxlength="32" class="sl" name="usr_password" /></td></tr>');
		echo('<tr><td width="200" align="right">重复新密码</td><td align="left"><input tabindex="10" type="password" maxlength="32" class="sl" name="usr_confirm" /></td></tr>');
		echo('</form></table></div>');
		echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />如果你不打算修改密码的话，就不要在密码框处填入任何信息</div>');
		echo('</div>');
	}
	
	/* E module: User Modify block */
	
	/* S module: User Update block */
	
	public function vxUserUpdate($rt) {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_modifyprofile . '</div>');

		if ($rt['errors'] != 0) {
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />对不起，你刚才提交的信息里有些错误</span><table cellpadding="0" cellspacing="0" border="0" class="form"><form action="/user/update.vx" method="post" id="usrModify">');

			/* result: usr_email */
			if ($rt['usr_full_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">真实姓名</td><td width="200" align="left"><div class="error"><input type="text" maxlength="100" class="sl" name="usr_full" value="' . make_single_return($rt['usr_full_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_full_error_msg'][$rt['usr_full_error']] . '</div></td>');
			} else {
				echo('<tr><td width="200" align="right">真实姓名</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr_full" value="' . make_single_return($rt['usr_full_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td>');
			}
			
			/* cell: submit button */
			echo('<td width="150" rowspan="9" valign="middle" align="right"><input type="image" src="/img/silver/btn_user_modify.gif" alt="' . Vocabulary::action_modifyprofile . '" /></td></tr>');
			
			/* result: usr_nick */
			if ($rt['usr_nick_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">昵称</td><td width="200" align="left"><div class="error"><input type="text" maxlength="20" class="sl" name="usr_nick" value="' . make_single_return($rt['usr_nick_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_nick_error_msg'][$rt['usr_nick_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">昵称</td><td width="200" align="left"><input type="text" maxlength="20" class="sl" name="usr_nick" value="' . make_single_return($rt['usr_nick_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_brief */
			if ($rt['usr_brief_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">自我简介</td><td width="200" align="left"><div class="error"><input type="text" maxlength="200" class="sl" name="usr_brief" value="' . make_single_return($rt['usr_brief_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_brief_error_msg'][$rt['usr_brief_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">自我简介</td><td width="200" align="left"><input type="text" maxlength="200" class="sl" name="usr_brief" value="' . make_single_return($rt['usr_brief_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_addr */
			if ($rt['usr_addr_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">家庭住址</td><td width="200" align="left"><div class="error"><input type="text" maxlength="100" class="sl" name="usr_addr" value="' . make_single_return($rt['usr_addr_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_addr_error_msg'][$rt['usr_addr_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">家庭住址</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr_addr" value="' . make_single_return($rt['usr_addr_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_telephone */
			if ($rt['usr_telephone_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">电话号码</td><td width="200" align="left"><div class="error"><input type="text" maxlength="40" class="sl" name="usr_telephone" value="' . make_single_return($rt['usr_telephone_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_telephone_error_msg'][$rt['usr_telephone_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">电话号码</td><td width="200" align="left"><input type="text" maxlength="40" class="sl" name="usr_telephone" value="' . make_single_return($rt['usr_telephone_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_identity */
			if ($rt['usr_identity_error'] != 0) {
				echo('<tr><td width="200" align="right" valign="top">身份证号码</td><td width="200" align="left"><div class="error"><input type="text" maxlength="18" class="sl" name="usr_identity" value="' . make_single_return($rt['usr_identity_value']) . '" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_identity_error_msg'][$rt['usr_identity_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="200" align="right">身份证号码</td><td width="200" align="left"><input type="text" maxlength="18" class="sl" name="usr_identity" value="' . make_single_return($rt['usr_identity_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" /></td></tr>');
			}
			
			/* result: usr_gender */
			echo('<tr><td width="200" align="right" valign="top">性别</td><td align="left"><select tabindex="6" maxlength="20" size="6" name="usr_gender">');
			
			foreach ($this->User->usr_gender_a as $c => $g) {
				if ($c == $rt['usr_gender_value']) {
					echo('<option value="' . $c . '" selected="selected">' . $g . '</option>');
				} else {
					echo('<option value="' . $c . '">' . $g . '</option>');
				}
			}
			echo('</select></td></tr>');
			
			/* result: usr_width */
			echo('<tr><td width="200" align="right" valign="top">常用屏幕宽度</td><td align="left"><select tabindex="7" maxlength="20" size="' . count($rt['usr_width_array']) . '" name="usr_width">');
			foreach ($rt['usr_width_array'] as $width) {
				if ($width == $rt['usr_width_value']) {
					echo('<option value="' . $width . '" selected="selected">' . $width . '</option>');
				} else {
					echo('<option value="' . $width . '">' . $width . '</option>');
				}
			}
			echo('</select></td></tr>');
			
			/* S result: usr_password and usr_confirm */
			
			/* pswitch:
			a => p0 c0
			b => p1 c1
			c => p1 c0
			d => p0 c1 */
			
			switch ($rt['pswitch']) {
				default:
				case 'a':
					echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_password" /></td></tr>');
					echo('<tr><td width="200" align="right">重复密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" /></td></tr>');
					break;
				case 'b':
					if ($rt['usr_password_error'] == 0) {
						if ($rt['usr_confirm_error'] != 0) {
							echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '" /></td></tr>');
							echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_confirm" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_confirm_error_msg'][$rt['usr_confirm_error']] . '</div></td></tr>');
						} else {
							echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left""><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td></tr>');
							echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left""><input type="password" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($rt['usr_confirm_value']) . '" />&nbsp;<img src="/img/sico_ok.gif" align="absmiddle" alt="ok" /></td></tr>');
						}
					} else {
						echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_password_error_msg'][$rt['usr_password_error']] . '</div></td></tr>');
					echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" /></td></tr>');
					}
					break;
				case 'c':
					echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_password" value="' . make_single_return($rt['usr_password_value']) . '" /></td></tr>');
					echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_confirm" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_confirm_error_msg'][$rt['usr_confirm_error']] . '</div></td></tr>');
					break;
				case 'd':
					echo('<tr><td width="200" align="right">新密码</td><td width="200" align="left"><div class="error"><input type="password" maxlength="32" class="sl" name="usr_password" />&nbsp;<img src="/img/sico_error.gif" align="absmiddle" /><br />' . $rt['usr_password_error_msg'][$rt['usr_password_error']] . '</div></td></tr>');
					echo('<tr><td width="200" align="right">重复新密码</td><td width="200" align="left"><input type="password" maxlength="32" class="sl" name="usr_confirm" value="' . make_single_return($rt['usr_confirm_value']) . '" /></td></tr>');
					break;
			}
			
			/* E result: usr_password and usr_confirm */
			
			echo('</form></table></div>');
			echo('<div class="blank"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />如果你不打算修改密码的话，就不要在密码框处填入任何信息</div>');
		} else {
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />' . make_plaintext($rt['usr_nick_value']) . ' 的会员信息修改成功</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			echo('<tr><td width="200" align="right" valign="top">真实姓名</td><td align="left">' . make_plaintext($rt['usr_full_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">昵称</td><td align="left">' . make_plaintext($rt['usr_nick_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">自我简介</td><td align="left">' . make_plaintext($rt['usr_brief_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">家庭住址</td><td align="left">' . make_plaintext($rt['usr_addr_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">电话号码</td><td align="left">' . make_plaintext($rt['usr_telephone_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">身份证号码</td><td align="left">' . make_plaintext($rt['usr_identity_value']) . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">性别</td><td align="left">' . $this->User->usr_gender_a[$rt['usr_gender_value']] . '</td></tr>');
			echo('<tr><td width="200" align="right" valign="top">常用屏幕宽度</td><td align="left">' . $rt['usr_width_value'] . '</td></tr>');
			if ($rt['usr_password_touched'] == 1) {
				echo('<tr><td width="200" align="right" valign="top">新密码</td><td align="left"><div class="important">');
				$max = rand(1, 6) * 4;
				for ($i = 1; $i <= $max; $i++) {
					echo($i == 0) ? '':'&nbsp;&nbsp;';
					echo('<strong style="font-weight: ' . rand(1, 8) . '00; font-size: ' . rand(8,28) . 'px; border: 2px solid ' . rand_color(4, 5) . '; background-color: ' . rand_color(3, 5) . '; color: ' . rand_color(0, 2) . ';font-family: ' . rand_font() . ';">' . $rt['usr_password_value'] . '</strong>');
					echo (($i % 4 == 0) && ($i != 1)) ? '<br />':'';
				}
				echo('<br /><br />在你下次更改密码之前，你将使用这个长度为 ' . mb_strlen($rt['usr_password_value'], 'utf-8') . ' 个字符的密码进行登录，请花些时间记住这个密码</div></td></tr>');
			}
			echo('</table></div>');
			if ($rt['pswitch'] == 'b') {
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_tip.gif" align="absmiddle" class="home" />修改密码之后你现在将需要重新登录</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/login.vx" method="post" id="Login">');
				echo('<tr><td width="200" align="right">电子邮件或昵称</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="/img/silver/btn_login.gif" alt="' . Vocabulary::action_login . '" /></td></tr><tr><td width="200" align="right">密码</td><td align="left"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" /></td></tr></form></table></div>');
			} else {
				echo('<div class="blank" align="left"><img src="/img/ico_tip.gif" align="absmiddle" class="ico" />' . make_plaintext($this->User->usr_nick) . ' <span class="tip">&lt; ' . $this->User->usr_email . ' &gt;</span> 的会员信息已经更新，你可以点击这里 [ <a href="/user/modify.vx">再次修改</a> ]</div>');
			}
		}
		echo('</div>');
	}
	
	/* E module: User Update block */
	
	/* S module: Topic Favorite block */
	
	public function vxTopicFavorite() {
		$p = array();
		$p['base'] = '/topic/favorite/';
		$p['ext'] = '.vx';
		$sql = "SELECT COUNT(fav_id) FROM babel_favorite WHERE fav_uid = {$this->User->usr_id}";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_favorite . '</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_star.gif" align="absmiddle" class="home" />' . Vocabulary::term_favorite . '</span>');
		echo('<br />目前你共在 <a href="/">' . Vocabulary::site_name . '</a> 社区收藏了 ' . $p['items'] . ' 个项目');
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		if ($p['items'] > 0) {
			$p['size'] = BABEL_NOD_PAGE;
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
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			$sql = "SELECT fav_title, fav_author, fav_res, fav_type, fav_created FROM babel_favorite WHERE fav_uid = {$this->User->usr_id} ORDER BY fav_created DESC LIMIT {$p['sql']},{$p['size']}";
			$rs = mysql_query($sql, $this->db);
			$i = 0;
			while ($Fav = mysql_fetch_object($rs)) {
				$i++;
				echo('<tr>');
				switch ($Fav->fav_type) {
					default:
					case 0:
						echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/mico_topic.gif" /></td>');
						break;
					case 1:
						echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/mico_gear.gif" /></td>');
						break;
					case 2:
						echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/mico_news.gif" /></td>');
						break;
				}
				if ($i % 2 == 0) {
					$css_class = 'even';
				} else {
					$css_class = 'odd';
				}
				echo('<td class="' . $css_class . '" height="24" align="left">');
				switch ($Fav->fav_type) {
					default:
					case 0:
						echo('<a href="/topic/view/' . $Fav->fav_res . '.html" target="_self">' . make_plaintext($Fav->fav_title) . '</a>&nbsp;');
						break;
					case 1:
						echo('<a href="/board/view/' . $Fav->fav_res . '.html" target="_self">' . make_plaintext($Fav->fav_title) . '</a>&nbsp;');
						break;
					case 2:
						echo('<a href="/channel/view/' . $Fav->fav_res . '.html" target="_self">' . make_plaintext($Fav->fav_title) . '</a>&nbsp;');
						break;
				}
				echo('</td>');
					echo('<td class="' . $css_class . '" width="120" height="24" align="left">');
				switch ($Fav->fav_type) {
					default:
					case 0:
						echo(make_plaintext($Fav->fav_author));
						break;
					case 1:
						$section_a = explode(':', $Fav->fav_author);
						echo('<a href="/section/view/' . $section_a[1] . '.html">' . $section_a[0] . '</a>');
						break;
					case 2:
						$board_a = explode(':', $Fav->fav_author);
						echo('<a href="/board/view/' . $board_a[1] . '.html">' . $board_a[0] . '</a>');
						break;
				}
				echo('</td>');
				echo('<td class="' . $css_class . '" width="120" height="24" align="left"><small class="time">' . make_descriptive_time($Fav->fav_created) . '</small></td>');
				echo('</tr>');
			}
			mysql_free_result($rs);
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			echo('<tr><td align="left" class="hf" colspan="4">如何将不喜欢的主题移出收藏？<span class="text"><br /><br />如果你想把曾经收藏过的一篇主题从收藏中移出的话，你可以点击主题正文下面的“我不再喜欢这篇主题“按钮，然后你可以将这篇主题从收藏中移出来啦！</span></td></tr>');
		} else {
			echo('<tr><td align="left" class="hf">你现在还没有收藏任何喜欢的主题？<span class="text"><br /><br />如果你在 <a href="/">' . Vocabulary::site_name . '</a> 社区看到一篇你非常喜欢的主题，你可以点击主题正文下面的“我喜欢这篇主题“按钮，然后你可以将这篇主题收藏起来啦！</span></td></tr>');
		}
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Topic Favorite block */
	
	/* S module: Topic Fresh block */
	
	public function vxTopicFresh() {
		$p = array();
		$p['base'] = '/topic/fresh/';
		$p['ext'] = '.html';
		$sql = "SELECT COUNT(tpc_id) FROM babel_topic WHERE tpc_posts = 0";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_freshtopic . '</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_fresh.gif" align="absmiddle" class="home" />' . Vocabulary::action_freshtopic . '</span>');
		echo('<br /><a href="/">' . Vocabulary::site_name . '</a> 社区目前目前共有 ' . $p['items'] . ' 个未回复主题');
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		if ($p['items'] > 0) {
			$p['size'] = BABEL_NOD_PAGE;
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
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created, tpc_lastupdated, tpc_lasttouched, usr_id, usr_nick, usr_gender, usr_portrait FROM babel_topic, babel_user WHERE tpc_uid = usr_id AND tpc_posts = 0 ORDER BY tpc_lasttouched DESC, tpc_created DESC LIMIT {$p['sql']},{$p['size']}";
			$rs = mysql_query($sql, $this->db);
			$i = 0;
			while ($Topic = mysql_fetch_object($rs)) {
				$i++;
				$img_p = $Topic->usr_portrait ? '/img/p/' . $Topic->usr_portrait . '_n.jpg' : '/img/p_' . $Topic->usr_gender . '_n.gif';
				echo('<tr>');
				if ($Topic->usr_id == $this->User->usr_id) {
					echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_active.png" /></td>');
				} else {
					echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_inactive.png" /></td>');
				}
				if ($i % 2 == 0) {
					$css_class = 'even';
				} else {
					$css_class = 'odd';
				}
				echo('<td class="' . $css_class . '" height="30" align="left"><a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				if ($Topic->tpc_posts > 0) {
					echo('<small class="fade">(' . $Topic->tpc_posts . ')</small>');
				}
				echo('<small class="grey">+' . $Topic->tpc_hits . '</small>');
				echo('</td>');
				echo('<td class="' . $css_class . '" width="120" height="30" align="left"><a href="/u/' . $Topic->usr_nick . '"><img src="' . $img_p . '" class="portrait" align="absmiddle" border="0" /> ' . $Topic->usr_nick . '</a></td>');
				if ($Topic->tpc_lasttouched > $Topic->tpc_created) {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_lasttouched) . '</small></td>');
				} else {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_created) . '</small></td>');
				}
				echo('</tr>');
			}
			mysql_free_result($rs);
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
		}
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Topic Fresh block */
	
	/* S module: Channel View block */
	
	public function vxChannelView($Channel) {
		$Node = new Node($Channel->chl_pid, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		if ($this->User->vxIsLogin()) {
			$sql = "SELECT fav_id FROM babel_favorite WHERE fav_uid = {$this->User->usr_id} AND fav_type = 2 AND fav_res = {$Channel->chl_id}";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$Fav = mysql_result($rs, 0, 0);
			} else {
				$Fav = 0;
			}
			mysql_free_result($rs);
		} else {
			$Fav = 0;
		}
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/go/' . $Section->nod_name . '" target="_self">' . $Section->nod_title . '</a> &gt; <a href="/go/' . $Node->nod_name . '" target="_self">' . $Node->nod_title . '</a> &gt; ' . make_plaintext($Channel->chl_title));
		echo('</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large">');
		if ($Fav > 0) {
			$nod_ico = 'star';
		} else {
			$nod_ico = 'channel';
		}
		echo('<img src="/img/ico_' . $nod_ico . '.gif" align="absmiddle" class="home" />' . make_plaintext($Channel->chl_title));
		/* S: add to favorite */
		if ($this->User->vxIsLogin()) {
			if ($Fav > 0) {
				echo('<div id="chlFav" style="font-size: 12px; display: inline; margin-left: 10px;"><input type="image" onclick="removeFavoriteChannel(' . $Fav . ')" src="/img/tico_minus.gif" align="absmiddle" /></div>');
			} else {
				echo('<div id="chlFav" style="font-size: 12px; display: inline; margin-left: 10px;"><input type="image" onclick="addFavoriteChannel(' . $Channel->chl_id . ')" src="/img/tico_add.gif" align="absmiddle" /></div>');
			}
		}
		/* E: add to favorite */
		echo('</span>');
		echo('<br />本频道中共有 ' . count($Channel->rss->items) . ' 条消息');
		echo('，返回讨论版 <a href="/go/' . $Node->nod_name . '" class="nod">' . $Node->nod_title . '</a>');
		if ($Fav > 0) {
			echo('，你已经收藏了此频道');
		}
		if (!$Node->vxDrawChannels($Node->nod_id, $Channel->chl_id)) {
			echo('，无其他相关频道');
		}
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		$i = 0;
		if ($Fav > 0) {
			$img_item = 'star_active.png';
		} else {
			$img_item = 'star_inactive.png';
		}
		foreach ($Channel->rss->items as $Item) {
			$i++;
			echo('<tr>');
			
			echo('<td width="24" height="30" align="center" valign="middle" class="star"><!--<img src="/img/' . $img_item . '" />--></td>');
			if ($i % 2 == 0) {
				$css_class = 'even';
			} else {
				$css_class = 'odd';
			}
			$css_color = rand_color();
			echo('<td class="' . $css_class . '" height="30" align="left"><span class="rss_t"><a href="' . $Item['link'] . '" target="_blank" style="color: ' . $css_color . '" class="var">' . make_plaintext($Item['title']) . '</a>&nbsp;</span>');
			echo('</td>');
			
			if (isset($Item['pubdate'])) { // RSS 2.0
				$int_time = strtotime($Item['pubdate']);
			} else {
				if (isset($Item['dc']['date'])) { // RSS 0.9
					$int_time = strtotime($Item['dc']['date']);
				} else {
					if (isset($Item['created'])) { // Atom
						$int_time = strtotime($Item['created']);
					}
				}
			}
			
			echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($int_time) . '</small></td>');
			
			echo('</tr>');
		
			/* S: content */
			if (isset($Item['description'])) {
				if (isset($Item['content']['encoded'])) {
					$txt_content = trim($Item['content']['encoded']);
				} else {
					$txt_content = trim($Item['description']);
				}
				if (!preg_match('/(<br >)|(<table>)|(<div>)|(<p>)|(<\/p>)|(<p >)|(<br \/>)|(<br>)|(<br\/>)/i', $txt_content)) {
					$txt_content = nl2br($txt_content);
				} else {
					$txt_content = make_safe_display($txt_content);
				}
			} else {
				if (isset($Item['content']['encoded'])) {
					$txt_content = trim($Item['content']['encoded']);
					if (!preg_match('/(<br >)|(<table>)|(<div>)|(<p>)|(<\/p>)|(<p >)|(<br \/>)|(<br>)|(<br\/>)/i', $txt_content)) {
						$txt_content = nl2br($txt_content);
					} else {
						$txt_content = make_safe_display($txt_content);
					}	
				} else {
					$txt_content = '<a href="' . $Item['link'] . '" target="_blank">read more on ' . $Item['link'] . '</a>';
				}
			}
			echo('<tr>');
			echo('<td width="24" height="30" align="center" valign="middle" class="star"></td>');
			echo('<td class="' . $css_class . ' rss" align="left" colspan="2">');
			echo $txt_content;
			echo('</td>');
			echo('</tr>');
			
			/* E: content */
		}
		
		echo('<tr><td width="24" height="30" align="center" valign="middle" class="start"><img src="/img/pico_feed.gif" align="absmiddle" /></td>');
		echo('<td class="odd" align="left" colspan="2">欢迎使用 RSS 阅读器订阅本页种子 <a href="' . $Channel->chl_url . '" rel="nofollow external">' . $Channel->chl_url . '</a></td>');
		echo('</tr>');
		
		/* S ultimate cool flickr */
		
		if ($this->User->usr_id == 1) {
			$f = Image::vxFlickrBoardBlock($Node->nod_name, $this->User->usr_width, 3);
			echo $f;
			$this->cl->save($f, 'board_flickr_' . $Node->nod_name);
		} else {
			if ($f = $this->cl->get('board_flickr_' . $Node->nod_name)) {
				echo $f;
			} else {
				$f = Image::vxFlickrBoardBlock($Node->nod_name, $this->User->usr_width, 3);
				echo $f;
				$this->cl->save($f, 'board_flickr_' . $Node->nod_name);
			}
		}
		
		/* E ultimate cool flickr */
		
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Channel View block */
	
	/* S module: Board View block */
	
	public function vxBoardView($board_id) {
		global $GOOGLE_AD_LEGAL;
		
		$Node = new Node($board_id, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		if ($this->User->vxIsLogin()) {
			$sql = "SELECT fav_id FROM babel_favorite WHERE fav_uid = {$this->User->usr_id} AND fav_type = 1 AND fav_res = {$Node->nod_id}";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$Fav = mysql_result($rs, 0, 0);
			} else {
				$Fav = 0;
			}
			mysql_free_result($rs);
		} else {
			$Fav = 0;
		}
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/go/' . $Section->nod_name . '" target="_self">' . $Section->nod_title . '</a> &gt; ' . $Node->nod_title);
		echo('</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large">');
		if ($Fav > 0) {
			$nod_ico = 'star';
		} else {
			$nod_ico = 'board';
		}
		echo('<img src="/img/ico_' . $nod_ico . '.gif" align="absmiddle" class="home" />' . $Node->nod_title);
		/* S: add to favorite */
		if ($this->User->vxIsLogin()) {
			if ($Fav > 0) {
				echo('<div id="nodFav" style="font-size: 12px; display: inline; margin-left: 10px;"><input type="image" onclick="removeFavoriteNode(' . $Fav . ')" src="/img/tico_minus.gif" align="absmiddle" /></div>');
			} else {
				echo('<div id="nodFav" style="font-size: 12px; display: inline; margin-left: 10px;"><input type="image" onclick="addFavoriteNode(' . $Node->nod_id . ')" src="/img/tico_add.gif" align="absmiddle" /></div>');
			}
		}
		echo('&nbsp;<a href="/remix/' . $Node->nod_name . '"><img src="/img/tico_fw.gif" border="0" align="absmiddle" /></a>');
		/* E: add to favorite */
		echo('</span>');
		echo('<br />本讨论区中共有 ' . $Node->nod_topics . ' 个主题');
		/* S: how many favs */
		echo $Node->nod_favs ? '，' . $Node->nod_favs . ' 人收藏了此讨论区' : '，无人收藏此讨论区';
		echo('，<a href="/go/' . $Node->nod_name . '" class="var"><img src="/img/pico_fw.gif" align="absmiddle" border="0" /></a>&nbsp;<a href="/remix/' . $Node->nod_name . '" class="t">切换到 REMIX 模式</a>');
		/* E: how many favs */
		if (!$Node->vxDrawChannels()) {
			echo('，无相关频道');
		}
		
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		if (strlen($Node->nod_header) > 0) {
			echo('<tr><td align="left" class="hf" colspan="3">' . $Node->nod_header . '</td><td class="hf" align="right"><a href="/topic/new/' . $Node->nod_id . '.vx" target="_self" class="img"><img src="/img/silver/btn_topic_new.gif" alt="创建新主题" border="0" /></a></td></tr>');
		} else {
			echo('<tr><td align="left" class="hf" colspan="3"></td><td class="hf" align="right"><a href="/topic/new/' . $Node->nod_id . '.vx" target="_self" class="img"><img src="/img/silver/btn_topic_new.gif" alt="创建新主题" border="0" /></a></td></tr>');
		}
		$p = array();
		$p['base'] = '/board/view/' . $board_id . '/';
		$p['ext'] = '.html';
		$sql = "SELECT COUNT(tpc_id) FROM babel_topic WHERE tpc_pid = {$board_id}";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		if ($p['items'] > 0) {
			$p['size'] = BABEL_NOD_PAGE;
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
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
			
			// sticky topics
			$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created, tpc_lastupdated, tpc_lasttouched, usr_id, usr_nick, usr_gender, usr_portrait FROM babel_topic, babel_user WHERE tpc_uid = usr_id AND tpc_pid = {$Node->nod_id} AND tpc_flag = 2 ORDER BY tpc_lasttouched DESC, tpc_created DESC";
			$rs = mysql_query($sql, $this->db);
			$i = 0;
			while ($Topic = mysql_fetch_object($rs)) {
				$img_p = $Topic->usr_portrait ? '/img/p/' . $Topic->usr_portrait . '_n.jpg' : '/img/p_' . $Topic->usr_gender . '_n.gif';
				$i++;
				echo('<tr>');
				echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_sticky.gif" /></td>');
				if ($i % 2 == 0) {
					$css_class = 'even';
				} else {
					$css_class = 'odd';
				}
				echo('<td class="' . $css_class . '" height="30" align="left"><span class="tip">[置顶]</span> <a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				if ($Topic->tpc_posts > 0) {
					echo('<span class="tip_i"><small class="aqua">... ' . $Topic->tpc_posts . ' replies</small> <small>... viewed ' . $Topic->tpc_hits . ' times</small></span>');
				} else {
					echo('<span class="tip_i"><small>... no reply ... viewed ' . $Topic->tpc_hits . ' times</small></span>');
				}
				echo('</td>');
				echo('<td class="' . $css_class . '" width="120" height="30" align="left"><a href="/u/' . $Topic->usr_nick . '"><img src="' . $img_p . '" class="portrait" align="absmiddle" /> ' . $Topic->usr_nick . '</a></td>');
				if ($Topic->tpc_lasttouched > $Topic->tpc_created) {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_lasttouched) . '</small></td>');
				} else {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_created) . '</small></td>');
				}
				echo('</tr>');
			}
			mysql_free_result($rs);
			
			// normal topics
			$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created, tpc_lastupdated, tpc_lasttouched, usr_id, usr_nick, usr_gender, usr_portrait FROM babel_topic, babel_user WHERE tpc_uid = usr_id AND tpc_pid = {$Node->nod_id} AND tpc_flag = 0 ORDER BY tpc_lasttouched DESC, tpc_created DESC LIMIT {$p['sql']},{$p['size']}";
			$rs = mysql_query($sql, $this->db);
			while ($Topic = mysql_fetch_object($rs)) {
				$img_p = $Topic->usr_portrait ? '/img/p/' . $Topic->usr_portrait . '_n.jpg' : '/img/p_' . $Topic->usr_gender . '_n.gif';
				$i++;
				echo('<tr>');
				if ($Topic->usr_id == $this->User->usr_id) {
					echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_active.png" /></td>');
				} else {
					echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_inactive.png" /></td>');
				}
				if ($i % 2 == 0) {
					$css_class = 'even';
				} else {
					$css_class = 'odd';
				}
				echo('<td class="' . $css_class . '" height="30" align="left"><a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				if ($Topic->tpc_posts > 0) {
					$plural_posts = $Topic->tpc_posts > 1 ? 'replies' : 'reply';
					echo('<span class="tip_i"><small class="aqua">... ' . $Topic->tpc_posts . ' ' . $plural_posts . '</small> <small>... viewed ' . $Topic->tpc_hits . ' times</small></span>');
				} else {
					echo('<span class="tip_i"><small>... no reply ... viewed ' . $Topic->tpc_hits . ' times</small></span>');
				}
				echo('</td>');
				echo('<td class="' . $css_class . '" width="120" height="30" align="left"><a href="/u/' . $Topic->usr_nick . '"><img src="' . $img_p . '" class="portrait" align="absmiddle" /> ' . $Topic->usr_nick . '</a></td>');
				if ($Topic->tpc_lasttouched > $Topic->tpc_created) {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_lasttouched) . '</small></td>');
				} else {
					echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_created) . '</small></td>');
				}
				echo('</tr>');
			}
			mysql_free_result($rs);
			if ($p['total'] > 1) {
				echo('<tr><td align="left" class="hf" colspan="4">');
				$this->vxDrawPages($p);
				echo('</td></tr>');
			}
		}
		if (strlen($Node->nod_footer) > 0) {
			echo('<tr><td align="left" class="hf" colspan="4">' . $Node->nod_footer . '</td></tr>');
		}
		
		/* S ultimate cool flickr */
		$tag = $Node->nod_name;
		if ($this->User->usr_id == 1) {
			$f = Image::vxFlickrBoardBlock($tag, $this->User->usr_width, 4);
			echo $f;
			$this->cl->save($f, 'board_flickr_' . $tag);
		} else {
			if ($f = $this->cl->get('board_flickr_' . $tag)) {
				echo $f;
			} else {
				$f = Image::vxFlickrBoardBlock($tag, $this->User->usr_width, 4);
				echo $f;
				$this->cl->save($f, 'board_flickr_' . $tag);
			}
		}
		
		/* E ultimate cool flickr */
		
		/* S ultimate cool technorati */
		
		if (TN_API_ENABLED) {
			$tn = TN_PREFIX . $Node->nod_name;
			
			$T = fetch_rss($tn);
			echo('<tr><td align="left" class="hf" colspan="4" style="border-top: 1px solid #CCC;">');
			echo('<a href="http://www.technorati.com/tags/' . $Node->nod_name . '"><img src="/img/tn_logo.gif" align="absmiddle" border="0" /></a>&nbsp;&nbsp;&nbsp;<span class="tip_i">以下条目链接到外部的与本讨论主题 [ ' . $Node->nod_title . ' ] 有关的 Blog。</span>');
			echo('</td></tr>');
			$b = count($T->items) > 6 ? 6 : count($T->items);
			for ($i = 0; $i < $b; $i++) {
				$Related = $T->items[$i];
				$css_class = $i % 2 ? 'odd' : 'even';
				$css_color = rand_color();
				@$count = $Related['tapi']['inboundlinks'] + $Related['tapi']['inboundblogs'];
				$css_font_size = '12';
				echo('<tr><td width="24" height="22" align="center"><a href="' . $Related['comments'] . '" target="_blank" rel="nofollow external"><img src="/img/tnico_cosmos.gif" align="absmiddle" border="0" /></a></td>');
				echo('<td class="' . $css_class . '" height="22" align="left">');
				if (isset($Related['title'])) {
					echo '<a href="' . $Related['link'] . '" target="_blank" rel="external nofollow" class="var" style="color: ' . $css_color . '; font-size: ' . $css_font_size . 'px;">' . $Related['title'] . '</a>';
				} else {
					echo '<a href="' . $Related['link'] . '" target="_blank" rel="external nofollow">' . $Related['link'] . '</a>';
				}
				echo('</td>');
				
				echo('<td class="' . $css_class . '" width="120" height="22" align="left">');
				if (isset($Related['tapi']['inboundlinks'])) {
					echo('<span class="tip_i"><small>' . $Related['tapi']['inboundlinks'] . ' inbound links</small></span>');
				}
				echo('</td>');
				echo('<td class="' . $css_class . '" width="120" height="22" align="left"><small class="time">' . make_descriptive_time($Related['date_timestamp']) . '</small></td>');
				echo('</tr>');
			}
		}
		
		/* E ultimate cool technorati */

		if (GOOGLE_AD_ENABLED && $GOOGLE_AD_LEGAL && ($this->User->usr_width > 800)) {
			echo('<tr>');
			echo('<td align="center" class="odd" colspan="4" style="border-top: 1px solid #CCC; padding-top: 10px; padding-bottom: 10px;">');
			echo('<iframe src="/cts/728x90.html" width="728" height="90" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe>');
			echo('</td>');
			echo('</tr>');
		}
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Board View block */
	
	/* S module: Topic Archive User block */
	
	public function vxTopicArchiveUser($User) {
		global $GOOGLE_AD_LEGAL;
		
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/u/' . make_plaintext($User->usr_nick) . '" target="_self">' . make_plaintext($User->usr_nick) . '</a> &gt; 所有主题');
		echo('</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large">');
		$ico = 'board';
		echo('<img src="/img/ico_' . $ico . '.gif" align="absmiddle" class="home" /><a href="/u/' . make_plaintext($User->usr_nick) . '">' . make_plaintext($User->usr_nick) . '</a>');
		echo('</span>');
		$sql = "SELECT SUM(tpc_hits) AS tpc_hits_user_total FROM babel_topic WHERE tpc_uid = {$User->usr_id}";
		$rs = mysql_query($sql, $this->db);
		$count_hits = mysql_result($rs, 0, 0);
		mysql_free_result($rs);

		$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts, tpc_created, tpc_lastupdated, tpc_lasttouched, usr_id, usr_nick, usr_gender, usr_portrait FROM babel_topic, babel_user WHERE tpc_uid = usr_id AND tpc_uid = {$User->usr_id} AND tpc_flag IN (0, 2) ORDER BY tpc_lasttouched DESC, tpc_created DESC";
		$rs = mysql_query($sql, $this->db);
		$count_topics = mysql_num_rows($rs);
		
		echo('<br /><span class="tip_i"><a href="/u/' . $User->usr_nick . '">' . make_plaintext($User->usr_nick) . '</a>，共创建过 ' . $count_topics . ' 个主题，');
		if ($this->tpc_count > 0) {
			printf("占整个社区的比率 %.3f%%，", ($count_topics / $this->tpc_count) * 100);
		}
		echo('这些主题一共被点击过 ' . $count_hits . ' 次。');
		
		echo('</span></div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		echo('<tr><td align="left" class="hf" colspan="3"></td><td class="hf" align="right"></td></tr>');
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$img_p = $Topic->usr_portrait ? '/img/p/' . $Topic->usr_portrait . '_n.jpg' : '/img/p_' . $Topic->usr_gender . '_n.gif';
			$i++;
			echo('<tr>');
			echo('<td width="24" height="30" align="center" valign="middle" class="star"><img src="/img/star_inactive.png" /></td>');
			if ($i % 2 == 0) {
				$css_class = 'even';
			} else {
				$css_class = 'odd';
			}
			echo('<td class="' . $css_class . '" height="30" align="left"><a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
			if ($Topic->tpc_posts > 0) {
				$plural_posts = $Topic->tpc_posts > 1 ? 'replies' : 'reply';
				echo('<span class="tip_i"><small class="aqua">... ' . $Topic->tpc_posts . ' ' . $plural_posts . '</small> <small>... viewed ' . $Topic->tpc_hits . ' times</small></span>');
			} else {
				echo('<span class="tip_i"><small>... no reply ... viewed ' . $Topic->tpc_hits . ' times</small></span>');
			}
			echo('</td>');
			echo('<td class="' . $css_class . '" width="120" height="30" align="left"><a href="/u/' . $Topic->usr_nick . '"><img src="' . $img_p . '" class="portrait" align="absmiddle" /> ' . $Topic->usr_nick . '</a></td>');
			if ($Topic->tpc_lasttouched > $Topic->tpc_created) {
				echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_lasttouched) . '</small></td>');
			} else {
				echo('<td class="' . $css_class . '" width="120" height="30" align="left"><small class="time">' . make_descriptive_time($Topic->tpc_created) . '</small></td>');
			}
			echo('</tr>');
		}
		mysql_free_result($rs);
		echo('<tr><td align="left" class="hf" colspan="4"></td></tr>');
		
		/* S ultimate cool flickr */
		$tag = $User->usr_nick;
		if ($this->User->usr_id == 1) {
			$f = Image::vxFlickrBoardBlock($tag, $this->User->usr_width, 4);
			echo $f;
			$this->cl->save($f, 'board_flickr_' . $tag);
		} else {
			if ($f = $this->cl->get('board_flickr_' . $tag)) {
				echo $f;
			} else {
				$f = Image::vxFlickrBoardBlock($tag, $this->User->usr_width, 4);
				echo $f;
				$this->cl->save($f, 'board_flickr_' . $tag);
			}
		}
		
		/* E ultimate cool flickr */
		
		/* S ultimate cool technorati */
		
		if (TN_API_ENABLED) {
			$tn = TN_PREFIX . $User->usr_nick;
			
			if ($T = fetch_rss($tn)) {
				echo('<tr><td align="left" class="hf" colspan="4" style="border-top: 1px solid #CCC;">');
				echo('<a href="http://www.technorati.com/tags/' . $User->usr_nick . '"><img src="/img/tn_logo.gif" align="absmiddle" border="0" /></a>&nbsp;&nbsp;&nbsp;<span class="tip_i">以下条目链接到外部的与本讨论主题 [ ' . $User->usr_nick . ' ] 有关的 Blog。</span>');
				echo('</td></tr>');
				$b = count($T->items) > 6 ? 6 : count($T->items);
				for ($i = 0; $i < $b; $i++) {
					$Related = $T->items[$i];
					if (isset($Related['link'])) {
						$css_class = $i % 2 ? 'odd' : 'even';
						$css_color = rand_color();
						@$count = $Related['tapi']['inboundlinks'] + $Related['tapi']['inboundblogs'];
						$css_font_size = '12';
						if (isset($Related['comments'])) {
							echo('<tr><td width="24" height="22" align="center"><a href="' . $Related['comments'] . '" target="_blank" rel="nofollow external"><img src="/img/tnico_cosmos.gif" align="absmiddle" border="0" /></a></td>');
						} else {
							echo('<tr><td width="24" height="22" align="center"><img src="/img/tnico_cosmos.gif" align="absmiddle" border="0" /></td>');
						}
						echo('<td class="' . $css_class . '" height="22" align="left">');	
						if (isset($Related['title'])) {
							echo '<a href="' . $Related['link'] . '" target="_blank" rel="external nofollow" class="var" style="color: ' . $css_color . '; font-size: ' . $css_font_size . 'px;">' . $Related['title'] . '</a>';
						} else {
							echo '<a href="' . $Related['link'] . '" target="_blank" rel="external nofollow">' . $Related['link'] . '</a>';
						}
						echo('</td>');
						
						echo('<td class="' . $css_class . '" width="120" height="22" align="left">');
						if (isset($Related['tapi']['inboundlinks'])) {
							echo('<span class="tip_i"><small>' . $Related['tapi']['inboundlinks'] . ' inbound links</small></span>');
						}
						echo('</td>');
						if (isset($Related['date_timestamp'])) {
							$t = $Related['date_timestamp'];
						} else {
							$t = time();
						}
						echo('<td class="' . $css_class . '" width="120" height="22" align="left"><small class="time">' . make_descriptive_time($t) . '</small></td>');
						echo('</tr>');
					}
				}
			}
		}
		
		if (GOOGLE_AD_ENABLED && $GOOGLE_AD_LEGAL && ($this->User->usr_width > 800)) {
			echo('<tr>');
			echo('<td align="center" class="odd" colspan="4" style="border-top: 1px solid #CCC; padding-top: 10px; padding-bottom: 10px;">');
			echo('<iframe src="/cts/728x90.html" width="728" height="90" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe>');
			echo('</td>');
			echo('</tr>');
		}
		echo('</table>');
		echo('</div>');
	}

	/* E module: Topic Archive User block */
	
	/* S module: Topic Modify block */
	
	public function vxTopicModify($Topic) {
		$Node = new Node($Topic->tpc_pid, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		$permit = 0;
		if ($this->User->usr_id == $Topic->tpc_uid) {
			$permit = 1;
		}
		if ($this->User->usr_id == 1) {
			$permit = 1;
		}
		echo('<div id="main">');
		if ($permit == 1) {
			echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> &gt; ' . Vocabulary::action_modifytopic . '</div>');
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_conf.gif" align="absmiddle" class="home" />' . Vocabulary::action_modifytopic . '</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			echo('<form action="/topic/update/' . $Topic->tpc_id . '.vx" method="post">');
			echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><input type="text" class="sll" name="tpc_title" value="' . make_single_return($Topic->tpc_title, 0) . '" /></td></tr>');
			echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><textarea rows="5" class="ml" name="tpc_description">' . make_multi_return($Topic->tpc_description, 0) . '</textarea></td></tr>');
			echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="tpc_content">' . make_multi_return($Topic->tpc_content, 0) . '</textarea></td></tr>');
			echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><img src="/img/pico_left.gif" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html">返回主题 / ' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_modify.gif" alt="' . Vocabulary::action_modifytopic . '" align="absmiddle" /></span></td></tr>');
			echo('</form>');
			echo('</table>');
			echo('</div>');
		} else {
			echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> &gt; ' . Vocabulary::action_modifytopic . ' &gt; <strong>Access Denied</strong></div>');
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_bomb.gif" align="absmiddle" class="home" />Access Denied</span><br />你在一个你不应该到达的地方，停止你的任何无意义的尝试吧</div>');
		}
		echo('</div>');
	}
	
	/* E module: Topic Modify block */
	
	/* S module: Topic Update block */
	
	public function vxTopicUpdate($rt) {
		$Topic = new Topic($rt['topic_id'], $this->db);
		$Node = new Node($Topic->tpc_pid, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		echo('<div id="main">');
		if ($rt['permit'] == 1) {
			if ($rt['errors'] == 0) {
				$usr_money_a = $this->User->vxParseMoney(abs($rt['exp_amount']));
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> &gt; ' . Vocabulary::action_modifytopic . '</div>');
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />主题成功修改</span><br />主题 [ <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> ] 成功更新，<strong>修改该主题花费了你的' . $usr_money_a['str'] . '</strong>，将在 3 秒内自动转向到你刚才创建的主题<br /><br /><img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html">立刻转到刚才被修改的主题 / ' . $Topic->tpc_title . '</a><br /><br />');
				echo('<img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Node->nod_name . '">转到主题所在讨论区 / ' . make_plaintext($Node->nod_title) . '</a><br /><br />');
				echo('<img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Section->nod_name . '">转到主题所在区域 / ' . make_plaintext($Section->nod_title) . '</a><br /><br />');
				echo('<span class="tip_i">' . Vocabulary::site_name . ' 感谢你对细节的关注！</span>');
				echo('</div>');
			} else {
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . $Topic->tpc_id . '</a> &gt; ' . Vocabulary::action_modifytopic . '</div>');
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />对不起，你刚才提交的信息里有些错误</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/topic/update/' . $Topic->tpc_id . '.vx" method="post">');
				if ($rt['tpc_title_error'] > 0) {
					echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><div class="error"><input type="text" class="sll" name="tpc_title" value="' . make_single_return($rt['tpc_title_value']) . '" /><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_title_error_msg'][$rt['tpc_title_error']] . '</div></td></tr>');
				} else {
					echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><input type="text" class="sll" name="tpc_title" value="' . make_single_return($rt['tpc_title_value']) . '" /></td></tr>');
				}
				if ($rt['tpc_description_error'] > 0) {
					echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><div class="error"><textarea rows="5" class="ml" name="tpc_description">' . make_multi_return($rt['tpc_description_value']) . '</textarea><br /><img src="/ico/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_description_error_msg'][$rt['tpc_description_error']] . '</div></td></tr>');
				} else {
					echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><textarea rows="5" class="ml" name="tpc_description">' . make_multi_return($rt['tpc_description_value']) . '</textarea></td></tr>');
				}
				if ($rt['tpc_content_error'] > 0) {
					echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><div class="error"><textarea rows="15" class="ml" name="tpc_content">' . make_multi_return($rt['tpc_content_value']) . '</textarea><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_content_error_msg'][$rt['tpc_content_error']] . '</div></td></tr>');
				} else {
					echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="tpc_content">' . make_multi_return($rt['tpc_content_value']) . '</textarea></td></tr>');
				}
				echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><small>&lt;&lt;&nbsp;</small><a href="/topic/view/' . $Topic->tpc_id . '.html">返回 ' . $Topic->tpc_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_modify.gif" alt="' . Vocabulary::action_modifytopic . '" align="absmiddle" /></span></td></tr>');
				echo('</form>');
				echo('</table>');
				echo('</div>');
			}
		} else {
			echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> &gt; ' . Vocabulary::action_modifytopic . ' &gt; <strong>Access Denied</strong></div>');
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_bomb.gif" align="absmiddle" class="home" />Access Denied</span><br />你在一个你不应该到达的地方，停止你的任何无意义的尝试吧</div>');
		}
		echo('</div>');
	}
	
	/* E module: Topic Update block */
	
	/* S module: Topic New block */
	
	public function vxTopicNew($options) {
		switch ($options['mode']) {
			case 'board':
				$Node = new Node($options['board_id'], $this->db);
				$Section = $Node->vxGetNodeInfo($Node->nod_sid);
				echo('<div id="main">');
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; ' . Vocabulary::action_newtopic . '</div>');
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_conf.gif" align="absmiddle" class="home" />' . Vocabulary::action_newtopic . '</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/topic/create/' . $Node->nod_id . '.vx" method="post">');
				echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><input type="text" class="sll" name="tpc_title" /></td></tr>');
				echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><textarea rows="5" class="ml" name="tpc_description"></textarea></td></tr>');
				echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="tpc_content"></textarea></td></tr>');
				echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><img src="/img/pico_left.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Node->nod_name . '">返回讨论区 / ' . $Node->nod_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_new.gif" alt="' . Vocabulary::action_newtopic . '" align="absmiddle" /></span></td></tr>');
				echo('</form>');
				echo('</table>');
				echo('</div>');
				echo('</div>');
				break;
			case 'section':
				$Section = new Node($options['section_id'], $this->db);
				echo('<div id="main">');
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; ' . Vocabulary::action_newtopic . '</div>');
				echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_conf.gif" align="absmiddle" class="home" />' . Vocabulary::action_newtopic . '</span>');
				echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
				echo('<form action="/topic/create/' . $Section->nod_id . '.vx" method="post">');
				echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><input type="text" class="sll" name="tpc_title" /></td></tr>');
				echo('<tr><td width="100" align="right">位于</td><td width="400" align="left"><select name="tpc_pid">');
				$Children = $Section->vxGetNodeChildren();
				$i = 0;
				while ($Node = mysql_fetch_object($Children)) {
					$i++;
					if ($i == 0) {
						echo('<option value="' . $Node->nod_id . '" selected="selected">' . $Node->nod_title . '</option>');
					} else {
						echo('<option value="' . $Node->nod_id . '">' . $Node->nod_title . '</option>');
					}
				}
				mysql_free_result($Children);
				echo('</select></td></tr>');
				echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><textarea rows="5" class="ml" name="tpc_description"></textarea></td></tr>');
				echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="tpc_content"></textarea></td></tr>');
				echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><img src="/img/pico_left.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Section->nod_name . '">返回区域 / ' . $Section->nod_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_new.gif" align="absmiddle" alt="' . Vocabulary::action_newtopic . '" /></span></td></tr>');
				echo('</form>');
				echo('</table>');
				echo('</div>');
				echo('</div>');
				break;
		}
	}
	
	/* E module: Topic New block */
	
	/* S module: Topic Create block */
	
	public function vxTopicCreate($rt) {
		
		if ($rt['mode'] == 'board') {
			$Node = new Node($rt['board_id'], $this->db);
			$Section = $Node->vxGetNodeInfo($Node->nod_pid, $this->db);
		} else {
			if ($rt['tpc_pid_error'] == 0) {
				$Node = new Node($rt['tpc_pid_value'], $this->db);
			}
			$Section = new Node($rt['section_id'], $this->db);
		}
		
		if ($rt['errors'] == 0) {
		
			global $Topic;

			$Node->vxUpdateTopics();
			$usr_money_a = $this->User->vxParseMoney(abs($rt['exp_amount']));
			echo('<div id="main">');

			echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; ' . Vocabulary::action_newtopic . '</div>');
			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />新主题成功创建</span><br />新主题 [ <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> ] 成功创建，<strong>创建该长度为 ' . $rt['tpc_content_length'] . ' 个字符的主题花费了你的' . $usr_money_a['str'] . '</strong>，将在 3 秒内自动转向到你刚才创建的主题<br /><br /><img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html">立刻转到新主题 / ' . make_plaintext($Topic->tpc_title) . '</a><br /><br />');
			echo('<img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Node->nod_name . '">转到新主题所在讨论区 / ' . make_plaintext($Node->nod_title) . '</a><br /><br />');
			echo('<img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Section->nod_name . '">转到新主题所在区域 / ' . make_plaintext($Section->nod_title) . '</a><br /><br />');
			echo('<span class="tip">讨论区 ' . make_plaintext($Node->nod_title) . ' 中现在有 ' . ($Node->nod_topics + 1) . ' 篇主题，感谢你的贡献！</span>');
			echo('</div>');
			echo('</div>');
		
		} else {
		
			echo('<div id="main">');
			
			if ($rt['tpc_pid_error'] == 0) {
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; ' . Vocabulary::action_newtopic . '</div>');
			} else {
				echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html">' . $Section->nod_title . '</a> &gt; ' . Vocabulary::action_newtopic . '</div>');
			}

			echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />对不起，你刚才提交的信息里有些错误</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			
			if ($rt['mode'] == 'board') {
				echo('<form action="/topic/create/' . $Node->nod_id . '.vx" method="post">');
			} else {
				echo('<form action="/topic/create/' . $Section->nod_id . '.vx" method="post">');
			}
			
			if ($rt['tpc_title_error'] > 0) {
				echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><div class="error"><input type="text" class="sll" name="tpc_title" value="' . make_single_return($rt['tpc_title_value']) . '" /><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_title_error_msg'][$rt['tpc_title_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="100" align="right">标题</td><td width="400" align="left"><input type="text" class="sll" name="tpc_title" value="' . make_single_return($rt['tpc_title_value']) . '" /></td></tr>');
			}
			
			if ($rt['mode'] == 'section') {
				if ($rt['tpc_pid_error'] > 0) {
					echo('<tr><td width="100" align="right">位于</td><td width="400" align="left"><div class="error"><select name="tpc_pid">');
				} else {
					echo('<tr><td width="100" align="right">位于</td><td width="400" align="left"><select name="tpc_pid">');
				}
				
				$Children = $Section->vxGetNodeChildren();
				$i = 0;
				while ($O = mysql_fetch_object($Children)) {
					$i++;
					if ($rt['tpc_pid_error'] > 0) {
						if ($i == 1) {
							echo('<option value="' . $O->nod_id . '" selected="selected">' . $O->nod_title . '</option>');
						} else {
							echo('<option value="' . $O->nod_id . '">' . $O->nod_title . '</option>');
						}
					} else {
						if ($O->nod_id == $rt['tpc_pid_value']) {
							echo('<option value="' . $O->nod_id . '" selected="selected">' . $O->nod_title . '</option>');
						} else {
							echo('<option value="' . $O->nod_id . '">' . $O->nod_title . '</option>');
						}
					}
					$O = null;
				}
				
				if ($rt['tpc_pid_error'] > 0) {
					echo ('</select><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_pid_error_msg'][$rt['tpc_pid_error']] . '</div></td></tr>');
				} else {
					echo ('</select></td></tr>');
				}
			}
			
			if ($rt['tpc_description_error'] > 0) {
				echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><div class="error"><textarea rows="5" class="ml" name="tpc_description">' . make_multi_return($rt['tpc_description_value']) . '</textarea><br /><img src="/ico/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_description_error_msg'][$rt['tpc_description_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="100" align="right" valign="top">主题简介</td><td width="400" align="left"><textarea rows="5" class="ml" name="tpc_description">' . make_multi_return($rt['tpc_description_value']) . '</textarea></td></tr>');
			}
			if ($rt['tpc_content_error'] > 0) {
				echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><div class="error"><textarea rows="15" class="ml" name="tpc_content">' . make_multi_return($rt['tpc_content_value']) . '</textarea><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['tpc_content_error_msg'][$rt['tpc_content_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="100" align="right" valign="top">主题内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="tpc_content">' . make_multi_return($rt['tpc_content_value']) . '</textarea></td></tr>');
			}
			if ($rt['mode'] == 'board') {
				echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><img src="/img/pico_left.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Node->nod_name . '">返回讨论区 / ' . $Node->nod_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_new.gif" alt="' . Vocabulary::action_newtopic . '" align="absmiddle" /></span></td></tr>');
			} else {
				echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><img src="/img/pico_left.gif" align="absmiddle" />&nbsp;<a href="/go/' . $Section->nod_name . '">返回区域 / ' . $Section->nod_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_new.gif" align="absmiddle" alt="' . Vocabulary::action_newtopic . '" /></span></td></tr>');
			}
			echo('</form>');
			echo('</table>');
			echo('</div>');
			echo('</div>');

		}
	}
	
	/* E module: Topic Create block */
	
	/* S module: Post Create block */
	
	public function vxPostCreate($rt) {
		$Topic = new Topic($rt['topic_id'], $this->db);
		$Node = new Node($Topic->tpc_pid, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/section/view/' . $Section->nod_id . '.html" target="_self">' . $Section->nod_title . '</a> &gt; <a href="/board/view/' . $Node->nod_id . '.html">' . $Node->nod_title . '</a> &gt; <a href="/topic/view/' . $Topic->tpc_id . '.html">' . make_plaintext($Topic->tpc_title) . '</a> &gt; ' . Vocabulary::action_replytopic . '</div>');
		if ($rt['errors'] > 0) {
			echo('<div id="vxReply" align="left"><span class="text_large"><img src="/img/ico_important.gif" align="absmiddle" class="home" />' . Vocabulary::msg_submitwrong . '</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			echo('<form action="/post/create/' . $Topic->tpc_id . '.vx" method="post">');
			if ($rt['pst_title_error'] > 0) {
				echo('<tr><td width="100" align="right">回复标题</td><td width="400" align="left"><div class="error"><input type="text" class="sll" name="pst_title" value="' . make_single_return($rt['pst_title_value']) . '" /><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . $rt['pst_title_error_msg'][$rt['pst_title_error']] . '</div></td></tr>');
			} else {
				echo('<tr><td width="100" align="right">回复标题</td><td width="400" align="left"><input type="text" class="sll" name="pst_title" value="' . make_single_return($rt['pst_title_value']) . '" /></td></tr>');
			}
			if ($rt['pst_content_error'] > 0) {
				echo('<tr><td width="100" align="right" valign="top">回复内容</td><td width="400" align="left"><div class="error"><textarea rows="15" class="ml" name="pst_content">' . $rt['pst_content_value'] . '</textarea><br /><img src="/img/sico_error.gif" align="absmiddle" />&nbsp;' . make_multi_return($rt['pst_content_error_msg'][$rt['pst_content_error']]) . '</div></td></tr>');
			} else {
				echo('<tr><td width="100" align="right" valign="top">回复内容</td><td width="400" align="left"><textarea rows="15" class="ml" name="pst_content">' . make_multi_return($rt['pst_content_value']) .'</textarea></td></tr>');
			}
			echo('<td width="500" colspan="3" valign="middle" align="right"><span class="tip"><small>&lt;&lt;&nbsp;</small><a href="/topic/view/' . $Topic->tpc_id . '.html">返回 ' . $Topic->tpc_title . '</a>&nbsp;&nbsp;&nbsp;<input type="image" src="/img/silver/btn_topic_reply.gif" alt="' . Vocabulary::action_replytopic . '" align="absmiddle" /></span></td></tr>');
			echo('</form>');
			echo('</table>');
			echo('</div>');
		} else {
			$usr_money_a = $this->User->vxParseMoney(abs($rt['exp_amount']));
			$Topic->vxTouch();
			$Topic->vxUpdatePosts();
			echo('<div class="blank"><span class="text_large"><img src="/img/ico_smile.gif" align="absmiddle" class="home" />主题回复成功</span><br />你已经成功回复了一篇主题，<strong>回复该主题花费了' . $usr_money_a['str'] . '</strong>，将在 3 秒内自动返回到主题<br /><br /><img src="/img/pico_right.gif" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self">转到刚才回复的主题 / ' . make_plaintext($Topic->tpc_title) . '</a></div>');
		}
		echo('</div>');
	}
	
	/* E module: Post Create block */
	
	/* S module: Topic Top block */
	
	public function vxTopicTop() {
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::term_toptopic . '</div>');
		echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_top.gif" align="absmiddle" align="" class="ico" />' . Vocabulary::term_toptopic . '</span></div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		echo('<tr><td width="50%" align="left" valign="top" class="container"><table width="100%" cellpadding="0" cellspacing="0" border="0" class="drawer"><tr><td height="18" class="orange">最多回复主题 Top 50</td></tr>');
		$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts FROM babel_topic WHERE tpc_flag IN (0, 2) ORDER BY tpc_posts DESC LIMIT 50";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$i++;
			$css_font_size = $this->vxGetItemSize($Topic->tpc_posts);
			if ($Topic->tpc_posts > 3) {
				$css_color = rand_color();
			} else {
				$css_color = rand_gray(2, 4);
			}
			if ($Topic->tpc_uid == $this->User->usr_id) {
				$img_star = 'star_active.png';
			} else {
				$img_star = 'star_inactive.png';
			}
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><img src="/img/' . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;<small class="fade">(' . $Topic->tpc_posts . ')</small><small class="grey">+' . $Topic->tpc_hits . '</small>&nbsp;<a href="/board/view/' . $Topic->tpc_pid . '.html" target="_self" class="img"><img src="/img/arrow.gif" border="0" align="absmiddle" /></a></td></tr>');
			} else {
				echo('<tr><td class="odd" height="20"><img src="/img/' . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;<small class="fade">(' . $Topic->tpc_posts . ')</small><small class="grey">+' . $Topic->tpc_hits . '</small>&nbsp;<a href="/board/view/' . $Topic->tpc_pid . '.html" target="_self" class="img"><img src="/img/arrow.gif" border="0" align="absmiddle" /></a></td></tr>');
			}
		}
		mysql_free_result($rs);
		
		echo('</table></td><td width="50%" align="left" valign="top" class="container"><table width="100%" cellpadding="0" cellspacing="0" border="0" class="drawer"><tr><td width="50%" height="18" class="blue">最多点击主题 Top 50</td></tr>');
		$sql = "SELECT tpc_id, tpc_pid, tpc_uid, tpc_title, tpc_hits, tpc_posts FROM babel_topic WHERE tpc_flag = 0 ORDER BY tpc_hits DESC LIMIT 50";
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		while ($Topic = mysql_fetch_object($rs)) {
			$i++;
			$css_font_size = $this->vxGetItemSize($Topic->tpc_posts);
			if ($Topic->tpc_posts > 3) {
				$css_color = rand_color();
			} else {
				$css_color = rand_gray(2, 4);
			}
			if ($Topic->tpc_uid == $this->User->usr_id) {
				$img_star = 'star_active.png';
			} else {
				$img_star = 'star_inactive.png';
			}
			if (($i % 2) == 0) {
				echo('<tr><td class="even" height="20"><img src="/img/' . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				if ($Topic->tpc_posts > 0) {
					echo('<small class="fade">(' . $Topic->tpc_posts . ')</small>');
				}
				echo('<small class="grey">+' . $Topic->tpc_hits . '</small>&nbsp;<a href="/board/view/' . $Topic->tpc_pid . '.html" target="_self" class="img"><img src="/img/arrow.gif" border="0" align="absmiddle" /></a></td></tr>');
			} else {
				echo('<tr><td class="odd" height="20"><img src="/img/' . $img_star . '" align="absmiddle" />&nbsp;<a href="/topic/view/' . $Topic->tpc_id . '.html" target="_self" style="font-size: ' . $css_font_size . 'px; color: ' . $css_color . ';" class="var">' . make_plaintext($Topic->tpc_title) . '</a>&nbsp;');
				if ($Topic->tpc_posts > 0) {
					echo('<small class="fade">(' . $Topic->tpc_posts . ')</small>');
				}
				echo('<small class="grey">+' . $Topic->tpc_hits . '</small>&nbsp;<a href="/board/view/' . $Topic->tpc_pid . '.html" target="_self" class="img"><img src="/img/arrow.gif" border="0" align="absmiddle" /></a></td></tr>');
			}
		}
		mysql_free_result($rs);
		echo('</table></td></tr>');
		echo('</table>');
		echo('</div>');
	}
	
	/* E module: Topic Top block */
	
	/* S module: Topic View block */
	
	public function vxTopicView($topic_id) {
		$Topic = new Topic($topic_id, $this->db, 1, 1);
		$Node = new Node($Topic->tpc_pid, $this->db);
		$Section = $Node->vxGetNodeInfo($Node->nod_sid);
		if ($this->User->vxIsLogin()) {
			$sql = "SELECT fav_id FROM babel_favorite WHERE fav_uid = {$this->User->usr_id} AND fav_type = 0 AND fav_res = {$Topic->tpc_id}";
			$rs = mysql_query($sql, $this->db);
			if (mysql_num_rows($rs) == 1) {
				$Fav = mysql_result($rs, 0, 0);
			} else {
				$Fav = 0;
			}
			mysql_free_result($rs);
		} else {
			$Fav = 0;
		}
		echo('<div id="main">');
		echo('<div class="blank">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/go/' . $Section->nod_name . '" target="_self">' . $Section->nod_title . '</a> &gt; <a href="/go/' . $Node->nod_name . '" target="_self">' . $Node->nod_title . '</a> &gt; ' . make_plaintext($Topic->tpc_title) . '</div>');
		
		echo('<div class="blank"><table cellpadding="0" cellspacing="0" border="0">');
		echo('<tr><td valign="top" align="center"><a name="imgPortrait"></a>');
		if ($Topic->usr_portrait == '') {
			echo('<a href="/u/' . $Topic->usr_nick . '" class="var"><img src="/img/p_' . $Topic->usr_gender . '.gif" style="margin-bottom: 5px;" class="portrait" /></a><br /><a href="/u/' . $Topic->usr_nick . '">' . $Topic->usr_nick . '</a>');
		} else {
			echo('<a href="/u/' . $Topic->usr_nick . '" class="var"><img src="/img/p/' . $Topic->usr_portrait . '.' . BABEL_PORTRAIT_EXT . '" style="margin-bottom: 5px;" class="portrait" /></a><br /><a href="/u/' . $Topic->usr_nick . '">' . $Topic->usr_nick . '</a>');
		}
		$sql = "SELECT COUNT(tpc_id) FROM babel_topic WHERE tpc_uid = {$Topic->usr_id}";
		$rs = mysql_query($sql, $this->db);
		if ($this->tpc_count > 0) {
			$usr_share = (mysql_result($rs, 0, 0) / $this->tpc_count) * 100;
		} else {
			$usr_share = 0;
		}
		mysql_free_result($rs);
		printf("<small class=\"grey\"><br /><br /><a href=\"/topic/archive/user/{$Topic->usr_nick}\">%.3f%%</a></small>", $usr_share);
		if ($this->User->vxIsLogin()) {
			if ($Topic->usr_id != $this->User->usr_id) {
				echo("<br /><br /><button class=\"mini\" onclick=\"sendMessage({$Topic->usr_id})\"><img src=\"/img/tico_mail_send.gif\" border=\"0\" /></button>");
			}
		}
		echo('</td><td valign="top" align="right" class="text">');
		if ($this->User->vxIsLogin()) {
			echo('<a href="#replyForm" onclick="jumpReply();">回复主题</a>');
		} else {
			echo('<a href="/login//topic/view/' . $Topic->tpc_id . '.html">登录后回复主题</a>');	
		}
		echo(' | ');
		if (strlen($Topic->tpc_description) > 0) {
			echo('<a href="#" onclick="switchDisplay(' . "'" . 'tpcBrief' . "'" . ');">切换简介显示</a> | ');
		}
		echo('<a href="#reply">跳到回复</a>');
		if ($Topic->tpc_posts > 0) {
			echo('<small class="fade">(' . $Topic->tpc_posts . ')</small>');
		}
		if ($this->User->usr_id == 1) {
			echo(' | <a href="/topic/move/' . $Topic->tpc_id . '.vx">移动主题</a>');
		}
		if ($Topic->tpc_uid == $this->User->usr_id) {
			echo(' | <a href="/topic/modify/' . $Topic->tpc_id . '.vx">修改主题</a>');
		} else {
			if ($this->User->usr_id == 1) {
				echo(' | <a href="/topic/modify/' . $Topic->tpc_id . '.vx">修改主题</a>');
			}
		}
		echo('<div class="brief" id="tpcBrief">' . $Topic->tpc_description . '</div><table cellpadding="0" cellspacing="0" border="0"><tr><td width="40" height="30" class="lt"></td><td height="30" class="ct"></td><td width="40" height="30" class="rt"></td></tr><tr><td width="40" class="lm" valign="top"><img src="/img/td_arrow.gif" /></td><td class="origin" valign="top"><span class="text_title">');
		if ($Fav > 0) {
			echo('<img src="/img/tico_star.gif" align="absmiddle" />&nbsp;');
		}
		echo('<strong>' . make_plaintext($Topic->tpc_title) . '</strong></span> <span class="tip_i">... by ' . $Topic->usr_nick . ' ... ' . make_descriptive_time($Topic->tpc_created) . '，' . $Topic->tpc_hits . ' 次点击 </span>');
		if ($this->User->usr_id == 1) {
			echo('&nbsp;<img src="/img/tico_erase.gif" align="absmiddle" onclick="if (confirm(' . "'确认擦除？'" . ')) {location.href=' . "'/topic/erase/{$Topic->tpc_id}.vx';" . '}" border="0" />');
		}
		echo('</span><br /><br />' . $Topic->tpc_content);
		
		echo('</span></td><td width="40" class="rm"></td></tr><tr><td width="40" height="20" class="lb"></td><td height="20" class="cb"></td><td width="40" height="20" class="rb"></td></tr></table></td></tr>');
		echo('</table>');
		echo('</div>');
		
		echo('<div class="blank" align="right">');
		
		/* S: left and right */
		
		$sql = "SELECT tpc_id, tpc_title FROM babel_topic WHERE tpc_created < {$Topic->tpc_created} AND tpc_uid = {$Topic->tpc_uid} ORDER BY tpc_created DESC LIMIT 1";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			$Left = mysql_fetch_object($rs);
			echo ('<a href="/topic/view/' . $Left->tpc_id . '.html" class="h"><span class="tip_i">&lt; ... </span>' . make_plaintext($Left->tpc_title) . '&nbsp;</a>');
		}
		mysql_free_result($rs);
		
		$sql = "SELECT tpc_id, tpc_title FROM babel_topic WHERE tpc_created > {$Topic->tpc_created} AND tpc_uid = {$Topic->tpc_uid} ORDER BY tpc_created ASC LIMIT 1";
		$rs = mysql_query($sql, $this->db);
		if (mysql_num_rows($rs) == 1) {
			$Right = mysql_fetch_object($rs);
			echo ('<a href="/topic/view/' . $Right->tpc_id . '.html" class="h">&nbsp;' . make_plaintext($Right->tpc_title) . '<span class="tip_i"> ... &gt;</span></a>');
		}
		mysql_free_result($rs);
		
		/* E: left and right */
		
		/* S: add to favorite */
		
		if ($this->User->vxIsLogin()) {
			if ($Fav > 0) {
				echo('<div id="tpcFav" style="display: inline;"><a onclick="removeFavoriteTopic(' . $Fav . ')" href="#;" class="h">X&nbsp;我不再喜欢这篇主题</a></div>');
			} else {
				echo('<div id="tpcFav" style="display: inline;"><a onclick="addFavoriteTopic(' . $Topic->tpc_id . ')" href="#;" class="h">:) 我喜欢这篇主题</a></div>');
			}
		}
		
		/* E: add to favorite */
		
		echo('</div>');
		echo('<div class="blank">');
		echo('<a name="reply" class="img"><img src="/img/spacer.gif" width="1" height="1" style="display: none;" /></a>');
		$p = array();
		$p['base'] = '/topic/view/' . $topic_id . '/';
		$p['ext'] = '.html';
		$sql = "SELECT COUNT(pst_id) FROM babel_post WHERE pst_tid = {$topic_id}";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		$Topic->tpc_reply_count = $p['items'];
		mysql_free_result($rs);
		$i = 0;
		if ($p['items'] > 0) {
			$p['size'] = BABEL_TPC_PAGE;
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
			$rs = $Topic->vxGetAllReply($p);
		}
		if ($Topic->tpc_reply_count > 0) {
			echo('<div id="vxReplyTop">本主题共有 ' . $Topic->tpc_posts . ' 条回复 | <a href="#top">回到顶部</a> | ');
			if ($this->User->vxIsLogin()) {
				echo('<a href="#replyForm" onclick="jumpReply();">回复主题</a>');
			} else {
				echo('<a href="/login//topic/view/' . $Topic->tpc_id . '.html">登录后回复主题</a>');	
			}
			if ($p['total'] > 1) {
				echo('<br /><br />');
				$this->vxDrawPages($p);
			}
			echo('</div>');
			$i = 0;
			while ($Reply = mysql_fetch_object($rs)) {
				if ($Reply->usr_portrait == '') {
					$img_usr_portrait = '/img/p_' . $Reply->usr_gender . '_s.gif';
				} else {
					$img_usr_portrait = '/img/p/' . $Reply->usr_portrait . '_s.' . BABEL_PORTRAIT_EXT;
				}
				if ($this->User->usr_id == 1) {
					$ico_erase = '&nbsp;<img src="/img/tico_erase.gif" align="absmiddle" onclick="if (confirm(' . "'确认擦除？'" . ')) {location.href=' . "'/post/erase/{$Reply->pst_id}.vx';" . '}" border="0" />';
				} else {
					$ico_erase = '';
				}
				$i++;
				$j = ($p['cur'] - 1) * 60 + $i;
				if (substr($Reply->pst_title, 0, 4) == 'Re: ') {
					if ($Reply->usr_id == $Topic->tpc_uid) {
						$txt_title = $j . ' 楼 <strong class="red">**</strong> <a href="/u/' . $Reply->usr_nick . '">' . $Reply->usr_nick . '</a> @ ' . make_descriptive_time($Reply->pst_created);
					} else {
						$txt_title = $j . ' 楼 <a href="/u/' . $Reply->usr_nick . '">' . $Reply->usr_nick . '</a> @ ' . make_descriptive_time($Reply->pst_created);
					}
				} else {
					if ($Reply->usr_id == $Topic->tpc_uid) {
						$txt_title = $j . ' 楼 <strong class="red">**</strong> <a href="/u/' . $Reply->usr_nick . '">' . $Reply->usr_nick . '</a> @ ' . make_descriptive_time($Reply->pst_created) . '说: ' . $Reply->pst_title;
					} else {
						$txt_title = $j . ' 楼 <a href="/u/' . $Reply->usr_nick . '">' . $Reply->usr_nick . '</a> @ ' . make_descriptive_time($Reply->pst_created) . '说: ' . $Reply->pst_title;
					}
				}
				
				$txt_title .= $ico_erase;
				
				if (($i % 2) == 0) {
					echo ('<div class="light_even"><span style="color: ' . rand_color() . ';"><img src="' . $img_usr_portrait . '" align="absmiddle" style="border-left: 2px solid ' . rand_color() . '; padding: 0px 5px 0px 5px;" />');
					if ($Reply->usr_id == $Topic->tpc_uid) {
						echo ($txt_title . '</span><br /><br />' . format_ubb($Reply->pst_content));
					} else {
						echo ($txt_title . '</span><br /><br />' . format_ubb($Reply->pst_content));
					}
					echo ('</div>');
				} else {
					echo ('<div class="light_odd"><span style="color: ' . rand_color() . ';"><img src="' . $img_usr_portrait . '" align="absmiddle" style="border-left: 2px solid ' . rand_color() . '; padding: 0px 5px 0px 5px;" />');
					if ($Reply->usr_id == $Topic->tpc_uid) {
						echo ($txt_title . '</span><br /><br />' . format_ubb($Reply->pst_content));
					} else {
						echo ($txt_title . '</span><br /><br />' . format_ubb($Reply->pst_content));
					}
					echo ('</div>');
				}
			}
			if ($p['total'] > 1) {
				$this->vxDrawPages($p);
				echo('<br /><br />');
			}
			echo('<div id="vxReplyTip"><a name="replyForm" class="img"><img src="/img/spacer.gif" width="1" height="1" style="display: none;" /></a>看完之后有话想说？那就帮楼主加盖一层吧！</div>');
		} else {
			echo('<div id="vxReplyTip"><a name="replyForm" class="img"><img src="/img/spacer.gif" width="1" height="1" style="display: none;" /></a>目前这个主题还没有回复，或许你可以帮楼主加盖一层？</div>');
		}
		$i++;
		if (($i % 2) == 0) { $_tmp = 'light_even'; } else { $_tmp = 'light_odd'; }
		if ($this->User->vxIsLogin()) {
			if ($this->User->usr_portrait == '') {
				$img_usr_portrait = '/img/p_' . $this->User->usr_gender . '_s.gif';
			} else {
				$img_usr_portrait = '/img/p/' . $this->User->usr_portrait . '_s.' . BABEL_PORTRAIT_EXT;
			}
			echo('<div class="' . $_tmp . '"><form action="/post/create/' . $Topic->tpc_id . '.vx" method="post"><span style="color: ' . rand_color() . ';"><img src="' . $img_usr_portrait . '" align="absmiddle" style="border-left: 2px solid ' . rand_color(0, 1) . '; padding: 0px 5px 0px 5px;" />现在回复楼主道：<input type="text" class="sll" name="pst_title" value="Re: ' . make_single_return($Topic->tpc_title, 0) . '" /><br /><br /><textarea name="pst_content" rows="10" class="quick" id="taQuick"></textarea><div align="left" style="margin: 10px 0px 0px 0px; padding-left: 450px;"><input id="imgReply" type="image" src="/img/silver/sbtn_reply.gif" border="0" alt="回复" /></div></span></form></div>');
		} else {
			echo('<div class="' . $_tmp . '" align="left"><span class="text_large"><img src="/img/ico_conf.gif" align="absmiddle" class="home" />在回复之前你需要先进行登录</span>');
			echo('<table cellpadding="0" cellspacing="0" border="0" class="form">');
			echo('<form action="/login.vx" method="post" id="Login">');
			echo('<input type="hidden" name="return" value="/topic/view/' . $Topic->tpc_id . '.html" />');
			echo('<tr><td width="200" align="right">电子邮件或昵称</td><td width="200" align="left"><input type="text" maxlength="100" class="sl" name="usr" tabindex="1" /></td><td width="150" rowspan="2" valign="middle" align="right"><input type="image" src="/img/silver/btn_login.gif" alt="' . Vocabulary::action_login . '" tabindex="3" /></td></tr><tr><td width="200" align="right">密码</td><td align="left"><input type="password" maxlength="32" class="sl" name="usr_password" tabindex="2" /></td></tr></form></table></div>');
		}
		echo('<a href="#top">回到顶部</a> | <a href="/">回到首页</a>');
		if ($this->User->vxIsLogin()) {
			echo(' | <a href="/user/modify.vx">修改信息</a>');
		} else {
			echo(' | <a href="/signup.html">注册</a> | <a href="/passwd.vx">忘记密码</a>');
		}
		echo('</div>');
		echo('</div>');
	}
	
	/* E module: Topic View block */

	/* S module: Expense View block */
	
	public function vxExpenseView() {
		$p = array();
		$p['base'] = '/expense/view/';
		$p['ext'] = '.vx';
		$sql = "SELECT COUNT(exp_id) FROM babel_expense WHERE exp_uid = {$this->User->usr_id}";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_viewexpense . '</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_expense.gif" align="absmiddle" class="home" />' . Vocabulary::action_viewexpense);
		/* S: truncate */
		if ($p['items'] > 0) {
			echo('<input type="image" style="margin-left: 10px;" onclick="truncateExpense()" src="/img/tico_truncate.gif" align="absmiddle" />');
		}
		/* E: truncate */
		echo('</span>');
		echo('<br />目前你口袋里有' . $this->User->usr_money_a['str']);
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		$p['size'] = BABEL_USR_EXPENSE_PAGE;
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
		if ($p['total'] > 1) {
			echo('<tr><td align="left" class="hf" colspan="4">');
			$this->vxDrawPages($p);
			echo('</td></tr>');
		}
		$sql = "SELECT exp_id, exp_amount, exp_type, exp_memo, exp_created FROM babel_expense WHERE exp_uid = {$this->User->usr_id} ORDER BY exp_created DESC LIMIT {$p['sql']},{$p['size']}";
		$rs = mysql_query($sql, $this->db);
		while ($Expense = mysql_fetch_object($rs)) {
			echo('<tr>');
			if ($Expense->exp_amount > 0) {
				echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/star_active.png" /></td>');
			} else {
				echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/star_inactive.png" /></td>');
			}
			echo('<td height="24" align="left" id="tdExpense' . $Expense->exp_id . 'T" onmouseover="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFCC'" . ');" onmouseout="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFFF'" . ');">' . $this->User->usr_expense_type_msg[$Expense->exp_type] . '&nbsp;<span class="text_property">');
			switch ($Expense->exp_type) {
				default:
					echo($Expense->exp_memo);
					break;
				case 8:
					echo('收件人：<strong>' . $Expense->exp_memo . '</strong>');
					break;
			}
			echo('</span></td>');
			echo('<td width="120" height="24" align="left" id="tdExpense' . $Expense->exp_id . 'N" onmouseover="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFCC'" . ');" onmouseout="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFFF'" . ');">');
			if ($Expense->exp_amount > 0) {
				echo('<small class="green">');
			} else {
				echo('<small class="red">');
			}
			printf("%.2f</small></td>", $Expense->exp_amount);
			echo('<td width="120" height="24" align="left" id="tdExpense' . $Expense->exp_id . 'L" onmouseover="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFCC'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFCC'" . ');" onmouseout="changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "T', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "N', '#FFFFFF'" . ');changeBlockStyle(' . "'" . 'tdExpense' . $Expense->exp_id . "L', '#FFFFFF'" . ');"><small class="time">' . make_descriptive_time($Expense->exp_created) . '</small></td>');
			echo('</tr>');
		}
		mysql_free_result($rs);
		if ($p['total'] > 1) {
			echo('<tr><td align="left" class="hf" colspan="4">');
			$this->vxDrawPages($p);
			echo('</td></tr>');
		}
		echo('</table>');
		echo('</div>');
		echo('</div>');
	}
	
	/* E module: Expense View block */
	
	/* S module: Online View block */
	
	public function vxOnlineView() {
		$p = array();
		$p['base'] = '/online/view/';
		$p['ext'] = '.vx';
		$sql = "SELECT COUNT(onl_hash) FROM babel_online";
		$rs = mysql_query($sql, $this->db);
		$p['items'] = mysql_result($rs, 0, 0);
		mysql_free_result($rs);
		
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
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_viewonline . '</div>');
		echo('<div class="blank" align="left">');
		echo('<span class="text_large"><img src="/img/ico_board.gif" align="absmiddle" class="home" />' . Vocabulary::action_viewonline . '</span>');
		echo('<br />目前共有 ' . $this->online_count . ' 个会员在线，其中注册会员 ' . $this->online_count_reg . ' 个，游客 ' . $this->online_count_anon . ' 个');
		echo('</div>');
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		if ($p['total'] > 1) {
			echo('<tr><td align="left" class="hf" colspan="5">');
			$this->vxDrawPages($p);
			echo('</td></tr>');
		}
		echo('<tr><td width="24" height="18" class="star"></td><td height="18" class="blue">会员昵称</td><td width="120" height="18" class="apple">IP</td><td width="120" height="18" class="orange">最后活动时间</td><td width="120" height="18" class="blue">进入时间</td></tr>');
		$sql = 'SELECT onl_nick, onl_ip, onl_ua, onl_uri, onl_ref, onl_created, onl_lastmoved FROM babel_online ORDER BY onl_lastmoved DESC LIMIT ' . $p['sql'] . ',' . $p['size'];
		$rs = mysql_query($sql, $this->db);
		while ($Online = mysql_fetch_object($rs)) {
			echo('<tr>');
			if ($this->User->usr_nick == $Online->onl_nick) {
				echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/star_active.png" /></td>');
			} else {
				if ($Online->onl_nick == '') {
					echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/star_inactive.png" /></td>');
				} else {
					echo('<td width="24" height="24" align="center" valign="middle" class="star"><img src="/img/star_inactive_hover.png" /></td>');
				}
			}
			if ($Online->onl_nick != '') {
				echo('<td height="24" align="left" class="even"><a href="/u/' . $Online->onl_nick . '">' . $Online->onl_nick . '</a></td>');
			} else {
				echo('<td height="24" align="left" class="even">游客</td>');
			}
			echo('<td width="120" height="24" align="left" class="even">' . make_masked_ip($Online->onl_ip) . '</td>');
			echo('<td width="120" height="24" align="left" class="even">' . make_descriptive_time($Online->onl_lastmoved) . '</td>');
			echo('<td width="120" height="24" align="left" class="even">' . make_descriptive_time($Online->onl_created) . '</td>');
			echo('</tr>');
			echo('<tr><td width="24" height="20" class="star"></td><td height="20" colspan="3" align="right" class="odd"><small class="fade">' . $Online->onl_ua . '</small></td><td width="120" height="20" class="even">浏览器标识</td></tr>');
			if ($Online->onl_ref != '') {
				if (strlen($Online->onl_ref) >= 60) {
					$ref = substr($Online->onl_ref, 0, 60) . '...';
				} else {
					$ref = $Online->onl_ref;
				}
				echo('<tr><td width="24" height="20" class="star"></td><td height="24" colspan="3" align="right" class="odd"><small class="fade"><a href="' . $Online->onl_ref . '" target="_self">' . $ref . '</small></td><td width="120" height="24" class="even">来源地址</td></tr>');
			}
			echo('<tr><td width="24" height="20" class="star"></td><td height="24" colspan="3" align="right" class="odd"><small class="fade"><a href="' . $Online->onl_uri . '" target="_self">' . $Online->onl_uri . '</a></small></td><td width="120" height="24" class="even">最后停留地址</td></tr>');
		}
		mysql_free_result($rs);
		if ($p['total'] > 1) {
			echo('<tr><td align="left" class="hf" colspan="5">');
			$this->vxDrawPages($p);
			echo('</td></tr>');
		}
		echo('</table>');
		echo('</div>');
		echo('</div>');
	}
	
	/* E module: Online View block */
	
	public function vxMobile() {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_mobile_search . '</div>');
		if (isset($_GET['no'])) {
			$no = trim($_GET['no']);
			if (strlen($no) == 11) {
				$no_7 = mysql_real_escape_string(substr($no, 0, 7));
				$sql = "SELECT mob_no, mob_area, mob_subarea FROM babel_mobile_data WHERE mob_no = {$no_7}";
				$rs = mysql_query($sql);
				if (mysql_num_rows($rs) == 1) {
					
					$N = mysql_fetch_object($rs);
					echo('<div class="blank"><span class="mob"><img src="/img/pico_web.gif" align="absmiddle" class="portrait" />&nbsp;手机号码 <span class="mobile">' . $no . '</span> 的所在地：' . $N->mob_area);
					if ($N->mob_subarea != '') {
						echo(' / ' . $N->mob_subarea);
					}
					echo('</span></div>');
				} else {
					echo('<div class="blank"><span class="mob"><img src="/img/pico_web.gif" align="absmiddle" class="portrait" />&nbsp;手机号码 <span class="mobile">' . $no . '</span> 的所在地未知。</span></div>');
				}
				mysql_free_result($rs);
			}
		}
		echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_search.gif" onload="document.getElementById(' . "'k_search_q'" . ').focus()" class="home" align="absmiddle" />' . Vocabulary::action_mobile_search . '</span><form action="http://www.v2ex.com/search_mobile.php" method="get"><input type="text" name="q" id="k_search_q" onmouseover="this.focus()" class="search" /><span class="tip"></span><br /><br /><input type="image" src="/img/silver/btn_search.gif" /></form></div>');
		echo('<div class="blank"><img src="/img/pico_tuser.gif" align="absmiddle" class="portrait" />&nbsp;数据来源 <a href="http://www.imobile.com.cn/" target="_blank">手机之家</a></div>');
		echo('</div>');
	}
	
	public function vxMan() {
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; ' . Vocabulary::action_man_search . '</div>');
		if (isset($_GET['q'])) {
			$_q = urldecode(strtolower(substr($_SERVER['REQUEST_URI'], 5, (strlen($_SERVER['REQUEST_URI']) - 5))));
			$_q_h = 'search_man_' . md5($_q);
		} else {
			$_q = '';
		}
		echo('<div class="blank" align="left"><span class="text_large"><img src="/img/ico_freebsd.gif" onload="document.getElementById(' . "'k_search_q'" . ').focus()" class="home" align="absmiddle" />' . Vocabulary::action_man_search . '</span><form action="http://www.v2ex.com/search_man.php" method="get"><input type="text" name="q" id="k_search_q" onmouseover="this.focus()" class="search" value="' . make_single_return($_q) . '" /><span class="tip"></span><br /><br /><input type="image" src="/img/silver/btn_search.gif" /></form></div>');
		
		echo('<table width="100%" border="0" cellpadding="0" cellspacing="2" class="board">');
		if ($_q != '') {
			$time_start = microtime_float();
			if ($hits_c = $this->cl->get($_q_h)) {
				$hits_c = unserialize($hits_c);
				$_count = $this->cl->get('count_' . $_q_h);
				$time_end = microtime_float();
				$time_elapsed = $time_end - $time_start;
				if ($_count > 0) {
					$i = 0;
					echo('<tr><td colspan="2" class="hf" height="18" style="border-bottom: 1px solid #CCC;">找到 ' . $_count . ' 篇匹配的参考文档，以下是最相关的前 ' . ($_count > 30 ? 30 : $_count) . ' 篇，');
					printf('耗时 %.3f 秒。', $time_elapsed);
					echo('</td></tr>');
					echo('<tr><td valign="top">');
					echo('<table border="0" cellpadding="0" cellspacing="2">');
					foreach ($hits_c as $hit_c) {
						$i++;
						if ($i == 1) {
							echo('<tr><td colspan="2" height="10"></td></tr>');
						}
						echo('<tr><td width="24" height="18" valign="top" align="center" class="star"><a href="http://' . BABEL_DNS_NAME . '/man/' . $hit_c['set_name'] . '/" target="_blank" class="var"><img src="/img/man/' . $hit_c['set_name'] . '.gif" alt="' . $hit_c['set_title'] . '" border="0" /></a></td>');
						echo('<td height="18" class="star"><a href="http://' . BABEL_DNS_NAME . $hit_c['url'] . '" class="blue" target="_blank">' . htmlspecialchars_decode($hit_c['title']) . '</a><small> - <a href="http://' . BABEL_DNS_NAME . '/man/' . $hit_c['set_name'] . '/" class="t" target="_blank">' . $hit_c['set_title'] . '</a> - <span class="tip_i">');
						printf("%.3f%%", $hit_c['score'] * 10);
						echo('</span></small></td></tr>');
						echo('<tr><td width="24"></td><td class="hf"><span class="excerpt">');
						echo(trim_br(make_excerpt_man(wordwrap(htmlspecialchars_decode($hit_c['contents']), 76, '<br />', 1), $_q, '<span class="text_matched">\1</span>')));
						echo('</span></td></tr>');
						echo('<tr><td width="24"></td><td valign="top"><span class="tip"><span class="green">');
						echo(BABEL_DNS_NAME .  $hit_c['url']);
						echo(' - ' . date('Y年n月j日', $hit_c['mtime']));
						echo('</span></span></td></tr>');
						echo('<tr><td colspan="2" height="10"></td></tr>');
					}
					echo('</table></td>');
					echo('<td width=" class="hf" valign="top" align="right"><iframe src="/cts/160x600.html" width="160" height="600" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe></td></tr>');
				} else {
					printf("<tr><td colspan=\"2\" class=\"hf\">没有找到任何匹配的参考文档，本次操作耗时 %.3f 秒。</td></tr>", $time_elapsed);
				}
			} else {
				try {
					$index = new Zend_Search_Lucene(BABEL_PREFIX . '/data/lucene/man');
					$hits = $index->find($_q);
					$_count = count($hits);
					$time_end = microtime_float();
					$time_elapsed = $time_end - $time_start;
				} catch (Zend_Search_Lucene_Exception $e) {
					printf("<tr><td colspan=\"2\" class=\"hf\">没有找到任何匹配的参考文档，建议你检查你搜索时候所用的语法，本次操作耗时 %.3f 秒。</td></tr>", $time_elapsed);
					$_count = 0;
				}
				
				if ($_count > 0) {
					$hits_c = array();
					echo('<tr><td colspan="2" class="hf" height="18" style="border-bottom: 1px solid #CCC;">找到 ' . $_count . ' 篇匹配的参考文档，以下是最相关的前 ' . ($_count > 30 ? 30 : $_count) . ' 篇，');
					printf('耗时 %.3f 秒。', $time_elapsed);
					echo('</td></tr>');
					$i = 0;
					echo('<tr><td valign="top">');
					echo('<table border="0" cellpadding="0" cellspacing="2">');
					foreach ($hits as $hit) {
						$doc = $hit->getDocument();
						$hit_c = array();
						$hit_c['url'] = $doc->getFieldValue('url');
						$hit_c['title'] = $doc->getFieldValue('title');
						$hit_c['contents'] = $doc->getFieldValue('contents');
						$hit_c['set_name'] = $doc->getFieldValue('set_name');
						$hit_c['set_title'] = $doc->getFieldValue('set_title');
						$hit_c['mtime'] = $doc->getFieldValue('mtime');
						$hit_c['score'] = $hit->score;
						$hits_c[] = $hit_c;
						$i++;
						if ($i > 30) {
							break;
						}
						if ($i == 1) {
							echo('<tr><td colspan="2" height="10"></td></tr>');
						}
						echo('<tr><td width="24" height="18" valign="top" align="center" class="star"><a href="http://' . BABEL_DNS_NAME . '/man/' . $hit_c['set_name'] . '/" target="_blank" class="var"><img src="/img/man/' . $hit_c['set_name'] . '.gif" alt="' . $hit_c['set_title'] . '" border="0" /></a></td>');
						echo('<td height="18" class="star"><a href="http://' . BABEL_DNS_NAME . $hit_c['url'] . '" class="blue" target="_blank">' . htmlspecialchars_decode($hit_c['title']) . '</a><small> - <a href="http://' . BABEL_DNS_NAME . '/man/' . $hit_c['set_name'] . '/" class="t" target="_blank">' . $hit_c['set_title'] . '</a> - <span class="tip_i">');
						printf("%.3f%%", $hit_c['score'] * 10);
						echo('</span></small></td>');
						echo('</tr>');
						echo('<tr><td width="24"></td><td class="hf"><span class="excerpt">');
						echo(trim_br(make_excerpt_man(wordwrap(htmlspecialchars_decode($hit_c['contents']), 76, '<br />', 1), $_q, '<span class="text_matched">\1</span>')));
						echo('</span></td>');
						echo('</tr>');
						echo('<tr><td width="24"></td><td valign="top"><span class="tip"><span class="green">');
						echo(BABEL_DNS_NAME .  $hit_c['url']);
						echo(' - ' . date('Y年n月j日', $hit_c['mtime']));
						echo('</span></span></td></tr>');
						echo('<tr><td colspan="2" height="10"></td></tr>');
					}
					echo('</table></td>');
					echo('<td width=" class="hf" valign="top" align="right"><iframe src="/cts/160x600.html" width="160" height="600" frameborder="0" marginheight="0" marginwidth="0" scrolling="no"></iframe></td></tr>');
					$this->cl->save(serialize($hits_c), $_q_h);
					$this->cl->save($_count, 'count_' . $_q_h);
				} else {
					$hits_c = array();
					$_count = 0;
					$this->cl->save(serialize($hits_c), $_q_h);
					$this->cl->save($_count, 'count_' . $_q_h);
					if (@!$e) {
						printf("<tr><td colspan=\"2\" class=\"hf\">没有找到任何匹配的参考文档，本次操作耗时 %.3f 秒。</td></tr>", $time_elapsed);
					}
				}
			}
			
			echo('<tr><td class="hf" colspan="2" height="18" style="border-top: 1px solid #CCC;"><img src="/img/pico_tuser.gif" align="absmiddle" class="portrait" />&nbsp;目前索引有 <span class="tip_i">');
			if ($sets = $this->cl->get('sets_search_man')) {
				$sets = unserialize($sets);
				foreach ($sets as $key => $data) {
					$css_color = rand_color();
					echo(' ... <a href="http://' . BABEL_DNS_NAME . '/man/' . $key . '/" target="_blank" style="color: ' . $css_color . '" class="var">' . $value . '</a>');
				}
			} else {
				$sets = array();
				$xml = simplexml_load_file(BABEL_PREFIX . '/res/man.xml');
				foreach ($xml->sets->set as $o) {
					$css_color = rand_color();
					$set = array();
					$set[strval($o['name'])] = strval($o['title']);
					$sets[] = $set;
					echo(' ... <a href="http://' . BABEL_DNS_NAME . '/man/' . $o['name'] . '/" target="_blank" style="color: ' . $css_color . '" class="var">' . $o['title'] . '</a>');
				}
				$this->cl->save('sets_search_man', serialize($sets));
			}
			echo('</span></td></tr>');
			echo('</table>');
		} else {
			echo('<tr><td class="hf" height="18"><img src="/img/pico_tuser.gif" align="absmiddle" class="portrait" />&nbsp;目前索引有 <span class="tip_i">');
			if ($sets = $this->cl->get('sets_search_man')) {
				$sets = unserialize($sets);
				foreach ($sets as $key => $data) {
					$css_color = rand_color();
					echo(' ... <a href="http://' . BABEL_DNS_NAME . '/man/' . $key . '/" target="_blank" style="color: ' . $css_color . '" class="var">' . $value . '</a>');
				}
			} else {
				$sets = array();
				$xml = simplexml_load_file(BABEL_PREFIX . '/res/man.xml');
				foreach ($xml->sets->set as $o) {
					$css_color = rand_color();
					$set = array();
					$set[strval($o['name'])] = strval($o['title']);
					$sets[] = $set;
					echo(' ... <a href="http://' . BABEL_DNS_NAME . '/man/' . $o['name'] . '/" target="_blank" style="color: ' . $css_color . '" class="var">' . $o['title'] . '</a>');
				}
				$this->cl->save('sets_search_man', serialize($sets));
			}
			echo('</span></td></tr>');
			echo('</table>');
		}
		
		echo('</div>');
	}
	
	public function vxZen($options) {
		$User =& $options['target'];
		
		/* S: Unfinished Projects */
		if ($this->User->usr_id == $User->usr_id) {
			$sql = "SELECT zpr_id, zpr_uid, zpr_private, zpr_title, zpr_created, zpr_lastupdated, zpr_lasttouched, zpr_completed FROM babel_zen_project WHERE zpr_progress = 0 AND zpr_uid = {$User->usr_id} ORDER BY zpr_created ASC";
		} else {
			$sql = "SELECT zpr_id, zpr_uid, zpr_private, zpr_title, zpr_created, zpr_lastupdated, zpr_lasttouched, zpr_completed FROM babel_zen_project WHERE zpr_progress = 0 AND zpr_uid = {$User->usr_id} AND zpr_private = 0 ORDER BY zpr_created ASC";
		}
		
		$rs = mysql_query($sql, $this->db);
		echo('<div id="main">');
		echo('<div class="blank" align="left">你当前位于 <a href="/">' . Vocabulary::site_name . '</a> &gt; <a href="/u/' . $User->usr_nick . '">' . $User->usr_nick . '</a> &gt; ' . Vocabulary::term_zen . ' <span class="tip_i"><small>alpha</small></span></div>');
		echo('<div class="blank"><span class="text_large"><a style="color: ' . rand_color() . ';" href="/u/' . $User->usr_nick . '" class="var">' . $User->usr_nick . '</a> / 进行中的项目</span>');
		
		if ($_SESSION['babel_ua']['GECKO_DETECTED'] | $_SESSION['babel_ua']['KHTML_DETECTED'] | $_SESSION['babel_ua']['OPERA_DETECTED']) {
			$hack_width = 'width="100%" ';
		} else {
			$hack_width = '';
		}
		
		echo('<table '. $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="zen">');
		
		while ($Project = mysql_fetch_object($rs)) {
			echo('<tr><td class="zen_project">');
			echo('<a name="p' . $Project->zpr_id . '"></a>');
			echo('<span class="zen_project"><img src="' . CDN_IMG . 'gt.gif" align="absmiddle" />&nbsp;&nbsp;' . make_plaintext($Project->zpr_title) . '</span><span class="tip_i"> ... 创建于 ' . make_descriptive_time($Project->zpr_created));
			if ($Project->zpr_uid == $this->User->usr_id) {
				echo(' ... <a href="#;" onclick="if (confirm(' . "'确认删除项目及其下面的所有任务？\\n\\n" . addslashes(make_single_return(make_plaintext($Project->zpr_title))) . "'" . ')) { location.href = ' . "'" . '/erase/zen/project/' . $Project->zpr_id . '.vx' . "'" . ';}" class="zen_rm">X del</a>');
				if ($Project->zpr_private == 1) {
					$permission = '* private';
				} else {
					$permission = '@ public';
				}
				echo(' <a href="/change/zen/project/permission/' . $Project->zpr_id . '.vx" class="zen_pr">' . $permission . '</a>');	
			}
			if ($Project->zpr_uid == $this->User->usr_id) {
				if ($Project->zpr_private == 1) {
					echo (' ... 这个项目只有你自己可以看到');
				} else {
					echo (' ... 这个项目人人可见');
				}
			}
			echo('</span></td></tr>');
			$sql = "SELECT zta_id, zta_uid, zta_title, zta_progress, zta_created, zta_lastupdated, zta_completed FROM babel_zen_task WHERE zta_pid = {$Project->zpr_id} ORDER BY zta_progress ASC, zta_created ASC";
			$tasks = mysql_query($sql, $this->db);
			$i = 0;
			$j = 0;
			while ($Task = mysql_fetch_object($tasks)) {
				if ($Task->zta_progress == 0) {
					$i++;
					echo('<tr><td class="zen_task_todo">');
					if ($Project->zpr_uid == $this->User->usr_id) {
						echo('<input onchange="ZENDoneTask(' . $Task->zta_id . ');" type="checkbox" />');
					} else {
						echo('<input disabled="disabled" type="checkbox" />');
					}
					echo('&nbsp;' . make_plaintext($Task->zta_title));
					if ($Task->zta_uid == $this->User->usr_id) {
						echo('<span class="tip_i"> ... <a href="#;" onclick="if (confirm(' . "'确认删除任务？\\n\\n" . addslashes(make_single_return(make_plaintext($Task->zta_title))) . "'" . ')) { location.href = ' . "'" . '/erase/zen/task/' . $Task->zta_id . '.vx' . "'" . ';}" class="zen_rm">X del</a></span>');
					}
					echo('</td></tr>');
				} else {
					$j++;
					if (($j == 1) && ($Project->zpr_uid == $this->User->usr_id)) {
						$this->vxZENProjectForm($Project);
					}
					echo('<tr><td class="zen_task_done"><img src="' . CDN_IMG . 'check_green.gif" align="absmiddle" alt="done" />&nbsp;&nbsp;' . make_plaintext($Task->zta_title));
					if ($Task->zta_uid == $this->User->usr_id) {
						echo('<span class="tip_i"> ... <a href="#;" onclick="if (confirm(' . "'确认删除任务？\\n\\n" . addslashes(make_single_return(make_plaintext($Task->zta_title))) . "'" . ')) { location.href = ' . "'" . '/erase/zen/task/' . $Task->zta_id . '.vx' . "'" . ';}" class="zen_rm">X del</a> <a href="/undone/zen/task/' . $Task->zta_id . '.vx" class="zen_undone">- undone</a></span>');
					}
					echo('</td></tr>');
				}
			}
			if (($i == 0 && $j == 0) && ($Project->zpr_uid == $this->User->usr_id)) {
				$this->vxZENProjectForm($Project);
			}
			
			if (($i > 0 && $j == 0) && ($Project->zpr_uid == $this->User->usr_id)) {
				$this->vxZENProjectForm($Project);
			}
			mysql_free_result($tasks);
		}
		
		mysql_free_result($rs);
		echo('</table>');
		if ($this->User->usr_id == $User->usr_id && $this->User->vxIsLogin()) {
			echo('<form class="zen" action="/recv/zen/project.vx" method="post">');
			echo('创建新项目 <input type="text" class="sll" name="zpr_title" maxlength="80" /> <input type="submit" class="zen_btn" value="创建" />');
			echo('</form>');
		}
		if (!$this->User->vxIsLogin()) {
			echo('<span class="tip">ZEN 是帮助你管理时间的一个小工具，如果你就是 <a href="/u/' . make_plaintext($User->usr_nick) . '" class="t">' . make_plaintext($User->usr_nick) . '</a>，你可以在 [ <a href="/login.vx" class="t">登录</a> ] 之后管理自己的时间</span>');
		} else {
			if (isset($_SESSION['babel_zen_message'])) {
				if ($_SESSION['babel_zen_message'] != '') {
					echo('<span class="tip_i">' . $_SESSION['babel_zen_message'] . '</span>');
					$_SESSION['babel_zen_message'] = '';
				} else {
				}
			} else {
				$_SESSION['babel_zen_message'] = '';
			}
		}
		echo('</div>');
		/* E: Unfinished Projects */
		
		/* S: Finished Projects */
		if ($this->User->usr_id == $User->usr_id) {
			$sql = "SELECT zpr_id, zpr_uid, zpr_private, zpr_title, zpr_created, zpr_lastupdated, zpr_lasttouched, zpr_completed FROM babel_zen_project WHERE zpr_progress = 1 AND zpr_uid = {$User->usr_id} ORDER BY zpr_completed ASC";
		} else {
			$sql = "SELECT zpr_id, zpr_uid, zpr_private, zpr_title, zpr_created, zpr_lastupdated, zpr_lasttouched, zpr_completed FROM babel_zen_project WHERE zpr_progress = 1 AND zpr_uid = {$User->usr_id} AND zpr_private = 0 ORDER BY zpr_completed ASC";
		}
		$rs = mysql_query($sql, $this->db);
		
		echo('<div class="blank"><span class="text_large"><a style="color: ' . rand_color() . ';" href="/u/' . $User->usr_nick . '" class="var">' . $User->usr_nick . '</a> / 完成了的项目</span>');
		
		echo('<table '. $hack_width . 'cellpadding="0" cellspacing="0" border="0" class="zen">');
		
		while ($Project = mysql_fetch_object($rs)) {
			echo('<tr><td class="zen_project">');
			echo('<a name="p' . $Project->zpr_id . '"></a>');
			echo('<span class="zen_project"><img src="' . CDN_IMG . 'gt.gif" align="absmiddle" />&nbsp;&nbsp;' . make_plaintext($Project->zpr_title) . '</span><span class="tip_i"> ... ');
			if ((time() - $Project->zpr_completed) < 100) {
				echo('刚刚完成');
			} else {
				echo('完成于 ' . make_descriptive_time($Project->zpr_completed));
			}
			if ($Project->zpr_uid == $this->User->usr_id) {
				echo(' ... <a href="#;" onclick="if (confirm(' . "'确认删除项目及其下面的所有任务？\\n\\n" . addslashes(make_single_return(make_plaintext($Project->zpr_title))) . "'" . ')) { location.href = ' . "'" . '/erase/zen/project/' . $Project->zpr_id . '.vx' . "'" . ';}" class="zen_rm">X del</a>');
				if ($Project->zpr_private == 1) {
					$permission = '* private';
				} else {
					$permission = '@ public';
				}
				echo(' <a href="/change/zen/project/permission/' . $Project->zpr_id . '.vx" class="zen_pr">' . $permission . '</a>');	
			}
			if ($Project->zpr_uid == $this->User->usr_id) {
				if ($Project->zpr_private == 1) {
					echo (' ... 这个项目只有你自己可以看到');
				} else {
					echo (' ... 这个项目人人可见');
				}
			}
			echo('</span></td></tr>');
			$sql = "SELECT zta_id, zta_uid, zta_title, zta_progress, zta_created, zta_lastupdated, zta_completed FROM babel_zen_task WHERE zta_pid = {$Project->zpr_id} ORDER BY zta_completed ASC";
			$tasks = mysql_query($sql, $this->db);
			$i = 0;
			$j = 0;
			while ($Task = mysql_fetch_object($tasks)) {
				if ($Task->zta_progress == 0) {
					$sql = "UPDATE babel_zen_project SET zpr_progress = 0 WHERE zpr_id = {$Task->zta_pid} LIMIT 1";
					mysql_unbuffered_query($sql, $this->db);
					echo('<script type="text/javascript">location.href="/zen/{$User->usr_nick}";</script>');
				}
				echo('<tr><td class="zen_task_done"><img src="' . CDN_IMG . 'check_green.gif" align="absmiddle" alt="done" />&nbsp;&nbsp;' . make_plaintext($Task->zta_title));
				echo('<span class="tip_i"> ... <a href="/undone/zen/task/' . $Task->zta_id . '.vx" class="zen_undone">- undone</a></span>');
				echo('</td></tr>');
			}
			mysql_free_result($tasks);
		}
		
		echo('</table>');
		
		$count_projects_done = mysql_num_rows($rs);
		if ($count_projects_done > 0) {
			echo('<span class="tip_i">恭喜，' . $User->usr_nick . ' 已经完成了 ' . $count_projects_done . ' 个项目！</span>');
		} else {
			echo('<span class="tip_i">还没有任何已经完成了的项目，何不今天就试试用 ZEN 来管理你的时间？</span>');
		}
		
		mysql_free_result($rs);
		
		echo('</div>');
		/* E: Finished Projects */

		echo('<div class="blank"><img src="' . CDN_IMG . 'pico_zen.gif" alt="ZEN" align="absmiddle" /> 关于 ZEN <span class="tip_i"><small>alpha</small></span><br /><br /><span class="tip">ZEN 是一个帮助你管理时间的小工具。我们的愿望是，通过合理地使用 ZEN，你将可以有更多的时间用于一些更有意义的事情。<br /><br />使用 ZEN 非常简单，感觉就像是在一张白纸上写上要做的事情，然后再一项一项地划掉。<br /><br />目前 ZEN 正处于 alpha 测试阶段，并不是十分地稳定，并不是每一个功能都足够完善，不过请放心，我们每天都在改进它！</span></div>');
		echo('</div>');
	}
	
	/* E public modules */
	
	/* S private modules */
	
	/* S module: Home Section block */
	
	private function vxZENProjectForm($Project) {
		echo('<tr><td class="zen_task_new"><div id="pf_' . $Project->zpr_id . '"><img src="' . CDN_IMG . 'plus_green.gif" align="absmiddle" alt="+" /> <a href="#;" class="t" onclick="ZENSwitchProjectForm(' . $Project->zpr_id . ');">添加新任务</a></div></td></tr>');
	}
	
	private function vxHomeSection($section_id, $items = 18) {
		$sql = "SELECT nod_id FROM babel_node WHERE nod_sid = {$section_id}";
		$rs = mysql_query($sql, $this->db);
		$board_count = mysql_num_rows($rs);
		$board_ids = '';
		$i = 0;
		while ($Board = mysql_fetch_object($rs)) {
			$i++;
			if ($i == $board_count) {
				$board_ids = $board_ids . $Board->nod_id;
			} else {
				$board_ids = $board_ids . $Board->nod_id . ', ';
			}
		}
		mysql_free_result($rs);
		$which = rand(1, 2);
		if ($which == 1) {
			$action = '/topic/view/';
			$suffix = '.html';
			$sql = "SELECT tpc_id AS itm_id, tpc_title AS itm_title, tpc_created AS itm_time, tpc_posts AS itm_items FROM babel_topic WHERE tpc_pid IN ({$board_ids}) AND tpc_flag IN (0, 2) ORDER BY rand() LIMIT {$items}";
		} else {
			$action = '/go/';
			$suffix = '';
			$sql = "SELECT nod_name AS itm_id, nod_title AS itm_title, nod_lastupdated AS itm_time, nod_topics AS itm_items FROM babel_node WHERE nod_sid = {$section_id} ORDER BY rand()";
		}
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		$o = '';
		while ($Item = mysql_fetch_object($rs)) {
			if ((time() - $Item->itm_time) < 86400) {
				$img_star = '<img src="/img/bunny.gif" align="absmiddle" />&nbsp;';
			} else {
				$img_star = '';
			}
			$i++;
			if ($Item->itm_items > 3) {
				$css_color = ' color: ' . rand_color();
			} else {
				$css_color = ' color: ' . rand_gray(2, 4);
			}
			$css_font_size = $this->vxGetItemSize($Item->itm_items);
			$o .= '<span class="tip_i">';
			if ($i != 1) {
				$o .= ' ... ';
			}
			$o .= $img_star . '<a href="' . $action . $Item->itm_id . $suffix . '" class="var" style="font-size: ' . $css_font_size . 'px; ' . $css_color . ';">' . make_plaintext($Item->itm_title);
			$o .= '</a></span>';
		}
		mysql_free_result($rs);
		
		return $o;
	}
	
	/* E module: Home Section block */
	
	/* S module: Home Section block Remix */
	
	private function vxHomeSectionRemix($node_id, $node_level = 1, $items = 3) {
		if ($node_level < 2) {
			$sql = "SELECT nod_id FROM babel_node WHERE nod_sid = {$node_id}";
			$rs = mysql_query($sql, $this->db);
			$board_count = mysql_num_rows($rs);
			$board_ids = '';
			$i = 0;
			while ($Board = mysql_fetch_object($rs)) {
				$i++;
				if ($i == $board_count) {
					$board_ids = $board_ids . $Board->nod_id;
				} else {
					$board_ids = $board_ids . $Board->nod_id . ', ';
				}
			}
			mysql_free_result($rs);
			$items = rand($items - 1, $items * 2);
			$sql = "SELECT usr_id, usr_nick, usr_gender, usr_portrait, nod_id, nod_name, nod_title, tpc_id, tpc_title, tpc_description, tpc_content, tpc_hits, tpc_posts, tpc_created, tpc_lasttouched FROM babel_topic, babel_user, babel_node WHERE nod_id = tpc_pid AND usr_id = tpc_uid AND tpc_posts > 1 AND tpc_hits > 10 AND tpc_pid IN ({$board_ids}) AND tpc_flag IN (0) ORDER BY tpc_lasttouched DESC LIMIT {$items}";
		} else {
			$board_ids = $node_id;
			$items = 15;
			$sql = "SELECT usr_id, usr_nick, usr_gender, usr_portrait, nod_id, nod_name, nod_title, tpc_id, tpc_title, tpc_description, tpc_content, tpc_hits, tpc_posts, tpc_created, tpc_lasttouched FROM babel_topic, babel_user, babel_node WHERE nod_id = tpc_pid AND usr_id = tpc_uid AND tpc_pid IN ({$board_ids}) AND tpc_flag IN (0) ORDER BY tpc_lasttouched DESC LIMIT {$items}";
		}
		
		
		$rs = mysql_query($sql, $this->db);
		$i = 0;
		$o = '';
		while ($Topic = mysql_fetch_object($rs)) {
			$i++;
			$css_color = rand_color();
			$o = $o . '<dl class="home_topic">';
			$img_p = $Topic->usr_portrait ? CDN_IMG . 'p/' . $Topic->usr_portrait . '_s.jpg' : CDN_IMG . 'p_' . $Topic->usr_gender . '_s.gif';
			$o .= '<dt style="margin-bottom: 2px;">&nbsp;';
			$o .= '<a href="/u/' . $Topic->usr_nick . '" class="var"><img src="' . $img_p . '" align="absmiddle" class="portrait" border="0" /></a>&nbsp;&nbsp;';
			$o .= '<a href="/topic/view/' . $Topic->tpc_id . '.html" class="var" style="color: ' . $css_color . '; font-size: 18px;">';
			$o .= make_plaintext($Topic->tpc_title);
			$url = 'http://' . BABEL_DNS_NAME . '/topic/view/' . $Topic->tpc_id . '.html';
			$o .= '</a><span class="tip_h"> ... ' . make_descriptive_time($Topic->tpc_lasttouched) . '，' . $Topic->tpc_posts . ' 篇回复，' . $Topic->tpc_hits . ' 次点击</span></dt><dd>';
			if (preg_match('/\[media/i', $Topic->tpc_content)) {
				$o .= '本主题含有多媒体影音内容，请 <a href="/topic/view/' . $Topic->tpc_id . '.html" class="t">点击这里阅读全文</a> ...';
			} else {
				$o .= make_excerpt_home($Topic);
			}
			if ($node_level < 2) {
				$o .= '<span class="tip_i" style="display: block; clear: left; margin-top: 10px; padding-top: 5px; padding-bottom: 5px; border-top: 1px solid #E0E0E0; font-size: 12px; font-size: 12px;">... <a href="/topic/view/' . $Topic->tpc_id . '.html#reply" class="t">' . $Topic->tpc_posts . ' 篇回复</a> | <a href="/topic/view/' . $Topic->tpc_id . '.html#replyForm" class="t">添加回复</a> | 阅读讨论区 <a href="/remix/' . $Topic->nod_name . '" class="t">' . $Topic->nod_title . '</a> | <a href="/u/' . $Topic->usr_nick . '" class="t">' . $Topic->usr_nick . '</a> 的个人空间';
			} else {
				$o .= '<span class="tip_i" style="display: block; clear: left; margin-top: 10px; padding-top: 5px; padding-bottom: 5px; border-top: 1px solid #E0E0E0; font-size: 12px; font-size: 12px;">... <a href="/topic/view/' . $Topic->tpc_id . '.html#reply" class="t">' . $Topic->tpc_posts . ' 篇回复</a> | <a href="/topic/view/' . $Topic->tpc_id . '.html#replyForm" class="t">添加回复</a> | <a href="/u/' . $Topic->usr_nick . '" class="t">' . $Topic->usr_nick . '</a> 的个人空间';
			}
			$o .= ' | ';
			
			$title = urlencode($Topic->tpc_title);
			$o .= '<a href="http://del.icio.us/post?url=' . $url . '&title=' . $title . '" class="var" target="_blank"><img src="/img/prom/delicious.png" border="0" align="absmiddle" alt="收藏到 del.icio.us" /></a> | ';
			$o .= '<a href="http://reddit.com/submit?url=' . $url . '&title=' . $title . '" class="var" target="_blank"><img src="/img/prom/reddit.png" border="0" align="absmiddle" alt="收藏到 reddit" /></a> | ';
			$o .= '<a href="http://technorati.com/cosmos/search.html?url=' . $url . '" class="var" target="_blank"><img src="/img/prom/technorati.png" border="0" align="absmiddle" alt="在 Technorati 中搜索本主题" /></a> | ';
			$o .= '<a href="http://ma.gnolia.com/bookmarklet/add?url=' . $url . '&title=' . $title . '" class="var" target="_blank"><img src="/img/prom/magnoliacom.png" border="0" align="absmiddle" alt="收藏到 Ma.gonolia" /></a> | ';
			$o .= '<a href="http://blogmarks.net/my/new.php?mini=1&truc=3&title=' . $title . '&url=' . $url . '" class="var" target="_blank"><img src="/img/prom/blogmarks.png" border="0" align="absmiddle" alt="收藏到 BlogMarks" /></a> | ';
			$o .= '<a href="http://www.furl.net/storeIt.jsp?t=' . $title . '&u=' . $url . '" class="var" target="_blank"><img src="/img/prom/furl.png" border="0" align="absmiddle" alt="收藏到 LookSmart FURL" /></a> | ';
			$o .= '<a href="http://www.spurl.net/spurl.php?v=3&title=' . $title . '&url=' . $url . '&blocked=" class="var" target="_blank"><img src="/img/prom/spurl.png" border="0" align="absmiddle" alt="收藏到 Spurl" /></a> | ';
			$o .= '<a href="http://simpy.com/simpy/LinkAdd.do?title=' . $title . '&href=' . $url . '&note=&_doneURI=http%3A%2F%2Fwww.simpy.com%2F&v=6&src=bookmarklet" class="var" target="_blank"><img src="/img/prom/simpy.png" border="0" align="absmiddle" alt="收藏到 simpy" /></a> | ';
			$o .= '<a href="http://tailrank.com/share/?title=' . $title . '&link_href=' . $url . '&text=" class="var" target="_blank"><img src="/img/prom/tailrank.png" border="0" align="absmiddle" alt="收藏到 Tailrank" /></a>';
			$o .= '</span></dd></dl>';
		}
		mysql_free_result($rs);
		
		return $o;
	}
	
	/* E module: Home Section block Remix */
	
	/* S module: Get Item Size logic */
	
	private function vxGetItemSize($posts) {
		if ($posts > 100) {
			return 17;
		} else {
			if ($posts >= 50) {
				return 16;
			} else {
				if ($posts >= 26) {
					return 15;
				} else {
					if ($posts >= 10) {
						return 14;
					} else {
						if ($posts >= 4) {
							return 13;
						} else {
							return 12;
						}
					}
				}
			}
		}
	}
	
	/* E module: Get Item Size logic */
	
	/* S module: Get Menu Size logic */
	
	private function vxGetMenuSize($posts) {
		if ($posts > 200) {
			return 17;
		} else {
			if ($posts >= 100) {
				return 16;
			} else {
				if ($posts >= 50) {
					return 15;
				} else {
					if ($posts >= 20) {
						return 14;
					} else {
						if ($posts >= 4) {
							return 13;
						} else {
							return 12;
						}
					}
				}
			}
		}
	}
	
	/* E module: Get Menu Size logic */
	
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
	
	/* E private modules */
}

/* E Page class */
?>
