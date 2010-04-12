/*
 * applist.js
 * Kyek
 * August 5, 2008
 *
 * To be packed before release: http://dean.edwards.name/packer/
 */

var UNUSED_FILTER = 'search name or description';

var appsPerPage = 15;
var sortBy = 'newvers';
var filterBy = '';
var category = 0;
var showPage = 1;
var lastPage = 1;

function trim(str) {
	return str.replace(/^\s+|\s+$/g,'');
}

function appendApp(app) {
	$('#applistblock').append('<div class="appblock"><div class="appblock_icon">&nbsp;</div><div class="appblock_infoblock"><span class="appblock_name"></span><span class="appblock_company"></span><span class="appblock_category"></span><span class="appblock_version"></span></div></div>');
	var block = $('#applistblock').children('.appblock:last');
	var infoblock = block.children('.appblock_infoblock');
	if (app.smallicon != "false")
		block.children('.appblock_icon').html('<img src="' + app.smallicon + '" alt="' + app.name + '" />');
	if (app.name.length > 20)
		infoblock.children(".appblock_name").addClass("appblock_longname");
	else
		infoblock.children(".appblock_name").removeClass("appblock_longname");
	infoblock.children(".appblock_name").html(app.name);
	if (app.company.length > 20)
		infoblock.children(".appblock_company").addClass("appblock_longcompany");
	else
		infoblock.children(".appblock_company").removeClass("appblock_longcompany");
	infoblock.children(".appblock_company").html(app.company);
	infoblock.children(".appblock_version").html("Latest version: " + app.version);
	infoblock.children(".appblock_category").html("Category: " + app.category);
	block.click(function() {
		$.facebox({ appid: app.app_id });
	});
}

function setAppsPerPage(appspp) {
	appsPerPage = appspp;
	$(".perpage").val(appspp);
}

function setSortBy(sort) {
	sortBy = sort;
	$(".sortby").val(sort);
}

function setPage(curPage, appspp, totalapps) {
	showPage = curPage;
	lastPage = (totalapps / appspp) | 0;
	if (totalapps % appspp != 0)
		lastPage++;
	var pagelist = '';
	var i = 1;
	for (i = 1; i <= lastPage; i++)
		pagelist += '<option value="' + i + '">' + i + '</option>';
	$('.pagesel').html(pagelist).val(curPage);
	if (curPage > 1) {
		$('.nav_first').addClass('nav_first_on');
		$('.nav_prev').addClass('nav_prev_on');
	}
	else {
		$('.nav_first').removeClass('nav_first_on');
		$('.nav_prev').removeClass('nav_prev_on');
	}
	if (curPage < lastPage) {
		$('.nav_last').addClass('nav_last_on');
		$('.nav_next').addClass('nav_next_on');
	}
	else {
		$('.nav_last').removeClass('nav_last_on');
		$('.nav_next').removeClass('nav_next_on');
	}
}

function setCategory(cat, full) {
	category = cat;
	var opts = '<option value="0">All Categories</option>';
	$.each(full, function(i, item) {
		opts += '<option value="' + i + '">' + item + "</option>";
	});
	$(".category").html(opts);
	$(".category").val(cat);
}

function setFilter(filter) {
	filterBy = filter.replace(/&quot;/g, '"');
	$('.filter').val(filterBy);
	filterSwitch();
}

function setRSS(link) {
	if (link == 0)
		$('.rss_link').html('&nbsp;');
	else
		$('.rss_link').html('<a href="' + link + '" title="RSS Feed"><img src="images/rss.png" alt="rss" /><span>RSS for this listing</span></a>');
}

function filterSwitch() {
	$('.filter').each(function (i) {
		if (trim($(this).val()) == '' || $(this).val() == UNUSED_FILTER) {
			$(this).val(UNUSED_FILTER);
			$(this).css('color', '#aaa');
		}
		else
			$(this).css('color', '#000');
	});
}

function getPage() {
	var req_url = BASE_URL;
	$.getJSON(req_url, {calltype: 'ajax', call: 'applisting', perpage: appsPerPage, sort: sortBy, cat: category, page: showPage, filter: filterBy}, function(data) {
		if (data.valid == '1') {
			$('#applistblock').html('');
			setAppsPerPage(data.perpage);
			setSortBy(data.sort);
			setCategory(data.category, data.categories);
			setFilter(data.filter);
			setRSS(data.rss);
			setPage(data.page, data.perpage, data.totalapps);
			$.each(data.apps, function(i, item) {
				appendApp(item);
			});
		}
		else {
			$('#applistblock').html('<span class="error">' + data.error + '</span>');
			$('.rss_link').html('');
			setPage(1, 0, 0);
		}
	});
}

function debug(str) {
	$('#copyright').append('<br />' + str);
	return true;
}

$(document).ready(function() {
	// Break out of frames
	if (top.location.href != window.location.href)
		top.location.href = window.location.href;
		
	// Is search disabled?
	if (!search) {
		UNUSED_FILTER = "search temporarily disabled";
		$('.filter').each(function (i) {
				$(this).val(UNUSED_FILTER);
				$(this).attr("disabled", true);
		});
	}
	
	// First thing's first.  Let's get the page contents.
	getPage();

	// Hook up the page selector's autoloader.
	$('.pagesel').change(function() {
		if (showPage != $(this).val()) {
			showPage = $(this).val();
			getPage();
		}
	});
	
	// Attach the events to each of the navigation buttons
	$('.nav_first').click(function() {
		if (showPage > 1) {
			showPage = 1;
			getPage();
		}
	});
	$('.nav_prev').click(function() {
		if (showPage > 1) {
			showPage--;
			getPage();
		}
	});
	$('.nav_next').click(function() {
		if (showPage < lastPage) {
			showPage++;
			getPage();
		}
	});
	$('.nav_last').click(function() {
		if (showPage < lastPage) {
			showPage = lastPage;
			getPage();
		}
	});
	
	// Make the text field dynamic
	$('.filter').focus(function () {
		if ($(this).val() == UNUSED_FILTER)
			$(this).val('');
		$(this).css('color', '#000');
	}).blur(function () {
		filterSwitch();
	});

	// Finally, let's make our setup form work..
	$('form').submit(function() {
		showPage = 1;
		appsPerPage = $(this).children('.perpage').val();
		sortBy = $(this).children('.sortby').val();
		category = $(this).children('.category').val();
		filterBy = $(this).children('.filter').val();
		if (filterBy == UNUSED_FILTER)
			filterBy = '';
		getPage();
		return false;
	});
});