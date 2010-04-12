<?php
namespace appdb\api\objects;

use appdb\api\APIObject;
use appdb\models\AppCategoryModel;
use appdb\api\ResponseCode;

class CategoryObject extends APIObject {
	
	public static function action_getList($args, &$returncode, &$profile, &$auth=false, $format=false) {
		if ($auth) {
			$returncode = ResponseCode::BAD_REQUEST;
			return false;
		}
		if (!$profile->perm_category_list) {
			$returncode = ResponseCode::ACTION_NOT_ALLOWED;
			return false;
		}
		$acm = AppCategoryModel::getInstance();
		$cats = $acm->getAllCached();
		$data = array('categories' => array());
		foreach ($cats as $cat) {
			$i = count($data['categories']);
			$data['categories'][$i] = array(
				'id' => $cat->id,
				'name' => $cat->category_name
				);
		}
		$returncode = ResponseCode::OK;
		return $data;
	}
}

?>