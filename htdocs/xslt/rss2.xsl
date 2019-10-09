<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" />

<xsl:template match="/">
	<xsl:element name="html">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><xsl:value-of select="rss/channel/title" /></title>
	<style type="text/css">
	body {
	padding: 0;
	margin: 0;
	}
	
	div#top {
	background-color: #000;
	text-align: left;
	font-family: "Lucida Grande", Geneva, Verdana, sans;
	font-size: 20px;
	color: #FFF;
	padding: 10px;
	clear: both;
	border-bottom: 1px solid #333;
	}
	
	div#bottom {
	background-color: #000;
	text-align: right;
	font-family: "Lucida Grande", Geneva, Verdana, sans;
	font-size: 11px;
	color: #FFF;
	padding: 5px;
	clear: both;
	border-top: 1px solid #333;
	}
	
	div#main {
	margin: 0;
	padding: 10px 10px 0 10px;
	background-color: #E0E0C6;
	}
		
	h2 {
	margin: 0;
	padding: 3px;
	background-color: #EDEDDD;
	border-top: 2px solid #EDEDFF;
	border-bottom: 2px solid #C7C7A4;
	font-family: "Lucida Grande", Geneva, Verdana, sans;
	font-size: 16px;
	font-weight: 400;
	}
	
	div.text {
	margin: 0;
	padding: 10px;
	font-family: "Lucida Grande", Geneva, Verdana, sans;
	font-size: 12px;
	color: #000000;
	}
	
	div.tip {
	margin: 0 0 10px 0;
	padding: 10px;
	background-color: #EDEDDD;
	border: 1px dotted #C7C7A4;
	font-family: "Lucida Grande", Geneva, Verdana, sans;
	font-size: 12px;
	color: #000000;
	}
	
	ul {
	padding: 0;
	margin: 10px 0px 10px 2em;
	list-style: square;
	}
	
	a.top:link, a.top:visited, a.top:active {
	color: #fff;
	text-decoration: none;
	}
	
	a.top:hover {
	color: #ccc;
	text-decoration: none;
	}
	
	a:link, a:visited, a:active {
	color: #333;
	text-decoration: none;
	}
	
	a:hover {
	color: #666;
	text-decoration: none;
	}
	</style>
	</head>
	<body>
	<div id="top"><xsl:value-of select="rss/channel/title" /> | <a class="top"><xsl:attribute name="href"><xsl:value-of select="rss/channel/link" /></xsl:attribute><xsl:value-of select="rss/channel/link" /></a></div>
	<div id="main">
	<div class="tip">The file you're viewing now is a news feed designed for Headlines Reader like <a href="http://www.newsgator.com/" target="_blank">NewsGator</a>, <a href="http://www.bloglines.com/" target="blank">Bloglines</a>, NetNewsWire and SharpReader.</div>
	<xsl:for-each select="rss/channel/item">
		<h2><a target="_blank"><xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute><xsl:value-of select="title" /></a></h2>
		<div class="text"><xsl:value-of select="description" disable-output-escaping="yes" /><br /><br />[ <a target="_blank"><xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>read more</a> ] - <xsl:value-of select="pubDate" /></div>
	</xsl:for-each>
	</div>
	<div id="bottom">V2EX | software for internet</div>
	</body>
	</xsl:element>
</xsl:template>
</xsl:stylesheet>
