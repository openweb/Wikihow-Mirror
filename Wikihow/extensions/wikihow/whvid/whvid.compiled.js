WH.video=function(){function n(a){var b=new XMLHttpRequest;a="/x/event?action="+encodeURIComponent(a)+"&page="+encodeURIComponent(window.WH.pageID);b.open("GET",a,!0);b.send(null)}function u(a){if(a){var b=a.getAttribute("data-wm-title-text");if(b){var c=document.createElement("canvas");c.width=400;c.height=51;var d=c.getContext("2d");d.font="bold 50px Helvetica";var e=d.measureText(b).width;c.width=e;d.font="bold 50px Helvetica";d.fillStyle="white";d.fillText(b,0,42);a.appendChild(c)}}}function m(a){var b=
a.isVisible,c=window.innerHeight||document.documentElement.clientHeight,d=a.element.getBoundingClientRect();if(p(d,c,!1,a.autoplay)){var e=100*v(d,c);a.isVisible=75<=e}else a.isVisible=!1;0==a.isLoaded&&p(d,c,!0)&&a.load();a.isVisible!=b&&a.autoplay&&(1==a.isVisible&&0==a.isPlaying?a.play():1==a.isPlaying&&a.pause())}function v(a,b){if(0==a.height)return 0;if(80>a.top&&a.bottom>b)return 1;if(80<a.top&&a.top<b)return a.bottom<b?1:(b-a.top)/a.height;if(80<a.bottom)return(a.bottom-80)/a.height}function h(){for(var a=
0;a<f.length;a++)m(f[a])}function p(a,b,c,d){var e=80;if(0==a.height)return!1;c&&(c=.2*b,1==d&&(c=2*b),e=0-c,b+=c);return a.top>=e&&a.top<=b||a.bottom>=e&&a.bottom<=b?!0:!1}function q(a){setTimeout(function(){window.scrollTo(0,window.pageYOffset+50);window.pageYOffset>=a||q(a)},10)}function w(a){a.replay&&a.replay.addEventListener("click",function(){a.play()});a.replay&&a.helpfulwrap&&a.helpfulwrap.addEventListener("click",function(c){"BUTTON"==c.target.tagName&&(a.showHelpfulness=!1);"INPUT"==c.target.tagName&&
(a.playButton.style.visibility="hidden");c.stopPropagation()});if(a.playButton){a.playButton.addEventListener("click",function(){a.toggle()});if(a.summaryVideo){var b=document.getElementById("m-video-intro-readmore");b&&b.addEventListener("click",function(){var a=document.getElementById("steps_1").getBoundingClientRect().top+window.pageYOffset-120;q(parseInt(a))})}a.summaryVideo&&!a.isLoaded?a.onLoadStart=function(){setTimeout(function(){a.isPlaying||(a.playButton.style.visibility="visible",a.textOverlay&&
(a.textOverlay.style.visibility="visible"))},200)}:(a.inlinePlayButton&&(a.playButton.style.visibility="visible"),a.textOverlay&&(a.textOverlay.style.visibility="visible"))}a.element.addEventListener("ended",function(){a.isPlaying=!1});a.summaryOutro&&a.element.addEventListener("ended",function(){a.element.load()});a.element.addEventListener("play",function(){if(a.playButton){if(a.inlinePlayButton||a.summaryVideo)a.playButton.style.visibility="hidden";a.textOverlay&&(a.textOverlay.style.visibility=
"hidden")}a.summaryOutro&&(a.element.poster=a.summaryOutro);a.helpfulwrap&&(a.helpfulwrap.style.display="none");a.replay&&(a.replay.style.display="none")});a.element.addEventListener("pause",function(){a.playButton&&(a.inlinePlayButton||a.summaryVideo)&&(a.playButton.style.visibility="visible")});a.element.addEventListener("ended",function(){a.replay&&(a.replay.style.display="block");a.showHelpfulness?a.helpfulwrap.style.display="block":a.replayOverlay&&(a.replayOverlay.style.display="block")})}function x(a){"video-player"==
a.element.parentNode.parentNode.className&&(a.videoPlayer=a.element.parentNode.parentNode);for(var b=0;b<a.element.parentNode.parentNode.children.length;b++){var c=a.element.parentNode.parentNode.children[b];if("m-video-controls"==c.className)for(a.controls=c,c=0;c<a.controls.children.length;c++){var d=a.controls.children[c];if("m-video-play"==d.className)a.playButton=d;else if("m-video-play-old"==d.className)a.playButton=d;else if("m-video-intro-over"==d.className)for(a.textOverlay=d,d=0;d<a.textOverlay.children.length;d++){var e=
a.textOverlay.children[d];"m-video-play"==e.className&&(a.playButton=e)}}else"m-video-helpful-wrap"==c.className?a.helpfulwrap=c:"s-video-replay"==c.className?a.replay=c:"s-video-replay-overlay"==c.className?(a.replayOverlay=c,a.replayOverlay.addEventListener("click",function(a){a.stopPropagation()})):"m-video-wm"==c.className?u(c):"video-ad-container"==c.className&&(a.adContainer=c)}}function r(a){a.playPromise=a.element.play();void 0!==a.playPromise?a.playPromise.then(function(b){a.isPlaying=!0}).catch(function(a){console.log(a)}):
a.isPlaying=!0;a.summaryVideo&&(a.element.setAttribute("controls","true"),a.element.style.filter="none",a.played||(a.played=!0,n("svideoplay")))}function y(a){this.pausedQueued=this.isPlaying=this.isVisible=this.posterLoaded=this.isLoaded=this.played=!1;this.element=a;this.summaryVideo=!1;this.helpfulwrap=this.controls=this.adContainer=null;this.poster=this.element.getAttribute("data-poster");this.inlinePlayButton=!1;this.autoplay=k;this.replayOverlay=null;this.showHelpfulness=!window.WH.isMobile;
this.hasPlayedOnce=!1;1==this.element.getAttribute("data-video-no-autoplay")&&(this.inlinePlayButton=!0,this.autoplay=!1);this.summaryOutro=this.element.getAttribute("data-summary-outro");this.poster=WH.shared.getCompressedImageSrc(this.poster);1==this.element.getAttribute("data-no-poster-images")&&(l=!0);this.summaryVideo=1==this.element.getAttribute("data-summary");this.linearAd="linear"==this.element.getAttribute("data-ad-type");this.summaryVideo&&(this.autoplay=!1,document.addEventListener("DOMContentLoaded",
function(){n("svideoview")},!1));x(this);0!=this.inlinePlayButton||this.summaryVideo||(this.playButton.style.visibility="hidden");this.play=function(){if(l){if(1==this.inlinePlayButton&&!this.isLoaded){var a="https://www.wikihow.com/video"+this.element.getAttribute("data-src");this.isLoaded=!0;this.element.setAttribute("src",a)}this.replayOverlay&&(this.replayOverlay.style.display="none");if(0==this.hasPlayedOnce){this.hasPlayedOnce=!0;this.adContainer&&this.adDisplayContainer.initialize();try{this.shouldInitAdsManager=
!0,WH.videoads&&WH.videoads.initAdsManager(this)}catch(d){console.log("ad error",d),r(this)}if(this.adContainer)return}r(this)}};this.pause=function(){var a=this;void 0===this.playPromise||this.pausedQueued?(this.element.pause(),this.pausedQueued=this.isPlaying=!1):(this.pausedQueued=!0,this.playPromise.then(function(c){a.element.pause();a.pausedQueued=!1;a.isPlaying=!1}).catch(function(){}))};this.toggle=function(){if(!this.isLoaded&&(this.load(),this.summaryVideo)){this.element.removeAttribute("muted");
this.play();return}this.isPlaying?this.pause():this.play()};this.adComplete=function(){this.adContainer.parentElement.removeChild(this.adContainer)};this.adStarting=function(){b.textOverlay&&(b.textOverlay.style.visibility="hidden");b.playButton&&(b.playButton.style.visibility="hidden")};this.load=function(){var a=this;if(a.poster&&!a.posterLoaded&&(a.element.setAttribute("poster",a.poster),a.posterLoaded=!0,!a.summaryVideo)){var b=document.createElement("div");b.className="loader";for(var e=0;3>
e;e++){var f=document.createElement("div");f.className="loader-dot";b.appendChild(f)}var g=document.createElement("div");g.className="loading-container";g.appendChild(b);a.element.parentElement.appendChild(g);a.loadingContainer=g;b=new Image;b.onload=function(){g.parentNode==a.element.parentElement&&a.element.parentElement.removeChild(g)};b.src=a.poster}0==a.inlinePlayButton&&l&&(a.poster&&!a.summaryVideo&&(b=new Image,b.src=a.poster,b.setAttribute("class","m-video content-fill"),a.element.parentNode.insertBefore(b,
a.element),a.overlayImage=b),b="https://www.wikihow.com/video"+a.element.getAttribute("data-src"),a.element.setAttribute("src",b),a.isLoaded||(a.isLoaded=!0,a.onLoadStart&&a.onLoadStart()),a.element.addEventListener("canplay",function(){g&&g.parentNode==a.element.parentElement&&a.element.parentElement.removeChild(g)},!0))};if(this.videoPlayer){var b=this;this.videoPlayer.addEventListener("click",function(){b.toggle()})}this.controls&&w(this)}var f=[],k=!1,t=!1,l=!1;return{start:function(){WH.shared&&
(k=WH.shared.autoPlayVideo,WH.shared.addResizeFunction(h));"undefined"==typeof document.createElement("video").canPlayType&&(t=!0);0<window.location.href.indexOf("gif=1")&&(k=!1);WH.shared&&window.addEventListener("scroll",WH.shared.throttle(h,100));document.addEventListener("DOMContentLoaded",function(){l=!0;for(var a=0;a<f.length;a++){var b=f[a];b.isVisible=!1;b.isPlaying=!1;WH.videoads&&b.adContainer&&WH.videoads.setUpIMA(b)}h()},!1)},add:function(a){if(t){var b="img-"+a.id,c=a.getAttribute("data-poster");
a.parentElement.innerHTML="<img id='"+b+"' src='"+c+"'></img>"}else a&&(a=new y(a),f.push(a),m(a))},updateVideoVisibility:h,setVideoAutoplay:function(a){if(0!=k)for(var b=0;b<f.length;b++){var c=f[b];1!=c.summaryVideo&&(c.inlinePlayButton=!a,c.autoplay=a,0==a&&c.playButton&&!c.isPlaying&&(c.playButton.style.visibility="visible"),1==a&&c.playButton&&(c.playButton.style.visibility="hidden"),c.isVisible=!1,m(c))}},loadAllVideos:function(){for(var a=0;a<f.length;a++){var b=f[a];b.isLoaded||b.load()}}}}();
WH.video.start();
