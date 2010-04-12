<?php
namespace appdb\api\objects;

use appdb\api\APIObject;
use appdb\api\ResponseCode;

class UserObject extends APIObject {
	
	public static function action_checkauth($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if (!$profile->perm_user_checkauth) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		if (!$auth) {
			$returncode = ResponseCode::UNAUTHORIZED;
			return false;
		}
		$returncode = ResponseCode::OK;
		return true;
	}
}

?>