<?php

// Includes
require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\usersession\UserSession;
use appdb\models\ApplicationModel;
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
	
// Clear the current index
$query = new Query('DELETE');
$query->from('search_name_index');
$stmt = $query->prepare();
$stmt->execute();
echo "Index deleted.<br />\n";

// Find total apps
$query = new Query('SELECT');
$query->field('COUNT(*)', 'count');
$query->from('applications');
$stmt = $query->prepare();
$stmt->execute();
$obj = $stmt->fetchObj();
$count = $obj->count;
echo "Starting indexing for $count apps...<br />\n";

// Loop it!
$i = 0;
$sindex = array();
$cm = RECacheManager::getInstance();
$am = ApplicationModel::getInstance();
while ($i < $count) {
	$query = new Query('SELECT');
	$query->field('id');
	$query->field('name');
	$query->from('applications');
	$query->orderby('name', 'ASC');
	$query->limit(500, $i);
	$stmt = $query->prepare();
	$stmt->execute();
	while ($row = $stmt->fetchAssoc()) {
		echo "$row[name] ($row[id]): ";
		$i++;
		$name = $row['name'];
		$app_id = $row['id'];
		
		// Pull out the words we'll need to index
		$iwords = $am->getIndexable($name);
		
		// Add 'em
		if (count($iwords) > 0) {
			echo "Indexing: " . implode(', ', $iwords) . "<br />\n";
			foreach ($iwords as $iword)
				$sindex[$iword][] = $app_id;
		}
		else
			echo "No words to be indexed.<br />\n";
	}
}
echo "<br />Saving and caching:<br />\n";
foreach ($sindex as $term => $idarray) {
	echo "<strong>$term</strong> ";
	echo str_replace("\n", "<br />\n", print_r($idarray, true));
	$cm->set("search_name_index:$term", $idarray, 0);
	$query = new Query('INSERT');
	$query->intoTable('search_name_index');
	$query->intoField('term');
	$query->intoField('app_id_array');
	$query->values("(?, ?)", array($term, serialize($idarray)));
	$stmt = $query->prepare();
	if (!$stmt->execute())
		echo "Error while adding term '$term'.<br />\n";
}

echo "<br />Done!";
?>