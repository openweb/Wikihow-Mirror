WH.mediumScreenMinWidth=728;WH.largeScreenMinWidth=975;
WH.shared=function(){function y(a){p.push(a)}function q(a,b){var c=null,d=0,e=function(){d=0;c=null;a.apply()};return function(){var f=D(),g=b-(f-d);0>=g||g>b?(c&&(clearTimeout(c),c=null),d=f,a.apply()):c||(c=setTimeout(e,g))}}function n(){for(var a=!1,b=window.innerHeight||document.documentElement.clientHeight,c=0;c<g.length;c+=1){var d=g[c];if(0!=d.useScrollLoader&&!d.isLoaded){a=d;var e=a.element.getBoundingClientRect(),f={top:e.top,bottom:e.bottom};0==a.lastTop-e.top&&window.scrollY!=a.lastY&&
(f.top-=window.scrollY,f.bottom-=window.scrollY);a.lastTop=e.top;a.lastY=window.scrollY;a=f;e=b;f=52;var h=2*e;f-=h;e+=h;(0==a.top&&0==a.bottom?0:a.top>=f&&a.top<=e||a.bottom>=f&&a.bottom<=e||a.top<=f&&a.bottom>=e)&&d.load();a=!0}}!a&&g.length&&(window.removeEventListener("scroll",k),k=null)}function r(a){if(a&&a.element)if(document.addEventListener&&a.finishedLoadingEvent){a.alt&&(a.element.alt=a.alt);a.loaderRemoved=!1;var b=document.createElement("div");b.className="loader";for(var c=0;3>c;c++){var d=
document.createElement("div");d.className="loader-dot";b.appendChild(d)}var e=document.createElement("div");e.className="loading-container";e.appendChild(b);a.element.parentElement.appendChild(e);a.element.addEventListener(a.finishedLoadingEvent,function(){a.loadedCallback&&a.loadedCallback();0==a.loaderRemoved&&(this.parentElement.removeChild(e),a.loaderRemoved=!0,"undefined"!==typeof a.element.classList&&a.element.classList.remove("img-loading-hide"))})}else"undefined"!==typeof a.element.classList&&
a.element.classList.remove("img-loading-hide")}function t(a){this.lastTop=a.getBoundingClientRect().top;this.lastY=window.scrollY;this.isVisible=this.isLoaded=!1;this.element=a;this.load=function(){}}function u(a){if(!a)return"";if(!v)return a;var b=a.split(".").pop();"jpg"!==b&&"png"!==b||!a.match(/images(_[a-z]{2})?\/thumb\//)||a.match(/(\.[a-zA-Z]+){2}$/)||(a+=".webp");return a}function E(a){t.call(this,a);this.src=a.getAttribute("data-src");this.load=function(){this.element.setAttribute("src",
this.src);this.isLoaded=!0}}function F(a){t.call(this,a);this.alt=a.alt;a.alt="";this.finishedLoadingEvent="load";this.src=a.getAttribute("data-src");if(w){var b=a.getAttribute("data-srclarge");null!=b&&(this.src=b)}this.src=u(this.src);a&&void 0!==a.classList&&a.classList.add("img-loading-hide");this.load=function(){this.element.setAttribute("src",this.src);this.isLoaded=!0;r(this)}}function G(a){t.call(this,a);this.finishedLoadingEvent="loadeddata";this.isPlaying=!1;this.src="https://www.wikihow.com/video"+
this.element.getAttribute("data-src");this.poster=this.element.getAttribute("data-poster");(this.poster=u(this.poster))&&"jpg"==this.poster.split(".").pop()&&v&&(this.poster+=".webp");this.noAutoplay=this.element.getAttribute("data-noautoplay");this.play=function(){this.element.play();this.isPlaying=!0};this.pause=function(){this.element.pause();this.isPlaying=!1};this.load=function(){this.element.setAttribute("poster",this.poster);!z||this.noAutoplay||!w&&!0!==window.wgIsMainPage?this.finishedLoadingEvent=
null:(this.element.setAttribute("src",this.src),this.play());this.isLoaded=!0;this.noAutoplay||r(this)}}function A(a){if(a=document.getElementById(a)){var b=null==l?!1:!0;if("img"===a.nodeName.toLowerCase()){var c=new F(a);if(b)c.useScrollLoader=!1,l.observe(c.element);else if(1==x){c.useScrollLoader=!1;var d=c;d.element.classList.remove("img-loading-hide");d.element.setAttribute("loading","lazy");d.load()}}else if("video"===a.nodeName.toLowerCase())c=new G(a),b&&(c.useScrollLoader=!1,l.observe(c.element));
else if("iframe"===a.nodeName.toLowerCase())c=new E(a),b&&(c.useScrollLoader=!1,l.observe(c.element));else return;c&&g.push(c);d=a.getAttribute("data-width")||a.getAttribute("width");var e=a.getAttribute("data-height")||a.getAttribute("height");0<d&&(a.parentElement.style.paddingTop=e/d*100+"%");b||n();B?c.load():k||b||(k=q(n,500),window.addEventListener("scroll",k))}}var p=[],g=[],B=!1,D=Date.now||function(){return(new Date).getTime()},h=navigator.userAgent,v=h.match(/Linux/)&&h.match(/Android/)||
h.match(/Opera/)||h.match(/Chrome/)&&!h.match(/Edge/),m=window.innerWidth||document.documentElement.clientWidth;h=m<WH.mediumScreenMinWidth;m=!h&&m<WH.largeScreenMinWidth;var C=!h&&!m,w=C,x=!1,l=null;"loading"in HTMLImageElement.prototype&&1==WH.nativeBrowserLazyLoadingTest&&(x=!0);0==x&&"IntersectionObserver"in window&&(l=new IntersectionObserver(function(a,b){a.forEach(function(a){if(a.isIntersecting){for(var b=a.target,c=0;c<g.length;c+=1){var f=g[c];f.element==b&&f.load()}l.unobserve(a.target)}})},
{rootMargin:"0px 0px 100% 0px"}));window.onresize=function(){p.forEach&&p.forEach(function(a){a()})};y(n);var z=function(){var a=window.document.createElement("video");a.setAttribute("muted","");a.setAttribute("playsinline","");a.setAttribute("webkit-playsinline","");a.muted=!0;a.playsinline=!0;a.webkitPlaysinline=!0;a.setAttribute("height","0");a.setAttribute("width","0");a.style.position="fixed";a.style.top=0;a.style.width=0;a.style.height=0;a.style.opacity=0;try{var b=a.play();b&&b.catch&&b.then(function(){}).catch(function(){})}catch(c){}return!a.paused}();
var k=q(n,500);window.addEventListener?window.addEventListener("scroll",k):B=!0;return{isDesktopSize:w,isSmallSize:h,isMedSize:m,isLargeSize:C,getScreenSize:function(){var a=document;var b=a.documentElement,c=a.body,d=c&&c.clientWidth,e=0;!b||!b.clientWidth||"CSS1Compat"!==a.compatMode&&d?d&&(e=c.clientWidth):e=b.clientWidth;a=e;return 0==a||a>=WH.largeScreenMinWidth?"large":a>=WH.mediumScreenMinWidth?"medium":"small"},throttle:q,TOP_MENU_HEIGHT:52,autoPlayVideo:z,webpSupport:v,addScrollLoadItem:A,
addScrollLoadItemByElement:function(a){var b=a.id;b||(b="id-"+Math.random().toString(36).substr(2,16));a.id=b;A(b)},videoRoot:"https://www.wikihow.com/video",setupLoader:r,addResizeFunction:y,loadAllImages:function(){for(var a=0;a<g.length;a++){var b=g[a];b.isLoaded||b.load()}},loadAllEmbed:function(){for(var a=0;a<g.length;a++){var b=g[a];b.isLoaded||"iframe"==b.element.nodeName.toLowerCase()&&b.load()}},addLoadedCallback:function(a,b){for(var c=0;c<g.length;c++){var d=g[c];d.element.id==a&&(d.loadedCallback=
b)}},showVideoPlay:function(a){a=a.element.parentElement.getElementsByClassName("m-video-intro-over");"undefined"!==typeof a&&(a=a[0]);a&&(a.style.visibility="visible")},getCompressedImageSrc:u}}();
