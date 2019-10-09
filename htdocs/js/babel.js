/* Project Babel
*  Author: Livid Torvalds
*  File: /htdocs/js/babel.js
*  Usage: Client side functions for Project Babel
*  Format: 1 tab ident(4 spaces), LF, UTF-8, no-BOM
*
*  Subversion Keywords:
*
*  $Id: babel.js 412 2006-06-26 09:36:31Z livid $
*  $LastChangedDate: 2006-06-26 17:36:31 +0800 (Mon, 26 Jun 2006) $
*  $LastChangedRevision: 412 $
*  $LastChangedBy: livid $
*/

var getObj = function(objId) {
	return document.all ? document.all[objId] : document.getElementById(objId);
}

var switchDisplay = function(objId) {
	obj = getObj(objId);
	if (obj.style.display != "block") {
		obj.style.display = "block";
	} else {
		obj.style.display = "none";
	}
}

var changeBlockStyle = function(objId, strBgColor) {
	obj = getObj(objId);
	obj.bgColor = strBgColor;
}

var showReply = function(replyId) {
	if (replyId == "vxReplyA" ) {
		vertScroll = 0;
	} else {
		if (document.all) {
			vertScroll = document.body.offsetHeight + 400;
		} else {
			vertScroll = window.pageYOffset + 400;
		}
	}
	
	action = "window.scroll(0, " + vertScroll + ")";
	
	objReply = getObj(replyId);
	if (objReply.style.display != "block") {
		objReply.style.display = "block";
		setTimeout(action, 10);
	}
	
	if (replyId == "vxReplyB") {
		objReplyTip = getObj("vxReplyTip");
		if (objReplyTip.style.display != "none") {
			objReplyTip.style.display = "none";
		}
	}	
}

var jumpReply = function() {
	objQuick = getObj("taQuick");
	setTimeout("objQuick.focus()", 200);
}

var req;

function loadXML(url, cb) {
	req = false;
	// branch for native XMLHttpRequest object
	if(window.XMLHttpRequest) {
		try {
			req = new XMLHttpRequest();
		} catch(e) {
			req = false;
		}
	// branch for IE/Windows ActiveX version
	} else if(window.ActiveXObject) {
		try {
			req = new ActiveXObject("Msxml2.XMLHTTP");
		} catch(e) {
			try {
				req = new ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {
				req = false;
			}
		}
	}
	if(req) {
		req.onreadystatechange = cb;
		req.open("GET", url, true);
		req.send("");
	}
}

var addFavoriteTopic = function(topicId) {
	objFav = getObj("tpcFav");
	objFav.innerHTML = '&nbsp;&nbsp;<img src="/img/progress_bar.gif" align="absmiddle" />';
	url = "/fav/topic/add/" + topicId + ".vx";
	setTimeout("loadXML(url, addFavoriteTopicCallback)", 700);
}

var addFavoriteTopicCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("tpcFav");
			objFav.innerHTML = '<a href="/topic/favorite.vx" class="h"><span class="tip_i"> ++ </span>本主题已加入收藏</a>';
		} else {
			objFav = getObj("tpcFav");
			objFav.innerHTML = '<a href="#" class="h">oops</a>';
		}
	}
}

var removeFavoriteTopic = function(topicId) {
	objFav = getObj("tpcFav");
	objFav.innerHTML = '&nbsp;&nbsp;<img src="/img/progress_bar.gif" align="absmiddle" />';
	url = "/fav/remove/" + topicId + ".vx";
	setTimeout("loadXML(url, removeFavoriteTopicCallback)", 700);
}

var removeFavoriteTopicCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("tpcFav");
			objFav.innerHTML = '<a href="/topic/favorite.vx" class="h">本主题已从收藏中移出<span class="tip_i"> -- </span></a>';
		} else {
			objFav = getObj("tpcFav");
			objFav.innerHTML = '<a href="#" class="h">oops</a>';
		}
	}
}

var addFavoriteNode = function(nodeId) {
	objFav = getObj("nodFav");
	objFav.innerHTML = '<img src="/img/loading.gif" align="absmiddle" />&nbsp;正在发送请求...';
	url = "/fav/node/add/" + nodeId + ".vx";
	loadXML(url, addFavoriteNodeCallback);
}

var addFavoriteNodeCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("nodFav");
			objFav.innerHTML = '<img src="/img/pico_ok.gif" align="absmiddle" />&nbsp;本讨论板已加入收藏！';
		} else {
			objFav = getObj("nodFav");
			objFav.innerHTML = '<img src="/img/pico_error.gif" align="absmiddle" />&nbsp;oops';
		}
	}
}

var removeFavoriteNode = function(nodeId) {
	objFav = getObj("nodFav");
	objFav.innerHTML = '<img src="/img/loading.gif" align="absmiddle" />&nbsp;正在发送请求...';
	url = "/fav/remove/" + nodeId + ".vx";
	loadXML(url, removeFavoriteNodeCallback);
}

var removeFavoriteNodeCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("nodFav");
			objFav.innerHTML = '<img src="/img/pico_ok.gif" align="absmiddle" />&nbsp;本讨论板已从收藏中移出！';
		} else {
			objFav = getObj("nodFav");
			objFav.innerHTML = '<img src="/img/pico_error.gif" align="absmiddle" />&nbsp;oops';
		}
	}
}

var addFavoriteChannel = function(channelId) {
	objFav = getObj("chlFav");
	objFav.innerHTML = '<img src="/img/loading.gif" align="absmiddle" />&nbsp;正在发送请求...';
	url = "/fav/channel/add/" + channelId + ".vx";
	loadXML(url, addFavoriteChannelCallback);
}

var addFavoriteChannelCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("chlFav");
			objFav.innerHTML = '<img src="/img/pico_ok.gif" align="absmiddle" />&nbsp;本频道已加入收藏！';
		} else {
			objFav = getObj("chlFav");
			objFav.innerHTML = '<img src="/img/pico_error.gif" align="absmiddle" />&nbsp;oops';
		}
	}
}

var removeFavoriteChannel = function(favId) {
	objFav = getObj("chlFav");
	objFav.innerHTML = '<img src="/img/loading.gif" align="absmiddle" />&nbsp;正在发送请求...';
	url = "/fav/remove/" + favId + ".vx";
	loadXML(url, removeFavoriteChannelCallback);
}

var removeFavoriteChannelCallback = function() {
	if (req.readyState == 4) {
		if (req.status == 200) {
			objFav = getObj("chlFav");
			objFav.innerHTML = '<img src="/img/pico_ok.gif" align="absmiddle" />&nbsp;本频道已从收藏中移出！';
		} else {
			objFav = getObj("chlFav");
			objFav.innerHTML = '<img src="/img/pico_error.gif" align="absmiddle" />&nbsp;oops';
		}
	}
}

function sendMessage(userId) {
	newWin = window.open("/message/compose/" + userId + ".vx", "winMessage", "width=400,height=300");
	newWin.moveTo(100,100);
	newWin.focus();
}

function openMessage() {
	newWin = window.open("/message/home.vx", "winMessage", "width=400,height=300");
	newWin.moveTo(100,100);
	newWin.focus();
}