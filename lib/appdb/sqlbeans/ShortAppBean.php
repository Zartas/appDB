<?php
namespace appdb\sqlbeans;

use \hydrogen\config\Config;
use \hydrogen\sqlbeans\SQLBean;

class ShortAppBean extends SQLBean {
	protected static $tableNoPrefix = 'applications';
	protected static $tableAlias = 'app';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'itunes_id',
		'name',
		'category_id',
		'company',
		'releasedate',
		'seller',
		'size',
		'price',
		'bigicon_url',
		'smallicon_url',
		'date_added',
		'last_updated',
		'latest_version',
		'latest_version_first_cracker',
		'latest_version_added'
		);
	protected static $beanMap = array(
		'category' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\AppCategoryBean',
			'foreignKey' => 'category_id'
			)
		);
		
	protected function get_release_date() {
		return $this->stored['releasedate'];
	}
	protected function set_release_date($val, $func=false) {
		return $this->set('releasedate', $val, $func);
	}
	
	protected function get_category_name() {
		$cat = $this->getMapped('category');
		if ($cat === false)
			return false;
		return $cat->category_name;
	}
	protected function set_category_name($val, $func=false) {
		$cat = $this->getMapped('category');
		if ($cat == false)
			return false;
		return $cat->set('category_name', $val, $func);
	}
	
	protected function get_bigicon_url() {
		$url = $this->stored['bigicon_url'];
		$url = str_replace('%/', '%', $url);
		$url = str_replace('%BASE_URL%', Config::getVal('urls', 'base_url') . '/', $url);
		return str_replace('%ICON_URL%', Config::getVal('urls', 'icon_url') . '/', $url);
	}
	
	protected function get_smallicon_url() {
		$url = $this->stored['smallicon_url'];
		$url = str_replace('%/', '%', $url);
		$url = str_replace('%BASE_URL%', Config::getVal('urls', 'base_url') . '/', $url);
		return str_replace('%ICON_URL%', Config::getVal('urls', 'icon_url') . '/', $url);
	}
}

?>