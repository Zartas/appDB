<?php
namespace appdb\models;

use appdb\sqlbeans\APIProfileBean;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\recache\RECacheManager;
use hydrogen\database\Query;
use hydrogen\model\Model;

class APIProfileModel extends Model {
	protected static $modelID = 'apipm';
	
	public function getByName__3600_apiprofiles($name) {
		$query = new Query('SELECT');
		$query->where('name = ?', $name);
		$apip = APIProfileBean::select($query);
		if (!$apip)
			return false;
		return $apip[0];
	}
	
	public function generateProfile($name, $owner_id=0) {
		$bean = new APIProfileBean();
		$bean->name = $name;
		$bean->owner_id = $owner_id;
		$bean->set('created', 'NOW()', true);
		$oprivkey = openssl_pkey_new(array(
			'private_key_bits' => 512,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
			));
		openssl_pkey_export($oprivkey, $out);
		$bean->priv_pem = $out;
		try {
			$bean->insert();
		}
		catch (QueryFailedException $e) {
			return false;
		}
		return true;
	}
}

?>