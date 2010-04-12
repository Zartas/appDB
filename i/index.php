<?php
/*
 * AppDB Mobile Interface
 * index.php
 * Kyek
 * September 28, 2008
 */

	// Includes
	require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
	use hydrogen\config\Config;
	
	// Send 'em back to the main site if they're not mobile
	/*if (!preg_match('/(iPod|iPhone)/', $_SERVER['HTTP_USER_AGENT']))
		die(header('Location: ' . Config::getVal('urls', 'base_url')));*/
	
	// Hit mint
	$MINT_resource_title = Config::getVal('general', 'site_name') . " Mobile";
	include(__DIR__ . '/../lib/appdb/inc/hitmint.php');
	
	// What are we using?
	$device = 'iPhone';
	if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false)
		$device = 'iPod';
		
	// Are we allowed to search?
	$searchButton = Config::getVal('general', 'disable_search') ? '' : '<a href="#" rel="action" onclick="return WA.Form(\'form1\')" class="iButton iBClassic">Search</a>';

?>
<html>
	<head>
		<title><?php echo Config::getVal('general', 'site_name'); ?></title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
		<link rel="Stylesheet" href="WebApp/Design/Render.css" />
		<script type="text/javascript" src="WebApp/Action/Logic.js"></script>
		<link rel="Stylesheet" href="Custom/Design/Render.css" />
	</head>

<body -dir="rtl"><div id="WebApp">
<div id="iHeader">
	<a href="#" id="waBackButton">Back</a>
	<a href="#" id="waHomeButton">Home</a>
	<a href="#" onclick="return WA.HideBar()"><span id="waHeadTitle"><?php echo Config::getVal('general', 'site_name'); ?></span></a>
	<?php if (!Config::getVal('general', 'disable_search')) { ?>
	<form action="applist.php#_Applist">
		<div class="iForm" id="form1">
			<a href="#" rel="action" id="gogo" class="iButton iBAction">Search</a>
			<a href="#" rel="back" class="iButton iBClassic">Cancel</a>
	
			<fieldset class="attach">
				<legend>Search</legend>
				<input type="search" name="filter" placeholder="search name or description" />
			</fieldset>
		</div>
	</form>
	<?php } ?>
</div>

<div id="iGroup">
	<div id="iLoader">Loading...</div>

	<div class="iLayer" id="waHome" title="Home">
		<?php echo $searchButton; ?>
		<div id="logo"><img src="images/translogo.png" alt="<?php echo Config::getVal('general', 'site_name'); ?>" /></div>
		<div class="iMenu">
			<h3>Browse Apps</h3>
			<ul class="iArrow">
				<li><a href="categories.php?sort=appname#_Categories" rev="async">Browse alphabetically</a></li>
				<li><a href="categories.php?sort=newapps#_Categories" rev="async">Browse by newest apps</a></li>
				<li><a href="categories.php?sort=newvers#_Categories" rev="async">Browse by newest updates</a></li>
			</ul>
			<h3>Information</h3>
			<ul class="iArrow">
				<li><a href="#_About">About <?php echo Config::getVal('general', 'site_name'); ?> Mobile</a></li>
				<li><a href="http://hackulo.us/forums/">Support at Hackulo.us</a></li>
			</ul>
		</div>
		<div class="iFooter">
			&copy;2008 <?php echo Config::getVal('general', 'site_name'); ?>, all rights reserved.<br/>
		</div>
	</div>
	
	<div class="iLayer" id="waCategories" title="Categories">
		<?php echo $searchButton; ?>
		<div class="iMenu" id="catlist"></div>
	</div>
	
	<div class="iLayer" id="waApplist" title="Applications">
		<?php echo $searchButton; ?>
		<div class="iList" id="applist"></div>
	</div>
	
	<div class="iLayer" id="waAbout" title="About">
		<?php echo $searchButton; ?>
		<div class="iBlock">
			<h1>About <?php echo Config::getVal('general', 'site_name'); ?> Mobile</h1>
			<p>Welcome to the <?php echo $device; ?> interface for <?php echo Config::getVal('general', 'site_name'); ?>!
				The site is a bit slimmed down in this version, with unnecessary information
				taken out to show you only what you need when you're away from your computer.<br />
			<br />For the best experience, <?php echo Config::getVal('general', 'site_name'); ?> recommends adding <strong>Installous</strong>
				in Cydia (see <a href="http://hackulo.us/forums">Hackulo.us</a> for more information), or
				at the very minimum, the Safari Download Plugin.  That will let you take advantage of
				our <?php echo $device; ?>-friendly links, and download right to your <?php echo $device; ?>!<br />
			<br />At this point, not all sites allow downloading of .ipa files with this plugin.  Sites that
				are confirmed to work will be marked for your convenience.<br />
			<br />Enjoy your visit!</p>
		</div>
	</div>
</div>
</div>
</body>

</html>