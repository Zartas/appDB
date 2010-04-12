<?php
namespace appdb\models;

use appdb\sqlbeans\AutoLoginBean;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\database\Query;
use hydrogen\recache\RECacheManager;
use hydrogen\model\Model;

class AutoLoginModel extends Model {
	protected static $modelID = 'aulm';
	
	public function getByPublicKey__300_users($pubkey) {
		$query = new Query('SELECT');
		$query->where('publickey like ?', $pubkey);
		$result = AutoLoginBean::select($query, true);
		if (count($result) == 0)
			return false;
		return $result[0];
	}
	
	public function create($user_id, $pubkey, $privkey) {
		$bean = new AutoLoginBean();
		$bean->user_id = $user_id;
		$bean->publickey = $pubkey;
		$bean->privatekey = $privkey;
		$bean->set('date_added', 'NOW()', true);
		try {
			$bean->insert();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		return true;
	}
	
	public function update($alBean) {
		try {
			$alBean->update();
		}
		catch (QueryFailedException $e) {
			return false;
		}
	}
	
	public function delete($alBean) {
		try {
			$alBean->delete();
		}
		catch (QueryFailedException $e) {
			return false;
		}
	}
}

?>