window.dfprequested=!1;
function initDFP(){if(1!=window.dfprequested&&(WH.shared.isDesktopSize||dfpSmallTest)){var a=document.createElement("script");a.async=!0;a.type="text/javascript";a.src="https://securepubads.g.doubleclick.net/tag/js/gpt.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b);window.dfprequested=!0;window.googletag=window.googletag||{};googletag.cmd=googletag.cmd||[];gptRequested=!0;googletag.cmd.push(function(){defineGPTSlots();googletag.pubads().addEventListener("slotRenderEnded",function(a){WH.ads&&
WH.ads.slotRendered(a.slot,a.size,a)});googletag.pubads().addEventListener("impressionViewable",function(a){WH.ads&&WH.ads.impressionViewable(a.slot)});0<=document.cookie.indexOf("ccpa_out=")&&googletag.pubads().setPrivacySettings({restrictDataProcessing:!0})})}}var format="sma",viewportWidth=window.innerWidth||document.documentElement.clientWidth;0==WH.isMobile?format="dsk":viewportWidth>=WH.largeScreenMinWidth?format="lrg":viewportWidth>=WH.mediumScreenMinWidth&&(format="med");
function setDFPTargeting(a,b){b=b[a.getAdUnitPath()];for(var c in b)a.setTargeting(c,b[c]);a.setTargeting("bucket",bucketId);a.setTargeting("language",WH.pageLang);a.setTargeting("format",format);a.setTargeting("site",window.location.hostname);a.setTargeting("coppa",isCoppa);""!=dfpCategory&&a.setTargeting("category",dfpCategory)}1==window.loadGPT&&initDFP();
