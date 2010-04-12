<?php
	require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
	use hydrogen\config\Config;
	use hydrogen\recache\RECacheManager;
	use appdb\usersession\UserSession;
	use appdb\models\ApplicationModel;
	use appdb\models\AppLinkModel;
	use appdb\models\ScreenshotModel;
	
	UserSession::open_session();
	$pagetitle = '';
	$removed = false;
	$hitmint = isset($_GET['calltype']) && $_GET['calltype'] == 'ajax';

	$app_id = isset($_GET['id']) ? $_GET['id'] + 0 : 0;
	if ($app_id <= 0)
		die('Invalid request');
	
	$am = ApplicationModel::getInstance();
	$app = $am->getDetailsByAppIDCached($app_id);
	if (!$app) {
		$removed = true;
		$pagetitle = 'App Not Found';
	}
	else {
		$itunes_url = 'http://phobos.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=' . $app->itunes_id . '&mt=8';

		$bigicon = 'iTunes';
		if ($app->bigicon_url)
			$bigicon = '<img src="' . $app->bigicon_url . '" alt="' . $app->name . '" />';
		$fullLink = '<a href="' . $itunes_url . '" title="Visit in iTunes">' . $bigicon . '</a>';
	
		$seller = '&nbsp;';
		if ($app->seller)
			$seller = 'Seller: ' . $app->seller;
	
		$size = '&nbsp;';
		if ($app->size)
			$size = 'Size: ' . $app->size;
	
		$released = '&nbsp;';
		if ($app->releasedate)
			$released = 'Released: ' . $app->releasedate;
	
		$price = 'Unknown';
		if ($app->price)
			$price = $app->price;
	
		$desc = 'Unavailable';
		if ($app->description)
			$desc = $app->description;
	
		$verinfo = 'Unavailable';
		$verbean = $app->getMapped('latestVersion');
		if ($verbean)
			$verinfo = $verbean->versioninfo;
	
		$lang = 'Unavailable';
		if ($app->languages)
			$lang = $app->languages;

		$req = 'Unavailable';
		if ($app->requirements)
			$req = $app->requirements;
			
		$cat = 'Unknown';
		$catbean = $app->getMapped('category');
		if ($catbean)
			$cat = $catbean->category_name;
	
		$added = 'Added to Appulo.us: ' . date('M j, Y', strtotime($app->date_added));

		$version = 'Latest version: ' . $app->latest_version;

		$alm = AppLinkModel::getInstance();
		$links = $alm->getByAppIDCached($app_id);
		$sm = ScreenshotModel::getInstance();
		$screens = $sm->getByAppIDCached($app_id);

		function blankIfFalse($var) {
			if (!$var)
				return '';
			return $var;
		}
	
		$pagetitle = $app->name;
		if ($hitmint) {
			$MINT_resource_title = 'Ajax: ' . $pagetitle;
			include(__DIR__ . '/../lib/appdb/inc/hitmint.php');
		}
	}
?>
<!--[PAGETITLE]><?php echo $pagetitle; ?><[/PAGETITLE]-->
<?php if ($removed) { ?>
<div id="page_about" class="staticpage">
	<p style="color:#f55;">This application does not exist or has been removed.  The most common reason for this is an app having no more valid links.  If this app has been re-submitted, it will be on a different page.  You can search for it from the app listing.</p>
</div>
<?php } else { ?>
<div id="page_viewapp">
	<div id="infobox">
		<div id="leftcol">
			<div id="detailbox">
				<div id="iconbox">
					<?php echo $fullLink; ?>
				</div>
				<div id="textinfo">
					<span id="textinfo_name"><?php echo $app->name; ?></span>
					<span id="textinfo_company"><?php echo $app->company; ?></span>
					<span id="textinfo_category">Category: <?php echo $cat; ?></span>
					<span id="textinfo_price">Price: <?php echo $price; ?></span>
					<span id="textinfo_released"><?php echo $released; ?></span>
					<span id="textinfo_size"><?php echo $size; ?></span>
					<span id="textinfo_seller"><?php echo $seller; ?></span>
					<span id="textinfo_added"><?php echo $added; ?></span>
					<span id="textinfo_version"><?php echo $version; ?></span>
				</div>
			</div>
			<div id="screenshots">
				<span class="sectiontitle">Screenshots</span>
				<?php
					foreach ($screens as $shot)
						echo '<img src="' . $shot->shot_url . '" alt="screenshot" class="' . ($shot->is_horiz == 1 ? 'horiz' : 'vert') . '" />';
				?>
			</div>
		</div>
		<div id="rightcol">
			<div id="descriptionblock">
				<span class="sectiontitle">Application description</span>
				<span class="textblock"><?php echo $desc; ?></span>
			</div>
			<div id="versionblock">
				<span class="sectiontitle">New in this version</span>
				<span class="textblock"><?php echo $verinfo; ?></span>
			</div>
			<div id="languageblock">
				<span class="sectiontitle">Languages</span>
				<span class="textblock"><?php echo $lang; ?></span>
			</div>
			<div id="requirementsblock">
				<span class="sectiontitle">Requirements</span>
				<span class="textblock"><?php echo $req; ?></span>
			</div>
		</div>
	</div>
	<div id="linkbox">
<?php
		$tableStart = '<table><tr class="toprow"><td class="linkid">Link ID</td>';
		if (UserSession::getPermission('view_all_posters'))
			$tableStart .= '<td class="submitter">Submitter</td>';
		$tableStart .= '<td class="dateadded">Date added</td>';
		$tableStart .= '<td class="cracker">Cracker</td>';
		$tableStart .= '<td class="ptype">Package type</td>';
		$tableStart .= '<td class="download">Download</td></tr>';
		$versions = array();
		$htmlversions = array();
		// Pass 1: Sort links into version groups
		foreach ($links as $link) {
			$verbean = $link->getMapped('version');
			if (!isset($versions[$verbean->version]))
				$versions[$verbean->version] = array();
			$versions[$verbean->version][] = $link;
		}
		
		// Pass 2: Parse links into html table format, with AppScene on top
		foreach ($versions as $version => $linkset) {
			$aslinks = '';
			$normallinks = '';
			foreach ($linkset as $link) {
				$line = "<tr>";
				$line .= "<td>" . $link->id . "</td>\n";
				if (UserSession::getPermission('view_all_posters')) {
					$sub = $link->getMapped('submitter');
					$line .= "<td>" . $sub->username . " (" . $link->submitter_id . ")</td>\n";
				}
				$line .= "<td>" . blankIfFalse($link->date_added) . "</td>\n";
				$line .= "<td>" . ($link->cracker ? $link->cracker : '--') . "</td>\n";
				$line .= "<td>";
				switch ($link->filetype) {
					case 1:
						$line .= 'APP';
						break;
					case 2:
						$line .= 'IPA';
						break;
					default:
						$line .= 'Unknown';
				}
				$line .= "</td>\n";
				$parsed_url = parse_url($link->url);
				$domain = $parsed_url['host'];
				while (preg_match('/\\..+\\./', $domain))
					$domain = substr($domain, strpos($domain, '.') + 1);
				if (!UserSession::getPermission('view_unscrambled_links')) {
					$path = substr($parsed_url['path'], 1);
					$lastslash = strrpos($path, '/');
					$frontpath = substr($path, 0, $lastslash);
					$endpath = substr($path, $lastslash);
					$newpath = '/';
					while (strlen($frontpath) > 0) {
						$digit = rand(0, strlen($frontpath) - 1);
						$newpath .= $frontpath{$digit};
						$frontpath = substr($frontpath, 0, $digit) . substr($frontpath, $digit + 1);
					}
					$newpath .= $endpath;

					$url = $parsed_url['scheme'] . "://" . $parsed_url['host'] . $newpath;
					if ($parsed_url['query']) {
						$query = $parsed_url['query'];
						$newquery = '?';
						while (strlen($query) > 0) {
							$digit = rand(0, strlen($query) - 1);
							$newquery .= $query{$digit};
							$query = substr($query, 0, $digit) . substr($query, $digit + 1);
						}
						$url .= $newquery;
					}

					$link->url = $url;
				}
				$line .= '<td><a href="' . $link->url . '">' . $domain . "</a></td>\n";
				$line .= "</tr>";
				if ($domain == 'appscene.org')
					$aslinks .= $line . "\n";
				else
					$normallinks .= $line . "\n";
			}
			$htmlversions[$version] = $aslinks . $normallinks;
		}
		foreach ($htmlversions as $version => $block) {
			if ($version == 'unknown' || $version == '')
				echo '<span class="sectiontitle">Unknown Version</span>' . "\n";
			else
				echo '<span class="sectiontitle">Version ' . $version . '</span>' . "\n";
			echo $tableStart . $block . "</table><br /><br />\n";
		}
?>
	</div>
</div>
<?php } ?>