<?php

require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\models\UserProfileModel;
use appdb\models\BannedIPModel;
use appdb\usersession\UserSession;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;

// Open up our session
UserSession::open_session();

if (!($ubean = UserSession::getUserBean()))
	die('Hey. Log in.');

// Are we allowed in?
$group = $ubean->getMapped('group');
if ($group->group_name != 'Administrator' && $group->group_name != 'Moderator')
	die('Your permissions suck.');
	
$banid = isset($_GET['banid']) ? $_GET['banid'] : false;
if (!$banid)
	die("You must specify a banid.");
if ($banid != $banid + 0)
	die("That's not a user ID.");
	
$upm = UserProfileModel::getInstance();
$user = $upm->getByUserID($banid);
if (!$user)
	die('You screwed up. There\'s no user with that ID.</body></html>');
echo "Banning $user->username:<br />\n";

$ipstoban = array();
if ($user->reg_ip != 0)
	$ipstoban[] = $user->reg_ip;
if (!is_null($user->last_ip) && $user->last_ip)
	$ipstoban[] = $user->last_ip;

// And now, let's get all known IPs
$query = new Query('SELECT');
$query->field('submitter_ip');
$query->from('links');
$query->where('submitter_id = ?', $banid);
$stmt = $query->prepare();
$stmt->execute();
while ($obj = $stmt->fetchObj())
	$ipstoban[] = $obj->submitter_ip;

// Filter out the dupes and query to see which are already banned
$ipadded = array();
$toban = array();
$bipm = BannedIPModel::getInstance();
foreach ($ipstoban as $ip) {
	if (!isset($ipadded[$ip])) {
		$ipadded[$ip] = true;
		if (!$bipm->isBanned($ip)) {
			$toban[] = $ip;
			echo "Banning $ip...<br />\n";
		}
		else
			echo "IP $ip already banned.<br />\n";
	}
}

// Do it to it
if ($toban) {
	$bipm->banIPs($user->name, $toban);
	echo "Done.<br />\n";
}
else
	echo "No IPs to be banned.<br />\n";

echo "Now banning account...<br />\n";
$user->permission_override_id = 10;
$user->update();

echo 'Banned.  Clearing the user cache to make all the changes real...<br />' . "\n";
$cm = RECacheManager::getInstance();
$cm->clearGroup('users');
echo 'Done.';

?>