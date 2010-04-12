<?php

require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;

// Get all the versions that have no links attached
$query = new Query('SELECT');
$query->field('v.id');
$query->field('v.app_id');
$query->from('versions', 'v');
$query->join('links', 'l', 'LEFT');
$query->on('v.id = l.version_id');
$query->where('l.id IS NULL');
$stmt = $query->prepare();
$stmt->execute();

// Compile the version-deleting SQL
$apps = array();
$count = 0;
$query = new Query('DELETE');
$query->from('versions');
while ($row = $stmt->fetchAssoc()) {
	$query->where("id = ?", $row['id'], 'OR');
	$apps[$row['app_id']] = true;
	$count++;
}

if ($count == 0)
	die("Nothing to fix.");
	
echo "Deleting $count unused versions... ";
$stmt = $query->prepare();
if (!$stmt->execute())
	die("Something broke.  We're gonna stop here.");
echo "Done.<br />\nFixing " . count($apps) . " apps...<br />\n";
ob_flush();
flush();

// Get all the app_ids and latest versions for the apps with the strawberry query
$query = new Query('SELECT');
$query->field('a.id');
$query->field('a.name');
$query->field('v1.id', 'version_id');
$query->field('v1.version');
$query->field('v1.date_added');
$query->from('applications', 'a');
$query->join('versions', 'v1', 'LEFT');
$query->on('a.id = v1.app_id');
$query->join('versions', 'v2', 'LEFT');
$query->on('v1.app_id = v2.app_id');
$query->on('v1.intversion < v2.intversion');
$query->where('v2.app_id IS NULL');
$query->whereOpenGroup('AND');
$apps = array_keys($apps);
foreach ($apps as $app)
	$query->where('a.id = ?', $app, 'OR');
$stmt = $query->prepare();
//echo $stmt->getQuery() . "<br />\n";

$aver = array();
$adate = array();
$aname = array();
$query = new Query('DELETE');
$query->from('applications');
$vquery = new Query('SELECT');
$vquery->whereOpenGroup();
$appsToDelete = false;
$versToCorrect = false;
$stmt->execute();
while ($row = $stmt->fetchAssoc()) {
	if (!$row['version']) {
		$appsToDelete = true;
		echo "Deleting #$row[id] ($row[name])...<br />\n";
		$query->where('id = ?', $row['id'], 'OR');
	}
	else {
		$versToCorrect = true;
		$aver[$row['id']] = $row['version'];
		$adate[$row['id']] = $row['date_added'];
		$aname[$row['id']] = $row['name'];
		$vquery->where("v.id = ?", $row['version_id'], 'OR');
	}
}
if ($appsToDelete) {
	$stmt = $query->prepare();
	if ($stmt->execute())
		echo "Done.<br />\n";
	else
		echo "Couldn't delete apps.<br />\n";
}
else
	echo "No apps to delete.<br />\n";
ob_flush();
flush();

if ($versToCorrect) {
	$vquery->whereCloseGroup();
	echo "Got versions and dates.<br />\n";

	// Get all the first crackers for each version with another strawbery query
	$vquery->field('v.app_id');
	$vquery->field('l1.cracker');
	$vquery->from('versions', 'v');
	$vquery->join('links', 'l1', 'LEFT');
	$vquery->on('v.id = l1.version_id');
	$vquery->join('links', 'l2', 'LEFT');
	$vquery->on('l1.version_id = l2.version_id');
	$vquery->on('l1.date_added > l2.date_added');
	$vquery->where('l2.date_added IS NULL');
	$stmt = $vquery->prepare();
	if (!$stmt->execute())
		die("You broke shit.");

	$acracker = array();
	while ($row = $stmt->fetchAssoc())
		$acracker[$row['app_id']] = $row['cracker'];
	echo "Got crackers.<br />\n";
	ob_flush();
	flush();
	
	// And finally, update the facades
	foreach ($aname as $key => $value) {
		$version = $aver[$key];
		$date = $adate[$key];
		$cracker = (isset($acracker[$key]) && !is_null($acracker[$key]) && !empty($acracker[$key])) ? "'" . $acracker[$key] . "'" : NULL;
		echo "Updating $key: $value ($version) cracked by $cracker on $date... ";
		$query = new Query('UPDATE');
		$query->table('applications');
		$query->set('latest_version = ?', $version);
		$query->set('latest_version_added = ?', $date);
		if (is_null($cracker))
			$query->set('latest_version_first_cracker = NULL');
		else
			$query->set('latest_version_first_cracker = ?', $cracker);
		$query->where('id = ?', $key);
		$stmt = $query->prepare();
		if ($stmt->execute())
			echo "Done.<br />\n";
		else
			echo "Failed.<br />\n";
	}
}
else
	echo "No versions to be corrected.<br />\n";

echo "Clearing the applist cache... ";
$cm = RECacheManager::getInstance();
if ($cm->clearGroup('applist') && $cm->clearGroup('applinks'))
	echo 'Success.';
else
	echo 'Fail.';

echo "<br />\nCompleted.";
?>