<!DOCTYPE html>
<html lang="en">
<head prefix=
  "og: http://ogp.me/ns#
   fb: http://ogp.me/ns/fb#
   product: http://ogp.me/ns/product#">
		<?php include "settings.php"; ?>
		<title><?php echo $gameName; ?> (<?php echo $serverName; ?>)</title>
<meta charset="utf-8">
<meta name="apple-itunes-app" content="app-id=556886960">
<meta property="og:type"                   content="game">
<meta property="og:title"                  content="Shakes & Fidget">
<meta property="og:description"            content="The fun Shakes & Fidget browser game">
<meta property="og:url"                    content="http://localhost">
<meta property="og:image" content="http://cdn.playa-games.com/res/legal/sfgame/logo_square_300.png">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<![endif]-->
<meta http-equiv="expires" content="0">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta name="author" content="Playa Games GmbH">
<meta name="publisher" content="Playa Games GmbH">
<meta name="copyright" content="Copyright 2018 Playa Games GmbH. All Rights reserved.">
<meta name="keywords" content="browser game">
<meta name="description" content="The fun Shakes & Fidget browser game">
<meta name="page-topic" content="Computer/Software/Games">
<meta name="audience" content="All">
<meta name="robots" content="index, follow">
<meta name="apple-mobile-web-app-capableX" content="yes">

<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<link rel="stylesheet" href="res/css/game.css?v=2">
<link rel="stylesheet" href="res/css/sfgame.css?v=3">

<style type="text/css">
  html,body{margin:0;padding:0;width:100%;height:100%; overflow: hidden;}
  #openfl-content { background: #000000; width: 100%; height: 100%; display: none; }

.pixel {
    display: none;
}

</style>
</head>
<body bgcolor="#000000" left="0" top="0" topmargin="0" leftmargin="0" scroll="no" id="body">

<script language="JavaScript" type="text/javascript">
// <!--
// -----------------------------------------------------------------------------
// Globals
var sociallinks = {"sfgame":{"facebook":"https:\/\/www.facebook.com\/pages\/Shakes-Fidget-The-Game\/107431265996255","twitter":"https:\/\/twitter.com\/SFgameOfficial"}};

var swfwidth = '100%';
var swfheight = '100%';
var swfquality = 'best';
var swfscale = 'showAll';
var project = 'sfgame';
var movie = 'res/greg<?php echo $swf_version; ?>';
var bgimage = 'https://cdn.playa-games.com/res/landingpage_new/old/background_nobox1.jpg';
var servername = 'localhost';
var branding_url = '';
var country = '';
var serverid = 'w13';
var forum_url = "//forum-int.sfgame.net";
var manual_url = "http://forum.sfgame.de/showthread.php?t=57";
var bgcolor = "#000000";
var wmode = "opaque";
var language = "";
var utm_parameters = null;
var strings = {};
var flimmerkistePoll = 300;
var moshid = 'hash=c6160238cd34b6fbbf97178aa3b835a6&time=1530441650';
var isIframe = false;
try {
  if (window.location != window.parent.location){
    isIframe = true;
  }
} catch(e){
}

// -----------------------------------------------------------------------------
strings["brandengage.no_offers"] = 'Sorry, there are no offers at this moment.';
// -->
</script>
<script crossorigin="anonymous" src="res/js/cookie.min.js"></script>
<script crossorigin="anonymous" src="res/js/qurl.js"></script>
<script crossorigin="anonymous" src="res/js/require.js"></script>
<script crossorigin="anonymous" src="res/js/thegame.js?version=12"></script>
<script crossorigin="anonymous" src="res/js/flimmerkiste_mosh.php?version=1" type="text/javascript"></script>

<script type="text/javascript">
var PlayaCookie = {}
var storage = {};
var font_url = "//cdn.playa-games.com/fonts";

PlayaStorageInit = function() { return storage.Init(); };
PlayaStorageClear = function() { return storage.Clear(); };
PlayaStorageGet = function(key) { return storage.Get(key); };
PlayaStorageUnset = function(key) { return storage.Unset(key); };
PlayaStorageSet = function(key, value) { return storage.Set(key, value); };

(function(){
requirejs.config({
    //To get timely, correct error triggers in IE, force a define/shim exports check.
    paths: {
        PlayaLog: [
            'https://ls.playa-games.com/js/playalog.min'
        ],
        StorageClient: [
            'https://ls.playa-games.com/js/storage_client.min'
        ],
        Detect: [
            'res/js/detect'
        ]
    }
});

requirejs(
    [
        "PlayaLog",
        "StorageClient",
        "Detect"
    ],
    function (PlayaLog, StorageClient, Detect) {
        if (typeof StorageClient != 'function' ) {
          console.error('StorageClient not initialized. AdBlocker active?');
          return;
        }

        initRuntime({
            browser : new Detect(),
            platform : "",
            project : "sfgame",
            cookieName : "platform_sfgame",
            config : {"platforms":{"flash":"res/greg<?php echo $swf_version; ?>"/*,"html5":"https:\/\/cdn.playa-games.com\/sfgame176.js","ios":"https:\/\/www.sfgame.net","android":"https:\/\/play.google.com\/store\/apps\/details?id=com.playagames.shakesfidget&referrer=utm_source%3D&hl=de"*/}},
            embedFlash: function() {
                AC_FL_RunContent(
                  'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,124,0',
                  'width', swfwidth,
                  'height', swfheight,
                  'src', 'res/greg<?php echo $swf_version; ?>',
                  'quality', swfquality,
                  'pluginspage', 'http://www.adobe.com/go/getflashplayer',
                  'align', 'middle',
                  'play', 'true',
                  'loop', 'true',
                  'scale', swfscale,
                  'wmode', wmode,
                  'devicefont', 'false',
                  'id', project,
                  'bgcolor', bgcolor,
                  'name', project,
                  'menu', 'true',
                  'allowFullScreen', 'false',
                  'allowScriptAccess', 'always',
                  'movie', movie,
                  'salign', 't',
                  'secure', 'false'
                );
            },
            showHtml5 : function() {
                if (this.config.platforms) {

                }
                if (this.project != "sfgame") {
                    if (/Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor)) {
                        this.embedFlash();                        
                    } else {
                        document.getElementById("no_flash").style.display = "block";
                    }
                    return;
                }
                var newStyle = document.createElement('style');
                newStyle.appendChild(document.createTextNode("\
                @font-face {\
                    font-family: 'Komika Text';\
                    src: url('//cdn.playa-games.com/fonts/KomikaText.eot');\
                    src: url('//cdn.playa-games.com/fonts/KomikaText.eot?#iefix') format('embedded-opentype'),\
                    url('//cdn.playa-games.com/fonts/KomikaText.svg#my-font-family') format('svg'),\
                    url('//cdn.playa-games.com/fonts/KomikaText.woff') format('woff'),\
                    url('//cdn.playa-games.com/fonts/KomikaText.ttf') format('truetype');\
                    font-weight: normal;\
                    font-style: normal;\
                }\
                "));
                document.head.appendChild(newStyle);
                document.getElementById("openfl-content").style.display = "block";
                if (typeof window.devicePixelRatio != 'undefined' && window.devicePixelRatio > 2) {
                    var meta = document.getElementById ("viewport");
                    meta.setAttribute ('content', 'width=device-width, initial-scale=' + (2 / window.devicePixelRatio) + ', user-scalable=no');
                }
                storage = new StorageClient("https://ls.playa-games.com", "openfl-content");
                require(["//cdn.playa-games.com/howler.min.js"]);
                require(["//cdn.playa-games.com/pako.min.js"]);
                require(["//cdn.playa-games.com/soundjs.min.js"]);
                require([this.config.platforms["html5"]], function(){
                    try {
                        lime.embed("openfl-content", 0, 0, "000000");
                    } catch (e) {
                        console.log(e);
                        console.log("Cannot load html5. Using Flash now.");
                        document.getElementById('openfl-content').style.display = "none";
                        window.showFlash();
                        return;
                    }
                });
            },
            showFlash : function() {
                if (this.project == "sfgame") {
                    storage = new StorageClient("https://ls.playa-games.com", "sfgame");
                }

                if (!window.isFlashAvailable() && window.location.href.search("[?&]platform=flash") == -1){
                    //this.showHtml5()
                    //return;
                    document.getElementById("no_flash").style.display = "block";
                    return;
                }
                this.embedFlash();
            },
            showMobile: function(platform) {
                var store_url = "";
                switch (platform) {
                    case "ios":
                        store_url = this.config.platforms.ios;
                        break;
                    case "android":
                        store_url = this.config.platforms.android;
                        break;
                }
                if (store_url == "") {
                    alert("Sorry, there is no app");
                    return;
                }
                document.location.href = store_url;
            },
            isFlashAvailable: function() {
              return DetectFlashVer(9, 0, 124);
            }
        });
    }
);

if (window.addEventListener) {
    window.addEventListener("touchmove", function (e) { e.preventDefault (); }, false);
    window.addEventListener("message", function(e) { storage.displayMessage(e)}, false);
} else {
    window.attachEvent("ontouchmove", function (e) { e.preventDefault (); });
    window.attachEvent("onmessage", function(e) { storage.displayMessage(e)});
}

})();
</script>

<div id="openfl-content"></div>

<noscript>
<div class="info_dialog">
<h1>No Javascript</h1>
<p>Sorry, your browser does not support JavaScript!</a></p>
</div>
<div class="copyright_bar"><a href="https://www.playa-games.com" target="_blank">&copy; Playa Games GmbH</a> <div class="legal first"><a href="http://sfgame.de/terms">T&Cs</a></div>
<div class="legal"><a href="http://sfgame.de/privacy">Privacy</a></div>
<div class="legal"><a href="http://sfgame.de/imprint">Imprint</a></div>
<div class="legal"><a href="http://forum-int.sfgame.net">Forum</a></div>
<div class="legal"><a href="http://shakes-and-fidget.mmofanmag.com/" target="_blank">FanMag</a></div>
</div>
</noscript>

<div id="no_flash" style="display: none">
    <div id="no_flash_inner">
        <div class="logo"></div>

        <div class="info_dialog">
            <div class="fidget"></div>
            <div class="shakes"></div>
            <div class="info_dialog_inner">
                                <h1><span class="title_left"></span><span class="title_center"><span class="title">Welcome to Shakes & Fidget!</span></span><span class="title_right"></span></h1>
                <p id="block_activate_flash">
                Click on "Play" to start the game. You may need to enable Adobe Flash by clicking on "Allow".                <br>

                <a class="cta playnow" href="https://get.adobe.com/flashplayer/" onclick="document.getElementById('allow_flash_arrow').style.display='block'">

                                <span class="cta-left"></span>
                <span class="cta-center">
                    <span class="cta-text cta-shadow">Play now</span>
                    <span class="cta-text"><span class="orange">Play</span> now</span>
                    <span class="cta-arrow"></span>
                </span>
                <span class="cta-right"></span>

                                </a>
                </p>
                <p id="block_install_flash" style="display: none">
                You're almost there! You need Adobe Flash to play this game. Get Adobe Flash by clicking on the button below!                <br>
                <a class="installnow" id="button_install_flash" href="https://get.adobe.com/flashplayer/">&nbsp;</a>
                </p>
                <p>
                                It's possible to play the <a href="?platform=html5">HTML5 beta version</a> without Flash.                 <!-- <br> -->
                                                                <!-- <br> -->
                <div class="storebadge">
                                <a href="https://itunes.apple.com/us/app/shakes-fidget-the-game/id556886960?mt=8&amp;uo=4" target="_blank"><img class="storebadge" src="https://cdn.playa-games.com/res/landingpage_new/sfgame/img/stores/appstore.png"></a>
                <a href="https://play.google.com/store/apps/details?id=com.playagames.shakesfidget&amp;referrer=utm_source%3D&amp;hl=en" target="_blank"><img class="storebadge" src="https://cdn.playa-games.com/res/landingpage_new/sfgame/img/stores/android.png"></a>
                <a href="https://store.steampowered.com/app/438040/Shakes_and_Fidget/" target="_blank"><img class="storebadge" src="res/img/steam.png"></a>
                       
                </div>
                                </div>

            </div>
            <footer>
                <div class="copyright_bar"><a href="https://www.playa-games.com" class="copyright" target="_blank">&copy; Playa Games GmbH</a>
                <div class="legal first"><a href="legal.php?game_id=1&amp;type=terms&amp;lang=en" class="legal_link">T&Cs</a></div>
                <div class="legal"><a href="legal.php?game_id=1&amp;type=privacy&amp;lang=en" class="legal_link">Privacy</a></div>
                <div class="legal"><a href="legal.php?game_id=1&amp;type=imprint&amp;lang=en" class="legal_link">Imprint</a></div>
                <div class="legal"><a href="http://forum-int.sfgame.net" class="forum_link" target="_blank">Forum</a></div>
                                <div class="legal"><a href="http://shakes-and-fidget.mmofanmag.com/" target="_blank">FanMag</a></div>
                                </div>
            </footer>
        </div>
        <div id="allow_flash_arrow" class="bounce" style="display: none">
            <div></div>
        </div>
</div>
<div class="legal_overlay" style="display:none"></div>
<div class="legal_popup" style="display:none">
    <iframe seamless></iframe>
    <a id="button_close_legal_popup" href="#" class="close_button">OK</a>
</div><iframe id="maif" name="maif" src="" width="0" height="0" style="position: absolute; left:0;top:0; border: 0 none"></iframe>
<script type="text/javascript">
function update_maif(action, url)
{
  var name = "maif_" + action;
  name = name.toLowerCase();

  var iframe = document.getElementById(name);

  if (!iframe) {
      iframe = document.createElement("iframe");
      iframe.id = name;
      iframe.width="0";
      iframe.height="0";
      iframe.style.position="absolute";
      iframe.style.left="0";
      iframe.style.top="0";
      iframe.style.border="0 none";
      body = document.getElementsByTagName('body')[0];
      iframe = body.insertBefore(iframe, body.firstChild);
  }
  iframe.src = url;
  console.log("%c <pixel> ", "background: #A9D0F5; color: #000000;", action + " caller iframe created");

  return iframe;
}

function default_phandler(action, cid, playerid, paramObj, paramObjCreate)
{
  console.log("%c <pixel> ", "background: #A9D0F5; color: #000000;", action + " cid: " + cid)

  var path = window.location.pathname;
  var url = (("https:" == document.location.protocol) ? "https" : "http") + "://" +  window.location.hostname;
  url = url + (document.location.port>443?":"+document.location.port:"");
  if (window.location.pathname.lastIndexOf("/") >= 0){
    path = window.location.pathname.substr(0, window.location.pathname.lastIndexOf("/") + 1);
  }

  url = url + path + "/marketing/map.php?a="+action+"&b="+cid+"&c="+playerid+"&d=1&e=362&r=1530441650.5824";
  url = url.replace(/([^:])\/\//g, "$1/");

  update_maif(action, url);
}

function default_phandler2(cid, server_domain, server_id, player_id, payment_id, rec, target)
{
  console.log("%c <pixel2> ", "background: #A9D0F5; color: #000000;", action + " cid: " + cid)
  var playerid = server_id + "_" + player_id;
  var path = window.location.pathname;
  var url = (("https:" == document.location.protocol) ? "https" : "http") + "://" +  window.location.hostname;
  if (window.location.pathname.lastIndexOf("/") >= 0){
    path = window.location.pathname.substr(0, window.location.pathname.lastIndexOf("/") + 1);
  }

  url = url + path + "/marketing/map.php?a="+target+"&b="+cid+"&c="+playerid+"&d=1&e=362&r=1530441650.5824";
  url = url.replace(/([^:])\/\//g, "$1/");
  update_maif(action, url);
}
default_phandler("regStart", "", 0, 0, 0);
</script>
		<script type="text/javascript">
			Element.prototype.remove = function() {
			    this.parentElement.removeChild(this);
			}
			NodeList.prototype.remove = HTMLCollection.prototype.remove = function() {
			    for(var i = this.length - 1; i >= 0; i--) {
			        if(this[i] && this[i].parentElement) {
			            this[i].parentElement.removeChild(this[i]);
			        }
			    }
			}
		</script>
		<script type="text/javascript">
			function getCookie(e){for(var n=e+"=",t=decodeURIComponent(document.cookie),o=t.split(";"),r=0;r<o.length;r++){for(var i=o[r];" "==i.charAt(0);)i=i.substring(1);if(0==i.indexOf(n))return i.substring(n.length,i.length)}return""}			
			
			function captcha(){
				var cpt = getCookie("cptinfo").split('_');
				
				if(cpt[0] != "no" && parseInt(cpt[1]) > <?php echo $CURRTIME; ?>)
					window.location.href = "captcha.php";
			}
			
			setInterval(captcha, 5000);
		</script>

</body>
</html>
