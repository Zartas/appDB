<?php
use hydrogen\config\Config;
use hydrogen\recache\RECacheManager;
use hydrogen\semaphore\SemaphoreEngineFactory;

if (!class_exists('hydrogen\config\Config', false))
	die("Quit trying to hack my damn code.");

function BOTLIST_purgeOldItems(&$list) {
	$expire30min = time() - (30 * 60);
	$expire2hr = time() - (2 * 60 * 60);
	$expire6hr = time() - (6 * 60 * 60);
	$expire12hr = time() - (12 * 60 * 60);
	$expire1day = time() - (24 * 60 * 60);
	$expire2day = time() - (2 * 24 * 60 * 60);
	$expire1week = time() - (7 * 24 * 60 * 60);
	foreach ($list as $ip => $info) {
		if (($info['killed_hits'] < 50 && $info['timestamp'] <= $expire30min) ||
				($info['killed_hits'] < 100 && $info['timestamp'] <= $expire2hr) ||
				($info['killed_hits'] < 200 && $info['timestamp'] <= $expire6hr) ||
				($info['killed_hits'] < 300 && $info['timestamp'] <= $expire12hr) ||
				($info['killed_hits'] < 500 && $info['timestamp'] <= $expire1day) ||
				($info['killed_hits'] < 1000 && $info['timestamp'] <= $expire2day) ||
				($info['timestamp'] <= $expire1week))
			unset($list[$ip]);
	}
}

// Get resources
$sem = SemaphoreEngineFactory::getEngine();
$cm = RECacheManager::getInstance();

// Should we be bot-blocking?
if (substr($_SERVER['REMOTE_ADDR'], 0, 11) != "193.253.141" &&
		substr($_SERVER['REMOTE_ADDR'], 0, 10) != "142.22.186" &&
		$_SERVER['REMOTE_ADDR'] != "62.201.129.226" &&
		$cm->checkIfFrequent(10, 20, 'BOTTEST:' . $_SERVER['REMOTE_ADDR'])) {
	$sem->acquire(Config::getVal('general', 'site_name') . ":bot_list");
	$list = $cm->get("bot_list");
	if (!is_array($list)) 
		$list = array();
	if (isset($list[$_SERVER['REMOTE_ADDR']]))
		$list[$_SERVER['REMOTE_ADDR']]['killed_hits']++;
	else {
		BOTLIST_purgeOldItems($list);
		$list[$_SERVER['REMOTE_ADDR']] = array();
		$list[$_SERVER['REMOTE_ADDR']]['killed_hits'] = 1;
	}
	if (isset($_SERVER['REMOTE_HOST']) && $_SERVER['REMOTE_HOST'] != '')
		$list[$_SERVER['REMOTE_ADDR']]['hostname'] = $_SERVER['REMOTE_HOST'];
	$list[$_SERVER['REMOTE_ADDR']]['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
	if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '')
		$list[$_SERVER['REMOTE_ADDR']]['referrer'] = $_SERVER['HTTP_REFERER'];
	$list[$_SERVER['REMOTE_ADDR']]['last_hit'] = date("M j Y, g:i:s A");
	$list[$_SERVER['REMOTE_ADDR']]['timestamp'] = time();
	if (isset($list[$_SERVER['REMOTE_ADDR']]['URLs'][$_SERVER['REQUEST_URI']]))
		$list[$_SERVER['REMOTE_ADDR']]['URLs'][$_SERVER['REQUEST_URI']]++;
	else
		$list[$_SERVER['REMOTE_ADDR']]['URLs'][$_SERVER['REQUEST_URI']] = 1;
	$cm->set("bot_list", $list, 0);
	$sem->release(Config::getVal('general', 'site_name') . ":bot_list");
	die();
}
?>