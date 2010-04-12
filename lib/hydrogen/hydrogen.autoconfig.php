<?php
namespace hydrogen;

use hydrogen\config\Config;

/* 
 * Load the appropriate configuration file with caching.
 */
if (strpos($_SERVER['REQUEST_URI'], 'apitest') === false)
	Config::loadConfig(__DIR__ . '/../../config/config.ini.php', __DIR__ . '/../../cache', false);
else
	Config::loadConfig(__DIR__ . '/../../config/config.apitest.ini.php', __DIR__ . '/../../cache/apitest', false);

?>
