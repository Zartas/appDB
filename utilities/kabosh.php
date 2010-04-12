<?php

require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\models\UserProfileModel;
use appdb\models\AppLinkModel;
use appdb\models\BannedIPModel;
use appdb\sqlbeans\PermissionSetModel;
use appdb\usersession\UserSession;
use hydrogen\database\Query;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\recache\RECacheManager;

function endPage($msg) {
	die("$msg\n</body>\n</html>");
}

// Open up our session
UserSession::open_session();

if (!($ubean = UserSession::getUserBean()))
	die('Hey. Log in.');

// Are we allowed in?
$group = $ubean->getMapped('group');
if ($group->group_name != 'Administrator' && $group->group_name != 'Moderator')
	die('Your permissions suck.');
?>
<html>
<head>
	<title>Holy crap is this a freaking MODERATOR TOOL!?</title>
</head>
<body>
<?php
if (isset($_POST['submitid']) && isset($_POST['uid'])) {
	$upm = UserProfileModel::getInstance();
	$alm = AppLinkModel::getInstance();
	$user = $upm->getByUserID($_POST['uid']);
	if (!$user)
		die('You screwed up. There\'s no user with that ID.</body></html>');
	$count = $alm->countLinksBySubmitterID($_POST['uid']);
?>
Delete all <strong><?php echo $count; ?></strong> links submitted by 
<strong><?php echo $user->username . '(' . ((int)$_POST['uid']) . ')'; ?></strong>?<br />
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="uid" value="<?php echo ((int)$_POST['uid']); ?>" />
	<input type="submit" name="submitfull" value="Yes, Kabosh" />
	<input type="submit" name="cancel" value="No, I fucked up :(" />
</form>
<?php
}
else if (isset($_POST['submitfull']) && isset($_POST['uid'])) {
	$upm = UserProfileModel::getInstance();
	$user = $upm->getByUserID($_POST['uid']);
	if (!$user)
		die('You screwed up. There\'s no user with that ID.</body></html>');
	$banid = ((int)$_POST['uid']);
	echo "Deleting the links...<br />\n";
	$query = new Query('UPDATE');
	$query->table('links');
	$query->set('active = ?', 0);
	$query->where('submitter_id = ?', $banid);
	$stmt = $query->prepare();
	$stmt->execute();
	echo "All of user $user->username's links have been deleted.<br />\n";
	echo "Banning the dickhead...<br />\n";

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
	
	echo 'Banned.  Clearing the user and applist caches to make all the changes real...<br />' . "\n";
	$cm = RECacheManager::getInstance();
	$cm->clearGroup('users');
	$cm->clearGroup('applinks');
	echo 'Done.';
}
else {
?>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<label for="uid">User ID:</label>
	<input type="text" id="uid" name="uid" /><br />
	<input type="submit" name="submitid" value="Kabosh" />
</form>
<?php
}
?>
</body>
</html>