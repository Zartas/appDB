<?php
namespace appdb;

// Errors only if we're local
if ($_SERVER["HTTP_HOST"] == "localhost" && $_SERVER["SERVER_NAME"] == "localhost")
	error_reporting(E_ALL & E_STRICT);
else
	error_reporting(0);

// Import Hydrogen and start using its error handler
require_once(__DIR__ . "/../hydrogen/hydrogen.inc.php");
use hydrogen\errorhandler\ErrorHandler;
ErrorHandler::attachErrorPage();

// Time for timezones
// @date_default_timezone_set(@date_default_timezone_get());

function load($namespace) {
	return classProbe($namespace, true);
}

function classProbe($class, $showErrors=true) {
	$splitpath = explode('\\', $class);
	$path = '';
	$name = '';
	$firstword = true;
	for ($i = 0; $i < count($splitpath); $i++) {
		if ($splitpath[$i] && !$firstword) {
			if ($i == count($splitpath) - 1)
				$name = $splitpath[$i];
			else
				$path .= DIRECTORY_SEPARATOR . $splitpath[$i];
		}
		if ($splitpath[$i] && $firstword) {
			if ($splitpath[$i] != __NAMESPACE__)
				break;
			$firstword = false;
		}
	}
	if (!$firstword) {
		$fullpath = __DIR__ . $path . DIRECTORY_SEPARATOR . $name . '.php';
		if ($showErrors)
			return include($fullpath);
		else
			return @include($fullpath);
	}
	return false;
}

spl_autoload_register(__NAMESPACE__ . '\load');
include(__DIR__ . '/appdb.autoconfig.php');
?>