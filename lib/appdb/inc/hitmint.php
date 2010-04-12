<?php
/**************************************************************************************
 Hit Mint v1.0
 *************************************************************************************/
use hydrogen\config\Config;

if (!class_exists('hydrogen\config\Config', false))
	die("Quit trying to hack my damn code.");

// Only do it if we're supposed to
if (Config::getVal('mint', 'enabled', false)) {
	
	// Set the cookie policy
	header('P3P: CP="NOI NID ADMa OUR IND COM NAV STA LOC"');

	// Set MINT_ROOT
	if (!defined('MINT_ROOT'))
		define('MINT_ROOT', Config::getVal('mint', 'install_path'));

	// Check for overrides
	if (!isset($MINT_referer))
		$MINT_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if (!isset($MINT_resource))
		$MINT_resource = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if (!isset($MINT_resource_title))
		$MINT_resource_title = $MINT_resource;

	// Back up our current data
	$MINT_getbackup = $_GET;
	$MINT_cookiebackup = $_COOKIE;
	$MINT_errorbackup = error_reporting(0);

	// Get an instance of the Mint object ready for key generation
	if (!defined('MINT')) { define('MINT',true); }
	require(MINT_ROOT.'app/lib/mint.php');
	require(MINT_ROOT.'app/lib/pepper.php');
	require(MINT_ROOT.'config/db.php');
	$Mint->loadPepper();

	// We accept cookies
	$_COOKIE['MintAcceptsCookies'] = 1;

	// Define the data for the Record request
	$_GET = array(
		'record' => '1',
		'key' => $Mint->generateKey(),
		'referer' => $MINT_referer,
		'resource' => $MINT_resource,
		'resource_title' => $MINT_resource_title,
		'resource_title_encoded' => 0
		);

	// Execute the request
	include(MINT_ROOT.'app/paths/record/index.php');

	// Restore the pre-mint data
	error_reporting($MINT_errorbackup);
	$_COOKIE = $MINT_cookiebackup;
	$_GET = $MINT_getbackup;
}

?>