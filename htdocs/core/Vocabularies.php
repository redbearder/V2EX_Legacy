<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/Vocabularies.php
*  Usage: Controlled vocabulary
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: Vocabularies.php 474 2006-07-13 15:20:52Z livid $
*  $LastChangedDate: 2006-07-13 23:20:52 +0800 (Thu, 13 Jul 2006) $
*  $LastChangedRevision: 474 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/Vocabularies.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

class Vocabulary {
	const site_name = 'V2EX';
	const site_title = 'V2EX | Project Babel';
	const site_copyright = 'Project Babel | Copyright © 2006 Livid Torvalds';
	
	const site_banner = "<a href=\"/\" target=\"_self\" class=\"var\"><img src=\"/img/logo_upon.gif\" border=\"0\" alt=\"V2EX\" onclick=\"location.href='/';\" style=\"cursor: hand;\" /></a>";
	
	const meta_keywords = 'V2EX, Babel, Livid, PHP, ';
	const meta_description = 'V2EX | software for internet';
	const meta_category = 'Technology';
	
	const action_signup = '注册';
	const action_login = '登录';
	const action_logout = '登出';
	const action_passwd = '忘记密码';
	const action_passwd_reset = '重设密码';
	
	const action_mobile_search = '手机号码所在地查询';
	
	const action_man_search = '参考文档藏经阁';
	
	const action_newtopic = '创建新主题';
	const action_replytopic = '回复主题';
	const action_viewtopic = '查看主题';
	const action_modifytopic = '修改主题';
	const action_freshtopic = '未回复主题';
	
	const action_viewboard = '查看讨论版';
	const action_viewexpense = '查看消费记录';
	const action_viewonline = '查看谁在线';
	
	const action_modifyprofile = '修改会员信息';
	
	const action_composemessage = '撰写短消息';
	
	const action_search = '搜索';
	
	const msg_submitwrong = '对不起，你刚才提交的信息里有些问题';

	const term_toptopic = '最强主题排行';
	const term_favoritetopic = '我最喜欢的主题';
	const term_accessdenied = '访问阻止';
	
	const term_privatemessage = '短消息';
	
	const term_user_random = '茫茫人海';
	
	const term_favorite = '我的收藏夹';
	
	const term_zen = 'ZEN';
	
	const term_status = '系统状态';
	const term_jobs = 'Employment Opportunity';
	
	const term_rules = '电子公告服务规则';
	const term_terms = '使用规则';
	const term_privacy = '隐私权保护规则';
	const term_policies = '禁止性内容规则';
	
	const term_out_of_money = '没有铜币没有银币没有金币';
}
?>