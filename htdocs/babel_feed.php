<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/babel_sl.php
*  Usage: Standalone Logic
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel_sl.php 219 2006-04-18 23:27:20Z livid $
*  $LastChangedDate: 2006-04-19 07:27:20 +0800 (Wed, 19 Apr 2006) $
*  $LastChangedRevision: 219 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/babel_sl.php $
*/

DEFINE('V2EX_BABEL', 1);

require('core/FeedCore.php');

if (isset($_GET['m'])) {
	$m = strtolower(trim($_GET['m']));
} else {
	$m = 'home';
}

$f = new Feed;

switch ($m) {
	default:
	case 'home':
		$f->vxFeed();
		break;
		
	case 'board':
		if (isset($_GET['board_name'])) {
			$board_name = strtolower(trim($_GET['board_name']));
			$sql = "SELECT nod_id, nod_level, nod_name, nod_title, nod_topics FROM babel_node WHERE nod_name = '{$board_name}' AND nod_level > 0";
			$rs = mysql_query($sql);
			if ($Node = mysql_fetch_object($rs)) {
				mysql_free_result($rs);
				$f->vxFeedBoard($Node);
			} else {
				mysql_free_result($rs);
				$f->vxFeed();
			}
		} else {
			$f->vxFeed();
		}
		break;
}
?>