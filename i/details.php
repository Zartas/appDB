<?php

// Includes
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use hydrogen\config\Config;
use appdb\models\ApplicationModel;
use appdb\models\AppLinkModel;
use appdb\models\ScreenshotModel;

// Start us up
header("Content-type: text/xml; charset=utf-8");

if (!isset($_GET['id']))
	die("Invalid request");

// Functions
function getDomain($url) {
	$parsed_url = parse_url($url);
	$domain = $parsed_url['host'];
	while (preg_match('/\\..+\\./', $domain))
		$domain = substr($domain, strpos($domain, '.') + 1);
	return strtolower($domain);
}

// Get Data
$am = ApplicationModel::getInstance();
$app = $am->getDetailsByAppIDCached($_GET['id']);
if (!$app)
	die("Invalid request");
$sm = ScreenshotModel::getInstance();
$screens = $sm->getByAppIDCached($_GET['id']);
$alm = AppLinkModel::getInstance();
$links = $alm->getByAppIDCached($_GET['id']);
$icon = $app->bigicon_url;

// Process links
$versions = array();
$sepversions = array();
$safelinks = array();
$otherlinks = array();

// Pass 1: Separate into versions
foreach ($links as $link) {
	$verbean = $link->getMapped('version');
	if (!isset($sepversions[$verbean->version])) {
		$sepversions[$verbean->version] = array();
		$versions[] = $verbean->version;
	}
	$sepversions[$verbean->version][] = $link;
}

// Pass 2: Separate into link categories with AppScene on top
$friendlyDomains = Config::getVal('domains', 'friendly');
foreach ($sepversions as $version => $linkset) {
	$aslinks = array();
	$normsafe = array();
	$other = array();
	foreach ($linkset as $link) {
		$domain = getDomain($link->url);
		if ($link->filetype == 2 && in_array($domain, $friendlyDomains)) {
			if ($domain == 'appscene.org')
				$aslinks[] = $link;
			else
				$normsafe[] = $link;
		}
		else
			$other[] = $link;
	}
	$safelinks[$version] = array();
	foreach ($aslinks as $link)
		$safelinks[$version][] = $link;
	foreach ($normsafe as $link)
		$safelinks[$version][] = $link;
	$otherlinks[$version] = $other;
}

// Calculate screenshot dimensions
// Change these four:
$thumbmaxwidth = 120;
$thumbmaxheight = 120;
$fullmaxwidthvert = 310;
$fullmaxwidthhoriz = 310;

// Leave these alone:
$sheight = 480;
$swidth = 320;
$thumbvertwidth = floor($swidth * ($thumbmaxheight / $sheight));
$thumbvertheight = floor($sheight * ($thumbmaxheight / $sheight));
$fullvertwidth = floor($swidth * ($fullmaxwidthvert / $swidth));
$fullvertheight = floor($sheight * ($fullmaxwidthvert / $swidth));

$thumbhorizwidth = floor($sheight * ($thumbmaxwidth / $sheight));
$thumbhorizheight = floor($swidth * ($thumbmaxwidth / $sheight));
$fullhorizwidth = floor($sheight * ($fullmaxwidthhoriz / $sheight));
$fullhorizheight = floor($swidth * ($fullmaxwidthhoriz / $sheight));

// What are we using?
$device = 'iPhone';
if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false)
	$device = 'iPod';

// Modes and Zones
$mode = 'replace';
$zone = 'waDetails';

// Hit mint
$MINT_resource_title = $app->name . " :: " . Config::getVal('general', 'site_name') . " Mobile";
include(__DIR__ . '/../lib/appdb/inc/hitmint.php');
?>
<?php echo '<?'; ?>xml version="1.0" encoding="utf-8" ?>
<root>
	<title set="<?php echo $zone; ?>"><?php echo htmlentities($app->name, ENT_NOQUOTES, 'UTF-8'); ?></title>
	<part>
		<destination mode="<?php echo $mode; ?>" zone="<?php echo $zone; ?>" create="true" />
		<data><?php echo '<![CDATA[ '; ?>
			<?php if (!Config::getVal('general', 'disable_search')) { ?>
			<a href="#" rel="action" onclick="return WA.Form('form1')" class="iButton iBClassic">Search</a>
			<?php } ?>
			<div class="iBlock">
				<div class="appinfo">
					<img src="<?php echo $icon; ?>" alt="" />
					<em><?php echo $app->company; ?></em>
					<big>
						<?php echo $app->name; ?>
						<small>
							Category: <?php echo $app->category_name; ?><br />
							Latest version: <?php echo $app->latest_version; ?><br />
							Price: <?php echo $app->price; ?><br />
							Size: <?php echo $app->size; ?><br />
							Released: <?php echo $app->release_date; ?>
						</small>
					</big>
				</div>
				<h1>Screenshots</h1>
				<div id="screens">
					<?php
						for ($i = 0; $i < count($screens); $i++) {
							$url = $screens[$i]->shot_url;
							echo '<a class="screen" href="#_Screen' . $i . '">';
							echo '<img src="' . $url . '" alt="" style="';
							if ($screens[$i]->is_horiz)
								echo "width:" . $thumbhorizwidth . "px;height:" . $thumbhorizheight . "px;";
							else
								echo "width:" . $thumbvertwidth . "px;height:" . $thumbvertheight . "px;";
							echo '" /></a>';
						}
					?>
				</div>
				<h1>Description</h1>
				<div class="textblock"><?php echo $app->description; ?></div>
				<?php 
					if ($app->whats_new) {
						echo "<h1>New in $app->latest_version</h1>\n";
						echo "<div class=\"textblock\">$app->whats_new</div>\n";
					}
				?>
				<h1>Languages</h1>
				<div class="textblock"><?php echo $app->languages; ?></div>
				<h1>Requirements</h1>
				<div class="textblock"><?php echo $app->requirements; ?></div>
			</div>
			<div class="iMenu">
				<h3>Download</h3>
				<ul class="iArrow">
					<?php
						for ($i = 0; $i < count($versions); $i++)
							echo '<li><a href="#_Download' . $i . '">Version ' . $versions[$i] . "</a></li>\n";
					?>
				</ul>
			</div>
		]]></data>
	</part>
	<?php
		for ($i = 0; $i < count($screens); $i++) {
			echo "<part>\n";
			echo "\t<destination mode=\"replace\" zone=\"waScreen$i\" create=\"true\" />\n";
			echo "\t<data><![CDATA[ " .
				(Config::getVal('general', 'disable_search') == 1 ? '' : "<a href=\"#\" rel=\"action\" onclick=\"return WA.Form('form1')\" class=\"iButton iBClassic\">Search</a>") .
				"<div class=\"iBlock fullscreen\">";
			echo '<img src="' . $screens[$i]->shot_url . '" alt="" style="';
			if ($screens[$i]->is_horiz)
				echo "width:$fullhorizwidth;height:$fullhorizheight;";
			else
				echo "width:$fullvertwidth;height:$fullvertheight;";
			echo "\" /></div> ]]></data>\n";
			echo "</part>\n";
		}
		for ($i = 0; $i < count($versions); $i++) {
			$version = $versions[$i];
			echo "<part>\n";
			echo "\t<destination mode=\"replace\" zone=\"waDownload$i\" create=\"true\" />\n";
			echo "\t<data><![CDATA[ " .
				(Config::getVal('general', 'disable_search') == 1 ? '' : "<a href=\"#\" rel=\"action\" onclick=\"return WA.Form('form1')\" class=\"iButton iBClassic\">Search</a>") .
				"<div class=\"iList\">";
			
			echo '<h2>' . $device . '-friendly links</h2><ul class="iArrow">';
			foreach ($safelinks[$version] as $link) {
				$domain = getDomain($link->url);
				echo '<li><a href="' . $link->url . '">' . $domain;
				if ($link->cracker)
					echo '<br /><span class="cracker">Cracked by ' . $link->cracker . '</span>';
				echo "</a></li>\n";
			}
			echo '</ul><h2>Other links</h2><ul class="iArrow">';
			foreach ($otherlinks[$version] as $link) {
				$domain = getDomain($link->url);
				echo '<li><a href="' . $link->url . '">' . $domain;
				if ($link->cracker)
					echo '<br /><span class="cracker">Cracked by ' . $link->cracker . '</span>';
				echo "</a></li>\n";
			}
			echo "</ul>";
			
			echo "</div> ]]></data>\n";
			echo "</part>\n";
		}
	?>
</root>
