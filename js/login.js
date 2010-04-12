/*
 * login.js
 * Kyek
 * August 27, 2008
 *
 * Login and registration script for Appulo.us
 */

function debug(msg) {
	$('#copyright').append('<br />' + msg);
}

function trim(str) {
	return str.replace(/^\s+|\s+$/g,'');
}

function createCaptcha() {
	Recaptcha.create(RECAPTCHA_KEY, "recaptcha", { theme: "blackglass" });
}

function disableRegistrationForm() {
	$('#reguser').attr('readonly', 'true');
	$('#regpass').attr('readonly', 'true');
	$('#regpass2').attr('readonly', 'true');
	$('#regemail').attr('readonly', 'true');
	$('#agreeterms').attr('readonly', 'true');
	$('#recaptcha_response_field').attr('readonly', 'true');
	$('#regsubmit').attr('disabled', 'true');
}

function enableRegistrationForm() {
	$('#reguser').removeAttr('readonly');
	$('#regpass').removeAttr('readonly');
	$('#regpass2').removeAttr('readonly');
	$('#regemail').removeAttr('readonly');
	$('#agreeterms').removeAttr('readonly');
	$('#recaptcha_response_field').removeAttr('readonly');
	$('#regsubmit').removeAttr('disabled');
}

function disableLoginForm() {
	$('#loginuser').attr('readonly', 'true');
	$('#loginpass').attr('readonly', 'true');
	$('#loginsubmit').attr('disabled', 'true');
}

function enableLoginForm() {
	$('#loginuser').removeAttr('readonly');
	$('#loginpass').removeAttr('readonly');
	$('#loginsubmit').removeAttr('disabled');
}

$(document).ready(function() {
	if (!VERIFYING) {
		// Show the reCAPTCHA
		createCaptcha();

		// Set up and link AJAX form submission for registration.
		$("#register").submit(function() {
			if (!prepRegister())
				return false;
			$.ajax({
				cache: false,
				data: generateRegisterSubmission(),
				dataType: 'json',
				error: function(XMLHttpRequest, textStatus, errorThrown) {
						showRegisterResponse(false, textStatus);
					},
				success: showRegisterResponse,
				type: 'POST',
				url: BASE_URL + '?calltype=ajax&call=register'
			});
			return false;
		});
	}
	
	// Set up and link AJAX form submission for login.
	$("#login").submit(function() {
		if (!prepLogin())
			return false;
		$.ajax({
			cache: false,
			data: generateLoginSubmission(),
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown) {
					showLoginResponse(false, textStatus);
				},
			success: showLoginResponse,
			type: 'POST',
			url: BASE_URL + '?calltype=ajax&call=login'
		});
		return false;
	});
});

function generateRegisterSubmission() {
	var fields = {
		name: trim($('#reguser').val()),
		pass: $('#regpass').val(),
		pass2: $('#regpass2').val(),
		email: trim($('#regemail').val()),
		terms: new Boolean($('#agreeterms').attr("checked")) ? '1' : '0',
		recaptcha_challenge_field: $("input[name='recaptcha_challenge_field']").val(),
		recaptcha_response_field: $("input[name='recaptcha_response_field']").val()
	}
	return fields;
}

function prepRegister() {
	var reqFields = ['reguser', 'regpass', 'regpass2', 'regemail', 'recaptcha_response_field'];
	var actionTaken = false;
	for (var i in reqFields) {
		if (trim($('#' + reqFields[i]).val()) == '') {
			$('#' + reqFields[i]).css('background-color', '#f99').change(function() {
				$('#' + reqFields[i]).css('background-color', 'auto');
			});
			actionTaken = true;
		}
	}
	if (actionTaken)
		return false;
	if ($('#regpass').val() != $('#regpass2').val()) {
		$('#regpass2').css('background-color', '#f99').change(function() {
			$('#regpass2').css('background-color', 'auto');
		});
		return false;
	}
	if (new Boolean($('#agreeterms').attr("checked")) == false) {
		$('#agreeterms').css('background-color', '#f99').change(function() {
			$('#agreeterms').css('background-color', 'auto');
		});
		return false;
	}
	disableRegistrationForm();
    return true; 
}

function showRegisterResponse(data, statusText)  {
	if (!data) {
		$('#regblock').css('height', 'auto');
		$('#regerror').html('There was an error submitting your registration.  Please wait a few minutes and try again.').addClass('regerror');
		enableRegistrationForm();
		createCaptcha();
	}
	else if (data.successful == '1') {
		$('#regblock').css('height', '380px');
		$('#regblock').html('<span class="registersuccess">Success!  A verification E-mail is being sent.  You must click the link in this E-mail before you can log in.</span><span class="spambox">Didn\'t get the E-mail? Make sure it\'s not in your spam box.</span>');
	}
	else {
		$.each(data.errorfields, function(i, item) {
			$("input[name='" + item + "']").css('background-color', '#f99').change(function() {
				$(this).css('background-color', 'auto');
			});
		});
		$('#regblock').css('height', 'auto');
		$('#regerror').html(data.errormsg).addClass('regerror');
		enableRegistrationForm();
		createCaptcha();
	}
}

function generateLoginSubmission() {
	var fields = {
		username: trim($('#loginuser').val()),
		password: $('#loginpass').val(),
		rememberme: new Boolean($('#rememberme').attr("checked")) ? '1' : '0',
		verifying: '0'
	}
	if (VERIFYING) {
		fields.verifying = '1';
		fields.code = $("input[name='code']").val();
	}
	return fields;
}

function prepLogin() {
	var reqFields = ['loginuser', 'loginpass'];
	var actionTaken = false;
	for (var i in reqFields) {
		if (trim($('#' + reqFields[i]).val()) == '') {
			$('#' + reqFields[i]).css('background-color', '#f99').change(function() {
				$('#' + reqFields[i]).css('background-color', 'auto');
			});
			actionTaken = true;
		}
	}
	if (actionTaken)
		return false;
	disableLoginForm();
    return true; 
}

function showLoginResponse(data, statusText)  {
	if (!data) {
		$('#logblock').css('height', 'auto');
		$('#loginerror').html('There was an error logging in.  Please wait a few minutes and try again.').addClass('loginerror');
		enableLoginForm();
	}
	else if (data.successful == '1') {
		$('#logblock').css('height', '152px');
		if (VERIFYING)
			$('#logblock').html('<span class="verifysuccess">Your account has been verified!</span>');
		else
			$('#logblock').html('<span class="loginsuccess">You are being logged in.</span>');
		setTimeout("redirectToMain()", 1000);
	}
	else {
		$.each(data.errorfields, function(i, item) {
			$("input[name='" + item + "']").css('background-color', '#f99').change(function() {
				$(this).css('background-color', 'auto');
			});
		});
		$('#logblock').css('height', 'auto');
		$('#loginerror').html(data.errormsg).addClass('loginerror');
		enableLoginForm();
	}
}

function redirectToMain() {
	window.location = BASE_URL;
}