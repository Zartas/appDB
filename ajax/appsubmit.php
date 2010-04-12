<?php
	use hydrogen\config\Config;
	use appdb\usersession\UserSession;
	use appdb\models\AppLinkModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'success' => '0',
		'errormsg' => 'Server error.  Try again soon!'
		)));
	
	if (!class_exists('hydrogen\config\Config', false))
		die("Quit trying to hack my damn code.");
	
	function returnBadRequest() {
		die(json_encode(array(
			'success' => 0,
			'errormsg' => 'Bad request.'
			)));
	}
	
	// Must be logged in
	if (!UserSession::getUserBean()) {
		die(json_encode(array(
			'success' => 0,
			'errormsg' => 'You must be logged in to submit links.'
			)));
	}
	
	// Can't be done via proxy
	require_once(__DIR__ . '/../lib/proxydetector/proxy_detector.php');
	$pd = new proxy_detector();
	if ($pd->detect()) {
		die(json_encode(array(
			'success' => 0,
			'errormsg' => 'Sorry, ' . Config::getVal('general', 'site_name') .
				' does not accept submissions from proxy servers.'
			)));
	}
	
	// Assert that we have the essential value
	if (!isset($_POST['id']) || !preg_match("/\\d+/", $_POST['id']))
		returnBadRequest();
	$itunes_id = $_POST['id'];
	
	// Get cracker
	$cracker = false;
	if (isset($_POST['crackersel'])) {
		if ($_POST['crackersel'] == 'me') {
			$bean = UserSession::getUserBean();
			$cracker = $bean->username;
		}
		else if ($_POST['crackersel'] == 'other' && isset($_POST['crackerother']) && trim($_POST['crackerother']) != '')
			$cracker = trim($_POST['crackerother']);
	}
	$cracker = str_replace(array('<', '>'), array('&lt;', '&gt;'), $cracker);
	$cracker = preg_replace('/(?i)(\.|dot)\s?(com|net|info|org|tk|co\.uk|nl|it|ru)/', '', $cracker);
	
	// Get version
	$version = (isset($_POST['versionsel']) ? trim($_POST['versionsel']) : false);
	$version = $version ? trim(preg_replace('/(?i)\(iP\w+ OS 3\S+ Tested\)/', '', $version)) : false;
	if (!$version || $version == "other") {
		$version = (isset($_POST['versionother']) ? trim($_POST['versionother']) : false);
		$version = $version ? trim(preg_replace('/(?i)\(iP\w+ OS 3\S+ Tested\)/', '', $version)) : false;
		if (!$version || $version == '')
			$version = "unknown";
		else {
			$version = str_replace('<', '&lt;', $version);
			$version = str_replace('>', '&gt;', $version);
		}
	}
	
	// Get links
	$links = array();
	for ($i = 1; $i <= 4; $i++) {
		if (isset($_POST["link$i"]))
			$links[] = $_POST["link$i"];
	}
	
	// Do it to it
	$alm = AppLinkModel::getInstance();
	$result = $alm->submit($itunes_id, $version, $cracker, $links, UserSession::getUserBean());
	switch ($result) {
		case AppLinkModel::SUBMIT_OK:
		case AppLinkModel::SUBMIT_PARTIAL_OK:
			die(json_encode(array(
				'success' => 1
				)));
		case AppLinkModel::SUBMIT_FAIL_APP_NOT_FOUND:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'The submitted app was not found in the US iTunes Store.'
				)));
		case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_APP:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'You do not have permission to submit new apps.'
				)));
		case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_LINKS:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'You do not have permission to submit new links.'
				)));
		case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_VERSION:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'You do not have permission to submit new versions.'
				)));
		case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_FREE_APP:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'You do not have permission to submit free applications.'
				)));
		case AppLinkModel::SUBMIT_FAIL_ITUNES_TIMEOUT:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'The connection to iTunes has timed out. Please try again later.'
				)));
		case AppLinkModel::SUBMIT_FAIL_NO_VALID_LINKS:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'No valid links were submitted. Try again, making sure you\'re using approved hosts and not submitting any links we already have.'
				)));
		case AppLinkModel::SUBMIT_FAIL_UNKNOWN_ERROR:
		default:
			die(json_encode(array(
				'success' => 0,
				'errormsg' => 'Submission failed.'
				)));
	}
?>
