<?php

require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\models\APIProfileModel;
use appdb\sqlbeans\APIProfileBean;
use appdb\usersession\UserSession;
use hydrogen\sqlbeans\exceptions\QueryFailedException;

function endPage($msg) {
	die("$msg\n</body>\n</html>");
}

// Open up our session
UserSession::open_session();

if (!($ubean = UserSession::getUserBean()))
	die('Hey. Log in.');

// Are we an admin?
$group = $ubean->getMapped('group');
if ($group->group_name != 'Administrator')
	die('Your permissions suck.');
?>
<html>
<head>
	<title>API Profile Editor</title>
	<style type="text/css">
		textarea {
			width: 550px;
			height: 150px;
		}
	</style>
</head>
<body>
<?php
	if (!isset($_POST['submitCreate']) && !isset($_POST['submitEdit']) && !isset($_POST['submitChanges'])) {
?>
<form method="POST" action="<?php echo dirname($_SERVER['REQUEST_URI']) . '/' . basename($_SERVER['PHP_SELF']); ?>">
<label for="profilename">API Profile Name:</label> 
<input type="text" id="profilename" name="pname" /><br />
<input type="submit" name="submitCreate" value="Create" /> <input type="submit" name="submitEdit" value="Edit" />
</form>
<?php
	}
	else if (isset($_POST['submitCreate']) || isset($_POST['submitEdit']) || isset($_POST['submitChanges'])) {
		if (!isset($_POST['pname']))
			endPage('Stop hacking.');
		$apm = APIProfileModel::getInstance();
		$fields = APIProfileBean::getFields();
		if (isset($_POST['submitCreate']) && !$apm->generateProfile($_POST['pname']))
				endPage('Error creating profile.  Does that name already exist?');
		if (isset($_POST['submitChanges'])) {
			if (!isset($_POST['pname']))
				endPage('Stop hacking.');
			$apip = $apm->getByName($_POST['pname']);
			foreach ($fields as $field) {
				if ($field == 'allowed_app_fields'&& isset($_POST[$field]))
					$apip->$field = explode(' ', $_POST[$field]);
				else if ($field == 'active' || substr($field, 0, 5) == 'perm_') {
					if (isset($_POST[$field]) && $_POST[$field] == 1)
						$apip->$field = 1;
					else
						$apip->$field = 0;
				}
				else if (isset($_POST[$field]))
					$apip->$field = $_POST[$field];
			}
			try {
				$apip->update();
			}
			catch (QueryFailedException $e) {
				endPage("Couldn't save :(");
			}
			echo '<p>Saved profile <b>' . $apip->name . '</b><br />';
			$privkey = openssl_pkey_get_private($apip->priv_pem);
			$details = openssl_pkey_get_details($privkey);
			$pubkey = $details["key"];
			$details = $details["rsa"];
			echo '<b>Pubkey:</b><br /><pre>' . $pubkey . '</pre>' . "\n";
			echo '<b>Modulus (hex):</b> ' . bin2hex($details['n']) . '<br /><br />' . "\n";
			echo '<b>Public exponent (hex):</b> ' . bin2hex($details['e']) . '</p>' . "\n";
		}
		$apip = $apm->getByName($_POST['pname']);
		if (!$apip)
			endPage("Name $_POST[pname] does not exist.");
		echo "<h1>API Profile: [$apip->name]</h1>\n";
		echo '<form method="POST" action="' . dirname($_SERVER['REQUEST_URI']) . '/' . basename($_SERVER['PHP_SELF']) . '">' . "\n";
		echo '<input type="hidden" name="pname" value="' . $apip->name . '" />' . "\n";
		foreach ($fields as $field) {
			if (substr($field, 0, 5) == 'perm_' || $field == 'active') {
				echo '<label for="' . $field . '">' . $field . '</label>' . "\n";
				echo '<input type="checkbox" name="' . $field . '" id="' . $field . '" value="1" ';
				if ($apip->$field)
					echo 'checked="true" ';
				echo '/><br />' . "\n";
			}
			else if ($field != 'id' && $field != 'created' && $field != 'name'
					&& $field != 'allowed_app_fields' && $field != 'priv_pem') {
				echo '<label for="' . $field . '">' . $field . '</label>' . "\n";
				echo '<input type="text" name="' . $field . '" id="' . $field . '" value="' . 
					$apip->$field . '" /><br />' . "\n";
			}
			else if ($field == 'priv_pem') {
				echo '<label for="' . $field . '">' . $field . '</label>' . "\n";
				echo '<textarea name="' . $field . '" id="' . $field . '">' . 
					$apip->$field . '</textarea><br />' . "\n";
			}
			else if ($field == 'allowed_app_fields') {
				echo '<label for="' . $field . '">' . $field . '</label>' . "\n";
				echo '<textarea name="' . $field . '" id="' . $field . '">' . 
					implode(' ', $apip->$field) . '</textarea><br />' . "\n";
			}
		}
		echo '<input type="submit" name="submitChanges" value="Submit" />' . "\n";
		echo "</form>\n";
	}
?>
</body>
</html>