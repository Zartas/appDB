<?php

require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\usersession\UserSession;
use hydrogen\recache\RECacheManager;

// Open up our session
UserSession::open_session(false);

if (!($ubean = UserSession::getUserBean()))
	die('Hey. Log in.');

// Are we allowed in?
$group = $ubean->getMapped('group');
if ($group->group_name != 'Administrator' && $group->group_name != 'Moderator')
	die('Your permissions suck.');

$reset = false;
$success = false;
if (isset($_POST['submit'])) {
	$cm = RECacheManager::getInstance();
	if (isset($_POST['apiprofiles'])) {
		$success = $cm->clearGroup('apiprofiles');
		$reset = "apiprofiles";
	}
	else if (isset($_POST['appdetails'])) {
		$success = $cm->clearGroup('appdetails');
		$reset = "appdetails";
	}
	else if (isset($_POST['applinks'])) {
		$success = $cm->clearGroup('applinks');
		$reset = "applinks";
	}
	else if (isset($_POST['applist'])) {
		$success = $cm->clearGroup('applist');
		$reset = "applist";
	}
	else if (isset($_POST['screenshots'])) {
		$success = $cm->clearGroup('screenshots');
		$reset = "screenshots";
	}
	else if (isset($_POST['users'])) {
		$success = $cm->clearGroup('users');
		$reset = "users";
	}
	else if (isset($_POST['flush'])) {
		$success = $cm->clearAll();
		$reset = "flush";
	}
}
?>
<html>
<head>
	<title>Cache Reloader</title>
	<style type="text/css">
		body {
			background-color: #ddd;
			font-family: Arial, sans-serif;
			font-size: 12px;
			color: #222;
		}
		div {
			position: absolute;
			width: 600px;
			left: 50%;
			margin: 40px 0 0 -300px;
		}
		table {
			border-collapse: collapse;
			border: 1px solid #777;
			width: 100%;
		}
		table td {
			border: 1px solid #777;
			text-align: center;
			vertical-align: top;
			padding: 20px;
			background-color: #fff;
		}
		.apiprofiles,
		.appdetails,
		.applinks {
			width: 33%;
		}
		<?php
			if ($reset)
				echo ".$reset { background-color: " . ($success ? '#7f7' : '#f77') . "; }";
		?>
	</style>
</head>
<body>
	<div>
		<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="submit" value="1" />
			<table style="border:0;">
				<tr>
					<td class="apiprofiles flush">
						<input type="submit" name="apiprofiles" value="Clear apiprofiles" />
						<p>Everything having to do with API Profile accounts.</p>
					</td>
					<td class="appdetails flush">
						<input type="submit" name="appdetails" value="Clear appdetails" />
						<p>Everything on the app details pages but links and screenshots.</p>
					</td>
					<td class="applinks flush">
						<input type="submit" name="applinks" value="Clear applinks" />
						<p>All the links for every app.</p>
					</td>
				</tr>
				<tr>
					<td class="applist flush">
						<input type="submit" name="applist" value="Clear applist" />
						<p>Every page of app listings, mobile and normal website.</p>
					</td>
					<td class="screenshots flush">
						<input type="submit" name="screenshots" value="Clear screenshots" />
						<p>All the screenshots for every app.</p>
					</td>
					<td class="users flush">
						<input type="submit" name="users" value="Clear users" />
						<p>All user profiles, permission sets, and ban rules.</p>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="flush">
						<input type="submit" name="flush" value="HOLY FUCKING SHIT EXPLODE DESTROY UNIVERSE." />
						<p>To be used only as a holy shit omg last resort.  This button will force-delete all of the above, AND LOG OUT ALL LOGGED IN USERS who didn't check the "Remember Me" box.  All of this SIMULTANEOUSLY, and with NO fallback data, causing potentially catastrophic server overload.  That is BAD NEWS.  So only do it if there's a problem and nothing else is working.</p>
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>