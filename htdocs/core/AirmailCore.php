<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/AirmailCore.php
*  Usage: Airmail Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: AirmailCore.php 252 2006-04-26 13:01:38Z livid $
*  $LastChangedDate: 2006-04-26 21:01:38 +0800 (Wed, 26 Apr 2006) $
*  $LastChangedRevision: 252 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/AirmailCore.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

/* S Airmail class */

class Airmail {
	var $body;

	var $headers;
	var $params;

	public function __construct($receiver, $subject, $body) {
		$this->body = mb_convert_encoding($body, 'GBK', 'UTF-8');
		
		$this->headers = array();
		$this->headers['from'] = BABEL_AM_FROM;
		$this->headers['to'] = $receiver;
		$this->headers['subject'] = mb_convert_encoding($subject, 'GBK', 'UTF-8');
		
		$this->params = array();
		$this->params["sendmail_path"] = '/usr/sbin/sendmail';
	}
	
	public function __destruct() {
	}
	
	public function vxSend() {
		$m =& Mail::factory('sendmail', $this->params);
		$m->send($this->headers['to'], $this->headers, $this->body);
	}
}

/* E Airmail class */
?>
