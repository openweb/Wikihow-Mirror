(function($, mw) {

function doSearch() {
	var lang = $("#lang").val();
	var aid = $("#article_id").val();
	$.ajax({
		url: '/Special:TranslationLinkOverride',
		data: { 'action': 'fetchlinks', 'id': $("#article_id").val(), 'lang': $("#lang").val() },
		dataType: 'json',
		success: function(data) {
			var txt = "";
			for (var i in data) {
				txt += "<tr><td>" + data[i].fromLang + "</td><td>" + data[i].fromID + "</td><td>" + data[i].fromURL
					+ "</td><td>" + data[i].toLang + "</td><td>" + data[i].toURL + "</td><td>" + data[i].toID
					+ "</td><td><a href=\"#\" onclick=\"window.TranslationLinkOverrideDelete('" + data[i].fromLang
					+ "'," + data[i].fromID + ",'" + data[i].toLang + "'," + data[i].toID + ")\">Delete</a></td></tr>\n";
			}
			$("#langTable tbody").html(txt);
		}
	});
}

function Delete(fromLang, fromID, toLang, toId) {
	if (confirm("Are you sure you want to delete the link (" + fromLang + "," + fromID + "," + toLang + "," + toId + ")")) {
		$.ajax({
			url: "/Special:TranslationLinkOverride",
			data: { 'action': 'dodelete', 'fromLang': fromLang, 'fromId': fromID, 'toLang': toLang, 'toId': toId },
			dataType: 'json',
			success: function(data) {
				if (data.success == true) {
					alert("Successfully deleted link");
					doSearch();
				} else {
					alert("Link already deleted");
				}
			}
		});
	}
}

function init() {
	$("#searchBtn").click(doSearch);
	window.TranslationLinkOverrideDelete = Delete;
}

init();

}(jQuery, mediaWiki));
