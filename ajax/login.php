<?php
	use hydrogen\config\Config;
	use appdb\usersession\UserSession;
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
		'username',
		'password',
		'rememberme',
		'verifying'
		);
	$v = getRequiredPostFields($reqFields);
	$jsonErrorFields = array();
	foreach ($v as $key => $var) {
		if (is_null($var))
			$jsonErrorFields[] = $key;
	}
	if ($jsonErrorFields) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $jsonErrorFields,
			'errormsg' => 'All fields are required.'
			)));
	}
	$v['code'] = false;
	if ($v['verifying'] == '1' && (!isset($_POST['code']) || strlen($_POST['code']) != 32)) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $jsonErrorFields,
			'errormsg' => 'Invalid verification link.'
			)));
	}
	else
		$v['code'] = isset($_POST['code']) ? $_POST['code'] : false;
	
	// Everything's valid, let's log in.
	if (!UserSession::login($v['username'], $v['password'], $v['rememberme'], $v['code'])) {
		die(json_encode(array(
			'successful' => 0,
			'errorfields' => $jsonErrorFields,
			'errormsg' => 'Invalid login credentials.'
			)));
	}
	else {
		die(json_encode(array(
			'successful' => 1
			)));
	}
?>