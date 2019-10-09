<?php
define('V2EX_BABEL', 1);

require_once ('core/Settings.php');

/* 3rdparty Zend Framework cores */
ini_set('include_path', BABEL_PREFIX . '/libs/zf/' . ZEND_FRAMEWORK_VERSION . ':' . ini_get('include_path'));

require_once 'Zend/Search/Lucene.php';

$xml = simplexml_load_file(BABEL_PREFIX . '/res/man.xml');

$index = new Zend_Search_Lucene(BABEL_PREFIX . '/data/lucene/man', true);

foreach ($xml->sets->set as $o) {
	if ($dh = opendir(BABEL_PREFIX . strval($xml->input) . $o['name'])) {
		while (false !== ($file = readdir($dh))) {
			if ($file != '.' && $file != '..' && $file != '.svn') {
				$filename = BABEL_PREFIX . strval($xml->input) . $o['name'] . '/' . $file;
				if (is_file($filename) && (fnmatch('*.txt', $filename) | fnmatch('*.htm', $filename) | fnmatch('*.html', $filename))) {
					$fh = fopen($filename, "r");
					$fstat = stat($filename);
					$contents = @iconv('UTF-8', 'ASCII//TRANSLIT', fread($fh, filesize($filename)));
					preg_match('/TITLE([\s\n]?)>(.+)([\s\n]?)<\/TITLE/i', $contents, $m);
					if (isset($m[2])) {
						$title = trim($m[2]);
					} else {
						preg_match('/<B([\s\n]?)CLASS="function"([\s\n]?)>(.+)<\/B/i', $contents, $m);
						if (isset($m[3])) {
							$title = trim($m[3]);
						} else {
							$title = trim($file);
						}
					}
					unset($m);
					$doc = new Zend_Search_Lucene_Document();
					$doc->addField(Zend_Search_Lucene_Field::Text('url', strval($xml->prefix) . strval($o['name']) . '/' . $file));
					$doc->addField(Zend_Search_Lucene_Field::Text('title', htmlentities($title, ENT_NOQUOTES)));
					$doc->addField(Zend_Search_Lucene_Field::Text('contents', htmlentities(strip_tags($contents), ENT_NOQUOTES)));
					$doc->addField(Zend_Search_Lucene_Field::UnIndexed('set_name', $o['name']));
					$doc->addField(Zend_Search_Lucene_Field::UnIndexed('set_title', $o['title']));
					$doc->addField(Zend_Search_Lucene_Field::UnIndexed('mtime', $fstat[9]));
					$index->addDocument($doc);
					$doc = null;
					echo '[' . $o['title'] . '] Added: ' . "$title (" . strval(strlen($contents)) . " bytes)\n";
					fclose($fh);
					unset($contents);
					unset($title);
					unset($file);
					unset($filename);
				} else {
					echo '[' . $o['title'] . '] ' . "Skipped: $file\n";
				}
			}
		}
		closedir($dh);
	}
}

$index->commit();
?>