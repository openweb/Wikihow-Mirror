this.mfModules=this.mfModules||{},this.mfModules["mobile.startup"]=(window.webpackJsonp=window.webpackJsonp||[]).push([[4],{"./src/mobile.startup/Toggler.js":function(e,t,s){var r=s("./src/mobile.startup/Browser.js").getSingleton(),n=s("./src/mobile.startup/util.js"),a=n.escapeHash,i={name:"arrow",additionalClassNames:"indicator"},o=s("./src/mobile.startup/Icon.js");function c(e){this.eventBus=e.eventBus,this._enable(e.$container,e.prefix,e.page,e.isClosed)}function l(e){var t=JSON.parse(mw.storage.get("expandedSections")||"{}");return t[e.title]=t[e.title]||{},t}function h(e){mw.storage.set("expandedSections",JSON.stringify(e))}function u(e,t,s){var r,n,a=l(s);t.find(".section-heading span").each(function(){n=t.find(this),r=n.parents(".section-heading"),a[s.title][n.attr("id")]&&!r.hasClass("open-block")&&e.toggle(r,s)})}function p(e){var t=(new Date).getTime(),s=l(e);Object.keys(s).forEach(function(e){var r=s[e];Object.keys(r).forEach(function(n){var a=r[n];Math.floor((t-a)/1e3/60/60/24)>=1&&delete s[e][n]})}),h(s)}c.prototype.toggle=function(e){var t,s=e.is(".open-block"),n=e.data("page"),a=e.next();e.toggleClass("open-block"),e.data("indicator").remove(),i.rotation=s?0:180,t=new o(i).prependTo(e),e.data("indicator",t),a.toggleClass("open-block").attr({"aria-pressed":!s,"aria-expanded":!s}),this.eventBus.emit("section-toggled",{expanded:s,page:n,isReferenceSection:Boolean(a.attr("data-is-reference-section")),$heading:e}),r.isWideScreen()||function(e,t){var s=e.find("span").attr("id"),r=e.hasClass("open-block"),n=l(t);s&&(r?n[t.title][s]=(new Date).getTime():delete n[t.title][s],h(n))}(e,n)},c.prototype.reveal=function(e,t){var s,r;try{(r=(s=t.find(a(e))).parents(".collapsible-heading")).length||(r=s.parents(".collapsible-block").prev(".collapsible-heading")),r.length&&!r.hasClass("open-block")&&this.toggle(r),r.length&&window.scrollTo(0,s.offset().top)}catch(e){}},c.prototype._enable=function(e,t,s,a){var c,l,h,m,d,f=this,g=mw.config.get("wgMFCollapseSectionsByDefault");function b(){var t=window.location.hash;0===t.indexOf("#")&&f.reveal(t,e)}c=e.find("> h1,> h2,> h3,> h4,> h5,> h6,.section-heading").eq(0).prop("tagName")||"H1",void 0===g&&(g=!0),l=!g||"true"===mw.storage.get("expandSections"),e.children(c).each(function(n){var c,u=e.find(this),p=u.find(".indicator"),d=t+"collapsible-block-"+n;u.next().is("div")&&(m=u.next("div"),c=Boolean(m.attr("data-is-reference-section")),u.addClass("collapsible-heading ").data("section-number",n).data("page",s).attr({tabindex:0,"aria-haspopup":"true","aria-controls":d}).on("click",function(e){e.target.href||(e.preventDefault(),f.toggle(u))}),i.rotation=l?180:0,h=new o(i),p.length?p.replaceWith(h.$el):h.prependTo(u),u.data("indicator",h.$el),m.addClass("collapsible-block").eq(0).attr({id:d,"aria-pressed":"false","aria-expanded":"false"}),function(e,t){t.on("keypress",function(s){13!==s.which&&32!==s.which||e.toggle(t)}).find("a").on("keypress mouseup",function(e){e.stopPropagation()})}(f,u),!c&&(!a&&r.isWideScreen()||l)&&f.toggle(u))}),function(){var t=mw.config.get("wgInternalRedirectTargetUrl"),s=!!t&&t.split("#")[1];s&&(window.location.hash=s,f.reveal(s,e))}(),b(),(d=e.find("a:not(.reference a)")).on("click",function(){void 0!==d.attr("href")&&d.attr("href").indexOf("#")>-1&&b()}),n.getWindow().on("hashchange",function(){b()}),!r.isWideScreen()&&s&&(u(this,e,s),p(s))},c._getExpandedSections=l,c._expandStoredSections=u,c._cleanObsoleteStoredSections=p,e.exports=c},"./src/mobile.startup/mobile.startup.js":function(e,t,s){var r=s("./src/mobile.startup/moduleLoaderSingleton.js"),n=s("./src/mobile.startup/search/schemaMobileWebSearch.js");e.exports={moduleLoader:r,mfExtend:s("./src/mobile.startup/mfExtend.js"),context:s("./src/mobile.startup/context.js"),time:s("./src/mobile.startup/time.js"),util:s("./src/mobile.startup/util.js"),View:s("./src/mobile.startup/View.js"),PageGateway:s("./src/mobile.startup/PageGateway.js"),Browser:s("./src/mobile.startup/Browser.js"),Button:s("./src/mobile.startup/Button.js"),Icon:s("./src/mobile.startup/Icon.js"),ReferencesDrawer:s("./src/mobile.startup/references/ReferencesDrawer.js"),ReferencesGateway:s("./src/mobile.startup/references/ReferencesGateway.js"),ReferencesHtmlScraperGateway:s("./src/mobile.startup/references/ReferencesHtmlScraperGateway.js"),ReferencesMobileViewGateway:s("./src/mobile.startup/references/ReferencesMobileViewGateway.js"),icons:s("./src/mobile.startup/icons.js"),Page:s("./src/mobile.startup/Page.js"),Anchor:s("./src/mobile.startup/Anchor.js"),Skin:s("./src/mobile.startup/Skin.js"),OverlayManager:s("./src/mobile.startup/OverlayManager.js"),Overlay:s("./src/mobile.startup/Overlay.js"),loadingOverlay:s("./src/mobile.startup/loadingOverlay.js"),CtaDrawer:s("./src/mobile.startup/CtaDrawer.js"),toast:s("./src/mobile.startup/toast.js"),Watchstar:s("./src/mobile.startup/watchstar/Watchstar.js"),rlModuleLoader:s("./src/mobile.startup/rlModuleLoader.js"),eventBusSingleton:s("./src/mobile.startup/eventBusSingleton.js"),Toggler:s("./src/mobile.startup/Toggler.js"),toc:{TableOfContents:s("./src/mobile.startup/toc/TableOfContents.js")},search:{SearchOverlay:s("./src/mobile.startup/search/SearchOverlay.js"),MobileWebSearchLogger:s("./src/mobile.startup/search/MobileWebSearchLogger.js"),SearchGateway:s("./src/mobile.startup/search/SearchGateway.js")},lazyImages:{lazyImageLoader:s("./src/mobile.startup/lazyImages/lazyImageLoader.js")},talk:{overlay:s("./src/mobile.startup/talk/overlay.js")},languageOverlay:s("./src/mobile.startup/languageOverlay/languageOverlay.js")},mw.mobileFrontend=r,r.define("mobile.startup",e.exports),n.subscribeMobileWebSearchSchema()},"./src/mobile.startup/references/ReferencesDrawer.js":function(e,t,s){var r=s("./src/mobile.startup/Drawer.js"),n=s("./src/mobile.startup/util.js"),a=s("./src/mobile.startup/icons.js"),i=s("./src/mobile.startup/mfExtend.js"),o=s("./src/mobile.startup/references/ReferencesGateway.js"),c=s("./src/mobile.startup/Icon.js");function l(e){r.call(this,n.extend({className:"drawer position-fixed text references",events:{"click sup a":"showNestedReference"}},e))}i(l,r,{defaults:n.extend({},r.prototype.defaults,{spinner:a.spinner().toHtmlString(),cancelButton:a.cancel("gray").toHtmlString(),citation:new c({isSmall:!0,name:"citation",additionalClassNames:"text",hasText:!0,label:mw.msg("mobile-frontend-references-citation")}).toHtmlString(),errorClassName:new c({name:"error",hasText:!0,isSmall:!0}).getClassName()}),show:function(){return r.prototype.show.apply(this,arguments)},template:mw.template.get("mobile.startup","ReferencesDrawer.hogan"),closeOnScroll:!1,postRender:function(){var e=n.getWindow().height();r.prototype.postRender.apply(this),e/2<400&&this.$el.css("max-height",e/2),this.on("show",this.onShow.bind(this)),this.on("hide",this.onHide.bind(this))},onShow:function(){n.getDocument().find("body").addClass("drawer-enabled")},onHide:function(){n.getDocument().find("body").removeClass("drawer-enabled")},showReference:function(e,t,s){var r=this,n=this.options.gateway;return this.options.page=t,r.show(),n.getReference(e,t).then(function(e){r.render({title:s,text:e.text})},function(e){e===o.ERROR_NOT_EXIST?r.hide():r.render({error:!0,title:s,text:mw.msg("mobile-frontend-references-citation-error")})})},showNestedReference:function(e){var t=this.$el.find(e.target);return this.showReference(t.attr("href"),this.options.page,t.text()),!1}}),e.exports=l},"./src/mobile.startup/search/MobileWebSearchLogger.js":function(e,t){function s(){this.userSessionToken=null,this.searchSessionToken=null}s.prototype={_newUserSession:function(){this.userSessionToken=mw.user.generateRandomSessionId()},_newSearchSession:function(){this.searchSessionToken=mw.user.generateRandomSessionId(),this.searchSessionCreatedAt=(new Date).getTime()},onSearchShow:function(){this._newUserSession()},onSearchStart:function(){this._newSearchSession(),mw.track("mf.schemaMobileWebSearch",{action:"session-start",userSessionToken:this.userSessionToken,searchSessionToken:this.searchSessionToken,timeOffsetSinceStart:0})},onSearchResults:function(e){var t=(new Date).getTime()-this.searchSessionCreatedAt;mw.track("mf.schemaMobileWebSearch",{action:"impression-results",resultSetType:"prefix",numberOfResults:e.results.length,userSessionToken:this.userSessionToken,searchSessionToken:this.searchSessionToken,timeToDisplayResults:t,timeOffsetSinceStart:t})},onSearchResultClick:function(e){var t=(new Date).getTime()-this.searchSessionCreatedAt;mw.track("mf.schemaMobileWebSearch",{action:"click-result",clickIndex:e.resultIndex+1,userSessionToken:this.userSessionToken,searchSessionToken:this.searchSessionToken,timeOffsetSinceStart:t})}},s.register=function(e){var t=new s;e.on("search-show",t.onSearchShow.bind(t)),e.on("search-start",t.onSearchStart.bind(t)),e.on("search-results",t.onSearchResults.bind(t)),e.on("search-result-click",t.onSearchResultClick.bind(t))},e.exports=s},"./src/mobile.startup/search/SearchOverlay.js":function(e,t,s){var r=s("./src/mobile.startup/mfExtend.js"),n=s("./src/mobile.startup/Overlay.js"),a=s("./src/mobile.startup/util.js"),i=s("./src/mobile.startup/Anchor.js"),o=s("./src/mobile.startup/Icon.js"),c=s("./src/mobile.startup/watchstar/WatchstarPageList.js"),l=mw.config.get("wgCirrusSearchFeedbackLink");function h(e){var t=a.extend({isBorderBox:!1,className:"overlay search-overlay",events:{"input input":"onInputInput","click .clear":"onClickClear","click .search-content":"onClickSearchContent","click .overlay-content":"onClickOverlayContent","click .overlay-content > div":"onClickOverlayContentDiv","touchstart .results":"hideKeyboardOnScroll","mousedown .results":"hideKeyboardOnScroll","click .results a":"onClickResult"}},e);n.call(this,t),this.api=t.api,this.gateway=new t.gatewayClass(this.api),this.router=t.router}r(h,n,{templatePartials:a.extend({},n.prototype.templatePartials,{header:mw.template.get("mobile.startup","search/SearchHeader.hogan"),content:mw.template.get("mobile.startup","search/SearchContent.hogan"),icon:o.prototype.template}),defaults:a.extend({},n.prototype.defaults,{headerChrome:!0,clearIcon:new o({tagName:"button",name:"search-clear",isSmall:!0,label:mw.msg("mobile-frontend-clear-search"),additionalClassNames:"clear"}).options,searchContentIcon:new o({tagName:"a",href:"#",name:"search-content",label:mw.msg("mobile-frontend-search-content")}).options,searchTerm:"",placeholderMsg:"",noResultsMsg:mw.msg("mobile-frontend-search-no-results"),searchContentNoResultsMsg:mw.message("mobile-frontend-search-content-no-results").parse(),action:mw.config.get("wgScript"),feedback:!!l&&{feedback:new i({label:mw.msg("mobile-frontend-search-feedback-link-text"),href:l}).options,prompt:mw.msg("mobile-frontend-search-feedback-prompt")}}),onInputInput:function(){this.performSearch(),this.$clear.toggle(""!==this.$input.val())},onClickClear:function(){return this.$input.val("").trigger("focus"),this.performSearch(),this.$clear.hide(),!1},onClickSearchContent:function(){var e=a.getDocument().find("body"),t=this.$el.find("form");this.parseHTML("<input>").attr({type:"hidden",name:"fulltext",value:"search"}).appendTo(t),setTimeout(function(){t.appendTo(e),t.trigger("submit")},0)},onClickOverlayContent:function(){this.$el.find(".cancel").trigger("click")},onClickOverlayContentDiv:function(e){e.stopPropagation()},hideKeyboardOnScroll:function(){this.$input.trigger("blur")},onClickResult:function(e){var t=this.$el.find(e.currentTarget),s=t.closest("li");this.emit("search-result-click",{result:s,resultIndex:this.$results.index(s),originalEvent:e}),e.preventDefault(),this.router.back().then(function(){window.location.href=t.attr("href")})},postRender:function(){var e,t=this;function s(){t.$spinner.hide(),clearTimeout(e)}n.prototype.postRender.call(this),this.$input=this.$el.find("input"),this.$clear=this.$el.find(".clear"),this.$searchContent=this.$el.find(".search-content").hide(),this.$searchFeedback=this.$el.find(".search-feedback").hide(),this.$resultContainer=this.$el.find(".results-list-container"),this.$spinner=this.$el.find(".spinner-container"),this.on("search-start",function(r){e&&s(),e=setTimeout(function(){t.$spinner.show()},2e3-r.delay)}),this.on("search-results",s),""===t.$input.val()&&this.$clear.hide()},showKeyboard:function(){var e=this.$input.val().length;this.$input.trigger("focus"),this.$input[0].setSelectionRange&&this.$input[0].setSelectionRange(e,e)},show:function(){n.prototype.show.apply(this,arguments),this.showKeyboard(),this.emit("search-show")},hide:function(){var e=this;return a.getDocument().hasClass("animations")?(e.$el.addClass("fade-out"),setTimeout(function(){n.prototype.hide.apply(e,arguments)},500)):n.prototype.hide.apply(e,arguments),!0},performSearch:function(){var e=this,t=this.api,s=this.$input.val(),r=this.gateway.isCached(s)?0:300;s!==this.lastQuery&&(e._pendingQuery&&e._pendingQuery.abort(),clearTimeout(this.timer),s.length?this.timer=setTimeout(function(){var n;e.emit("search-start",{query:s,delay:r}),n=e.gateway.search(s),e._pendingQuery=n.then(function(s){s&&s.query===e.$input.val()&&(e.$el.toggleClass("no-results",0===s.results.length),e.$searchContent.show().find("p").hide().filter(s.results.length?".with-results":".without-results").show(),new c({api:t,funnel:"search",pages:s.results,el:e.$resultContainer}),e.$results=e.$resultContainer.find("li"),e.emit("search-results",{results:s.results}))}).promise({abort:function(){n.abort()}})},r):e.resetSearch(),this.lastQuery=s)},resetSearch:function(){this.$spinner.hide(),this.$searchContent.hide(),this.$searchFeedback.hide(),this.$resultContainer.empty()}}),e.exports=h},"./src/mobile.startup/search/schemaMobileWebSearch.js":function(e,t,s){var r=s("./src/mobile.startup/context.js");e.exports={subscribeMobileWebSearchSchema:function(){mw.loader.using(["ext.eventLogging.subscriber"]).then(function(){var e=new(0,mw.eventLog.Schema)("MobileWebSearch",mw.config.get("wgMFSchemaSearchSampleRate",.001),{platform:"mobileweb",platformVersion:r.getMode()});mw.trackSubscribe("mf.schemaMobileWebSearch",function(t,s){e.log(s)})})}}},"./src/mobile.startup/toc/TableOfContents.js":function(e,t,s){var r=s("./src/mobile.startup/View.js"),n=s("./src/mobile.startup/mfExtend.js"),a=s("./src/mobile.startup/util.js"),i=s("./src/mobile.startup/Icon.js");function o(e){r.call(this,a.extend({className:"toc-mobile",contentsMsg:mw.msg("toc")},e))}n(o,r,{templatePartials:{tocHeading:mw.template.get("mobile.startup","TableOfContentsHeading.hogan")},template:mw.template.get("mobile.startup","TableOfContents.hogan"),postRender:function(){new i({name:"toc",additionalClassNames:"toc-button"}).$el.prependTo(this.$el.find("h2"))}}),e.exports=o}},[["./src/mobile.startup/mobile.startup.js",0,1]]]);
//# sourceMappingURL=mobile.startup.js.map.json