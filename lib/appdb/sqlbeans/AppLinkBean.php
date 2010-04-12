<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class AppLinkBean extends SQLBean {
	protected static $tableNoPrefix = 'links';
	protected static $tableAlias = 'lnk';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'app_id',
		'version_id',
		'filetype',
		'cracker',
		'url',
		'clicks',
		'date_added',
		'last_updated',
		'submitter_id',
		'submitter_ip',
		'submitted_from',
		'approvedby_id',
		'approved_on'
		);
	protected static $beanMap = array(
		'app' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\ApplicationBean',
			'foreignKey' => 'app_id'
			),
		'version' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\AppVersionBean',
			'foreignKey' => 'version_id'
			),
		'submitter' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\UserProfileBean',
			'foreignKey' => 'submitter_id'
			)
		);
	
	protected function get_version() {
		if (isset($this->mapped['version']))
			return $this->mapped['version']->version;
		return false;
	}
}

?>