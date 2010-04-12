<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class APIProfileBean extends SQLBean {
	protected static $tableNoPrefix = 'apiprofiles';
	protected static $tableAlias = 'apip';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'owner_id',
		'name',
		'active',
		'priv_pem',
		'created',
		'allowed_app_fields',
		'perm_allow_rapidfire',
		'perm_allow_multiple_ips',
		'perm_app_getlist',
		'perm_app_getdetails',
		'perm_category_list',
		'perm_user_checkauth',
		'perm_link_get',
		'perm_link_get_auth',
		'perm_link_get_all_versions',
		'perm_link_submit_auth',
		'perm_screenshot_get'
		);
	protected static $beanMap = array(
		'owner' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\UserProfileBean',
			'foreignKey' => 'owner_id'
			)
		);
		
	protected function get_allowed_app_fields() {
		return explode(' ', $this->stored['allowed_app_fields']);
	}
	
	protected function set_allowed_app_fields($val) {
		$this->stored['allowed_app_fields'] = implode(' ', $val);
		return true;
	}
}

?>