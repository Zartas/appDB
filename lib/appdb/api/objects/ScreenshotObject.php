<?php
namespace appdb\api\objects;

use appdb\api\APIObject;
use appdb\models\ScreenshotModel;
use appdb\api\ResponseCode;
use hydrogen\config\Config;

class ScreenshotObject extends APIObject {
	
	public static function action_get($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if ($auth) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		if (!$profile->perm_screenshot_get) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		if (!isset($args->app_id)) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		$sm = ScreenshotModel::getInstance();
		$shots = $sm->getByAppIDCached($args->app_id);
		if (!$shots) {
			$returncode = ResponseCode::RESOURCE_NOT_FOUND;
			return false;
		}
		$data = array('screenshots' => array());
		foreach ($shots as $shot) {
			$i = count($data['screenshots']);
			$data['screenshots'][$i] = array(
				'is_horiz' => $shot->is_horiz,
				'url' => $shot->shot_url
				);
		}
		$returncode = ResponseCode::OK;
		return $data;
	}
}

?>