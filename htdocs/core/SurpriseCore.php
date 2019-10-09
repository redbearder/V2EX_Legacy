<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/UserCore.php
*  Usage: User Class
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: SurpriseCore.php 173 2006-04-09 20:12:41Z livid $
*  $LastChangedDate: 2006-04-10 04:12:41 +0800 (Mon, 10 Apr 2006) $
*  $LastChangedRevision: 173 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/SurpriseCore.php $
*/

class Surprise {
	var $srp_type;
	var $srp_type_msg;
	var $srp_amount;
	var $User;
	var $Foundation;
	
	public function __construct($User) {
		$this->srp_type_msg = array(0 => '什么事情都没发生~', 1 => '你今天运气特别特别好，被钱砸到了！', 2 => '不小心啊 ~ 不小心啊 ~ 钱掉了');
		$this->User = $User;
	}
	
	public function __destruct($User) {
	}
	
	public function vxDice($p) {
		$p1 = rand(1, $p); /* x axis */
		$p2 = rand(1, $p); /* y axis */
		$p3 = rand(1, 1000); /* srp_amount */
		$p4 = intval(($p * $p) * 0.75); /* threshold */
		$p5 = rand(1, ($p * $p)); /* plus or minus */ 
		$p6 = $p5 > $p4 ? 2:1; /* srp_type: plus or minus */

		if ($p1 == $p2) {
			$this->srp_type = $p6;
			$this->srp_amount = $p3;
		} else {
			$this->srp_type = 0;
			$this->srp_amount = 0;
		}
	}
}
?>