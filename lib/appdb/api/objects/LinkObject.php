<?php
namespace appdb\api\objects;

use appdb\api\APIObject;
use appdb\models\AppLinkModel;
use appdb\api\ResponseCode;

class LinkObject extends APIObject {
	
	public static function action_get($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if ($profile->perm_link_get_auth && !$auth && !$profile->perm_link_get) {
			$returncode = ResponseCode::UNAUTHORIZED;
			return false;
		}
		if (!$profile->perm_link_get_auth && !$profile->perm_link_get) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		if ($profile->perm_link_get_auth && !$profile->perm_link_get && $auth
				&& $auth->getPermission('view_unscrambled_links')) {
			$returncode = ResponseCode::USER_NOT_PERMITTED;
			return false;
		}
		if (!isset($args->app_id)) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		$alm = AppLinkModel::getInstance();
		$links = $alm->getByAppIDCached($args->app_id);
		if (!$links) {
			$returncode = ResponseCode::RESOURCE_NOT_FOUND;
			return false;
		}
		$data = array('links' => array());
		$curver = false;
		foreach ($links as $link) {
			$i = count($data['links']);
			$verbean = $link->getMapped('version');
			$version = $verbean->version;
			if (!$curver || $curver == $version ||
					(isset($args->all_versions) && $args->all_versions == 1 && $profile->perm_link_get_all_versions)) {
				$curver = $version;
				$data['links'][$i] = array(
					'id' => $link->id,
					'version' => $version,
					'cracker' => $link->cracker,
					'date' => $link->date_added,
					'url' => $link->url
					);
			}
		}
		$returncode = ResponseCode::OK;
		return $data;
	}
	
	public static function action_submit($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if (!$profile->perm_link_submit_auth) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		if (!$auth) {
			$returncode = ResponseCode::UNAUTHORIZED;
			return false;
		}
		if (!isset($args->itunes_id) || !isset($args->version) || !isset($args->cracker)
			|| !isset($args->links) || $args->itunes_id != (int)$args->itunes_id ||
			!is_array($args->links) || count($args->links) == 0 || count($args->links) > 10) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		$alm = AppLinkModel::getInstance();
		$result = $alm->submit($args->itunes_id, $args->version, $args->cracker, $args->links, $auth, $profile->name);
		switch ($result) {
			case AppLinkModel::SUBMIT_OK:
				$returncode = ResponseCode::CREATED;
				return true;
			case AppLinkModel::SUBMIT_PARTIAL_OK:
				$returncode = ResponseCode::PARTIALLY_CREATED;
				return true;
			case AppLinkModel::SUBMIT_FAIL_APP_NOT_FOUND:
				$returncode = ResponseCode::RESOURCE_NOT_FOUND;
				return false;
			case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_APP:
			case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_NEW_VERSION:
			case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_FREE_APP:
			case AppLinkModel::SUBMIT_FAIL_USER_CANNOT_SUBMIT_LINKS:
				$returncode = ResponseCode::USER_NOT_PERMITTED;
				return false;
			case AppLinkModel::SUBMIT_FAIL_ITUNES_TIMEOUT:
				$returncode = ResponseCode::RESOURCE_TIMEOUT;
				return false;
			case AppLinkModel::SUBMIT_FAIL_NO_VALID_LINKS:
				$returncode = ResponseCode::UNACCEPTABLE_DATA;
				return false;
			case AppLinkModel::SUBMIT_FAIL_UNKNOWN_ERROR:
			default:
				$returncode = ResponseCode::INTERNAL_SERVER_ERROR;
				return false;
		}
	}
}

?>