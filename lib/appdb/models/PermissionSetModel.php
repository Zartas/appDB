<?php
namespace appdb\models;

use appdb\sqlbeans\UsergroupBean;
use appdb\sqlbeans\BannedIPBean;
use appdb\sqlbeans\PermissionSetBean;
use appdb\models\BannedIPModel;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;

class PermissionSetModel extends Model {
	protected static $modelID = 'psm';
	
	public function getByGroupName__3600_users($group) {
		$query = new Query('SELECT');
		$query->where('grp.group_name like ?', $group);
		$result = UsergroupBean::select($query, true);
		if (count($result) == 0)
			return false;
		return $result[0]->getMapped('permissionset');
	}
	
	public function getByIPAddressCached($ip) {
		$bipm = BannedIPModel::getInstance();
		$bip = $bipm->getByIPAddressCached($ip);
		if (!$bip)
			return $bip;
		return $bip->getMapped('permissionset');
	}
	
	public function getByIPAddress($ip) {
		$bipm = BannedIPModel::getInstance();
		$bip = $bipm->getByIPAddress($ip);
		if (!$bip)
			return $bip;
		return $bip->getMapped('permissionset');
	}
	
	public function updatePermissionSet($psBean) {
		try {
			$psBean->update();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		$this->cm->clearGroup('users');
		return true;
	}
	
	protected function convertToIntIP($ip) {
		$tokens = explode('.', $ip);
		$intstr = '';
		foreach ($tokens as $token)
			$intstr .= $this->createChars('0', 3 - strlen($token)) . $token;
		return (int)$intstr; 
	}
	
	protected function createChars($char, $num) {
		$str = '';
		for ($i = 0; $i < $num; $i++)
			$str .= $char;
		return $str;
	}
}

?>