<?php
if (isset($keyword)) {
	$kw = make_single_return($keyword);
} else {
	$kw = '';
}
?>
<!-- SiteSearch Google -->
<form method="get" action="http://www.google.com/custom" target="google_window">
<a href="http://www.google.com/" class="var">
<img src="http://www.google.com/logos/Logo_25wht.gif" border="0" alt="Google" align="absmiddle"></img></a>
<input type="hidden" name="domains" value="www.v2ex.com;www.livid.cn"></input>
<?php
echo ('<input type="text" id="txt_google_search" class="google_search" name="q" size="31" maxlength="255" value="' . $kw . '"></input>');
?>&nbsp;&nbsp;
<label><input type="radio" name="sitesearch" value=""></input>
Web&nbsp;&nbsp;</label>
<label><input type="radio" name="sitesearch" value="www.v2ex.com" checked="checked"></input>
V2EX.com&nbsp;&nbsp;</label>
<label><input type="radio" name="sitesearch" value="www.livid.cn"></input>
Livid.cn&nbsp;&nbsp;</label>
<input type="submit" name="sa" value="搜索"></input>
<input type="hidden" name="client" value="pub-9823529788289591"></input>
<input type="hidden" name="forid" value="1"></input>
<input type="hidden" name="ie" value="UTF-8"></input>
<input type="hidden" name="oe" value="UTF-8"></input>
<input type="hidden" name="flav" value="0000"></input>
<input type="hidden" name="sig" value="sPOKErd-NdJXfEEx"></input>
<input type="hidden" name="cof" value="GALT:#008000;GL:1;DIV:#FFFFFF;VLC:663399;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:0000FF;LC:0000FF;T:000000;GFNT:0000FF;GIMP:0000FF;LH:50;LW:167;L:http://www.v2ex.com/img/logo_upon.gif;S:http://www.v2ex.com;FORID:1;"></input>
<input type="hidden" name="hl" value="zh-CN"></input>
</form>
<!-- SiteSearch Google -->