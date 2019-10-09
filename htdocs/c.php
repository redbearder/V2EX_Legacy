<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/img/c.php
*  Usage: Confirm Code Wrapper
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*/
define('V2EX_BABEL', 1);
require_once ('core/Settings.php');
header('Content-type: image/png');
session_start();
$im = imagecreatefrompng(BABEL_PREFIX . '/htdocs/img/c/' . session_id() . '.png');
imagepng($im);
imagedestroy($im);
?>