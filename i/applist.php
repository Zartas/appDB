<?php

// Includes
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use hydrogen\config\Config;
use appdb\models\ApplicationModel;

// Start us up
header("Content-type: text/xml; charset=utf-8");

// Constants
$PERPAGE = 15;

// Get the vars
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newvers';
$cat = isset($_GET['cat']) ? $_GET['cat'] : 0;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
if (isset($_POST['filter']))
	$_GET['filter'] = $_POST['filter'];
$filter = isset($_GET['filter']) && !Config::getVal('general', 'disable_search') ? trim($_GET['filter']) : false;
if ($filter)
	$sort = "relevance";

// Get the apps
$am = ApplicationModel::getInstance();
$apps = $am->getAppListCached($PERPAGE, $page, $sort, $cat, $filter);
$total = $am->getResultCountCached($cat, $filter);
$curshowing = $page * $PERPAGE;
$numNextPage = $total - $curshowing;
if ($numNextPage <= 0)
 	$numNextPage = false;
else if ($numNextPage > $PERPAGE)
	$numNextPage = $PERPAGE;

// Output
$cdata = '';
if (!isset($_GET['page']))
	$cdata .= '<ul class="iArrow iShop">' . "\n";
$i = $page;
if (is_array($apps)) {
	foreach ($apps as $app) {
		$icon = $app->bigicon_url;
		$cdata .= "\t" . '<li' . ($i % 2 == 0 ? ' class="alt"' : '') . '>' . "\n";
		$cdata .= "\t\t" . '<a href="details.php?id=' . $app->id . '#_Details" rev="async">' . "\n";
		$cdata .= "\t\t\t" . '<img src="' . $icon . '" class="iFull" />' . "\n";
		$cdata .= "\t\t\t" . '<em>' . $app->company . '</em>' . "\n";
		$cdata .= "\t\t\t" . '<big>' . $app->name . "\n";
		$cdata .= "\t\t\t\t" . '<small>Category: ' . $app->category_name . '<br />Latest version: ' . $app->latest_version . '</small>' . "\n";
		$cdata .= "\t\t\t" . '</big>' . "\n";
		$cdata .= "\t\t</a>\n";
		$cdata .= "\t</li>\n";
		$i++;
	}
}
if ($numNextPage)
	$cdata .= "\t" . '<li id="appmore" class="iMore"><a href="applist.php?sort=' . $sort . '&amp;cat=' . $cat . '&amp;page=' . ($page+1) .
		'&amp;filter=' . ($filter ? $filter : '') . '" rev="async" title="Loading apps...">View ' . $numNextPage . ' more apps...</a></li>' . "\n";
if (!isset($_GET['page']))
	$cdata .= '</ul>';
	
// Modes and Zones
$mode = isset($_GET['page']) ? 'self' : 'replace';
$zone = isset($_GET['page']) ? 'appmore' : 'applist';
?>
<root>
	<destination mode="<?php echo $mode ?>" zone="<?php echo $zone ?>" />
	<data><![CDATA[ 
		<?php echo $cdata; ?>
		]]></data>
</root>