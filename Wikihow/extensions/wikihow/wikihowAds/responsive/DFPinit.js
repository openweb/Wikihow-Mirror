if (WH.shared.isDesktopSize) {
	var gads = document.createElement('script');
	gads.async = true;
	gads.type = 'text/javascript';
	var useSSL = 'https:' == document.location.protocol;
	gads.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
	var node = document.getElementsByTagName('script')[0];
	node.parentNode.insertBefore(gads, node);

	// Load GPT asynchronously
	function setDFPTargeting(slot, data) {
		var slotData = data[slot.getAdUnitPath()];
		for (var key in slotData) {
		  slot.setTargeting(key, slotData[key]);
		}
	}
	var googletag = googletag || {};
	googletag.cmd = googletag.cmd || [];
	gptRequested = true;
	googletag.cmd.push(function() {
		defineGPTSlots();
		googletag.pubads().addEventListener('slotRenderEnded', function(event) {
			if (WH.ads) {
				WH.ads.slotRendered(event.slot, event.size, event);
			}
		});
		googletag.pubads().addEventListener('impressionViewable', function(event) {
			if (WH.ads) {
				WH.ads.impressionViewable(event.slot);
			}
		});
	});
}