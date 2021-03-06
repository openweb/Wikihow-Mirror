/*jslint browser: true, white:true, sloppy:true*/
//our media query breakpoints (also in responsive.less)
WH.mediumScreenMinWidth = 728;
WH.largeScreenMinWidth = 975;
WH.shared = (function () {
	'use strict';

	var TOP_MENU_HEIGHT = 52,
	resizeFunctions = [],
	scrollLoadItems = {},
	scrollLoadItemQueue = [],
	scrollLoadingHandler,
	autoPlayVideo,
	autoLoad = false,
	getNow = Date.now || function() { return new Date().getTime(); },
	nv = navigator.userAgent,
	// Old fallback functionality to use to allow for speedy loading of browsers we know support webp
	webpSupport = nv.match(/Linux/) && nv.match(/Android/) || nv.match(/Opera/) || nv.match(/Chrome/) && !nv.match(/Edge/),
	webpSupportInitialized = false,
	// Note: this is the same as php global WH_CDN_VIDEO_ROOT
	videoRoot = 'https://www.wikihow.com/video',
	viewportWidth = (window.innerWidth || document.documentElement.clientWidth),
	isSmallSize = viewportWidth < WH.mediumScreenMinWidth,
	isMedSize = !isSmallSize && viewportWidth < WH.largeScreenMinWidth,
	isLargeSize = !isSmallSize && !isMedSize,
	isDesktopSize = isLargeSize,
	useBrowserNativeLazyLoading = false,
	intersectionObserverRootMargin = "0px 0px 100% 0px",
	lazyLoadingObserver = null;

	// Check for lossy webp support. Adapted from https://developers.google.com/speed/webp/faq
	function setWebpSupport(callback) {
		var img = new Image();
		img.onload = function () {
			callback((img.width > 0) && (img.height > 0));
		};
		img.onerror = function () {
			callback(false);
		};
		// Lossy feature detection
		img.src = "data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA";
	}


	function handleWebpSupport(hasWebpSupport)  {
		webpSupport = hasWebpSupport;
		webpSupportInitialized = true;
		scrollLoadItemQueue.forEach(function(id) {
			addScrollLoadItem(id);
		});
	}

	setWebpSupport(handleWebpSupport);

	if ('loading' in HTMLImageElement.prototype) {
		useBrowserNativeLazyLoading = true;
	}

	if (WH.jsFastRender == 1) {
		useBrowserNativeLazyLoading = false;
		intersectionObserverRootMargin = "0px 0px 0px 0px";
	}

	if (useBrowserNativeLazyLoading == false && "IntersectionObserver" in window) {
		lazyLoadingObserver = new IntersectionObserver(function(entries, observer) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					loadElement(entry.target);
					lazyLoadingObserver.unobserve(entry.target);
				}
			});
		}, {
			rootMargin: intersectionObserverRootMargin
		});
	}

	function getScreenSize() {
		// Get viewport width without using jQuery.
		// This method was taken and modified from stu.js
		function getViewPortWidth() {
			var d = document,
				c = d.documentElement,
				e = d.body,
				g = e && e.clientWidth,
				cw = 0;
			if (c && c.clientWidth && ('CSS1Compat' === d.compatMode || !g)) {
				cw = c.clientWidth;
			} else {
				if (g) {
					cw = e.clientWidth;
				}
			}
			return cw;
		}

		var width = getViewPortWidth();
		if (width == 0 || width >= WH.largeScreenMinWidth) {
			return 'large';
		} else if (width >= WH.mediumScreenMinWidth) {
			return 'medium';
		} else {
			return 'small';
		}
	}

	function resize() {
		if ( resizeFunctions.forEach ) {
			resizeFunctions.forEach(function(callback) {
				  callback();
			});
		}
	}
	window.onresize = resize;

	function addResizeFunction(callback) {
		resizeFunctions.push(callback);
	}

	function throttle(func, wait) {
		var timeout = null;
		var previous = 0;
		var later = function() {
			previous = 0;
			timeout = null;
			func.apply();
		};
		var throttled = function() {
			var now = getNow();
			var remaining = wait - (now - previous);
			if (remaining <= 0 || remaining > wait) {
				if (timeout) {
					clearTimeout(timeout);
					timeout = null;
				}
				previous = now;
				func.apply();
			} else if (!timeout) {
				timeout = setTimeout(later, remaining);
			}
		};
		return throttled;
	}

	/*
	 *  check if either the top or the bottom of the element is in view
	 *  taking into account header + 50% of the screen size to load before you actually see things
	 *  @param rect - the result of calling  of getBoundingClientRect() on the target element
	 *  @param viewportHeight - the current viewport height
	 */
	function isInViewport(rect, viewportHeight) {
		var screenTop = TOP_MENU_HEIGHT,
			offset = viewportHeight * 2;
		screenTop -= offset;
		viewportHeight = viewportHeight + offset;
		if (rect.top == 0 && rect.bottom == 0) {
			return false;
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
		return false;
	}

	function getBoundingRect(item) {
		var rect = item.element.getBoundingClientRect();
		var result = {top:rect.top, bottom:rect.bottom};
		var diff = item.lastTop - rect.top;
		if (diff == 0) {
			if (window.scrollY != item.lastY) {
				result.top = result.top - window.scrollY;
				result.bottom = result.bottom - window.scrollY;
			}
		}
		item.lastTop = rect.top;
		item.lastY = window.scrollY;

		return result;
	}

	function showVideoPlay(item) {
		var overlay = item.element.parentElement.getElementsByClassName('m-video-intro-over');
		if (typeof overlay !== "undefined") {
			overlay = overlay[0];
		}
		if (overlay) {
			overlay.style.visibility = 'visible';
		}
	}
	function addLoadedCallback(id, callback) {
		var item = scrollLoadItems[id];
		item.loadedCallback = callback;
	}
	// loads all scroll load items. will be called if the user prints the page
	function loadAllImages() {
		for (var i in scrollLoadItems) {
			var item = scrollLoadItems[i];
			if (item.isLoaded) {
				continue;
			}
			item.load();
		}
	}

	// loads all scroll load embed videos. will be called if we click on video in toc
	function loadAllEmbed() {
		for (var i in scrollLoadItems) {
			var item = scrollLoadItems[i];
			if (item.isLoaded) {
				continue;
			}
			if (item.element.nodeName.toLowerCase() == 'iframe') {
				item.load();
			}
		}
	}

	function updateVisibility() {
		var unloadedItems = false,
			viewportHeight = (window.innerHeight || document.documentElement.clientHeight);

		for (var i in scrollLoadItems) {
			var item = scrollLoadItems[i];
			if (item.useScrollLoader == false) {
				continue;
			}
			if (item.isLoaded) {
				continue;
			}
			var rect = getBoundingRect(item);
			if (isInViewport(rect, viewportHeight)) {
				item.load();
			}
			unloadedItems = true;
		}

		if (!unloadedItems && scrollLoadItems.length) {
			window.removeEventListener('scroll', scrollLoadingHandler);
			scrollLoadingHandler = null;
		}
	}

	addResizeFunction(updateVisibility);

	function supportsAutoplay() {
		var el = window.document.createElement('video');
		el.setAttribute('muted', '');
		el.setAttribute('playsinline', '');
		el.setAttribute('webkit-playsinline', '');
		el.muted = true;
		el.playsinline = true;
		el.webkitPlaysinline = true;
		el.setAttribute('height', '0');
		el.setAttribute('width', '0');
		el.style.position = 'fixed';
		el.style.top = 0;
		el.style.width = 0;
		el.style.height = 0;
		el.style.opacity = 0;

		try {
			var promise = el.play();
			if (promise && promise.catch) {
				promise.then(function() {
					// do nothing
				}).catch( function() {
					// do nothing
				});
			}
		} catch(ignore) {
			// ignore errors since they are allowed
		}
		return !el.paused;
	}

	function setupLoader(item) {
		if (!item) {
			return;
		}
		if (!item.element) {
			return;
		}
		// add event listener if this is supported
		if (document.addEventListener && item.finishedLoadingEvent) {
			if (item.alt) {
				item.element.alt = item.alt;
			}
			item.loaderRemoved = false;
			var loader = document.createElement("div");
			loader.className = 'loader';
			for (var i = 0; i < 3; i++) {
				var loaderDot = document.createElement("div");
				loaderDot.className = 'loader-dot';
				loader.appendChild(loaderDot);
			}

			var loadingContainer = document.createElement("div");
			loadingContainer.className = 'loading-container';
			loadingContainer.appendChild(loader);
			item.element.parentElement.insertAdjacentElement('afterbegin', loadingContainer);
			item.element.addEventListener(item.finishedLoadingEvent, function() {
				if (item.loadedCallback) {
					item.loadedCallback();
				}
				if (item.loaderRemoved == false ) {
					this.parentElement.removeChild(loadingContainer);
					item.loaderRemoved = true;
					if (typeof item.element.classList !== "undefined") {
						item.element.classList.remove('img-loading-hide');
					}
				}
			} );
		} else {
			if (typeof item.element.classList !== "undefined") {
				item.element.classList.remove('img-loading-hide');
			}
		}
	}

	function ScrollLoadElement(element) {
		this.lastTop = element.getBoundingClientRect().top;
		this.lastY = window.scrollY;
		this.isLoaded = false;
		this.isVisible = false;
		this.element = element;
		this.load = function() {};
	}

	function getCompressedImageSrc( src ) {
		if ( !src ) {
			return '';
		}
		if ( !webpSupport ) {
			return src;
		}
		var ext = src.split('.').pop();
		if (
			( ext === 'jpg' || ext === 'png' ) &&
			src.match(/images(_[a-z]{2})?\/thumb\//) &&
			!src.match(/(\.[a-zA-Z]+){2}$/)
		) {
			src = src + '.webp';
		}
		return src;
	}

	function ScrollLoadIframe( element ) {
		ScrollLoadElement.call( this, element );
		this.src = element.getAttribute( 'data-src' );
		this.load = function () {
			this.element.setAttribute( 'src', this.src );
			this.isLoaded = true;
		};
	}

	function ScrollLoadImage( element ) {
		ScrollLoadElement.call( this, element );
		this.alt = element.alt;
		element.alt = '';
		this.finishedLoadingEvent = 'load';
		this.src = element.getAttribute( 'data-src' );
		// detect if we are on an ipad (in the same way that the old defer code does
		if (isDesktopSize) {
			// look for the data-srclarge and use it if it is set
			var large = element.getAttribute( 'data-srclarge' );
			if (large != null) {
				this.src = large;
			}
		}

		this.src = getCompressedImageSrc( this.src );
		if ( element && element.classList !== undefined) {
			element.classList.add( 'img-loading-hide' );
		}

		this.load = function () {
			this.element.setAttribute( 'src', this.src );
			this.isLoaded = true;
			setupLoader( this );
		};
	}

	function ScrollLoadVideo(element) {
		ScrollLoadElement.call(this, element);
		this.finishedLoadingEvent = 'loadeddata';
		this.isPlaying = false;
		this.src = videoRoot + this.element.getAttribute('data-src');
		this.poster = this.element.getAttribute('data-poster');
		this.poster = getCompressedImageSrc(this.poster);
		if (this.poster && this.poster.split('.').pop() == 'jpg' &&  webpSupport) {
			this.poster = this.poster + '.webp';
		}
		this.noAutoplay = this.element.getAttribute('data-noautoplay');
		this.play = function() {
			this.element.play();
			this.isPlaying = true;
		};
		this.pause = function() {
			this.element.pause();
			this.isPlaying = false;
		};
		this.load = function() {
			this.element.setAttribute('poster', this.poster);
			if (autoPlayVideo && !this.noAutoplay && (isDesktopSize || window.wgIsMainPage === true)) {
				this.element.setAttribute('src', this.src);
				this.play();
			} else {
				this.finishedLoadingEvent = null;
			}
			this.isLoaded = true;
			if (!this.noAutoplay) {
				setupLoader(this);
			}
		};
	}

	function useIntersectionObserver() {
		if (lazyLoadingObserver == null) {
			return false;
		}
		return true;
	}

	function setBrowserNativeLazyLoading(item) {
		item.element.classList.remove( 'img-loading-hide' );
		item.element.setAttribute('loading', 'lazy');
		item.load();
	}

	function queueScrollLoadItem(id) {
		scrollLoadItemQueue.push(id);
	}

	function addScrollLoadItem(id) {
		var el = document.getElementById(id);
		if (!el) {
			return;
		}

		if (!webpSupport && !webpSupportInitialized) {
			queueScrollLoadItem(id);
			return;
		}

		var item = null;
		var useObserver = useIntersectionObserver();
		if (el.nodeName.toLowerCase() === 'img') {
			item = new ScrollLoadImage(el);
			if (useObserver) {
				item.useScrollLoader = false;
				lazyLoadingObserver.observe(item.element);
			} else if (useBrowserNativeLazyLoading == true) {
				item.useScrollLoader = false;
				setBrowserNativeLazyLoading(item);
			}
		} else if (el.nodeName.toLowerCase() === 'video') {
			item = new ScrollLoadVideo(el);
			if (useObserver) {
				item.useScrollLoader = false;
				lazyLoadingObserver.observe(item.element);
			}
		} else if (el.nodeName.toLowerCase() === 'iframe') {
			item = new ScrollLoadIframe(el);
			if (useObserver) {
				item.useScrollLoader = false;
				lazyLoadingObserver.observe(item.element);
			}
		} else {
			// unknown type of item
			return;
		}
		if (item) {
			scrollLoadItems[item.element.id] = item;
		}

		// set padding top on the parent spacer element
		var width = el.getAttribute('data-width') || el.getAttribute('width');
		var height = el.getAttribute('data-height') || el.getAttribute('height');
		if (width > 0) {
			el.parentElement.style['paddingTop'] = ((height / width) * 100) + '%';
		}

		if (!useObserver) {
			updateVisibility();
		}
		if (autoLoad) {
			item.load();
		} else {
			if (!scrollLoadingHandler && !useObserver) {
				scrollLoadingHandler = throttle(updateVisibility, 500);
				window.addEventListener('scroll', scrollLoadingHandler);
			}
		}
	}

	function addScrollLoadItemByElement(element) {
		var id = element.id;
		if (!id) {
			id = 'id-' + Math.random().toString(36).substr(2, 16);
		}
		element.id = id;
		addScrollLoadItem(id);
	}

	autoPlayVideo = supportsAutoplay();
	scrollLoadingHandler = throttle(updateVisibility, 500);
	if (window.addEventListener) {
		window.addEventListener('scroll', scrollLoadingHandler);
	} else {
		autoLoad = true;
	}

	// finds the ScrollLoad item matching the element and loads it
	function loadElement(element) {
		var id = element.id;
		var item = scrollLoadItems[id];
		item.load()
	}


	return {
		'isDesktopSize' : isDesktopSize,
		'isSmallSize' : isSmallSize,
		'isMedSize' : isMedSize,
		'isLargeSize' : isLargeSize,
		'getScreenSize' : getScreenSize,
		'throttle' : throttle,
		'TOP_MENU_HEIGHT' : TOP_MENU_HEIGHT,
		'autoPlayVideo' : autoPlayVideo,
		'webpSupport' : webpSupport,
		'addScrollLoadItem' : addScrollLoadItem,
		'addScrollLoadItemByElement' : addScrollLoadItemByElement,
		'videoRoot' : videoRoot,
		'setupLoader' : setupLoader,
		'addResizeFunction' : addResizeFunction,
		'loadAllImages' : loadAllImages,
		'loadAllEmbed' : loadAllEmbed,
		'addLoadedCallback' : addLoadedCallback,
		'showVideoPlay' : showVideoPlay,
		'getCompressedImageSrc' : getCompressedImageSrc
	};

}());
