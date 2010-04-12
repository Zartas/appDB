<?php
require_once(__DIR__ . '/../lib/hydrogen/hydrogen.inc.php');
use hydrogen\recache\RECacheManager;

header('Content-type: text/plain; charset=utf-8');
$cm = RECacheManager::getInstance();
print_r($cm->getStats(array('bot_list')));
?>
