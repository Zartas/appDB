/*
 * submit.js
 * Kyek
 * August 5, 2008
 *
 * To be packed or minned before release:
 * http://dean.edwards.name/packer/
 * http://fmarcia.info/jsmin/test.html
 */

var numLinks = 1;
var lastURLEntry = '';
var curDownloadLinks = 1;
var MAX_DOWNLOADLINKS = 4;

function getStoreID(url) {
	var start = url.lastIndexOf("id=");
	if (start == -1)
		start = url.lastIndexOf("id");
	else
		start += 3;
	if (start == -1)
		return '';
	else
		start += 2;
	var end1 = url.indexOf("&", start);
	var end2 = url.indexOf("?", start);
	var end = end1 < end2 ? end1 : end2;
	if (end < 0)
		end = url.length + 1;
	if (end <= start)
		return '';
	var id = url.substring(start, end);
	var regex = /\d+/;
	if (id.search(regex) != -1)
		return id;
	else
		return '';
}

function trim(str) {
	return str.replace(/^\s+|\s+$/g,'');
}

function showForm(data) {
	var lastopt = 'unknown';
	var opts = '';
	$.each(data.allversions, function(i, ver) {
		opts += '<option value="' + ver + '">' + ver + "</option>";
		lastopt = ver;
	});
	opts += '<option value="other">Other</option>';
	$("#versionsel").append(opts).val(lastopt);
	$("#versionother").attr('readonly', 'true').addClass('disabledfield');
	$(".versionother").addClass('disabledlabel');
	$("#crackerother").attr('readonly', 'true').addClass('disabledfield');
	$(".crackerother").addClass('disabledlabel');
	$('#footer').removeClass('footerup');
	$('#fullform').css('visibility', 'visible').css('height', 'auto');
	$('#addlinkblock').css('visibility', 'visible');
}

function hideForm(data) {
	$('#fullform').css('visibility', 'hidden').css('height', '0px');
	$('#versionsel').html('<option value="unknown">Unknown</option>').val('unknown');
	$('#crackersel').val('unknown');
	$('#versionother, #cracker').val('');
	$('#addlinkblock').css('visibility', 'hidden');
	$('#footer').addClass('footerup');
}

function clearAppBlock() {
	$(".appblock_icon").html('&nbsp;');
	$(".appblock_name").html('');
	$(".appblock_company").html('');
	$(".appblock_category").html('');
	$(".appblock_version").html('');
}

function resetLinks(addVisisble) {
	$("#linksblock").empty();
	curDownloadLinks = 0;
	$("#addlinkblock").click();
}

$(document).ready(function() {
	// Hook up our kickass iTunes info autoloader
	$("#itunesurl").keyup(function () {
		if ($("#itunesurl").val() != lastURLEntry) {
			var storeID = getStoreID($("#itunesurl").val());
			if (storeID !== '') {
				$("#itunesvalid").removeClass("valid_check")
					.removeClass("valid_cross")
					.removeClass("valid_think")
					.addClass("valid_think");
				resetLinks();
				hideForm();
				clearAppBlock();
				$.getJSON(BASE_URL + "index.php", {calltype: 'ajax', call: 'itunesinfo', id: storeID, allver: 1}, function(data) {
					if (data.valid == "1") {
						if (data.smallicon != "false")
							$(".appblock_icon").html('<img src="' + data.smallicon + '" alt="' + data.appname + '" />');
						if (data.appname.length > 20)
							$(".appblock_name").addClass("appblock_longname");
						else
							$(".appblock_name").removeClass("appblock_longname");
						$(".appblock_name").html(data.appname);
						if (data.appcompany.length > 20)
							$(".appblock_company").addClass("appblock_longcompany");
						else
							$(".appblock_company").removeClass("appblock_longcompany");
						$(".appblock_company").html(data.appcompany);
						$(".appblock_category").html("Category: " + data.category);
						$(".appblock_version").html("Current version: " + data.appversion);
						$("#itunesvalid").removeClass("valid_think").addClass("valid_check");
						showForm(data);
					}
					else
						$("#itunesvalid").removeClass("valid_think").addClass("valid_cross");
				});
			}
			else {
				$("#itunesvalid").removeClass("valid_check")
					.removeClass("valid_cross")
					.removeClass("valid_think")
					.addClass("valid_cross");
				resetLinks();
				hideForm();
				clearAppBlock();
			}
		}
		lastURLEntry = $("#itunesurl").val();
	});
	
	// Cause the "other" version and cracker field to be enabled and disabled at the right times
	$("#versionsel").change(function () {
		if ($("#versionsel").val() != 'other') {
			$("#versionother").attr('readonly', 'true');
			if (!$(".versionother").hasClass('disabledlabel')) {
				$(".versionother").addClass('disabledlabel');
				$("#versionother").addClass('disabledfield');
			}
		}
		else {
			$("#versionother").removeAttr('readonly').removeClass('disabledfield');
			$(".versionother").removeClass('disabledlabel');
		}
	});
	$("#crackersel").change(function () {
		if ($("#crackersel").val() != 'other') {
			$("#crackerother").attr('readonly', 'true');
			if (!$(".crackerother").hasClass('disabledlabel')) {
				$(".crackerother").addClass('disabledlabel');
				$("#crackerother").addClass('disabledfield');
			}
		}
		else {
			$("#crackerother").removeAttr('readonly').removeClass('disabledfield');
			$(".crackerother").removeClass('disabledlabel');
		}
	});
	
	
	// Allow the adding of more download links to the form
	$("#addlinkblock").click(function () {
		curDownloadLinks++;
		if (curDownloadLinks <= MAX_DOWNLOADLINKS) {
			$("#linksblock").append('<div class="linkrow"><div class="linkblock"><label for="link' + curDownloadLinks + '" class="link">Download link ' + curDownloadLinks + '</label><input type="text" name="link' + curDownloadLinks + '" id="link' + curDownloadLinks + '" class="textfield" /><span class="linkerror"></span></div><div class="typeblock"><label for="linktype' + curDownloadLinks + '" class="linktype">Package type</label><select name="linktype' + curDownloadLinks + '" id="linktype' + curDownloadLinks + '" class="typeselect packtype"><option value="app">APP</option><option value="ipa" selected="true">IPA</option><option value="unknown">Unknown</option></select></div></div>');
			if (curDownloadLinks == MAX_DOWNLOADLINKS)
				$("#addlinkblock").css('visibility', 'hidden');
		}
	});
	
	// Set up and link AJAX form submission.
	$("form").submit(function() {
		if (!prepSend())
			return false;
		$.ajax({
			cache: false,
			data: generateSubmission(),
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown) {
					showResponse(false, textStatus);
				},
			success: showResponse,
			type: 'POST',
			url: BASE_URL + '?calltype=ajax&call=appsubmit'
		});
		return false;
	});
});

function generateSubmission() {
	var fields = {
		id: getStoreID($("#itunesurl").val()),
		versionsel: $('#versionsel').val(),
		versionother: $('#versionother').val(),
		crackersel: $('#crackersel').val(),
		crackerother: $('#crackerother').val()
	}
	var i = 1;
	for (i = 1; i <= MAX_DOWNLOADLINKS; i++) {
		if ($("#link" + i).length > 0 && trim($("#link" + i).val()) !== '') {
			fields['link' + i] = $("#link" + i).val();
			fields['linktype' + i] = $("#linktype" + i).val();
		}
		else
			break;
	}
	return fields;
}

function prepSend() {
    if ($('#versionsel').val() == 'other' && trim($('#versionother').val()) === '') {
		$('#versionother').css('background-color', '#f99');
		$('#versionother').change(function() {
			$('#versionother').css('background-color', 'auto');
		});
		$('#versionsel').change(function() {
			$('#versionother').css('background-color', 'auto');
		});
		return false;
	}
	if (trim($('#link1').val()) === '') {
		$('#linkerror1').html('You must provide a link.').addClass('linkerroractive');
		$('#link1').css('background-color', '#f99').change(function() {
			$('#link1').css('background-color', '#fff');
			$('#linkerror1').removeClass('linkerroractive').html('');
		});
		return false;
	}
	var linkhalt = false;
	for (var i = 1; i <= curDownloadLinks; i++) {
		var gotmatch = false;
		for (var q = 0; q < allowedDomains.length; q++) {
			if (trim($("#link" + i).val()) === '' || $('#link' + i).val().indexOf(allowedDomains[q]) != -1) {
				gotmatch = true;
				break;
			}
		}
		if (!gotmatch) {
			linkhalt = true;
			$('#link' + i).parent().children('.linkerror').html('Links from this domain are not allowed. <a href="?page=domains" target="_blank">See approved domains.</a>').addClass('linkerroractive');
			$('#link' + i).css('background-color', '#f99').change(function() {
				$(this).css('background-color', '#fff');
				$(this).parent().children('.linkerror').removeClass('linkerroractive').html('');
			});
		}
	}
	if (linkhalt)
		return false;
	$('#submitbutton').val("Adding application...");
	disableForm();
    return true; 
} 

function showResponse(data, statusText)  {
	if (!data)
		$('#fullform').html('<span class="formmessage formfailure">There was a problem submitting your application.  Please try again later.</span>');
    if (data.success == '1')
		$('#fullform').html('<span class="formmessage formsuccess">The application has been added!</span>');
	else
		$('#fullform').html('<span class="formmessage formfailure">' + data.errormsg + '</span>');
}

function disableForm() {
	$('input, select').attr('readonly', 'true').css('background-color', '#ccc');
	$('#submitbutton').attr('disabled', 'true').css('background-color', "#000");
}