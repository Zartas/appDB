<?php
namespace appdb\sqlbeans;

use hydrogen\sqlbeans\SQLBean;

class AutoLoginBean extends SQLBean {
	protected static $tableNoPrefix = 'autologin';
	protected static $tableAlias = 'ali';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'user_id',
		'date_added',
		'last_used',
		'last_used_ip',
		'publickey',
		'privatekey'
		);
	protected static $beanMap = array(
		'user' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\UserProfileBean',
			'foreignKey' => 'user_id'
			)
		);
}

?>