<?php
namespace appdb\sqlbeans;

use \hydrogen\sqlbeans\SQLBean;

class BannedIPBean extends SQLBean {
	protected static $tableNoPrefix = 'bans_ip';
	protected static $tableAlias = 'bip';
	protected static $primaryKey = 'id';
	protected static $primaryKeyIsAutoIncrement = true;
	protected static $fields = array(
		'id',
		'ban_desc',
		'ip_low',
		'ip_high',
		'permissionset_id',
		'date_banned'
		);
	protected static $beanMap = array(
		'permissionset' => array(
			'joinType' => 'LEFT',
			'joinBean' => 'appdb\sqlbeans\PermissionSetBean',
			'foreignKey' => 'permissionset_id'
			)
		);
	
	protected function toIntIP($ip) {
		$tokens = explode('.', $ip);
		$intstr = '';
		foreach ($tokens as $token) {
			for ($i = 0; $i < (3 - strlen($token)); $i++)
				$intstr .= '0';
			$intstr .= $token;
		}
		return (int)$intstr; 
	}
	
	protected function toStringIP($ip) {
		if (!is_int($ip) || strlen($ip) > 12)
			return $ip;
		$intstr = (string)$ip;
		$ipstr = '';
		$i = strlen($intstr) - 1;
		while ($i >= 0) {
			$q = 0;
			$set = '';
			while ($i >= 0 && $q < 3) {
				$set = $intstr[$i] . $set;
				$i--;
				$q++;
			}
			while (strlen($set) > 1 && $set[0] = '0')
				$set = substr($set, 1);
			$ipstr = '.' . $set . $ipstr;
		}
		return substr($ipstr, 1);
	}
	
	protected function set_ip_low($val, $isSQL) {
		if ($isSQL) {
			$stored['ip_low'] = $val;
			return true;
		}
		$stored['ip_low'] = $this->toIntIP($val);
		return true;
	}
	protected function get_ip_low() {
		if (!isset($stored['ip_low']))
			return false;
		return $this->toStringIP($stored['ip_low']);
	}
	
	protected function set_ip_high($val, $isSQL) {
		if ($isSQL) {
			$stored['ip_high'] = $val;
			return true;
		}
		$stored['ip_high'] = $this->toIntIP($val);
		return true;
	}
	protected function get_ip_high() {
		if (!isset($stored['ip_high']))
			return false;
		return $this->toStringIP($stored['ip_high']);
	}
}

?>