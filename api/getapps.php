<?php
/*
 * AppDB
 * api/getapps.php
 * Kyek
 * September 25, 2008
 */

	// Includes
	require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
	use appdb\models\ApplicationModel;
	use appdb\models\ScreenshotModel;
	use appdb\models\AppLinkModel;
	use hydrogen\errorhandler\ErrorHandler;
	
	ErrorHandler::attachErrorString(json_encode(array(
		'successful' => 0,
		'error' => 'Server error'
		)));
	
	// Returns an associative array if parsed successfully, String error message otherwise.
	function parse_request($req) {
		if (substr($req, 0, 1) != '{' || substr($req, strlen($req) - 1, strlen($req)) != '}')
			return 'Request must be encapsulated in { }.';
		$req = substr($req, 1, strlen($req) - 2);
		while (substr($req, strlen($req) - 1, strlen($req)) == ';')
			$req = substr($req, 0, strlen($req) - 1);
		$pairs = explode(';', $req);
		$specs = array(
			'maxresults' => 15,
			'startwith' => 0,
			'sortby' => 'newvers',
			'filterbyid' => false,
			'filterbyitunesid' => false,
			'filterbytext' => false,
			'filterbycategory' => false,
			'fields' => false,
			'showtotal' => 'false',
			'full' => false);
		$specregex = array(
			'maxresults' => '\\d+',
			'startwith' => '\\d+',
			'sortby' => '(newvers|newapps|appname|relevance)',
			'filterbyid' => '\\d+',
			'filterbyitunesid' => '\\d+',
			'filterbytext' => '.*',
			'filterbycategory' => '\\d+',
			'showtotal' => '(true|false)',
			'fields' => '.*');
		$fields = array('name' => false,
			'itunesid' => false,
			'latestversion' => false,
			'releasedate' => false,
			'addeddate' => false,
			'company' => false,
			'price' => false,
			'seller' => false,
			'size' => false,
			'category' => false,
			'description' => true,
			'whatsnew' => true,
			'languages' => true,
			'requirements' => true,
			'icon100' => false,
			'icon57' => false,
			'screenshots' => false,
			'links' => false,
			'latestcracker' => false);
		foreach ($pairs as $pair) {
			$kv = explode(':', $pair);
			if (count($kv) != 2)
				return 'Invalid key:value structure in: [' . $pair . ']';
			$found = false;
			foreach ($specregex as $key => $val) {
				if ($kv[0] == $key) {
					if (!preg_match('/^' . $val . '$/', $kv[1]))
						return "Invalid value for spec '$key': [" . $kv[1] . "]";
					$found = true;
					break;
				}
			}
			if (!$found)
				return "Invalid spec type: '" . $kv[0] . "'";
			if ($kv[0] == 'fields') {
				$fnames = explode(',', $kv[1]);
				foreach ($fnames as $fname) {
					$found = false;
					foreach ($fields as $key => $value) {
						if ($fname == $key) {
							$found = true;
							if ($value)
								$specs['full'] = true;
							break;
						}
					}
					if (!$found)
						return "Invalid field name in 'fields' spec: [$fname]";
					if (!is_array($specs['fields']))
						$specs['fields'] = array();
					$specs['fields'][count($specs['fields'])] = $fname;
				}
			}
			else
				$specs[$kv[0]] = $kv[1];
		}
		return $specs;
	}

	// Start us up
	header('Content-type: text/plain; charset=utf-8');

	$jsonPage = array();
	if (isset($_GET['request']))
		$_POST['request'] = $_GET['request'];
	if (!isset($_POST['request'])) {
		die(json_encode(array(
			'successful' => 0,
			'error' => 'Invalid request'
			)));
	}
	$specs = parse_request($_POST['request']);
	if (!is_array($specs)) {
		die(json_encode(array(
			'successful' => 0,
			'error' => $specs
			)));
	}
	
	$startwith = ($specs['startwith'] - 1 <= 0 ? false : $specs['startwith'] - 1);
	$page = ($startwith ? false : 1);
	$am = ApplicationModel::getInstance();
	$apps = $am->getAppListCached($specs['maxresults'], $page, $specs['sortby'], $specs['filterbycategory'], 
		$specs['filterbytext'], $specs['filterbyid'], $specs['filterbyitunesid'], $startwith, $specs['full']);
	$totApps = 0;
	if ($specs['showtotal'] == 'true')
		$totApps = $am->getResultCountCached($specs['filterbycategory'], $specs['filterbytext']);
	if (is_array($apps) && count($apps) == 0) {
		$jsonPage = array(
			'successful' => 1,
			'results' => 0
			);
		if ($specs['showtotal'] == 'true')
			$jsonPage['totalapps'] = $totApps;
		$jsonPage['applications'] = array();
		die(json_encode($jsonPage));
	}
	else if (!$apps) {
		die(json_encode(array(
			'successful' => 0,
			'error' => 'Unknown error'
			)));
	}
	
	$jsonPage = array('successful' => 1);
	$jsonPage['results'] = count($apps);
	if ($specs['showtotal'] == 'true')
		$jsonPage['totalapps'] = $totApps;
	foreach ($apps as $app) {
		$jsonApp = array('id' => $app->id);
		if ($specs['fields']) {
			foreach ($specs['fields'] as $field) {
				switch ($field) {
					case 'name':
						$jsonApp['name'] = $app->name; break;
					case 'itunesid':
						$jsonApp['itunesid'] = $app->itunes_id; break;
					case 'latestversion':
						$jsonApp['latestversion'] = $app->latest_version; break;
					case 'releasedate':
						$jsonApp['releasedate'] = $app->release_date; break;
					case 'addeddate':
						$jsonApp['addeddate'] = $app->date_added; break;
					case 'company':
						$jsonApp['company'] = $app->company; break;
					case 'price':
						$jsonApp['price'] = $app->price; break;
					case 'seller':
						$jsonApp['seller'] = $app->seller; break;
					case 'size':
						$jsonApp['size'] = $app->size; break;
					case 'category':
						$jsonApp['category'] = $app->category_name; break;
					case 'description':
						$jsonApp['description'] = $app->description; break;
					case 'whatsnew':
						$jsonApp['whatsnew'] = $app->whats_new; break;
					case 'languages':
						$jsonApp['languages'] = $app->languages; break;
					case 'requirements':
						$jsonApp['requirements'] = $app->requirements; break;
					case 'icon100':
						$jsonApp['icon100'] = $app->bigicon_url; break;
					case 'icon57':
						$jsonApp['icon57'] = $app->smallicon_url; break;
					case 'latestcracker':
						$jsonApp['latestcracker'] = $app->latest_version_first_cracker; break;
					case 'screenshots':
						if (count($apps) == 1) {
							$jsonShots = array();
							$sm = ScreenshotModel::getInstance();
							$shots = $sm->getByAppIDCached($app->id);
							foreach ($shots as $shot) {
								$jsonShot = array();
								$jsonShot['url'] = $shot->shot_url;
								$jsonShot['is_horiz'] = $shot->is_horiz ? 1 : 0;
								$jsonShots[] = $jsonShot;
							}
							$jsonApp['screenshots'] = $jsonShots;
						}
						break;
					case 'links':
						if (count($apps) == 1) {
							$alm = AppLinkModel::getInstance();
							$links = $alm->getByAppIDCached($app->id);
							$jsonLinks = array();
							foreach ($links as $link) {
								$jsonLink = array();
								$jsonLink['id'] = $link->id;
								$jsonLink['version'] = $link->version;
								$jsonLink['type'] = $link->filetype;
								$jsonLink['cracker'] = $link->cracker;
								$jsonLink['addeddate'] = $link->date_added;
								$jsonLink['url'] = $link->url;
								$jsonLinks[] = $jsonLink;
							}
							$jsonApp['links'] = $jsonLinks;
						}
						break;
				}
			}
		}
		$jsonApps[] = $jsonApp;
	}
	$jsonPage['applications'] = $jsonApps;
	
	die(json_encode($jsonPage));
?>