WH.ads = (function () {
	"use strict";
	var RR_REFRESH_TIME = 30000;
	var VIEW_REFRESH_TIME = 5000;
	var INTRO_REFRESH_TIME = 12000;
	var BOTTOM_MARGIN = 114;
	var adLabelHeight = 39;
	var rightRailElements = [];

	// keep track of the state of the right rail elements size
	// only check the size MaxCount times
	var rrSizeChanged = false;
	var rrSizeCheckCount = 0;
	var RR_SIZE_MAX_COUNT = 40;
	var AD_INSERT_MAX_COUNT = 1000;
	var adInsertCount = 0;

    var rightRailExtra = null;

	var quizAds = {};
	var introAd;
	var scrollToAd;
	var scrollToAdInsertCount = 0;
	var TOCAd;
	var bodyAds = [];
	var lastScrollPosition = window.scrollY;

	var topMenuHeight = WH.shared.TOP_MENU_HEIGHT;
	if (WH.isMobile) {
		topMenuHeight = 92;
	}
	function log() {
	}

	function isDocumentHidden() {
		var hidden = false;
		if (null!=document.hidden) {
			hidden = document.hidden;
		} else if (null!=document.mozHidden) {
			hidden = document.mozHidden;
		} else if (null!=document.webkitHidden) {
			hidden = document.webkitHidden;
		} else if (null!=document.msHidden) {
			hidden = document.msHidden;
		}
		return hidden;
	}

	function apsFetchBids(slotValues, gptSlotIds, timeoutValue) {
		log("apsFetchBids", slotValues, gptSlotIds, timeoutValue);
		var gptSlots = [];
		for (var i = 0; i < gptSlotIds.length; i++) {
			gptSlots.push(gptAdSlots[gptSlotIds[i]]);
		}

		apstag.fetchBids({
			slots: slotValues,
			timeout: timeoutValue
		}, function(bids) {
			googletag.cmd.push(function(){
				apstag.setDisplayBids();
				for (var i = 0; i < gptSlots.length; i++ ) {
					var slot = gptSlots[i];
					setDFPTargeting(slot, dfpKeyVals);
				}
				googletag.pubads().refresh(gptSlots);
			});
		});
	}

	function apsLoad(ad) {
		var id = ad.adTargetId;
		var slotName = gptAdSlots[id].getAdUnitPath();
		var sizes = gptAdSlots[id].getSizes();
		var sizesArray = [];
		for (var i = 0; i < sizes.length; i++) {
			var sizesSub = [];
			sizesSub.push(sizes[i].getWidth());
			sizesSub.push(sizes[i].getHeight());
			sizesArray.push(sizesSub);
		}
		var slotsArray = [{slotID: id, slotName: slotName, sizes: sizesArray}];
		var gptSlotIds = [id];
		apsFetchBids(slotsArray, gptSlotIds, ad.apsTimeout);
	}

	function updateKeyVal(adId, key, value) {
		if (!gptAdSlots[adId]) {
			return;
		}
		if (!dfpKeyVals[gptAdSlots[adId].getAdUnitPath()]) {
			return;
		}

		dfpKeyVals[gptAdSlots[adId].getAdUnitPath()][key] = value;
	}

	function gptLoad(ad) {
		log('gptLoad', ad);
		var id = ad.adTargetId;
		var display = ad.gptLateLoad;
		var refreshValue = ad.getRefreshValue();
		googletag.cmd.push(function() {
			// optionally call display first if dfp late loading is active
			if (display) {
				googletag.display(id);
			}
			updateKeyVal(id, 'refreshing', refreshValue);
			setDFPTargeting(gptAdSlots[id], dfpKeyVals);
			// the refresh call actually loads the ad
			log("gptLoad calling pubads().refresh on", gptAdSlots[id]);
			googletag.pubads().refresh([gptAdSlots[id]]);
		});
	}

	function impressionViewable(slot) {
		log('impressionViewable: slot', slot);
		var ad;
		for (var i = 0; i < rightRailElements.length; i++) {
			var tempAd = rightRailElements[i];
			if (gptAdSlots[tempAd.adTargetId] == slot) {
				ad = tempAd;
			}
		}
        if (!ad && introAd) {
            // check intro ad
			if (gptAdSlots[introAd.adTargetId] == slot) {
				ad = introAd;
			}
        }
		if (!ad) {
			return;
		}
		ad.height = ad.adElement.offsetHeight;

		if (ad.refreshable && ad.viewablerefresh) {
			setTimeout(function() {ad.refresh();}, ad.getRefreshTime());
		}
	}

	function slotRendered(slot, size, e) {
		log('slotRendered: slot, e', slot, e);
		// look for right rail ads which are the only ones that will be moved/refreshed
		var ad;
		for (var i = 0; i < rightRailElements.length; i++) {
			var tempAd = rightRailElements[i];
			if (gptAdSlots[tempAd.adTargetId] == slot) {
				ad = tempAd;
			}
		}
		if (!ad) {
			return;
		}
		ad.height = ad.adElement.offsetHeight;
        // don't even bother checking the space unless the ad is less than 300px in height
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
        if (ad.extraChild && size && parseInt(size[1]) < 300) {
			ad.extraChild.style.visibility = "visible";
        } else if (ad.extraChild) {
            ad.extraChild.style.visibility = "hidden";
        }
		if (!ad.notfixedposition) {
			updateFixedPositioning(ad, viewportHeight);
		}

		if (ad.refreshable && ad.renderrefresh) {
			setTimeout(function() {ad.refresh();}, ad.getRefreshTime());
		}
	}

	function DFPInit() {
		(function() {
			var gads = document.createElement('script');
			gads.async = true;
			gads.type = 'text/javascript';
			var useSSL = 'https:' == document.location.protocol;
			gads.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
			var node = document.getElementsByTagName('script')[0];
			node.parentNode.insertBefore(gads, node);
		})();
		function setDFPTargeting(slot, data) {
			var slotData = data[slot.getAdUnitPath()];
			for (var key in slotData) {
			  slot.setTargeting(key, slotData[key]);
			}
		}
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

	function insertDFPLightAd(ad) {
		var i = window.document.createElement('div');
		i.setAttribute('data-glade', '');
		var path = ad.adunitpath;
		i.setAttribute('class', 'gptlight');
		i.setAttribute('data-ad-unit-path', path);
		i.setAttribute('width', 728);
		i.setAttribute('height', 90);
		var target = ad.adTargetId;
		var glade = document.createElement('script');
		glade.async = true;
		glade.src = 'https://securepubads.g.doubleclick.net/static/glade.js';
		window.document.getElementById(target).appendChild(i);
		window.document.getElementById(target).appendChild(glade);
	}

	function insertNewAdsenseAd(ad) {
		if (adInsertCount >= AD_INSERT_MAX_COUNT) {
			return;
		}

		var count = rightRailElements.length + adInsertCount;
		var newItemId = 'rightrail' + count;
		var newRightrailAd = document.createElement("div");
		newRightrailAd.className = 'rr_container';
		newRightrailAd.id = newItemId;
		newRightrailAd.style.height = '3300px';

		var newAdItem = document.createElement("div");
		newAdItem.className = 'whad';
		newAdItem.setAttribute('data-service', 'adsense');
		newAdItem.setAttribute('data-refreshable', ad.refreshable ? 1 : 0);
		newAdItem.setAttribute('data-viewablerefresh', ad.viewablerefresh ? 1 : 0);
		newAdItem.setAttribute('data-refresh-time', ad.refreshTime );
		newAdItem.setAttribute('data-insert-refresh', ad.insertRefresh ? 1 : 0);
		newAdItem.setAttribute('data-adlabelclass', 'ad_label ad_label_dollar');
		newAdItem.setAttribute('data-adtargetid', newItemId);
		newAdItem.setAttribute('data-adsensewidth', ad.width);
		newAdItem.setAttribute('data-height', ad.height);
		newAdItem.setAttribute('data-channels', ad.channels);
		newAdItem.setAttribute('data-loaded', 0);
		newAdItem.setAttribute('data-slot', ad.slot);
		newRightrailAd.appendChild(newAdItem);
		ad.element.parentElement.insertBefore(newRightrailAd, ad.element);

		var newAd = new RightRailAd(newRightrailAd);
		for (var i = 0; i < rightRailElements.length; i++) {
			if (ad == rightRailElements[i]) {
				rightRailElements[i] = newAd;
			}
		}

		if (ad.last == true) {
			ad.last = false;
			newAd.last = true;
		}
		log("inserted adsense ad", newAd);
		ad.element.parentNode.removeChild(ad.element);
		adInsertCount++;
		updateVisibility();
	}

	function insertNewDFPAd(ad) {
		if (adInsertCount >= AD_INSERT_MAX_COUNT) {
			log("insertNewDFPAd: max refreshes reached");
			return;
		}

		var count = rightRailElements.length + adInsertCount;
		var newItemId = 'rightrail' + count;
		var newRightrailAd = document.createElement("div");
		newRightrailAd.className = 'rr_container';
		newRightrailAd.id = newItemId;
		newRightrailAd.style.height = '3300px';

		var newTargetId = 'rightrail_gpt_' + count;
		var newAdItem = document.createElement("div");
		newAdItem.className = 'whad';
		newAdItem.setAttribute('data-service', 'dfp');
		newAdItem.setAttribute('data-refreshable', ad.refreshable ? 1 : 0);
		newAdItem.setAttribute('data-viewablerefresh', ad.viewablerefresh ? 1 : 0);
		newAdItem.setAttribute('data-refresh-time', ad.refreshTime );
		newAdItem.setAttribute('data-insert-refresh', ad.insertRefresh ? 1 : 0);
		newAdItem.setAttribute('data-apsload', ad.apsload ? 1 : 0);
		newAdItem.setAttribute('data-aps-timeout', ad.apsTimeout );
		newAdItem.setAttribute('data-adtargetid', newTargetId);
		newAdItem.setAttribute('data-loaded', 0);
		var newTarget = document.createElement("div");
		newTarget.id = newTargetId;
		newTarget.className = 'ad_label ad_label_dollar';
		newAdItem.appendChild(newTarget);
		newRightrailAd.appendChild(newAdItem);
		ad.element.parentElement.insertBefore(newRightrailAd, ad.element);


		if (ad.service == 'adsense') {
			var slotName = ad.slotName;
			var sizesArray = ad.sizesArray;
			googletag.cmd.push(function() {
				gptAdSlots[newTargetId] = googletag.defineSlot(slotName, sizesArray, newTargetId).addService(googletag.pubads());
				googletag.display(newTargetId);
			});

			var newAd = new RightRailAd(newRightrailAd);
			for (var i = 0; i < rightRailElements.length; i++) {
				if (ad == rightRailElements[i]) {
					rightRailElements[i] = newAd;
				}
			}

			if (ad.last == true) {
				ad.last = false;
				newAd.last = true;
			}

			ad.element.parentNode.removeChild(ad.element);
		} else {
			// get the slot name and sizes of the current ad to use it
			// to define a new ad
			var slotName = gptAdSlots[ad.adTargetId].getAdUnitPath();
			var sizes = gptAdSlots[ad.adTargetId].getSizes();
			var sizesArray = [];
			for (var i = 0; i < sizes.length; i++) {
				var sizesSub = [];
				sizesSub.push(sizes[i].getWidth());
				sizesSub.push(sizes[i].getHeight());
				sizesArray.push(sizesSub);
			}
			googletag.cmd.push(function() {
				gptAdSlots[newTargetId] = googletag.defineSlot(slotName, sizesArray, newTargetId).addService(googletag.pubads());
				googletag.display(newTargetId);
			});

			var newAd = new RightRailAd(newRightrailAd);
			for (var i = 0; i < rightRailElements.length; i++) {
				if (ad == rightRailElements[i]) {
					rightRailElements[i] = newAd;
				}
			}

			if (ad.last == true) {
				ad.last = false;
				newAd.last = true;
			}

			googletag.cmd.push(function() {
				var result = googletag.destroySlots([gptAdSlots[ad.adTargetId]]);
			});
			// remove the ad causes a warning in GPT, but it's probably not a big deal..
			// in any case we already destroyed the ad so hiding it should be fine too
			ad.element.parentNode.removeChild(ad.element);
			//ad.element.style.display = "none";
		}
		adInsertCount++;
		updateVisibility();
	}

	function insertAdsenseAd(ad) {
		// set the height of he ad to the adsense height
		var client = "ca-pub-9543332082073187";
		var i = window.document.createElement('ins');
		i.setAttribute('data-ad-client', client);
		if (ad.adLabelClass) {
			i.setAttribute('class', 'adsbygoogle' + ' ' + ad.adLabelClass);
		} else {
			i.setAttribute('class', 'adsbygoogle');
		}
		var slot = ad.slot;
		if (!slot) {
			return;
		}
		i.setAttribute('data-ad-slot', slot);

		if (ad.type == 'middlerelated') {
			i.setAttribute('data-ad-format', 'fluid');
			i.setAttribute('data-ad-layout-key', '-fb+5w+4e-db+86');
		}

		var css = 'display:inline-block;width:'+ad.width+'px;height:'+ad.height+'px;';
		var noWidthTypes = ["method", "qa", "tips", "warnings"];
		// TODO do not use includes
		if (noWidthTypes.includes(ad.type)) {
			css = 'display:block;height:'+ad.height+'px;';
		}
		i.style.cssText = css;
		var target = null
		if (ad.adTargetId) {
			window.document.getElementById(ad.adTargetId).appendChild(i);
		} else {
			return;
		}

		var channels = ad.channels ? ad.channels: "";
		if (typeof adsbygoogle === 'undefined') {
			window.adsbygoogle = [];
		}
		(window.adsbygoogle = window.adsbygoogle || []).push({
			params: {
				google_ad_channel: channels
			}
		});
	}

	function recalcAdHeights() {
		for (var i = 0; i < rightRailElements.length; i++) {
			var ad = rightRailElements[i];
			ad.height = ad.adElement.offsetHeight;
		}
	}

	function getMobileAdWidth(type) {
		var width = document.documentElement.clientWidth;

		switch(type) {
			case "intro":
				width = width - 30;
				break;
			case "method":
				width = width - 20;
				break;
			case "related":
				width = width - 14;
				break;
			default:
				width = width - 20;
		}
		return width;
	}
	function Ad(element) {
		// the ad ELement has all the data attributes about the ad
		// the element is the contained div which is the target of the ad insertion
		// it is nested due to making css easier
		var adElement = element.parentElement;
		var viewportWidth = (window.innerWidth || document.documentElement.clientWidth);
		this.isDesktopSize = viewportWidth >= WH.mediumScreenMinWidth;
		this.isLargeSize = viewportWidth >= WH.largeScreenMinWidth;
		this.isMediumSize = this.isDesktopSize && !this.isLargeSize;

		this.element = element;
		this.adElement = adElement;
		this.height = this.adElement.offsetHeight;
		this.adTargetId = element.id;
		this.desktopOnly = this.adElement.getAttribute('data-desktoponly') == 1;
		if (this.desktopOnly && !this.isDesktopSize) {
			this.disabled = true;
			return;
		}

		this.mediumOnly = this.adElement.getAttribute('data-mediumonly') == 1;
		if (this.mediumOnly && !this.isMediumSize) {
			this.disabled = true;
			return;
		}

		this.largeOnly = this.adElement.getAttribute('data-largeonly') == 1;
		if (this.largeOnly && !this.isLargeSize) {
			this.disabled = true;
			return;
		}

		this.gptLateLoad = this.adElement.getAttribute('data-lateload') == 1;
		this.service = this.adElement.getAttribute('data-service');
		this.apsload = this.adElement.getAttribute('data-apsload') == 1;
		this.slot = this.adElement.getAttribute('data-slot');
		if (this.isDesktopSize && this.service == 'adsense' && !this.slot) {
			this.disabled = true;
			adElement.style.display = 'none';
			return;
		}
		this.adunitpath = this.adElement.getAttribute('data-adunitpath');
		this.channels = this.adElement.getAttribute('data-channels');
		this.mobileChannels = this.adElement.getAttribute('data-mobilechannels');
		this.refreshable = this.adElement.getAttribute('data-refreshable') == 1;
		this.insertRefresh = this.adElement.getAttribute('data-insert-refresh') == 1;
		this.slotName = this.adElement.getAttribute('data-slot-name');
		this.refreshType = this.adElement.getAttribute('data-refresh-type');
		this.sizesArray = this.adElement.getAttribute('data-sizes-array');
		if (this.sizesArray) {
			this.sizesArray = JSON.parse(this.sizesArray);
		}
		this.type = this.adElement.getAttribute('data-type');
		if (this.type == 'rightrail') {
			this.position = 'initial';
		}

		this.notfixedposition = this.adElement.getAttribute('data-notfixedposition') == 1;
		this.viewablerefresh = this.adElement.getAttribute('data-viewablerefresh') == 1;
		this.renderrefresh = this.adElement.getAttribute('data-renderrefresh') == 1;
		this.width = this.adElement.getAttribute('data-width');
		this.height = this.adElement.getAttribute('data-height');
		this.mobileHeight = this.adElement.getAttribute('data-mobileheight');
		this.mobileSlot = this.adElement.getAttribute('data-mobileslot');
		if (!this.isDesktopSize) {
			this.channels = this.mobileChannels;
			this.slot = this.mobileSlot;
			this.height = this.mobileHeight;
			this.width = getMobileAdWidth(this.type);;
			if (this.service == 'adsense' && !this.slot) {
				this.disabled = true;
				return;
			}
		}

		this.instantLoad = this.adElement.getAttribute('data-instantload') == 1;
		this.adLabelClass = this.adElement.getAttribute('data-adlabelclass');
		this.instantLoad = this.adElement.getAttribute('data-instantload') == 1;
		this.apsTimeout = this.adElement.getAttribute('data-aps-timeout');
		this.refreshtimeout = false;
		this.refreshNumber = 1;
        this.maxRefresh = this.adElement.getAttribute('data-max-refresh');
        this.refreshTime = this.adElement.getAttribute('data-refresh-time');
		if (!this.refreshTime) {
			this.refreshTime = RR_REFRESH_TIME;
		} else {
			this.refreshTime = parseInt(this.refreshTime);
		}
		this.firstRefresh = true;
        this.firstRefreshTime = this.adElement.getAttribute('data-first-refresh-time');
		if (!this.firstRefreshTime) {
			this.firstRefreshTime = this.refreshTime;
		} else {
			this.firstRefreshTime = parseInt(this.firstRefreshTime);
		}

		this.getRefreshTime = function() {
			if (this.firstRefresh == true ) {
				this.firstRefresh = false;
				return this.firstRefreshTime;
			} else {
				return this.refreshTime;
			}

		}

		// special type of adsense ad that is immediately loaded which we will refresh
		if (this.service == 'adsense' && this.insertRefresh && this.refreshTime) {
			var ad = this;
			setTimeout(function() {ad.refresh();}, ad.getRefreshTime());
		}

		this.getRefreshValue = function() {
			if (this.refreshNumber == 0 && !this.refreshable) {
				return 'not';
			}

			this.refreshNumber++;
			if (this.refreshNumber > 20) {
				return 'max';
			}
			return this.refreshNumber.toString();
		};
		this.load = function() {
			// if already loaded do nothing
			if (this.isLoaded == true) {
				return;
			}
			if (!this.isLargeSize && this.isRightRail) {
				return;
			}
			if (this.service == 'dfp') {
				// if dfp was not already initialized do so now
				if (gptRequested == false) {
					DFPInit();
					gptRequested = true;
				}
				if (this.apsload) {
					var id = this.adTargetId;
					var slot = gptAdSlots[id];
					var ad = this;
					// if gpt slots haven't bene defined yet then queue up this command
					if (!slot) {
						googletag.cmd.push(function() {
							apsLoad(ad);
						});
					} else {
						apsLoad(ad);
					}
				} else {
					gptLoad(this);
				}
			} else if (this.service == 'dfplight') {
				insertDFPLightAd(this);
			} else {
				insertAdsenseAd(this);
			}
			this.isLoaded = true;
		};

		this.refresh = function() {
			log('refresh: ad', this);
			var ad = this;
			if (isDocumentHidden()) {
				// check again later
				setTimeout(function() {ad.refresh();}, VIEW_REFRESH_TIME);
				return;
			}
			var lastScrollY = this.lastRefreshScrollY;
			this.lastRefreshScrollY = window.scrollY;
			// check if ad is in viewport
			var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
			var rect = this.element.getBoundingClientRect();
			if (!isInViewport(rect, viewportHeight, false, ad)) {
				// check again later
				log("refresh: not in viewport", rect, viewportHeight);
				setTimeout(function() {ad.refresh();}, VIEW_REFRESH_TIME);
				return;
			}
			var refreshValue = this.getRefreshValue();
			if (this.maxRefresh && refreshValue > this.maxRefresh) {
				log("max refreshes reached returning");
				this.refreshable = false;
				return;
			}
			if (this.service != 'adsense') {
				updateKeyVal(this.adTargetId, 'refreshing', refreshValue);
			}
			if (this.service == 'adsense') {
				if (this.refreshType == 'dfp') {
					insertNewDFPAd(this);
				} else {
					log("will insert new adsense ad");
					insertNewAdsenseAd(this);
				}
			} else if (this.insertRefresh) {
				insertNewDFPAd(this);
			} else if (this.apsload) {
				apsLoad(this);
			} else {
				var id = this.adTargetId;
				var display = this.gptLateLoad;
				googletag.cmd.push(function() {
					setDFPTargeting(gptAdSlots[id], dfpKeyVals);
					googletag.pubads().refresh([gptAdSlots[id]]);
				});
			}
		};
		this.show = function() {
			this.adElement.style.display = 'block';
		};

		if (this.instantLoad) {
			this.load();
		}
	}

	function BodyAd(element) {
		Ad.call(this, element);
	}

	function isNearEndOfStep(rect, viewportHeight) {
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		return false;
	}

	function getStepForScrollPosition(viewportHeight, scrollPosition, steps, ad) {
		var targetStep = null;
		var found = false;
		for (var i = 0; i < steps.length; i++) {
			var step = steps[i];
			if (step.nodeName != "LI") {
				continue;
			}
			if (found == true) {
				targetStep = step;
				break;
			}
			var rect = step.getBoundingClientRect();
			if (isInViewport(rect, viewportHeight, false, ad)) {
				// if we are near the end of teh step now, then put the ad at the next step instead
				if (isNearEndOfStep(rect, viewportHeight)) {
					found = true;
				} else {
					targetStep = step;
					break;
				}
			}
		}
		return targetStep;
	}

	function getInsertTargetForScrollPosition(ad) {
		var scrollPosition = ad.lastScrollPositionY;

		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		var sections = document.getElementsByClassName("section");
		var target = null;
		var found = false;
		for (var i = 0; i <= sections.length; i++) {
			var section = null;
			if (i == sections.length) {
				section = document.getElementById('ur_mobile');
				if (!section) {
					break;
				}
			} else {
				section = sections[i];
			}
			if (section.id == "aiinfo") {
				continue;
			}

			if (found == true) {
				var sectionText = section.getElementsByClassName("section_text");
				if (!sectionText || !sectionText[0]) {
					return null;
				}
				section = sectionText[0];
				var rect = section.getBoundingClientRect();
				if (isNearEndOfStep(rect, viewportHeight)) {
					continue;
				}
				break;
			}

			// skip intro
			if (section.id == "intro") {
				continue;
			}
			if (section.classList.contains('steps')) {
				var steps = section.getElementsByClassName("steps_list_2");
				if (!steps || !steps[0]) {
					continue;
				}
				var stepsTarget = getStepForScrollPosition(viewportHeight, scrollPosition, steps[0].childNodes, ad);
				if (!stepsTarget) {
					continue;
				}
				target = stepsTarget;
				break;
			}

			var sectionText = section.getElementsByClassName("section_text");
			if (!sectionText || !sectionText[0]) {
				continue;
			}
			section = sectionText[0];
			var rect = section.getBoundingClientRect();
			if (isInViewport(rect, viewportHeight, false, ad)) {
				// if we are near the end of the section now, then put the ad at the next section instead
				if (isNearEndOfStep(rect, viewportHeight)) {
					found = true;
				} else {
					target = section;
					break;
				}
			}
		}
		return target;
	}

	function ScrollToAd(element) {
		Ad.call(this, element);
		element.parentElement.style.display = 'none';

		this.scrollToTimer = null;
		this.lastScrollPositionY = 0;

		this.maxNonSteps = parseInt(this.adElement.getAttribute('data-maxnonsteps'));
		this.maxSteps = parseInt(this.adElement.getAttribute('data-maxsteps'));
		this.updateVisibility = function() {
			// handle scrollTo ad if we have one
			if (this.maxNonSteps < 1 && this.maxSteps < 1) {
				return;
			}

			this.lastScrollPositionY = window.scrollY;
			if (this.lastScrollPositionY > 10) {
				if (this.scrollToTimer !== null) {
					clearTimeout(this.scrollToTimer);
				}
				var ad = this;
				this.scrollToTimer = setTimeout(function() {
					ad.load();
				}, 1000);
			}
		};
		this.load = function() {
			var insertTarget = getInsertTargetForScrollPosition(this);
			if (!insertTarget) {
				return;
			}

			var isStep = insertTarget.tagName == "LI";

			if (isStep && this.maxSteps < 1) {
				return;
			} else if (!isStep && this.maxNonSteps < 1) {
				return;
			}

			var existingAds = insertTarget.getElementsByTagName("INS")
			if (existingAds.length > 0) {
				return;
			}
			existingAds = insertTarget.getElementsByClassName("wh_ad_inner")
			if (existingAds.length > 0) {
				return;
			}

			var addTips = insertTarget.getElementsByClassName("addTipElement");
			if (addTips.length > 0) {
				insertTarget.classList.add('has_scrolltoad');
			}

			var wrap = document.createElement('div');
			if (isStep) {
				wrap.className = "wh_ad_inner step_ad scrollto_wrap";
			} else {
				wrap.className = "wh_ad_inner scrollto_wrap";
			}
			insertTarget.appendChild(wrap);
			insertTarget = wrap;
			// give it an id for inserting
			if ( !insertTarget.id ) {
				insertTarget.id = 'scrollto-ad-'+scrollToAdInsertCount;
			}
			this.adTargetId = insertTarget.id;

			insertAdsenseAd(this);

			if (isStep) {
				this.maxSteps--;
			} else {
				this.maxNonSteps--;
			}

			scrollToAdInsertCount++;

			return;
		};
	}

	function IntroAd(element) {
		Ad.call(this, element);
		// TODO get all of these values from data attributes
		this.refreshTime = INTRO_REFRESH_TIME;
        this.maxRefresh = 2;
		this.stickingHeaderElement = document.getElementsByClassName("firstadsticking")[0];
		this.isAnimating = false;
		this.hasAnimated = false;
		this.sticky = element.getAttribute('data-sticky') == 1;
	}

    function RightRailAd(element) {
		Ad.call(this, element);
		// store the right rail container element and height for use later
		this.height = element.offsetHeight;
		this.position = 'initial';
	}

	/*
	 *  check if either the top or the bottom of the element is in view
	 *  taking into account header
	 *  if for loading we add 20% to the size of the viewport
	 *  @param rect - the result of calling  of getBoundingClientRect() on the target element
	 *  @param viewportHeight - the current viewport height
	 *  @param forLoading - adds 20% to viewport size
	 *  @param ad - the ad we are checking
	 */
	function isInViewport(rect, viewportHeight, forLoading, ad) {
		var screenTop = topMenuHeight;

		// TODO not sure if we should leave this or comment out...
		//if (rect.height == 0) return false;

		if (forLoading) {
			var offset = viewportHeight;
			// for body ads load them even sooner
			if (ad instanceof BodyAd) {
				offset = viewportHeight * 2;
			}
			screenTop = 0 - offset;
			viewportHeight = viewportHeight + offset;
		}
		if (rect.top >= screenTop && rect.top <= viewportHeight) {
			return true;
		}
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		if (rect.top <= screenTop && rect.bottom >= viewportHeight) {
			return true;
		}

		// if this is the last ad, then if the top of the rec is less than screen top
		// meaning the top of the ad container is above the page, then we will
		// set the add to always in the viewport..faking that it's height is infinite
		if (ad.last && rect.top <= screenTop) {
			return true;
		}
		return false;
	}

	function updateAdLoading(ad, viewportHeight) {
		if (ad.isLoaded) {
			return;
		}
		var rect = ad.element.getBoundingClientRect();
		// check viewport size + additional 20% so we load before the video is in view
		if (isInViewport(rect, viewportHeight, true, ad)) {
			ad.load();
		}
	}

	function finishIntroSlide(ad) {
		ad.isAnimating = false;
		ad.hasAnimated = true;
		ad.adElement.style.position = 'static';
		ad.adElement.style.top = 'auto';
		ad.adElement.style.zIndex = 'auto';
		ad.adElement.style.backgroundColor = '#fff';
		ad.isFixed = false;
		ad.position = 'static';
		if (ad.stickingHeaderElement) {
			var rect = ad.stickingHeaderElement.getBoundingClientRect();
			if (rect.top < 150) {
				ad.stickingHeaderElement.className = 'sticking';
				ad.stickingHeaderElement.style.top = null;
			}
		}
	}
	function slideIntroAdUp(ad, start, end) {
		// check if we should just stop early
		var rect = ad.element.getBoundingClientRect();
		if (rect.top > adLabelHeight || start <= end) {
			finishIntroSlide(ad);
		} else {
			ad.isAnimating = true;
			var headerVal = start + 132;
			if (ad.stickingHeaderElement) {
				//ad.stickingHeaderElement.setAttribute('data-animating', 1);
				ad.stickingHeaderElement.style.top = headerVal+'px';
			}
			ad.adElement.style.top = start+'px';
			setTimeout(function(){
				slideIntroAdUp(ad, start - 1, end);
			}, 3);
		}
	}

	function updateFixedPositioningIntro(ad, viewportHeight) {
		var rect = ad.element.getBoundingClientRect();

		if (ad.isAnimating == true) {
			return;
		}
		if (rect.top <= adLabelHeight) {
			// pick some random spot for the thing to slide back up
			if (rect.top > -500) {
				if (ad.hasAnimated == true) {
					return;
				}
				ad.adElement.style.position = 'fixed';
				ad.adElement.style.top = adLabelHeight + 'px';
				ad.adElement.style.zIndex = '1000';
				ad.isFixed = true;
				ad.position = 'fixed';
			} else if (ad.position == 'fixed'){
				slideIntroAdUp(ad, adLabelHeight, -94);
			}
		} else {
			ad.adElement.style.position = 'static';
			ad.adElement.style.top = 'auto';
			ad.adElement.style.zIndex = 'auto';
			ad.adElement.style.backgroundColor = '#fff';
			ad.isFixed = false;
			ad.position = 'static';
		}
	}

	/*
	 * returns the height of the element (in this case the ad) which is
	 * useful so we do not have to call getBoundingClientRect multiple times
	 * when trying to get the height of all three ads
	 */
	function updateFixedPositioning(ad, viewportHeight) {
		var rect = ad.adElement.getBoundingClientRect();

		if (!isInViewport(rect, viewportHeight, false, ad)) {
			// if the container is not in the viewport then make sure it is not fixed pos
			if (ad.position == 'fixed') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = '0';
				ad.element.style.bottom = 'auto';
				ad.position = 'top';
			}
			return rect.height;
		}

		var bottom = topMenuHeight + parseInt(ad.height);
		if (rect.bottom < bottom && !ad.last) {
			if (ad.position != 'bottom') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = 'auto';
				ad.element.style.bottom = '0';
				ad.position = 'bottom';
			}
		} else if (rect.top <= topMenuHeight) {
			if (ad.position != 'fixed') {
				ad.element.style.position = 'fixed';
				ad.isFixed = true;
				ad.position = 'fixed';
			}
			var topPx = topMenuHeight;
			if (ad.last) {
				var adBottom = window.scrollY + topMenuHeight + parseInt(ad.height);
				var offsetBottom = document.documentElement.scrollHeight - BOTTOM_MARGIN;
				if ( adBottom > offsetBottom ) {
					topPx = topPx - (adBottom - offsetBottom);
				}
			}
			ad.element.style.top = topPx + 'px';
		} else {
			if (ad.position != 'top') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = '0';
				ad.element.style.bottom = 'auto';
				ad.position = 'top';
			}
		}

		return rect.height;
	}

	/*
	 * if we have 3 right rail elements check sidebar height vs article height
	 * to make sure the rr ads are not longer than the article
	 */
	function checkSidebarHeight(rightRailElements, adHeights) {
		if (rrSizeChanged) {
			return;
		}
		if (rrSizeCheckCount >= RR_SIZE_MAX_COUNT) {
			return;
		}
		if (document.readyState != 'complete') {
			return;
		}
		rrSizeCheckCount++;
		var sidebarHeight = 0;
		var sidebar = document.getElementById('sidebar');
		if (sidebar) {
			sidebarHeight = sidebar.offsetHeight;
		}
		var articleHeight = 0;
		var article = document.getElementById('article');
		if (article) {
			articleHeight = article.offsetHeight;
		}

		// subtract from each RR element if the sidebar is bigger than the article
		if (articleHeight > 0 && sidebarHeight > 0 && sidebarHeight > articleHeight) {
			var diff = sidebarHeight - articleHeight;
			diff = parseInt((diff + 10)/3);
			var removeElements = false;
			for (var i = 0; i < rightRailElements.length; i++) {
				var value = adHeights[i] - diff;
				if (value < 600) {
					removeElements = true;
					break;
				}
				rightRailElements[i].element.style.height = value + 'px';
			}
			if (removeElements == true) {
				for (var i = 1; i < rightRailElements.length; i++) {
					var el = rightRailElements[i].element;
					el.parentElement.removeChild(el);
				}
				rightRailElements.length = 1;
			}
			// set flag to not resize again
			rrSizeChanged = true;
		}
	}

	// this is registered by the scroll handler
	function updateVisibility() {
		lastScrollPosition = window.scrollY;
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		var viewportWidth = (window.innerWidth || document.documentElement.clientWidth);
		// TODO put this into the ad itself instead of rechecking constantly?
		var hasRightRail = viewportWidth >= WH.largeScreenMinWidth;
		var hasDesktopAds = viewportWidth >= WH.mediumScreenMinWidth;

		// keep track of ad heights for possible use if they are too tall for the article
		var adHeights = [];
		for (var i = 0; i < rightRailElements.length; i++) {
			var ad = rightRailElements[i];
			if (!hasRightRail) {
				continue;
			}
			updateAdLoading(ad, viewportHeight);
			if (ad.notfixedposition) {
				continue;
			}
			adHeights[i] = updateFixedPositioning(ad, viewportHeight);
		}
		for (var i = 0; i < bodyAds.length; i++) {
			var ad = bodyAds[i];
			updateAdLoading(ad, viewportHeight);
		}

		// now for the intro ad
		if (introAd) {
			if (!introAd.stickingHeaderElement) {
				introAd.stickingHeaderElement = document.getElementsByClassName("firstadsticking")[0];
			}
			if (introAd.sticky) {
				updateFixedPositioningIntro(introAd, viewportHeight);
			}
		}

		if (hasRightRail) {
			checkSidebarHeight(rightRailElements, adHeights);
		}

		if (scrollToAd) {
			scrollToAd.updateVisibility();
		}
	}

	function init() {
		updateVisibility();
		window.addEventListener('scroll', WH.shared.throttle(updateVisibility, 10));
		document.addEventListener('DOMContentLoaded', function() {updateVisibility();}, false);
		if (WH.shared) {
			WH.shared.addResizeFunction(updateVisibility);
		}
	}

	function addScrollToAd(id) {
		var el = document.getElementById(id);
		scrollToAd = new ScrollToAd(el);
	}

	function addIntroAd(id) {
		var introElement = document.getElementById(id);
		introAd = new IntroAd(introElement);
		if (introAd.service == 'dfplight') {
			introAd.load();
		}
	}

    function addRightRailAd(id) {
        var rightRailElement = document.getElementById(id);
        var ad = new RightRailAd(rightRailElement);

        ad.last = true;
        if (rightRailElements.length > 0) {
            rightRailElements[rightRailElements.length -1].last = false;
        }
        rightRailElements.push(ad);
    }

	// requires jquery to have been loaded
    function loadTOCAd(anchor) {
		if (!TOCAd) {
			return;
		}
		var target = $(anchor).next('.section').find('.steps_list_2 > li:first');
		if (!target.length) {
			return;
		}
		target.append($(TOCAd.adElement));
		TOCAd.load();
		TOCAd = null;
	}

    function addBodyAd(id) {
        var element = document.getElementById(id);
        var ad = new BodyAd(element);

		// check if ad is disabled for this size screen
		// TODO could keep a list of disabled ads and try them if screen size changes
		if (ad.disabled) {
			return;
		}
		if (ad.type == 'rightrail') {
			ad.last = true;
			if (rightRailElements.length > 0) {
				rightRailElements[rightRailElements.length -1].last = false;
			}
			rightRailElements.push(ad);
		} else if (ad.type == 'toc') {
			TOCAd = ad;
			ad.adElement.style.display = "none";
		} else if (ad.type == 'scrollto') {
			scrollToAd = new ScrollToAd(element);
		} else if (ad.type == 'quiz') {
			quizAds[ad.adElement.parentElement.id] = ad;
			ad.adElement.parentElement.addEventListener("change", function(e) {
				var id = this.id;
				if (quizAds[id]) {
					ad.adElement.classList.remove("hidden");
					quizAds[id].load()
				}
			});
		} else {
			bodyAds.push(ad);
		}
		if (ad.service == 'dfp') {
			googletag.cmd.push(function() { googletag.display(ad.adTargetId); });;
		}
	}

	function RightRailElement(element) {
		this.adElement = element.getElementsByClassName('rr_inner')[0];
		this.element = element;
		this.height = element.offsetHeight;
	}

	function addRightRailElement(id) {
		var rightRailElement = document.getElementById(id);
		var elem = new RightRailElement(rightRailElement);
        elem.last = true;
        if (rightRailElements.length > 0) {
            rightRailElements[rightRailElements.length -1].last = false;
        }
		rightRailElements.push(elem);
	}

	function addQuizAd(id) {
		var innerAd = document.getElementById(id);
		var wrap = innerAd.parentElement;
		var ad = new QuizAd(wrap);
		var quizContainer = wrap.parentElement;
		quizAds[quizContainer.id] = ad;
		quizContainer.addEventListener("change", function(e) {
			var id = this.id;
			if (quizAds[id]) {
				quizAds[id].show()
				quizAds[id].load()
			}
		});
	}

	function getIntroAd() {
		return introAd;
	}

	return {
		'init' :init,
		'addIntroAd': addIntroAd,
		'addRightRailAd': addRightRailAd,
		'addRightRailElement': addRightRailElement,
		'addBodyAd': addBodyAd,
		'loadTOCAd': loadTOCAd,
		'getIntroAd' : getIntroAd,
		'slotRendered' : slotRendered,
		'impressionViewable' : impressionViewable,
		'apsFetchBids' : apsFetchBids,
	};

})();
WH.ads.init();