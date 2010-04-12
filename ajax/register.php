<?php
	require_once(__DIR__ . '/../lib/recaptcha/recaptchalib.php');
	require_once(__DIR__ . '/../lib/phpmailer/class.phpmailer.php');
	use hydrogen\config\Config;
	use appdb\usersession\UserSession;
	use appdb\models\PermissionSetModel;
	use appdb\models\UserProfileModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'successful' => 0,
		'errorfields' => array(),
		'errormsg' => 'Server error. Try again soon!'
		)));
	
	if (!class_exists('hydrogen\config\Config', false))
		die("Quit trying to hack my damn code.");
	
	function getRequiredPostFields($fieldNames) {
		$varstore = $_POST;
		$v = array();
		foreach ($fieldNames as $fname) {
			if (isset($varstore[$fname]) && trim($varstore[$fname]) != '')
				$v[$fname] = $varstore[$fname];
			else
				$v[$fname] = NULL;
		}
		return $v;
	}
	
	// Let's make sure all our variables are here and defined.
	$reqFields = array(
		'name',
		'pass',
		'pass2',
		'email',
		'terms',
		'recaptcha_challenge_field',
		'recaptcha_response_field'
		);
	$v = getRequiredPostFields($reqFields);
	$errorFields = array();
	foreach ($v as $key => $var) {
		if (is_null($var))
			$errorFields[] = $key;
	}
	if ($errorFields) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'All fields are required.'
			)));
	}
		
	// And now let's make sure they're right.
	$v['name'] = trim($v['name']);
	if (strlen($v['name']) > 20 || strlen($v['name']) < 4) {
		$errorFields[] = 'name';
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'Usernames must be between 4 and 19 characters.'
			)));
	}
	if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $v['name'])) {
		$errorFields[] = 'name';
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'Usernames can only contain characters A-Z, 0-9, and the symbols ._-'
			)));
	}
	if (strlen($v['pass']) < 6 || strlen($v['pass']) > 32) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => array('pass', 'pass2'),
			'errormsg' => 'Passwords must be between 6 and 32 characters.'
			)));
	}
	if ($v['pass'] != $v['pass2']) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => array('pass', 'pass2'),
			'errormsg' => 'The passwords must match.'
			)));
	}
	if (!preg_match('/\\@/', $v['email']) || strlen($v['email']) > 90) {
		$errorFields[] = 'email';
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'You must enter a valid E-mail address.'
			)));
	}
	if ($v['terms'] != '1') {
		$errorfields[] = 'terms';
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'You must agree with the Terms and Conditions to register.'
			)));
	}
	
	// Make sure the captcha is right...
	$resp = recaptcha_check_answer(Config::getVal('recaptcha', 'private_key'), $_SERVER["REMOTE_ADDR"],
		$v["recaptcha_challenge_field"], $v["recaptcha_response_field"]);

	if (!$resp->is_valid) {
		$errorFields[] = 'recaptcha_response_field';
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'Incorrect CAPTCHA. Please solve again.'
			)));
	}
	
	// Are we banned?
	$psm = PermissionSetModel::getInstance();
	if($psm->getByIPAddressCached($_SERVER['REMOTE_ADDR'])) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $errorFields,
			'errormsg' => 'You have been flagged as an ' . 
				Config::getVal('general', 'site_name') . ' attacker and cannot register.'
			)));
	}
	
	// It's all good! Enter it in!
	$result = UserSession::register($v['name'], $v['pass'], $v['email'], true, $code);
	switch ($result) {
		case UserProfileModel::CREATE_OK:
			break;
		case UserProfileModel::CREATE_USERNAME_EXISTS:
			die(json_encode(array(
				'successful' => 0,
				'errorfields' => array('name'),
				'errormsg' => 'Username already exists.  Please choose another.'
				)));
		case UserProfileModel::CREATE_EMAIL_EXISTS:
			die(json_encode(array(
				'successful' => 0,
				'errorfields' => array('email'),
				'errormsg' => 'Email address is already registered.  One account per person, please.'
				)));
		default:
			die(json_encode(array(
				'successful' => 0,
				'errorfields' => array(),
				'errormsg' => 'Unknown error. Please try again later.'
				)));
	}
	
	// Now for the verification E-mail..
	$codeurl = Config::getVal('urls', 'base_url') . "/?page=login&action=verify&code=" . $code;
	
	$text_body  = "Thanks for signing up at " . Config::getVal('general', 'site_name') . ", " . $v['name'] . "!\n\n" .
		"Before you can log in, you must verify your account.  Do that by clicking the following " .
		"link, or by pasting it into your browser's address bar:\n$codeurl\n\n" .
		"If you have not registered for an account at " . Config::getVal('urls', 'base_url') . " or think you " .
		"are receiving this message in error, please ignore it.\n\n" .
		"Thanks!\n" .
		"The " . Config::getVal('general', 'site_name') . " Team";

	$html_body  = "Thanks for signing up at " . Config::getVal('general', 'site_name') . ", " . $v['name'] . "!<br /><br />" .
		"Before you can log in, you must verify your account.  Do that by clicking the following " .
		"link, or by pasting it into your browser's address bar:<br />" .
		"<a href=\"$codeurl\">$codeurl</a><br /><br />" .
		"If you have not registered for an account at " . Config::getVal('urls', 'base_url') . " or think you " .
		"are receiving this message in error, please ignore it.<br /><br />" .
		"Thanks!<br />" .
		"The " . Config::getVal('general', 'site_name') . " Team";
		
	$parsed_url = parse_url(Config::getVal('urls', 'base_url')); 
	$domain = $parsed_url['host'];
	while (preg_match('/\\..+\\./', $domain))
		$domain = substr($domain, strpos($domain, '.') + 1);

	$mail = new PHPMailer();
	$mail->isSendmail();
	$mail->From     = "noreply@" . $domain;
	$mail->FromName = Config::getVal('general', 'site_name');
	$mail->Subject  = "Verify your account";
	$mail->Body     = $html_body;
	$mail->AltBody  = $text_body;
	$mail->AddAddress($v['email'], $v['name']);

	if(!$mail->Send()) {
		$upm = UserProfileModel::getInstance();
		$upm->forceDeleteByUsername($v['name']);
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => array('email'),
			'errormsg' => 'An E-mail could not be sent to this address.  Please check it and try again.'
			)));
	}
	
	// Done and successful!
	die(json_encode(array(
		'successful' => 1,
		'code' => $code
		)));
?>