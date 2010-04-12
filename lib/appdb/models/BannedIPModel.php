<?php
namespace appdb\models;

use appdb\sqlbeans\BannedIPBean;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;

class BannedIPModel extends Model {
	protected static $modelID = 'bipm';
	
	public function isBanned($ip) {
		$query = new Query('SELECT');
		$query->field('COUNT(*)', 'count');
		$query->from('bans_ip');
		$intip = $this->toIntIP($ip);
		$query->where('ip_low <= ?', $intip);
		$query->where('ip_high >= ?', $intip);
		$stmt = $query->prepare();
		$stmt->execute();
		$obj = $stmt->fetchObj();
		return $obj->count > 0;
	}
	
	public function getByIPAddress__300_users($ip) {
		$intip = $this->toIntIP($ip);
		$query = new Query("SELECT");
		$query->where('bip.ip_low <= ?', $intip);
		$query->where('bip.ip_high >= ?', $intip);
		$query->limit(1);
		$result = BannedIPBean::select($query, true);
		if ($result)
			return $result;
		return false;
	}
	
	public function toIntIP($ip) {
		$tokens = explode('.', $ip);
		$intstr = '';
		foreach ($tokens as $token) {
			for ($i = 0; $i < (3 - strlen($token)); $i++)
				$intstr .= '0';
			$intstr .= $token;
		}
		return $intstr; 
	}
	
	public function banIPs($desc, $ips, $permissionSet=10) {
		$query = new Query('INSERT');
		$query->intoTable('bans_ip');
		$query->intoField('ban_desc');
		$query->intoField('ip_low');
		$query->intoField('ip_high');
		$query->intoField('permissionset_id');
		$query->intoField('date_banned');
		foreach ($ips as $ip) {
			$intip = $this->toIntIP($ip);
			$query->values('(?, ?, ?, ?, NOW())', array($desc, $intip, $intip, $permissionSet));
		}
		$stmt = $query->prepare();
		return $stmt->execute();
	}
}

?>