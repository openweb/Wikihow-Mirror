var $jscomp=$jscomp||{};$jscomp.scope={};$jscomp.findInternal=function(c,g,f){c instanceof String&&(c=String(c));for(var m=c.length,k=0;k<m;k++){var n=c[k];if(g.call(f,n,k,c))return{i:k,v:n}}return{i:-1,v:void 0}};$jscomp.ASSUME_ES5=!1;$jscomp.ASSUME_NO_NATIVE_MAP=!1;$jscomp.ASSUME_NO_NATIVE_SET=!1;$jscomp.defineProperty=$jscomp.ASSUME_ES5||"function"==typeof Object.defineProperties?Object.defineProperty:function(c,g,f){c!=Array.prototype&&c!=Object.prototype&&(c[g]=f.value)};
$jscomp.getGlobal=function(c){return"undefined"!=typeof window&&window===c?c:"undefined"!=typeof global&&null!=global?global:c};$jscomp.global=$jscomp.getGlobal(this);$jscomp.polyfill=function(c,g,f,m){if(g){f=$jscomp.global;c=c.split(".");for(m=0;m<c.length-1;m++){var k=c[m];k in f||(f[k]={});f=f[k]}c=c[c.length-1];m=f[c];g=g(m);g!=m&&null!=g&&$jscomp.defineProperty(f,c,{configurable:!0,writable:!0,value:g})}};
$jscomp.polyfill("Array.prototype.findIndex",function(c){return c?c:function(c,f){return $jscomp.findInternal(this,c,f).i}},"es6","es3");$jscomp.polyfill("Object.is",function(c){return c?c:function(c,f){return c===f?0!==c||1/c===1/f:c!==c&&f!==f}},"es6","es3");
$jscomp.polyfill("Array.prototype.includes",function(c){return c?c:function(c,f){var g=this;g instanceof String&&(g=String(g));var k=g.length;f=f||0;for(0>f&&(f=Math.max(f+k,0));f<k;f++){var n=g[f];if(n===c||Object.is(n,c))return!0}return!1}},"es7","es3");
$jscomp.checkStringArgs=function(c,g,f){if(null==c)throw new TypeError("The 'this' value for String.prototype."+f+" must not be null or undefined");if(g instanceof RegExp)throw new TypeError("First argument to String.prototype."+f+" must not be a regular expression");return c+""};$jscomp.polyfill("String.prototype.includes",function(c){return c?c:function(c,f){return-1!==$jscomp.checkStringArgs(this,c,"includes").indexOf(c,f||0)}},"es6","es3");
$jscomp.polyfill("Array.prototype.find",function(c){return c?c:function(c,f){return $jscomp.findInternal(this,c,f).v}},"es6","es3");
WH.ads=function(){function c(){-1!=window.location.href.indexOf("adslog=1")&&console.log.apply(console,arguments)}function g(){var a=!1;null!=document.hidden?a=document.hidden:null!=document.mozHidden?a=document.mozHidden:null!=document.webkitHidden?a=document.webkitHidden:null!=document.msHidden&&(a=document.msHidden);return a}function f(a,b,d){d.apsTimeout||console.warn("ad has no timeout value",d);for(var e=[],E=0;E<b.length;E++)e.push(gptAdSlots[b[E]]);apstag.fetchBids({slots:a,timeout:d.apsTimeout},
function(a){googletag.cmd.push(function(){apstag.setDisplayBids();d.apsDisplayBidsCalled=!0;d.prebidload?1==d.prebidKVPadded?(c("apsFetchBids: prebid bidinfo done. will refresh",d.adTargetId),z(d)):c("apsFetchBids: prebidload bids not ready. not refreshing",d.adTargetId):z(d)})})}function m(a){for(var b in PWT.bidMap)if(b==a.bidLookupKey)for(var d in PWT.bidMap[b].adapters)for(var e in PWT.bidMap[b].adapters[d].bids){var c=PWT.bidMap[b].adapters[d].bids[e];p.push({ecpm:c.grossEcpm,lookupKey:a.bidLookupKey,
winningBid:!1,discardAt:(new Date).getTime()+55E3,pwtsid:c.bidID,pwtbst:c.status,pwtecp:c.netEcpm.toFixed(2),pwtdid:c.dealID,pwtpid:c.adapterID,pwtpubid:"159181",pwtprofid:openWrapProfileId,pwtverid:openWrapProfileVersionId?openWrapProfileVersionId:1,pwtsz:c.width+"x"+c.height,pwtplt:c.native?"native":"display"})}p=p.filter(function(a){return a.discardAt>(new Date).getTime()})}function k(a){PWT.isLoaded?F(a):(c("prebidLoad: PWT not ready yet. will queue load",a.adTargetId),a.prebidloadcommands.push(function(){F(a)}))}
function n(a){PWT.isLoaded?googletag.cmd.push(function(){t(a)}):(c("prebidLoad: PWT not ready yet. will queue request",a.adTargetId),A.push(a))}function z(a){var b=gptAdSlots[a.adTargetId];setDFPTargeting(b,dfpKeyVals);c("setDFPTargetingAndRefresh:",a.adTargetId,"targeting",b.getTargetingMap());googletag.pubads().refresh([b])}function F(a){T(a)?(a.prebidKVPadded=!0,a.apsload?1==a.apsDisplayBidsCalled?(c("prebidLoadInternal: aps bids done. will refresh",a.adTargetId),z(a)):c("prebidLoadInternal: aps bids not recieved yet for",
a.adTargetId):(c("prebidLoadInternal: apsload not active. calling gpt refresh",a.adTargetId),z(a))):(a.prebidloadcommands.push(function(){F(a)}),t(a))}function U(a){p=p.filter(function(a){return a.discardAt>(new Date).getTime()});var b=p.filter(function(b){return b.lookupKey==a.bidLookupKey});if(0==b.length)return null;for(var d=b[0].ecpm,e=0,c=b.length;e<c;e++){var f=b[e].ecpm;d=f>d?f:d;b[e].winningBid=!1}e=b.findIndex(function(a){return a.ecpm===d});b[e].winningBid=!0;b[e].discardAt=0;return b[e]}
function T(a){var b=U(a);if(!b)return!1;a=gptAdSlots[a.adTargetId];a.setTargeting("pwtsid",b.pwtsid);a.setTargeting("pwtbst",b.pwtbst);a.setTargeting("pwtecp",b.pwtecp);a.setTargeting("pwtpid",b.pwtpid);a.setTargeting("pwtpubid",b.pwtpubid);a.setTargeting("pwtprofid",b.pwtprofid);a.setTargeting("pwtverid",b.pwtverid);a.setTargeting("pwtsz",b.pwtsz);a.setTargeting("pwtplt",b.pwtplt);return!0}function V(){c("prebidRunQueuedRequests");for(var a={},b=0;b<A.length;a={ad:a.ad},b+=1)a.ad=A[b],googletag.cmd.push(function(a){return function(){t(a.ad)}}(a));
A=[]}function t(a){PWT.requestBids(PWT.generateConfForGPT([gptAdSlots[a.bidLookupKey]]),function(b){a.currentAdUnitsArray=b;m(a);for(b=0;b<a.prebidloadcommands.length;b+=1)a.prebidloadcommands[b]();a.prebidloadcommands=[]})}function B(a){for(var b=a.adTargetId,d=gptAdSlots[b].getAdUnitPath(),e=gptAdSlots[b].getSizes(),c=[],y=0;y<e.length;y++){var h=[];h.push(e[y].getWidth());h.push(e[y].getHeight());c.push(h)}f([{slotID:b,slotName:d,sizes:c}],[b],a)}function J(a,b,d){gptAdSlots[a]&&dfpKeyVals[gptAdSlots[a].getAdUnitPath()]&&
(dfpKeyVals[gptAdSlots[a].getAdUnitPath()][b]=d)}function W(a){c("gptLoad",a);var b=a.adTargetId,d=a.gptLateLoad,e=a.getRefreshValue();googletag.cmd.push(function(){d&&googletag.display(b);J(b,"refreshing",e);setDFPTargeting(gptAdSlots[b],dfpKeyVals);googletag.pubads().refresh([gptAdSlots[b]])})}function K(a){var b=window.document.createElement("ins");b.setAttribute("data-ad-client","ca-pub-9543332082073187");a.adLabelClass?b.setAttribute("class","adsbygoogle "+a.adLabelClass):b.setAttribute("class",
"adsbygoogle");var d=a.slot;if(d){b.setAttribute("data-ad-slot",d);d=null;var e=0<=document.cookie.indexOf("ccpa_out=")?!0:!1;e?(b.setAttribute("data-restrict-data-processing",1),"intro"==a.type&&(d=2385774741)):"intro"==a.type&&(d=2001974826);a.adElement.getAttribute("data-ad-format")&&b.setAttribute("data-ad-format",a.adElement.getAttribute("data-ad-format"));a.adElement.getAttribute("data-full-width-responsive")&&b.setAttribute("data-full-width-responsive",a.adElement.getAttribute("data-full-width-responsive"));
"middlerelated"==a.type&&(b.setAttribute("data-ad-format","fluid"),b.setAttribute("data-ad-layout-key","-fb+5w+4e-db+86"));e="display:inline-block;width:"+a.width+"px;height:"+a.height+"px;";var c=["method","qa","tips","warnings"];"small"==a.adSize&&c.includes(a.type)&&(e="display:block;height:"+a.height+"px;");b.style.cssText=e;a.adTargetId&&(window.document.getElementById(a.adTargetId).appendChild(b),a.channels&&(d=d?a.channels+","+d:a.channels),d||(d=""),"undefined"===typeof adsbygoogle&&(window.adsbygoogle=
[]),(window.adsbygoogle=window.adsbygoogle||[]).push({params:{google_ad_channel:d}}))}}function X(a){var b=document.documentElement.clientWidth;switch(a){case "intro":b-=30;break;case "method":b-=20;break;case "related":b-=14;break;default:b-=20}return b}function L(a){var b=a.parentElement;this.element=a;this.adElement=b;this.height=this.adElement.offsetHeight;this.adTargetId=a.id;a=1==this.adElement.getAttribute("data-small");var d=1==this.adElement.getAttribute("data-medium"),e=1==this.adElement.getAttribute("data-large"),
f=!1;if(a&&WH.shared.isSmallSize||d&&WH.shared.isMedSize||e&&WH.shared.isLargeSize)f=!0;if(f){this.gptLateLoad=1==this.adElement.getAttribute("data-lateload");this.service=this.adElement.getAttribute("data-service");this.apsload=1==this.adElement.getAttribute("data-apsload");this.apsDisplayBidsCalled=!1;this.prebidload=1==this.adElement.getAttribute("data-prebidload");this.prebidKVPadded=!1;this.prebidloadcommands=[];this.bidLookupKey=this.adTargetId;this.slot=this.adElement.getAttribute("data-slot");
this.adunitpath=this.adElement.getAttribute("data-adunitpath");this.channels=this.adElement.getAttribute("data-channels");this.mobileChannels=this.adElement.getAttribute("data-mobilechannels");this.refreshable=1==this.adElement.getAttribute("data-refreshable");this.slotName=this.adElement.getAttribute("data-slot-name");this.refreshType=this.adElement.getAttribute("data-refresh-type");if(this.sizesArray=this.adElement.getAttribute("data-size"))this.sizesArray=JSON.parse(this.sizesArray);this.dfpdisplaylate=
1==this.adElement.getAttribute("data-gptdisplaylate");this.type=this.adElement.getAttribute("data-type");"rightrail"==this.type&&(this.position="initial");this.notfixedposition=1==this.adElement.getAttribute("data-notfixedposition");this.viewablerefresh=1==this.adElement.getAttribute("data-viewablerefresh");this.renderrefresh=1==this.adElement.getAttribute("data-renderrefresh");this.width=this.adElement.getAttribute("data-width");this.height=this.adElement.getAttribute("data-height");a&&WH.shared.isSmallSize&&
(this.adSize="small",this.channels=this.mobileChannels,this.slot=this.adElement.getAttribute("data-smallslot")||this.slot,this.height=this.adElement.getAttribute("data-smallheight")||this.height,this.width=X(this.type),this.service=this.adElement.getAttribute("data-smallservice")||this.service);d&&WH.shared.isMedSize&&(this.adSize="medium",this.slot=this.adElement.getAttribute("data-mediumslot")||this.slot,this.height=this.adElement.getAttribute("data-mediumslot")||this.height,this.width=this.adElement.getAttribute("data-mediumwidth")||
this.width,this.service=this.adElement.getAttribute("data-mediumservice")||this.service);e&&WH.shared.isLargeSize&&(this.adSize="large");"adsense"!=this.service||this.slot?(this.instantLoad=1==this.adElement.getAttribute("data-instantload"),this.adLabelClass=this.adElement.getAttribute("data-adlabelclass"),this.instantLoad=1==this.adElement.getAttribute("data-instantload"),this.apsTimeout=this.adElement.getAttribute("data-aps-timeout"),this.refreshtimeout=!1,this.refreshNumber=1,this.maxRefresh=this.adElement.getAttribute("data-max-refresh"),
this.refreshTime=(this.refreshTime=this.adElement.getAttribute("data-refresh-time"))?parseInt(this.refreshTime):3E4,this.firstRefresh=!0,this.firstRefreshTime=(this.firstRefreshTime=this.adElement.getAttribute("data-first-refresh-time"))?parseInt(this.firstRefreshTime):this.refreshTime,this.useScrollLoader=!0,this.observerLoading=1==this.adElement.getAttribute("data-observerloading"),this.getRefreshTime=function(){return 1==this.firstRefresh?(this.firstRefresh=!1,this.firstRefreshTime):this.refreshTime},
this.getRefreshValue=function(){if(0==this.refreshNumber&&!this.refreshable)return"not";this.refreshNumber++;return 20<this.refreshNumber?"max":this.refreshNumber.toString()},this.load=function(){if(1!=this.isLoaded){if("dfp"==this.service){if(this.apsload){var a=this;gptAdSlots[this.adTargetId]?B(a):googletag.cmd.push(function(){B(a)})}this.prebidload&&(a=this,googletag.cmd.push(function(){k(a)}));this.apsload||this.prebidload||W(this)}else"dfplight"==this.service?insertDFPLightAd(this):K(this);
this.isLoaded=!0}},this.refresh=function(){var a=this;if(g())setTimeout(function(){a.refresh()},5E3);else{this.lastRefreshScrollY=window.scrollY;var b=window.innerHeight||document.documentElement.clientHeight,d=this.element.getBoundingClientRect();if(u(d,b,!1,a))if(b=this.getRefreshValue(),this.maxRefresh&&b>this.maxRefresh)c("max refreshes reached returning"),this.refreshable=!1;else{if("adsense"!=this.service&&J(this.adTargetId,"refreshing",b),this.apsload&&B(this),this.prebidload&&k(this),!this.apsload&&
!this.prebidload){var e=this.adTargetId;googletag.cmd.push(function(){setDFPTargeting(gptAdSlots[e],dfpKeyVals);googletag.pubads().refresh([gptAdSlots[e]])})}}else c("refresh: not in viewport",d,b),setTimeout(function(){a.refresh()},5E3)}},this.show=function(){this.adElement.style.display="block"},this.instantLoad&&this.load()):(this.disabled=!0,b.style.display="none")}else this.disabled=!0,b.style.display="none"}function M(a){L.call(this,a)}function Y(a){L.call(this,a);a.parentElement.style.display=
"none";this.scrollToTimer=null;this.lastScrollPositionY=0;this.maxNonSteps=parseInt(this.adElement.getAttribute("data-maxnonsteps"));this.maxSteps=parseInt(this.adElement.getAttribute("data-maxsteps"));this.updateVisibility=function(){if(1>this.maxNonSteps&&1>this.maxSteps)v&&(window.removeEventListener("scroll",v),v=null);else if(this.lastScrollPositionY=window.scrollY,10<this.lastScrollPositionY){null!==this.scrollToTimer&&clearTimeout(this.scrollToTimer);var a=this;this.scrollToTimer=setTimeout(function(){a.load()},
1E3)}};this.load=function(){a:{var a=window.innerHeight||document.documentElement.clientHeight;for(var d=document.getElementsByClassName("section"),e=null,c=!1,f=0;f<=d.length;f++){if(f==d.length){var h=document.getElementById("ur_mobile");if(!h)break}else h=d[f];if("aiinfo"!=h.id){if(1==c){h=h.getElementsByClassName("section_text");if(!h||!h[0]){a=null;break a}h=h[0];var g=h.getBoundingClientRect();if(g.bottom>=screenTop&&g.bottom<=a)continue;break}if("intro"!=h.id){if(h.classList.contains("steps")){g=
h.getElementsByClassName("steps_list_2");if(!g||!g[0])continue;h=a;g=g[0].childNodes;for(var k=null,l=!1,m=0;m<g.length;m++){var n=g[m];if("LI"==n.nodeName){if(1==l){k=n;break}var p=n.getBoundingClientRect();if(u(p,h,!1,this))if(p.bottom>=screenTop&&p.bottom<=h)l=!0;else{k=n;break}}}h=k;if(!h)continue;e=h;break}if((h=h.getElementsByClassName("section_text"))&&h[0]&&(h=h[0],g=h.getBoundingClientRect(),u(g,a,!1,this)))if(g.bottom>=screenTop&&g.bottom<=a)c=!0;else{e=h;break}}}}a=e}a&&(d="LI"==a.tagName,
d&&1>this.maxSteps||!d&&1>this.maxNonSteps||(e=a.getElementsByTagName("INS"),0<e.length||(e=a.getElementsByClassName("wh_ad_inner"),0<e.length||(0<a.getElementsByClassName("addTipElement").length&&a.classList.add("has_scrolltoad"),e=document.createElement("div"),e.className=d?"wh_ad_inner step_ad scrollto_wrap":"wh_ad_inner scrollto_wrap",a.appendChild(e),a=e,a.id||(a.id="scrollto-ad-"+G),this.insertSlotValue="00"+G,this.adTargetId=a.id,Z(this),d?this.maxSteps--:this.maxNonSteps--,G++))))}}function Z(a){"dfp"==
a.service?googletag.cmd.push(function(){gptAdSlots[a.adTargetId]=googletag.defineSlot(a.adunitpath,a.sizesArray,a.adTargetId).addService(googletag.pubads());gptAdSlots[a.adTargetId].setTargeting("slot",a.insertSlotValue);googletag.display(a.adTargetId);a.apsload&&B(a);a.prebidload&&(k(a),PWT.isLoaded&&t(a))}):K(a)}function u(a,b,d,e){var c=w;d&&(d=b,e instanceof M&&(d=2*b),c=0-d,b+=d);return a.top>=c&&a.top<=b||a.bottom>=c&&a.bottom<=b||a.top<=c&&a.bottom>=b||e.last&&a.top<=c?!0:!1}function N(a,b){if(!a.isLoaded&&
0!=a.useScrollLoader){var d=a.element.getBoundingClientRect();u(d,b,!0,a)&&a.load()}}function O(a,b){var d=a.adElement.getBoundingClientRect();if(!u(d,b,!1,a))return"fixed"==a.position&&(a.element.style.position="absolute",a.element.style.top="0",a.element.style.bottom="auto",a.position="top"),d.height;b=w+parseInt(a.height);if(d.bottom<b&&!a.last)"bottom"!=a.position&&(a.element.style.position="absolute",a.element.style.top="auto",a.element.style.bottom="0",a.position="bottom");else if(d.top<=w){"fixed"!=
a.position&&(a.element.style.position="fixed",a.isFixed=!0,a.position="fixed");b=w;if(a.last){var c=window.scrollY+w+parseInt(a.height),f=document.documentElement.scrollHeight-P;c>f&&(b-=c-f)}a.element.style.top=b+"px"}else"top"!=a.position&&(a.element.style.position="absolute",a.element.style.top="0",a.element.style.bottom="auto",a.position="top");return d.height}function aa(){for(var a=window.innerHeight||document.documentElement.clientHeight,b=[],d=0;d<l.length;d++){var c=l[d];c.notfixedposition||
(b[d]=O(c,a))}a=l;if(WH.shared.isLargeSize&&!(Q||40<=R||"complete"!=document.readyState)){R++;d=0;if(c=document.getElementById("sidebar"))d=c.offsetHeight;c=0;var f=document.getElementById("article");f&&(c=f.offsetHeight);if(0<c&&0<d&&d>c){c=parseInt((d-c+10)/3);f=!1;for(d=0;d<a.length;d++){var g=b[d]-c;if(600>g){f=!0;break}a[d].element.style.height=g+"px"}if(1==f){for(d=1;d<a.length;d++)b=a[d].element,b.parentElement.removeChild(b);a.length=1}Q=!0}}}function H(){for(var a=!0,b=window.innerHeight||
document.documentElement.clientHeight,c=0;c<l.length;c++){var e=l[c];e.isLoaded||(a=!1,N(e,b))}for(c in C)e=C[c],e.isLoaded||(a=!1,N(e,b));a&&(window.removeEventListener("scroll",D),D=null)}function ba(){x.updateVisibility()}var l=[],A=[],Q=!1,R=0,I={},x,G=0,q,C={},D=null,v=null,S=null,w=WH.shared.TOP_MENU_HEIGHT,P=WH.shared.BOTTOM_MARGIN,r=null,ca="0px 0px "+2*(window.innerHeight||document.documentElement.clientHeight)+"px 0px";window.PWT=window.PWT||{};"IntersectionObserver"in window&&(r=new IntersectionObserver(function(a,
b){a.forEach(function(a){if(a.isIntersecting){var b=a.target,c=C[b.id];c&&c.load();for(var d=0;d<l.length;d+=1)c=l[d],c.element==b&&c.load();r.unobserve(a.target)}})},{rootMargin:ca}));WH.isMobile&&(P=314);var p=[];PWT.jsLoaded=function(){PWT.isLoaded=!0;"function"!==typeof PWT.removeKeyValuePairsFromGPTSlots&&c("PWT.removeKeyValuePairsFromGPTSlots is not a function. PWT is",PWT);"function"!==typeof PWT.requestBids&&c("PWT.requestBids is not a function. PWT is",PWT);c("prebid js loaded");V()};return{init:function(){var a=
(window.innerWidth||document.documentElement.clientWidth)>=WH.largeScreenMinWidth;r||(D=WH.shared.throttle(H,100),window.addEventListener("scroll",D));1==a&&(S=WH.shared.throttle(aa,10),window.addEventListener("scroll",S));document.addEventListener("DOMContentLoaded",function(){H()},!1);WH.shared&&WH.shared.addResizeFunction(H)},addBodyAd:function(a){a=document.getElementById(a);var b=new M(a);var c=null==r?!1:!0;b.disabled||("rightrail"==b.type?(b.last=!0,0<l.length&&(l[l.length-1].last=!1),l.push(b),
c&&b.observerLoading&&0==b.instantLoad&&(b.useScrollLoader=!1,r.observe(b.element))):"toc"==b.type?(q=b,b.adElement.style.display="none"):"scrollto"==b.type?(x=new Y(a),x.disabled)||(v=WH.shared.throttle(ba,100),window.addEventListener("scroll",v)):"quiz"==b.type?(I[b.adElement.parentElement.id]=b,b.adElement.parentElement.addEventListener("change",function(a){a=this.id;I[a]&&(b.adElement.classList.remove("hidden"),I[a].load())})):(C[b.element.id]=b,c&&b.observerLoading&&0==b.instantLoad&&(b.useScrollLoader=
!1,r.observe(b.element))),"dfp"==b.service&&(b.dfpdisplaylate?googletag.cmd.push(function(){gptAdSlots[b.adTargetId]=googletag.defineSlot(b.adunitpath,b.sizesArray,b.adTargetId).addService(googletag.pubads());googletag.display(b.adTargetId)}):googletag.cmd.push(function(){googletag.display(b.adTargetId)})),b.prebidload&&n(b))},loadTOCAd:function(a){if(q){var b=$(a).next(".section").find(".steps_list_2 > li:first");$(a).hasClass("mw-headline")&&(b=$(a).parents(".section:first").find(".steps_list_2 > li:first"));
b.length&&(b.append($(q.adElement)),q.load(),q.adElement.style.display="block",q=null)}},slotRendered:function(a,b,d){c("slotRendered:",a.getSlotId().getDomId(),a.getSlotId().getAdUnitPath());var e;for(d=0;d<l.length;d++){var f=l[d];gptAdSlots[f.adTargetId]==a&&(e=f)}e||x.adTargetId==a.getSlotId().getDomId()&&(e=x);e&&(e.prebidKVPadded=!1,e.apsDisplayBidsCalled=!1,e.prebidload&&PWT.removeKeyValuePairsFromGPTSlots(a),"rightrail"==e.type&&(e.height=e.element.offsetHeight,e.element.classList.remove("blockthrough"),
a=window.innerHeight||document.documentElement.clientHeight,e.extraChild&&b&&300>parseInt(b[1])?e.extraChild.style.visibility="visible":e.extraChild&&(e.extraChild.style.visibility="hidden"),e.notfixedposition||O(e,a),e.refreshable&&e.renderrefresh&&setTimeout(function(){e.refresh()},e.getRefreshTime()),PWT.isLoaded&&t(e)))},showBidStack:function(){console.log("bids",p)},showBidMap:function(){console.log("bids",PWT.bidMap)},impressionViewable:function(a){c("impressionViewable:",a.getSlotId().getDomId(),
a.getSlotId().getAdUnitPath());for(var b,d=0;d<l.length;d++){var e=l[d];gptAdSlots[e.adTargetId]==a&&(b=e)}b&&(b.height=b.element.offsetHeight,b.refreshable&&b.viewablerefresh&&setTimeout(function(){b.refresh()},b.getRefreshTime()))}}}();WH.ads.init();
