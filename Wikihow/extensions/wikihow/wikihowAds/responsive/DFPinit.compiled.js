if(WH.shared.isDesktopSize||dfpSmallTest){var setDFPTargeting=function(a,b){b=b[a.getAdUnitPath()];for(var c in b)a.setTargeting(c,b[c]);a.setTargeting("bucket",bucketId);a.setTargeting("language",WH.pageLang);a.setTargeting("format",format);a.setTargeting("site",window.location.hostname);a.setTargeting("coppa",isCoppa);""!=dfpCategory&&a.setTargeting("category",dfpCategory)},gads=document.createElement("script");gads.async=!0;gads.type="text/javascript";gads.src="https://securepubads.g.doubleclick.net/tag/js/gpt.js";
var node=document.getElementsByTagName("script")[0];node.parentNode.insertBefore(gads,node);var format="sma",viewportWidth=window.innerWidth||document.documentElement.clientWidth;0==WH.isMobile?format="dsk":viewportWidth>=WH.largeScreenMinWidth?format="lrg":viewportWidth>=WH.mediumScreenMinWidth&&(format="med");var googletag=googletag||{};googletag.cmd=googletag.cmd||[];gptRequested=!0;googletag.cmd.push(function(){defineGPTSlots();googletag.pubads().addEventListener("slotRenderEnded",function(a){WH.ads&&
WH.ads.slotRendered(a.slot,a.size,a)});googletag.pubads().addEventListener("impressionViewable",function(a){WH.ads&&WH.ads.impressionViewable(a.slot)});0<=document.cookie.indexOf("ccpa_out=")&&googletag.pubads().setPrivacySettings({restrictDataProcessing:!0})})};