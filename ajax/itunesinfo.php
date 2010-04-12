<?php
	use hydrogen\config\Config;
	use appdb\usersession\UserSession;
	use appdb\itunes\AppStoreScraper;
	use appdb\models\ApplicationModel;
	use appdb\models\AppCategoryModel;
	use appdb\models\AppVersionModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'valid' => '0'
		)));
	
	if (!class_exists('hydrogen\config\Config', false))
		die("Quit trying to hack my damn code.");
		
	function returnInvalid() {
		die(json_encode(array(
			'valid' => 0
			)));
	}
	
	// Requires permission: submit_new_itunes_apps
	if (!UserSession::getPermission('submit_new_itunes_apps')) {
		returnInvalid();
	}
	
	if (isset($_GET['id']) && preg_match("/\\d+/", $_GET['id'])) {
		try {
			$appinfo = new AppStoreScraper((int)$_GET['id']);
		}
		catch (InvalidITunesIDException $e) {
			returnInvalid();
		}
		catch (AppNotFoundException $e) {
			returnInvalid();
		}
		catch (TimeoutException $e) {
			returnInvalid();
		}
		$smallicon = $appinfo->getITunesID() . 'icon-57x57.png';
		$bigicon = $appinfo->getITunesID() . 'icon-100x100.png';
		$am = ApplicationModel::getInstance();
		$icons = $am->saveIconsLocally($appinfo->getIconUrlPNG(), $smallicon, $bigicon);
		$bigicon = $icons ? Config::getVal('urls', 'icon_url') . '/' . $appinfo->getITunesID() . 'icon-100x100.png' : 'false';
		$smallicon = $icons ? Config::getVal('urls', 'icon_url') . '/' . $appinfo->getITunesID() . 'icon-57x57.png' : 'false';
		$version = trim(preg_replace('/(?i)\(iP\w+ OS 3\S+ Tested\)/', '', $appinfo->getVersion()));
		$avm = AppVersionModel::getInstance();
		$allvers = $avm->getByITunesIDCached($appinfo->getITunesID());
		$verlist = array();
		$i = 0;
		foreach ($allvers as $ver) {
			$verlist["$i"] = $ver->version;
			$i++;
		}
		if (!in_array($version, $verlist))
			$verlist["$i"] = $version;
		die(json_encode(array(
			'valid' => 1,
			'appname' => $appinfo->getName(),
			'appcompany' => $appinfo->getCompany(),
			'category' => $appinfo->getCategory(),
			'appversion' => $version,
			'bigicon' => $bigicon,
			'smallicon' => $smallicon,
			'allversions' => $verlist
			)));
	}
	else
		returnInvalid();
?>