(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Contribute = {
		initialOften: 0,

		init: function() {
			this.randomizeOptions();
			this.clickHandlers();
		},

		clickHandlers: function() {
			$(document).on("click", ".na_often", function(e){
				e.preventDefault();
				$(".na_often").removeClass("active");
				$(this).addClass("active");
			});
			$(document).on("click", ".na_amount", function(e){
				e.preventDefault();
				$(".na_amount").removeClass("active");
				$(this).addClass("active");
				$(".na_amount_error").hide();
			});
			$(document).on("click", ".na_payment", function(e){
				e.preventDefault();
				if($(".na_amount.active").length <= 0) {
					$(".na_amount_error").show();
					return;
				}
				$(this).hide();
				$("#na_soon").show();
			});
		},

		randomizeOptions: function() {
			//randomize the time
			var howMany = $(".na_often").length;
			var randomOften = Math.floor(Math.random()*howMany);
			$(".na_often").removeClass("active");
			$($(".na_often")[randomOften]).addClass("active");
			this.initialOften = $(".na_often.active").text();
		}

	};
	$(document).ready(function() {
		WH.Contribute.init();
	});
})();
