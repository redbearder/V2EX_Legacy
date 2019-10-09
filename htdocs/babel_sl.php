<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/babel_sl.php
*  Usage: Standalone Logic
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel_sl.php 464 2006-07-11 10:39:03Z livid $
*  $LastChangedDate: 2006-07-11 18:39:03 +0800 (Tue, 11 Jul 2006) $
*  $LastChangedRevision: 464 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/babel_sl.php $
*/

DEFINE('V2EX_BABEL', 1);

require('core/StandaloneCore.php');

if (isset($_GET['m'])) {
	$m = strtolower(trim($_GET['m']));
} else {
	$m = 'home';
}

$s = new Standalone;

switch ($m) {
	default:
	case 'home':
		$s->vxGoHome();
		break;
		
	case 'recv_portrait':
		$s->vxRecvPortrait();
		break;
	
	case 'recv_savepoint':
		$s->vxRecvSavepoint();
		break;
		
	case 'savepoint_erase':
		$s->vxSavepointErase();
		break;
		
	case 'recv_zen_project':
		$s->vxRecvZENProject();
		break;
		
	case 'erase_zen_project':
		$s->vxEraseZENProject();
		break;
		
	case 'recv_zen_task':
		$s->vxRecvZENTask();
		break;
	
	case 'change_zen_task_done':
		$s->vxChangeZENTaskDone();
		break;
		
	case 'change_zen_project_permission':
		$s->vxChangeZENProjectPermission();
		break;
		
	case 'erase_zen_task':
		$s->vxEraseZENTask();
		break;
		
	case 'undone_zen_task':
		$s->vxUndoneZENTask();
		break;
}
?>