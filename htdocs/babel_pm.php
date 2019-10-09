<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/babel_pm.php
*  Usage: Loader for Private Message System
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel_pm.php 44 2005-11-16 15:37:23Z livid $
*  $LastChangedDate: 2005-11-16 23:37:23 +0800 (Wed, 16 Nov 2005) $
*  $LastChangedRevision: 44 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/babel_pm.php $
*/

DEFINE('V2EX_BABEL', 1);

require('core/PrivateMessageCore.php');

if (isset($_GET['m'])) {
	$m = strtolower(trim($_GET['m']));
} else {
	$m = 'home';
}

$p = new PrivateMessage;

switch ($m) {
	default:
	case 'home':
		$p->vxHome();
		break;
	case 'compose':
		$p->vxCompose();
		break;
	case 'create':
		$p->vxCreate();
		break;
	case 'inbox':
		$p->vxInbox();
		break;
	case 'sent':
		$p->vxSent();
		break;
	case 'view':
		$p->vxView();
		break;
	case 'draft':
		$p->vxDraft();
		break;
}
?>