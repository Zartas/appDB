<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class AppVersionBean extends SQLBean {
	protected static $tableNoPrefix = 'versions';
	protected static $tableAlias = 'ver';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'app_id',
		'version',
		'intversion',
		'versioninfo',
		'date_added',
		'last_updated'
		);
	protected static $beanMap = array(
		'app' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\ApplicationBean',
			'foreignKey' => 'app_id'
			)
		);
}

?>