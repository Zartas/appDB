<?php
namespace appdb\sqlbeans;

use \hydrogen\config\Config;
use \hydrogen\sqlbeans\SQLBean;

class ScreenshotBean extends SQLBean {
	protected static $tableNoPrefix = 'screenshots';
	protected static $tableAlias = 'scr';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'app_id',
		'shot_url',
		'is_horiz',
		'date_added'
		);
	protected static $beanMap = array(
		'app' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\ApplicationBean',
			'foreignKey' => 'app_id'
			)
		);
	
	protected function get_shot_url() {
		$url = $this->stored['shot_url'];
		$url = str_replace('%/', '%', $url);
		$url = str_replace('%BASE_URL%', Config::getVal('urls', 'base_url') . '/', $url);
		return str_replace('%SCREEN_URL%', Config::getVal('urls', 'screenshot_url') . '/', $url);
	}
}

?>