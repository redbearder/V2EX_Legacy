<?php
/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/core/Utility.php
*  Usage: Utility functions
*  Format: 1 tab indent(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: Utilities.php 494 2006-07-13 22:59:10Z livid $
*  $LastChangedDate: 2006-07-14 06:59:10 +0800 (Fri, 14 Jul 2006) $
*  $LastChangedRevision: 494 $
*  $LastChangedBy: livid $
*  $URL: http://lividot.org/svn/kijiji/htdocs/core/Utilities.php $
*/

if (@V2EX_BABEL != 1) {
	die('<strong>Project Babel</strong><br /><br />Made by <a href="http://www.v2ex.com/">V2EX</a> | software for internet');
}

$dirs = array('/tmp', '/tplc', '/cache', '/cache/360', '/cache/7200', '/cache/rss', '/cache/dict', '/cache/smarty', '/htdocs/img/c', '/htdocs/img/n', '/htdocs/img/s', '/htdocs/img/p');

function check_env() {
	global $dirs;
	foreach ($dirs as $dir) {
		if (!is_writable(BABEL_PREFIX . $dir)) {
			return exception_message('permission');
		}
	}
}

function exception_message($func = '') {
	header('Content-type: text/html;charset=UTF-8');
	$o = '<html><head><title>Babel Fatal Error</title><meta http-equiv="content-type: text/html;charset=UTF-8" /></head><style>a, a:visited, a:active { color: #0C0; text-decoration: none; } a:hover { color: #0F0; text-decoration: none; } h1 { color: #FC0; } strong { color: #F00; } em { color: #0F0; } address { font-size: 10px; color: #999; }</style><body style="background-color: #333; color: #F0F0F0; font-size: 12px; font-family: Sans"><div><h1>Babel Fatal Error</h1>';
	switch ($func) {
		case 'permission':
			global $dirs;
			$o .= 'Babel 启动失败，请确认以下目录存在，并且可以被 web server 进程写入:<ul style="list-style: square; font-size: 15px; font-family: monospace">';
			foreach ($dirs as $dir) {
				$s_tmp = is_writable(BABEL_PREFIX . $dir) ? '<em>ok</em>' : '<strong>access denied</strong>';
				$o .= '<li>' . BABEL_PREFIX . $dir . ' ... ' . $s_tmp . '</li>';
			}
			$o .= '</ul>如果是在 Unix 操作系统上运行 Babel，你可以使用 chmod 777 方式来更改目录权限，或将以上目录的所有者更改为 web server 进程用户。';
			break;
		case 'db':
			$o .= 'Babel 启动失败，数据库连接无法建立。';
			break;
		case 'world':
			$o .= 'Babel 世界数据尚未导入，请创建你自己的 InstallCore.php 并运行一次。这样你将拥有一个最初的 Babel 世界。';
	}
	$o .= '<hr /><small>Powered by <a href="http://www.v2ex.com/">V2EX</a> | &copy; 2006 Xin Liu (a.k.a <a href="http://www.livid.cn/">Livid</a>)</small><address>$Id: Utilities.php 494 2006-07-13 22:59:10Z livid $</address></div></body></html>';
	die ($o);
}

// return: int
function format_api_date($sd) {
	$a = explode(' ', $sd);
	
	$day = $a[0];
	$month = $a[1];
	$year = $a[2];
	$time = $a[3];
	
	$a = explode(':', $time);
	
	$hour = $a[0];
	$minute = $a[1];
	$second = $a[2];
	
	return mktime($hour, $minute, $second, $month, $day, $year);
}

function format_def($def) {
	$o = htmlspecialchars(trim($def));
	$o = explode("\n", $o);
	$o[0] = '<span class="text_large">' . $o[0] . '</span>';
	$o = nl2br(implode("\n", $o));
	return $o;
}

function format_ubb($text) {
	$text = str_replace('<', '&lt;', $text);
	$text = str_replace('>', '&gt;', $text);
	$text = nl2br($text);
	$p[0] = '/\[img\]([^?].*?)\[\/img\]/i';
	
	// matches a [url]xxxx://www.livid.cn[/url] code..
	$p[1] = "#\[url\]([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
	
	// [url]www.livid.cn[/url] code.. (no xxxx:// prefix).
	$p[2] = "#\[url\]((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
	
	// [url=xxxx://www.phpbb.com]phpBB[/url] code..
	$p[3] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	
	// [url=www.phpbb.com]phpBB[/url] code.. (no xxxx:// prefix).
	$p[4] = "#\[url=((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	
	// [media,width,height]xxxx://www.livid.cn/example.mp3[/media] code..
	$p[5] = "#\[media,([0-9]*),([0-9]*)\]([^?].*?)\[\/media\]#is";
	
	// [email]user@domain.tld[/email] code..
	$p[6] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
	
	$p[7] = '/\[url\]([^?].*?)\[\/url\]/i';
	
	$p[8] = '/\[b\](.*?)\[\/b\]/i';
	
	$p[9] = '/\[strong\](.*?)\[\/strong\]/i';
	
	$p[10] = '/\[i\](.*?)\[\/i\]/i';
	
	$p[11] = '/\[em\](.*?)\[\/em\]/i';
	
	$p[12] = '/\[go=([a-zA-Z_\-0-9]+)\](.*?)\[\/go\]/';
	
	$r[0] = '<img class="code" src="$1" border="0" />';
	$r[1] = '<a href="$1" rel="nofollow" class="tpc" target="_blank">$1</a>';
	$r[2] = '<a href="http://$1" rel="nofollow" class="tpc" target="_blank" rel="nofollow">http://$1</a>';
	$r[3] = '<a href="$1" rel="nofollow" class="tpc" target="_blank" rel="nofollow">$2</a>';
	$r[4] = '<a href="http://$1" rel="nofollow" class="tpc" target="_blank" rel="nofollow">$2</a>';
	$r[5] = '<embed width="$1" height="$2" src="$3" autostart="true" loop="false" />';
	$r[6] = '<a class="tpc" href="mailto:$1">$1</a>';
	$r[7] = '<a class="tpc" href="$1">$1</a>';
	$r[8] = '<strong>$1</strong>';
	$r[9] = '<strong>$1</strong>';
	$r[10] = '<em>$1</em>';
	$r[11] = '<em>$1</em>';
	$r[12] = '讨论区 [ <a href="/go/$1" class="tpc">$2</a> ]';
	
	$text = preg_replace($p, $r, $text);
	return $text;
}

function make_spaces($count) {
	$o;
	while ($i < $count) {
		$o = $o . '&nbsp;';
		$i++;
	}
	return $o;
}

function make_safe_display($txt) {
	$txt = str_ireplace(' width="100%"', ' ', $txt);
	return $txt;
}

function make_single_return($value, $strip = 1) {
	if (get_magic_quotes_gpc()) {
		if ($strip == 1) {
			$value = stripslashes($value);
		}
		return str_replace('"', '&quot;', $value);
	} else {
		return str_replace('"', '&quot;', $value);
	}
}

function make_multi_return($value, $strip = 1) {
	if ($strip == 1) {
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
	}
	$value = str_replace('<', '&lt;', $value);
	$value = str_replace('>', '&gt;', $value);
	return $value;
}

function make_single_safe($value) {
	$value = trim($value);
	$value = str_replace(chr(10), '', $value);
	$value = str_replace(chr(13), '', $value);
	return $value;
}

function make_multi_safe($value) {
	$value = trim($value);
	return $value;
}

function make_plaintext($text) {
	$text = str_replace('<', '&lt;', $text);
	$text = str_replace('>', '&gt;', $text);
	$text = nl2br($text);
	return $text;
}

function trim_br($text) {
	$text = trim($text);
	if (substr($text, 0, 5) == '&#13;') {
		$text = substr($text, 6, (strlen($text) - 6));
	}
	$text = trim($text);
	if (substr($text, 0, 6) == '<br />') {
		$text = substr($text, 6, (strlen($text) - 6));
	}
	return $text;
}

function make_excerpt_man($man, $query, $style) {
	$excerpt = trim($man);
	$len = strlen($excerpt);
	$query = str_replace('(', '', $query);
	$query = str_replace(')', '', $query);
	$_tmp = explode(' ', $query);
	$keywords = array();
	foreach ($_tmp as $keyword) {
		$keyword = trim($keyword);
		if (in_array(substr($keyword, 0, 1), array('-', '+'))) {
			$keyword = substr($keyword, 1, (strlen($keyword) - 1));
		}
		$keywords[] = $keyword;
		$start = mb_strpos($excerpt, $keyword, 0, 'UTF-8');
		if ($start != false) {
			break;
		} else {
			$start = 0;
		}
	}
	if ($start != 0) {
		if ($start < 100) {
			$start = 0;
		} else {
			$start = $start - 100;
		}
	}
	$o = mb_substr($excerpt, $start, 300, 'UTF-8');
	$excerpt = make_highlight($o, $keywords, STR_HIGHLIGHT_SKIPLINKS, $style);
	if (strlen($excerpt) < $len) {
		$excerpt = $excerpt . ' ...';
	}
	return $excerpt;
}

function make_excerpt_ad($ad, $keywords, $style) {
	$o = trim(strip_tags($ad));
	
	$o = make_highlight($o, $keywords, STR_HIGHLIGHT_SKIPLINKS, $style);
	
	return $o;
}

function make_excerpt_home($Topic) {
	$len_content = strlen($Topic->tpc_content);
	$len_desc = strlen($Topic->tpc_description);
	$excerpt = '';
	$excerpt_c = '';
	$excerpt_d = '';
	if ($len_content > 0) {
		$excerpt_c = format_ubb($Topic->tpc_content);
		$excerpt_c = trim($excerpt_c);
	} else {
		$excerpt_d = format_ubb($Topic->tpc_description);
		$excerpt_d = trim($excerpt_d);
	}
	if (strlen($excerpt_c) > 0) {
		$stage = 1;
		$excerpt = $excerpt_c;
	} else {
		if (strlen($excerpt_d) > 0) {
			$stage = 2;
			$excerpt = $excerpt_d;
		} else {
			$stage = 3;
			$excerpt = $Topic->tpc_title;
		}
	}
	$start = 0;
	return $excerpt;
}

function make_excerpt_topic($Topic, $keywords, $style) {
	$len_content = strlen($Topic->tpc_content);
	$len_desc = strlen($Topic->tpc_description);
	$excerpt = '';
	$excerpt_c = '';
	$excerpt_d = '';
	if ($len_content > 0) {
		$excerpt_c = format_ubb($Topic->tpc_content);
		$excerpt_c = trim(strip_tags($excerpt_c));
	} else {
		$excerpt_d = format_ubb($Topic->tpc_description);
		$excerpt_d = trim(strip_tags($excerpt_d));
	}
	if (strlen($excerpt_c) > 0) {
		$stage = 1;
		$excerpt = $excerpt_c;
	} else {
		if (strlen($excerpt_d) > 0) {
			$stage = 2;
			$excerpt = $excerpt_d;
		} else {
			$stage = 3;
			$excerpt = $Topic->tpc_title;
		}
	}
	foreach ($keywords as $keyword) {
		$start = mb_strpos($excerpt, $keyword, 0, 'UTF-8');
		if ($start != false) {
			break;
		} else {
			$start = 0;
		}
	}
	if ($start != 0) {
		if ($start < 100) {
			$start = 0;
		} else {
			$start = $start - 100;
		}
	}
	$o = mb_substr($excerpt, $start, 300, 'UTF-8');
	$excerpt = make_highlight($o, $keywords, STR_HIGHLIGHT_SKIPLINKS, $style);
	if ($stage != 3) {
		if (strlen($excerpt) < $len_content) {
			$excerpt = $excerpt . ' ...';
		}
	}
	return $excerpt;
}

function make_masked_ip($ip) {
	return preg_replace('/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/', '$1.$2.$3.*', $ip);
}

function make_desc_time($unix_timestamp) {
	$now = time();
	$diff = $now - $unix_timestamp;
	
	if ($diff > 86400) {
		$d_span = intval($diff / 86400);
		$h_diff = $diff % 86400;
		if ($h_diff > 3600) {
			$h_span = intval($h_diff / 3600);
			return $d_span . 'd ' . $h_span . 'h';
		} else {
			return $d_span . 'd';
		}
	}
	
	if ($diff > 3600) {
		$h_span = intval($diff / 3600);
		$m_diff = $diff % 3600;
		if ($m_diff > 60) {
			$m_span = intval($m_diff / 60);
			return $h_span . 'h ' . $m_span . 'm';
		} else {
			return $h_span . 'h';
		}
	}
	
	if ($diff > 60) {
		$span = intval($diff / 60);
		return $span . 'm';
	}
	
	return $diff . 's';
}

function make_descriptive_time($unix_timestamp) {
	$now = time();
	$diff = $now - $unix_timestamp;
	
	if ($diff > (86400 * 30)) {
		$m_span = intval($diff / (86400 * 30));
		$d_diff = $diff % ($m_span * (86400 * 30));
		if ($d_diff > 86400) {
			$d_span = intval($d_diff / 86400);
			return $m_span . ' 月 ' . $d_span . ' 天前';
		} else {
			return $m_span . ' 月前';
		}
	}
	
	if ($diff > 86400) {
		$d_span = intval($diff / 86400);
		$h_diff = $diff % 86400;
		if ($h_diff > 3600) {
			$h_span = intval($h_diff / 3600);
			return $d_span . ' 天 ' . $h_span . ' 小时前';
		} else {
			return $d_span . ' 天前';
		}
	}
	
	if ($diff > 3600) {
		$h_span = intval($diff / 3600);
		$m_diff = $diff % 3600;
		if ($m_diff > 60) {
			$m_span = intval($m_diff / 60);
			return $h_span . ' 小时 ' . $m_span . ' 分钟前';
		} else {
			return $h_span . ' 小时前';
		}
	}
	
	if ($diff > 60) {
		$span = intval($diff / 60);
		return $span . ' 分钟前';
	}
	
	return $diff . ' 秒前';
}

function rand_color($color_start = 0, $color_end = 3) {
	$color = array(0 => '0', 1 => '3', 2 => '6', 3 => '9', 4 => 'C', 5 => 'F');
	while (($o ='#' . $color[rand($color_start, $color_end)] . $color[rand($color_start, $color_end)] . $color[rand($color_start, $color_end)]) != '#FFF') {
		return $o;
	}
}

function rand_gray($color_start = 1, $color_end = 3) {
	$color = array(0 => '0', 1 => '3', 2 => '6', 3 => '9', 4 => 'C', 5 => 'F');
	$g = $color[rand($color_start, $color_end)];
	while (($o = '#' . $g . $g . $g) != '#FFF') {
		return $o;
	}
}

function rand_font() {
	$font = array(0 => 'Tahoma', 1 => 'sans', 2 => 'Times', 3 => 'fantasy', 4 => 'mono', 5 => 'serif', 6 => 'Verdana', 7 => '"Times New Roman"', 8 => 'Lucida Grande', 9 => 'Arial', 10 => 'Georgia', 11 => 'Geneva');
	return $font[rand(0, 11)];
}

function microtime_float() {
	$ms = explode(' ', microtime());
	$usec = $ms[0];
	$sec = $ms[1];
	return ((float)$usec + (float)$sec);
}

function is_valid_email($email) {
	$regex = '/^[A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i';
	return (preg_match($regex, $email));
}

function is_valid_nick($nick) {
	$regex = "/[\\\\<>\\n\\t\\a\\r\\s\"'\\/\\.,\\-~!@#\$%^&*()_+=|\\[\\]\{\};:?]+/";
	if (preg_match($regex, $nick)) {
		return false;
	} else {
		$bad_words = array('。', '，', '？', '～', '！', '刘', '昕', '刘昕', '客齐集', '管', 'admin', 'fuck', 'kijiji', '斑竹', '版', '主');
		foreach ($bad_words as $w) {
			$pos = stripos($nick, $w);
			if ($pos === false) {
				$i = 1;
			} else {
				$i = 0;
				return false;
			}
		}
		if ($i == 1) {
			return true;
		}
	}
}

/**
* Perform a simple text replace
* This should be used when the string does not contain HTML
* (off by default)
*/
define('STR_HIGHLIGHT_SIMPLE', 1);

/**
* Only match whole words in the string
* (off by default)
*/
define('STR_HIGHLIGHT_WHOLEWD', 2);

/**
* Case sensitive matching
* (on by default)
*/
define('STR_HIGHLIGHT_CASESENS', 4);

/**
* Don't match text within link tags
* This should be used when the replacement string is a link
* (off by default)
*
* Doesn't work as yet - can't have variable length lookbehind sets
*/
define('STR_HIGHLIGHT_SKIPLINKS', 8);

/**
* Highlight a string in text without corrupting HTML tags
*
* @param       string          $text           Haystack - The text to search
* @param       array|string    $needle         Needle - The string to highlight
* @param       bool            $options        Bitwise set of options
* @param       array           $highlight      Replacement string
* @return      Text with needle highlighted
*/
function make_highlight($text, $needle, $options = null, $highlight = null)
{
    // Default highlighting
    if ($highlight === null) {
        $highlight = '<strong>\1</strong>';
    }

    // Select pattern to use
    if ($options & STR_HIGHLIGHT_SIMPLE) {
        $pattern = '#(%s)#';
    } elseif ($options & STR_HIGHLIGHT_SKIPLINKS) {
        // This is not working yet
        $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
    } else {
        $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
    }

    // Case sensitivity
    if ($options ^ STR_HIGHLIGHT_CASESENS) {
        $pattern .= 'i';
    }

    $needle = (array) $needle;
    foreach ($needle as $needle_s) {
        $needle_s = preg_quote($needle_s);

        // Escape needle with optional whole word check
        if ($options & STR_HIGHLIGHT_WHOLEWD) {
            $needle_s = '\b' . $needle_s . '\b';
        }

        $regex = sprintf($pattern, $needle_s);
        $text = preg_replace($regex, $highlight, $text);
    }

    return $text;
}
?>
