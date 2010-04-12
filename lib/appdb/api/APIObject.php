<?php
namespace appdb\api;

use appdb\api\ResponseCode;
use appdb\usersession\UserSession;

abstract class APIObject {
	
	private function __construct() {}
	
	public static function callAction($action, $rawreq, &$returncode, &$profile, $format=false) {
		$obj = get_called_class();
		$method = 'action_' . $action;
		if (!method_exists($obj, $method)) {
			$returncode = ResponseCode::NOT_IMPLEMENTED;
			return false;
		}
		if (isset($rawreq->args))
			$args = $rawreq->args;
		else
			$args = false;
		if (isset($rawreq->auth) && isset($rawreq->auth->username) && isset($rawreq->auth->passhash))
			$auth = UserSession::checkAuth($rawreq->auth->username, $rawreq->auth->passhash, true);
		else
			$auth = false;
		return call_user_func_array($obj . '::' . $method, array($args, &$returncode, &$profile, &$auth, $format));
	}
}

?>