<?php
/*
 * rss.php
 * Kyek
 * September 17, 2008
 */
require_once(__DIR__ . '/lib/appdb/appdb.inc.php');
require_once(__DIR__ . '/lib/feedcreator/feedcreator.class.php');
use hydrogen\config\Config;
use appdb\models\ApplicationModel;

$results = 15;
if (isset($_GET['results']) && $_GET['results'] > 0 && $_GET['results'] < 30)
	$results = $_GET['results'];

$sort = 'newvers';
if (isset($_GET['sort']) && $_GET['sort'] == 'newapps')
	$sort = 'newapps';
	
$cat = 0;
if (isset($_GET['cat']) && $_GET['cat'] > 0 && $_GET['cat'] + 0 == $_GET['cat'])
	$cat = $_GET['cat'];
	
$filter = false;
if (isset($_GET['filter']) && $_GET['filter'] != '' && $_GET['filter'] != '*') {
	$filter = trim($_GET['filter']);
	if (Config::getVal('general', 'disable_search'))
		die();
}
	
$type = 'html';
if (isset($_GET['type']) && ($_GET['type'] == 'bbcode' || $_GET['type'] == 'plain'))
	$type = $_GET['type'];
	
// Set us up
header('Content-type: application/rss+xml; charset=utf-8');
	
$br = "<br />\n";
$prebold = "<strong>";
$postbold = "</strong>";
$preitalic = "<em>";
$postitalic = "</em>";
$linkline = '<a href="%LINK%">Full info on ' . Config::getVal('general', 'site_name') . '</a>';
switch ($type) {
	case 'bbcode':
		$br = "\n";
		$prebold = '[b]';
		$postbold = '[/b]';
		$preitalic = '[i]';
		$postitalic = '[/i]';
		$linkline = '[url=' . Config::getVal('urls', 'base_url') . ']See ' .
			Config::getVal('general', 'site_name') . ' for more apps![/url]';
		break;
	case 'plain':
		$br = "\n";
		$prebold = $postbold = $preitalic = $postitalic = '';
		$linkline = 'Full info on ' . Config::getVal('general', 'site_name') . ': %LINK%';
		break;
}

$am = ApplicationModel::getInstance();
$appArray = $am->getAppListCached($results, 1, $sort, $cat, $filter, false, false, false, true);
if (!$appArray)
	die();

$rss = new UniversalFeedCreator();
$rss->title = Config::getVal('general', 'site_name') . " Newest Apps" . ($sort == 'newvers' ? ' & Updates' : '');
$rss->description = "The Solution to a Flawed App Store";
$rss->link = Config::getVal('urls', 'base_url');
$rss->syndicationURL = Config::getVal('urls', 'base_url') . '/' . $_SERVER['PHP_SELF'];

$image = new FeedImage();
$image->title = Config::getVal('general', 'site_name') . " logo";
$image->url = Config::getVal('urls', 'base_url') . "/images/logo_small_trans.png";
$image->link = Config::getVal('urls', 'base_url');
$image->description = Config::getVal('general', 'site_name');
$image->width = 100;
$image->height = 100;
$rss->image = $image;

foreach ($appArray as $app) {
	$desc = $app->description;
	$whats_new = $app->whats_new;
	if ($type == 'bbcode') {
		$desc = preg_replace('/\\n/', '', $desc);
		$desc = preg_replace('/\\<br\\s?\\/?\\>/i', "\n", $desc);
		if ($whats_new) {
			$whats_new = preg_replace('/\\n/', '', $whats_new);
			$whats_new = preg_replace('/\\<br\\s?\\/?\\>/i', "\n", $whats_new);
		}
	}
	$desc = preg_replace('/((^[\\n\\r\\s\\t(\\<br\\s?\\/?\\>)]+)|([\\n\\r\\s\\r(\\<br\\s?\\/?\\>)]+$))/', '', $desc);
	if ($whats_new)
		$whats_new = preg_replace('/((^[\\n\\r\\s\\t(\\<br\\s?\\/?\\>)]+)|([\\n\\r\\s\\r(\\<br\\s?\\/?\\>)]+$))/',
			'', $whats_new);
    $item = new FeedItem(); 
    $item->title = $app->name . ' (' . $app->latest_version . ')'; 
	$cracker = $app->latest_version_first_cracker ? stripslashes($app->latest_version_first_cracker) : false;
	if ($type == 'bbcode' && $cracker)
		$item->title .= ' (Cracked by ' . $cracker . ')';
    $item->link = Config::getVal('urls', 'base_url') . '/?page=viewapp&id=' . $app->id;
	$item->category = $app->category_name;
    $item->description = $prebold . $app->name . ' (' . $app->latest_version . ')' . $postbold . $br;
 	$item->description .= $preitalic . $app->company . $postitalic . $br;
	$item->description .= $prebold . 'Category: ' . $postbold . $app->category_name . $br;
	$item->description .= $prebold . 'Price: ' . $postbold . $app->price . $br;
	if ($cracker)
		$item->description .= $prebold . 'Cracker: ' . $postbold . stripslashes($app->latest_version_first_cracker) . $br . $br;
	else
		$item->description .= $br;
	$item->description .= $prebold . "Application Description:" . $postbold . $br;
	$item->description .= $desc . $br . $br;
	if ($whats_new) {
		$item->description .= $prebold . "New in this Version:" . $postbold . $br;
		$item->description .= $whats_new . $br . $br;
	}
    $item->description .= $prebold . preg_replace('/\\%LINK\\%/', $item->link, $linkline) . $postbold;
    if ($sort == 'newapps')
    	$item->date = strtotime($app->date_added);
    else
    	$item->date = strtotime($app->latest_version_added);
	$item->guid = $app->id . '-' . $app->latest_version;
    $item->source = Config::getVal('general', 'site_name'); 
    $item->author = Config::getVal('general', 'site_name');
     
    $rss->addItem($item); 
}

// valid format strings are: RSS0.91, RSS1.0, RSS2.0, PIE0.1 (deprecated), 
// MBOX, OPML, ATOM, ATOM0.3, HTML, JS 
$rendered = $rss->createFeed("RSS0.91");

// Write it
echo $rendered;
?>
