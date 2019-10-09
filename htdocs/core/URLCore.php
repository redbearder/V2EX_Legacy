<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/URLCore.php
*  Usage: URL Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: URLCore.php 464 2006-07-11 10:39:03Z livid $
*  $LastChangedDate: 2006-07-11 18:39:03 +0800 (Tue, 11 Jul 2006) $
*  $LastChangedRevision: 464 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/URLCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S URL class */

class URL {
	public function vxGetLogin($return) {
		$url = "/login/{$return}";
		return $url;
	}
	
	public function vxGetBoardView($board_id) {
		$url = "/board/view/{$board_id}.html";
		return $url;
	}
	
	public function vxGetPostErase($post_id) {
		$url = "/post/erase/{$post_id}.vx";
		return $url;
	}
	
	public function vxGetTopicErase($topipc_id) {
		$url = "/topic/erase/{$topic_id}.vx";
		return $url;
	}
	
	public function vxGetTopicView($topic_id) {
		$url = "/topic/view/{$topic_id}.html";
		return $url;
	}
	
	public function vxGetTopicNew($board_id) {
		$url = "/topic/new/{$board_id}.vx";
		return $url;
	}
	
	public function vxGetTopicModify($topic_id) {
		$url = "/topic/modify/{$topic_id}.vx";
		return $url;
	}
	
	public function vxToRedirect($addr) {
		header('Location: ' . $addr);
	}
	
	public function vxGetExpenseView() {
		$url = '/expense/view.vx';
		return $url;
	}
	
	public function vxGetOnlineView() {
		$url = '/online/view.vx';
		return $url;
	}
	
	public function vxGetTopicFavorite() {
		$url = '/topic/favorite.vx';
		return $url;
	}
	
	public function vxGetUserModify() {
		$url = '/user/modify.vx';
		return $url;
	}
	
	public function vxGetUserOwnHome($message = '') {
		if ($message == '') {
			$url = '/me';
		} else {
			$url = '/me/' . $message;
		}
		return $url;
	}
	
	public function vxGetZEN($anchor = '') {
		if ($anchor != '') {
			$url = '/zen#' . $anchor;
		} else {
			$url = '/zen';
		}
		return $url;
	}
	
	public function vxGetEraseZENProject($zen_project_id = 0) {
		$url = '/erase/zen/project/' . $zen_project_id . '.vx';
		return $url;
	}
	
	public function vxGetHome() {
		$url = '/';
		return $url;
	}
}

/* E URL class */
?>