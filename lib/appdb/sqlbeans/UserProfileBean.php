<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class UserProfileBean extends SQLBean {
	protected static $tableNoPrefix = 'users';
	protected static $tableAlias = 'usr';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'username',
		'email',
		'group_id',
		'permission_override_id',
		'password',
		'salt',
		'joindate',
		'lastlogin',
		'reg_ip',
		'last_ip',
		'must_validate'
		);
	protected static $beanMap = array(
		'group' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\UsergroupBean',
			'foreignKey' => 'group_id'
			),
		'permissionset' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\PermissionSetBean',
			'foreignKey' => 'permission_override_id'
			)
		);
	
	public function getPermission($perm) {
		$gps = $this->mapped['group']->getMapped('permissionset');
		$ups = $this->mapped['permissionset'];
		$psfields = PermissionSetBean::getFields();
		if (!in_array($perm, $psfields))
			return false;
		$status = $gps->$perm == 1;
		if (!isset($ups->$perm) || is_null($ups->$perm))
			return $status;
		return $ups->$perm == 1;
	}
}

?>