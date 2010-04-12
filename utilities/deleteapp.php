<?php

// Includes
require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\usersession\UserSession;
use appdb\models\ApplicationModel;

// Open up our session
UserSession::open_session();

if (!($ubean = UserSession::getUserBean()))
	die('Hey. Log in.');

// Are we allowed in?
$group = $ubean->getMapped('group');
if ($group->group_name != 'Administrator')
	die('Your permissions suck.');
	
$appid = isset($_GET['appid']) ? $_GET['appid'] : false;
if (!$appid)
	die("You must specify which app to delete.");
if ($appid != $appid + 0)
	die("That's not an App ID.");
	
echo "Deleting app #$appid... ";

$am = ApplicationModel::getInstance();
if ($am->deleteByAppID($appid))
	echo "Deleted.";
else
	echo "Failed.";
	
?>