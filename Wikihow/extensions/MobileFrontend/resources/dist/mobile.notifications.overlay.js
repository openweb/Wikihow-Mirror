this.mfModules=this.mfModules||{},this.mfModules["mobile.notifications.overlay"]=(window.webpackJsonp=window.webpackJsonp||[]).push([[10],{"./src/mobile.notifications.overlay/NotificationsFilterOverlay.js":function(e,i,o){var t,n=o("./src/mobile.startup/Overlay.js"),a=o("./src/mobile.startup/util.js");o("./src/mobile.startup/mfExtend.js")(t=function(e){var i=this;n.call(this,a.extend({className:"overlay notifications-filter-overlay notifications-overlay navigation-drawer"},e)),this.on("hide",function(){e.mainMenu.closeNavigationDrawers()}),e.$crossWikiUnreadFilter.on("click",function(){i.hide()}),e.$notifReadState.find(".oo-ui-buttonElement").on("click",function(){i.hide()}),this.$el.find(".overlay-content").append(this.parseHTML("<div>").addClass("notifications-filter-overlay-read-state").append(e.$notifReadState),e.$crossWikiUnreadFilter)},n,{defaults:a.extend({},n.prototype.defaults,{heading:mw.msg("mobile-frontend-notifications-filter-title")}),preRender:function(){this.options.heading="<strong>"+mw.message("mobile-frontend-notifications-filter-title").escaped()+"</strong>"}}),e.exports=t},"./src/mobile.notifications.overlay/NotificationsOverlay.js":function(e,i,o){var t,n=o("./src/mobile.startup/Overlay.js"),a=o("./src/mobile.startup/util.js"),s=o("./src/mobile.startup/mfExtend.js"),r=o("./src/mobile.startup/Anchor.js");s(t=function(e){var i,o,t,s=this,r=a.extend({},{isBorderBox:!1,className:"overlay notifications-overlay navigation-drawer"},e),l=mw.config.get("wgEchoMaxNotificationCount"),c=new mw.echo.api.EchoApi;n.call(this,r),this.badge=r.badge,this.$overlay=this.parseHTML("<div>").addClass("notifications-overlay-overlay position-fixed"),r.error?r.onError():(mw.echo.config.maxPrioritizedActions=1,this.doneLoading=!1,o=new mw.echo.dm.UnreadNotificationCounter(c,"all",l),i=new mw.echo.dm.ModelManager(o,{type:["message","alert"]}),this.controller=new mw.echo.Controller(c,i,{type:["message","alert"]}),t=new mw.echo.ui.NotificationsWrapper(this.controller,i,{$overlay:this.$overlay}),this.markAllReadButton=new OO.ui.ButtonWidget({icon:"checkAll",title:mw.msg("echo-mark-all-as-read")}),this.markAllReadButton.toggle(!1),this.$el.find(".overlay-header").append(this.parseHTML("<div>").addClass("notifications-overlay-header-markAllRead").append(this.markAllReadButton.$element)),this.confirmationWidget=new mw.echo.ui.ConfirmationPopupWidget,this.$overlay.append(this.confirmationWidget.$element),o.connect(this,{countChange:"onUnreadCountChange"}),i.connect(this,{update:"checkShowMarkAllRead"}),this.markAllReadButton.connect(this,{click:"onMarkAllReadButtonClick"}),this.$el.find(".overlay-content").append(t.$element,this.$overlay),t.populate().then(function(){s.setDoneLoading(),s.controller.updateSeenTime(),s.badge.markAsSeen(),s.checkShowMarkAllRead()}))},n,{defaults:a.extend({},n.prototype.defaults,{heading:mw.msg("notifications"),footerAnchor:new r({href:mw.util.getUrl("Special:Notifications"),progressive:!0,additionalClassNames:"footer-link notifications-archive-link",label:mw.msg("echo-overlay-link")}).options}),setDoneLoading:function(){this.doneLoading=!0},isDoneLoading:function(){return this.doneLoading},checkShowMarkAllRead:function(){this.markAllReadButton.toggle(this.isDoneLoading()&&this.controller.manager.hasLocalUnread())},onMarkAllReadButtonClick:function(){var e=this,i=this.controller.manager.getLocalUnread().length;this.controller.markLocalNotificationsRead().then(function(){e.confirmationWidget.setLabel(mw.msg("echo-mark-all-as-read-confirmation",i)),e.confirmationWidget.showAnimated()})},onUnreadCountChange:function(e){this.badge.setCount(this.controller.manager.getUnreadCounter().getCappedNotificationCount(e)),this.checkShowMarkAllRead()},preRender:function(){this.options.heading="<strong>"+mw.message("notifications").escaped()+"</strong>"},postRender:function(){n.prototype.postRender.apply(this),(this.options.notifications||this.options.errorMessage)&&this.$el.find(".loading").remove()}}),e.exports=t},"./src/mobile.notifications.overlay/mobile.notifications.overlay.js":function(e,i,o){var t=o("./src/mobile.startup/moduleLoaderSingleton.js"),n=o("./src/mobile.notifications.overlay/NotificationsOverlay.js"),a=o("./src/mobile.notifications.overlay/NotificationsFilterOverlay.js");t.define("mobile.notifications.overlay/NotificationsFilterOverlay",a),t.define("mobile.notifications.overlay/NotificationsOverlay",n)}},[["./src/mobile.notifications.overlay/mobile.notifications.overlay.js",0,1]]]);
//# sourceMappingURL=mobile.notifications.overlay.js.map.json