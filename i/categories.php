<?php

// Includes
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use hydrogen\config\Config;
use appdb\models\AppCategoryModel;

// Start us up
header("Content-type: text/xml; charset=utf-8");

// Get the sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newvers';

// Get categories
$catlist = '';
$acm = AppCategoryModel::getInstance();
$cats = $acm->getAllCached();
foreach ($cats as $cat)
	$catlist .= '<li><a href="applist.php?sort=' . $sort . '&amp;cat=' . $cat->id . '#_Applist" rev="async">' . $cat->category_name . "</a></li>\n";

// Set zones and modes
$mode = 'replace';
$zone = 'catlist';

// Write the CDATA
$cdata = '<ul class="iArrow">' . "\n" .
	'<li><a href="applist.php?sort=' . $sort . '&amp;cat=0#_Applist" rev="async">All Categories</a></li>' . "\n" . $catlist .
	'</ul>';
?>
<root>
	<destination mode="<?php echo $mode ?>" zone="<?php echo $zone ?>" />
	<data><![CDATA[ <?php echo $cdata; ?> ]]></data>
</root>