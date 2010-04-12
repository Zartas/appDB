<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class UsergroupBean extends SQLBean {
	protected static $tableNoPrefix = 'groups';
	protected static $tableAlias = 'grp';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'group_name',
		'permissionset_id'
		);
	protected static $beanMap = array(
		'permissionset' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\PermissionSetBean',
			'foreignKey' => 'permissionset_id'
			)
		);
}

?>