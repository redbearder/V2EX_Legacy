<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/babel_api.php
*  Usage: API Controller
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel_api.php 84 2005-12-03 12:07:34Z livid $
*  $LastChangedDate: 2005-12-03 20:07:34 +0800 (Sat, 03 Dec 2005) $
*  $LastChangedRevision: 84 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/babel_api.php $
*/

DEFINE('V2EX_BABEL', 1);

require('core/APICore.php');

if (isset($_GET['m'])) {
	$m = strtolower(trim($_GET['m']));
} else {
	$m = 'home';
}

$a =& new API;

switch ($m) {
	default:
	case 'topic_create':
		$a->vxTopicCreate();
		break;
}
?>